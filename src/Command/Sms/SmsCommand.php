<?php

namespace App\Command\Sms;

use App\Entity\Event\SmsEvent;
use App\Service\SmsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande d'envoi de SMS aux négociateurs,
 * via Sarbacane.
 *
 * @see https://developers.sarbacane.com
 */
class SmsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:sms';

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
            ->setDescription('Envoie les SMS aux négociateurs')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début d\'envoi des SMS', (new \DateTime())->format('d/m/Y H:i:s')));

        if (!$this->isDev) {
            // Les SMS à envoyer
            $events = $this
                ->em
                ->getRepository(SmsEvent::class)
                ->findToSend()
            ;

            /**
             * Nombre de SMS à envoyer.
             *
             * @var int
             */
            $eventsCount = count($events);

            if ($eventsCount > 0) {
                /**
                 * Nombre de SMS envoyés.
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
                        // Marque le SMS comme étant envoyé
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

                $io->writeln(sprintf('Nombre de SMS envoyés : %s/%s', $sentCount, $eventsCount));
            } else {
                $io->writeln('Aucun SMS à envoyer.');
            }
        } else {
            $io->writeln('Mode dev : aucun SMS ne sera envoyé.');
        }

        $io->title(sprintf('[%s] Fin d\'envoi des SMS', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
