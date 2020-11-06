<?php

namespace App\Command\ImportEvents;

use App\Entity\Event;
use App\Entity\User\Mandatary;
use App\Service\NotificationHelper;
use App\Service\SmsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande d'import des événements.
 */
abstract class AbstractImportEventsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;

    /**
     * @var SmsHelper
     */
    protected $smsHelper;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param EntityManagerInterface $em
     * @param NotificationHelper     $notificationHelper
     * @param SmsHelper              $smsHelper
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(EntityManagerInterface $em, NotificationHelper $notificationHelper, SmsHelper $smsHelper, ParameterBagInterface $parameterBag)
    {
        // Avant tout
        $this->checkForNetworkConstant();

        parent::__construct();

        $this->em = $em;
        $this->notificationHelper = $notificationHelper;
        $this->smsHelper = $smsHelper;
        $this->config = $parameterBag->get('app');

        // Désactivation des logs Doctrine pour réduire la consommation de mémoire
        $em
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(null)
        ;
    }

    /**
     * Construit les nouveaux événements du négociateur.
     *
     * @param Mandatary $mandatary Le négociateur
     *
     * @return EventInterface[]
     */
    public function getEvents(Mandatary $mandatary): array
    {
        /**
         * Les événements à créer.
         *
         * @var Event\EventInterface[]
         */
        $events = [];

        // Construction
        foreach ($this->config['events'] as $eventType => $eventConfig) {
            if (in_array($this::NETWORK, $eventConfig['networks'])) {
                // S'il s'agit d'un type d'événement automatique, on crée les événements correspondants
                $createMethod = $eventConfig['create_method'];
                if (null !== $createMethod) {
                    // Appelle la méthode de création
                    $eventEntity = $eventConfig['entity'];
                    $eventTypeEvents = $this->$createMethod($eventEntity, $mandatary);

                    foreach ($eventTypeEvents as $event) {
                        $events[] = $event;
                    }
                }
            }
        }

        return $events;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        throw new \LogicException('The configure() method must be overriden in the class implementation.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début import événements', (new \DateTime())->format('d/m/Y H:i:s')));

        $mandataries = $this
            ->em
            ->getRepository(Mandatary::class)
            ->findBy([
                'enabled' => true,
                'network' => $this::NETWORK,
            ])
        ;

        if ($mandataries) {
            ProgressBar::setFormatDefinition('custom', '%message%'.PHP_EOL.'%current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s% %memory:6s%)'.PHP_EOL);

            $io->writeln('Import :');

            $progressBar = new ProgressBar($output, count($mandataries));
            $progressBar->setFormat('custom');

            // Force l'activation du garbage collector
            gc_enable();

            foreach ($mandataries as $key => $mandatary) {
                // Événements
                $events = $this->getEvents($mandatary);
                foreach ($events as $event) {
                    $mandatary->addEvent($event);

                    // Les événements peuvent donner lieu à des notifications…
                    $this->notificationHelper->addEventNotification($event);

                    // … et à des SMS

                    // Date programmée du SMS
                    $scheduledDate = new \DateTime(); // maintenant
                    $hour = (int) $scheduledDate->format('H');
                    $isWeekDay = in_array((int) $scheduledDate->format('N'), [1, 2, 3, 4, 5]);
                    $isWorkingHour = $hour >= 9 && $hour < 17;

                    // Si on n'est pas dans les heures ouvrées d'un jour ouvré
                    if (!($isWeekDay && $isWorkingHour)) {
                        // On décale l'envoi au prochain jour ouvré entre 9h00 et 11h59
                        $scheduledDate
                            ->modify('+1 weekday')
                            ->setTime(rand(9, 11), rand(0, 59), rand(0, 59))
                        ;
                    }

                    $this->smsHelper->sendEventSms($event, $scheduledDate);
                }

                $this->em->persist($mandatary);

                // Flush tous les 100 négociateurs
                if (0 === $key % 100) {
                    $this->em->flush();

                    // Force l'appel du garbage collector
                    gc_collect_cycles();
                }

                $progressBar->setMessage((string) $mandatary);
                $progressBar->advance();

                // Indique au garbage collector de prendre en charge cet objet
                unset($mandatary);
            }

            $progressBar->finish();
            $io->newLine();

            $this->em->flush();

            // Force l'appel du garbage collector
            gc_collect_cycles();
        }

        $io->title(sprintf('[%s] Fin import événements', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }

    /**
     * Vérifie que l'implémentation de la classe définit bien la constante NETWORK,
     * désignant l'identifiant du réseau pour lequel importer les événements.
     *
     * @return void
     */
    private function checkForNetworkConstant(): void
    {
        if (!defined(sprintf('%s::NETWORK', get_class($this)))) {
            throw new \LogicException('The NETWORK constant must be defined in the class implementation.');
        }
    }
}
