<?php

namespace App\Command\Reminder\Mandatary;

use App\Entity\Event\MandataryReminderEvent;
use App\Service\EmailHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Commande de relance des négociateurs,
 * par e-mail.
 */
class EmailCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:reminder:mandatary:email';

    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param EmailHelper            $emailHelper
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     */
    public function __construct(EmailHelper $emailHelper, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        parent::__construct();

        $this->emailHelper = $emailHelper;
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Envoie les relances aux négociateurs, par e-mail')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début des relances par e-mail', (new \DateTime())->format('d/m/Y H:i:s')));

        // Les relances à envoyer
        $events = $this
            ->em
            ->getRepository(MandataryReminderEvent::class)
            ->findToSend(MandataryReminderEvent::WAY_EMAIL)
        ;

        /**
         * Nombre de relances à envoyer.
         *
         * @var int
         */
        $eventsCount = count($events);

        if ($eventsCount > 0) {
            /**
             * Nombre de relances envoyées.
             *
             * @var int
             */
            $sentCount = 0;

            $progressBar = new ProgressBar($output, $eventsCount);

            foreach ($events as $event) {
                $coach = $event->getCoach();
                $mandatary = $event->getMandatary();

                $sent = $this->emailHelper->send(
                    $this->translator->trans("Nouveau message de votre coach"),
                    $event->getContent(),
                    $coach->getEmail(),
                    (string) $coach,
                    $mandatary->getEmail(),
                    (string) $mandatary,
                    $mandatary->getNetwork(),
                    'email/default.%s.html.twig',
                    [
                        'sender_phone' => $coach->getPhone(),
                    ]
                );

                if ($sent) {
                    // Marque la relance comme étant envoyée
                    $event->setSent(true);
                    $event->setDeletable(false); // l'événement ne peut plus être supprimé
                    $this->em->persist($event);
                    $sentCount++;
                }

                $progressBar->advance();
            }

            $this->em->flush();

            $progressBar->finish();
            $io->newLine();
            $io->newLine();

            $io->writeln(sprintf('Nombre de relances envoyées : %s/%s', $sentCount, $eventsCount));
        } else {
            $io->writeln('Aucune relance à envoyer.');
        }

        $io->title(sprintf('[%s] Fin des relances par e-mail', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
