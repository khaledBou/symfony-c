<?php

namespace App\Command\Reminder\Coach;

use App\Entity\Event\CoachReminderEvent;
use App\Service\EmailHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Commande de rappel pour les coachs,
 * par e-mail.
 */
class EmailCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:reminder:coach:email';

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
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @param EmailHelper            $emailHelper
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(EmailHelper $emailHelper, EntityManagerInterface $em, TranslatorInterface $translator, ParameterBagInterface $parameterBag)
    {
        parent::__construct();

        $this->emailHelper = $emailHelper;
        $this->em = $em;
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Envoie les rappels aux coachs, par e-mail')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début des rappels par e-mail', (new \DateTime())->format('d/m/Y H:i:s')));

        // Les rappels à envoyer
        $events = $this
            ->em
            ->getRepository(CoachReminderEvent::class)
            ->findToSend(CoachReminderEvent::WAY_EMAIL)
        ;

        /**
         * Nombre de rappels à envoyer.
         *
         * @var int
         */
        $eventsCount = count($events);

        if ($eventsCount > 0) {
            /**
             * Nombre de rappels envoyés.
             *
             * @var int
             */
            $sentCount = 0;

            $progressBar = new ProgressBar($output, $eventsCount);

            foreach ($events as $event) {
                $mandatary = $event->getMandatary();
                $coach = $event->getCoach();

                $sent = $this->emailHelper->send(
                    $this->translator->trans("Rappel pour %mandatary%", ['%mandatary%' => $mandatary]),
                    $event->getContent(),
                    $coach->getEmail(),
                    $this->translator->trans("%coach% via l'application Coaching", ['%coach%' => $coach]),
                    $coach->getEmail(),
                    (string) $coach,
                    $coach->getNetwork(),
                    'email/reminder/coach.%s.html.twig',
                    [
                        'mandatary' => $mandatary,
                        /**
                         * URL de l'application, puisqu'il n'est pas possible de la récupérer en mode console.
                         * La documentation ci-dessous n'est pas applicable car l'application gère plusieurs réseaux
                         * avec chacun une URL différente.
                         *
                         * Ici le protocole sera toujours HTTP. On compte ensuite sur le serveur pour rediriger vers une version HTTPS.
                         *
                         * @see https://symfony.com/doc/3.4/console/request_context.html#configuring-the-request-context-globally
                         */
                        'app_url' => sprintf('http://%s', $this->parameterBag->get(sprintf('app.networks.%s.domain', $coach->getNetwork()))),
                    ]
                );

                if ($sent) {
                    // Marque le rappel comme étant envoyé
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

            $io->writeln(sprintf('Nombre de rappels envoyés : %s/%s', $sentCount, $eventsCount));
        } else {
            $io->writeln('Aucun rappel à envoyer.');
        }

        $io->title(sprintf('[%s] Fin des rappels par e-mail', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
