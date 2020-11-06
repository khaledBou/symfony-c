<?php

namespace App\Command\Reminder\Mandatary;

use App\Entity\Event\MandataryReminderEvent;
use App\Service\SmsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande de relance des négociateurs,
 * par SMS via Sarbacane.
 *
 * @see https://developers.sarbacane.com
 */
class SmsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:reminder:mandatary:sms';

    /**
     * @var SmsHelper
     */
    private $smsHelper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * @param SmsHelper              $smsHelper
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(SmsHelper $smsHelper, EntityManagerInterface $em, ParameterBagInterface $parameterBag)
    {
        parent::__construct();

        $this->smsHelper = $smsHelper;
        $this->em = $em;
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Envoie les relances aux négociateurs, par SMS')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début des relances par SMS', (new \DateTime())->format('d/m/Y H:i:s')));

        if (!$this->isDev) {
            // Les relances à envoyer
            $events = $this
                ->em
                ->getRepository(MandataryReminderEvent::class)
                ->findToSend(MandataryReminderEvent::WAY_SMS)
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
                    $mandatary = $event->getMandatary();
                    $content = $event->getContent();

                    $sent = $this->smsHelper->send($mandatary, $content);

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
        } else {
            $io->writeln('Mode dev : aucune relance ne sera envoyée.');
        }

        $io->title(sprintf('[%s] Fin des relances par SMS', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
