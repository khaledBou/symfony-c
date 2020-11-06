<?php

namespace App\Command\Reminder\Coach;

use App\Entity\Event\CoachReminderEvent;
use App\Service\NotificationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

/**
 * Commande de rappel aux coachs,
 * par notification.
 */
class NotificationCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:reminder:coach:notification';

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param NotificationHelper     $notificationHelper
     * @param EntityManagerInterface $em
     * @param RouterInterface        $router
     */
    public function __construct(NotificationHelper $notificationHelper, EntityManagerInterface $em, RouterInterface $router)
    {
        parent::__construct();

        $this->notificationHelper = $notificationHelper;
        $this->em = $em;
        $this->router = $router;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Envoie les rappels aux coachs, par notification')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début des rappels par notification', (new \DateTime())->format('d/m/Y H:i:s')));

        // Les rappels à envoyer
        $events = $this
            ->em
            ->getRepository(CoachReminderEvent::class)
            ->findToSend(CoachReminderEvent::WAY_NOTIFICATION)
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
                $coach = $event->getCoach();
                $mandatary = $event->getMandatary();
                $content = $event->getContent();
                $url = $this->router->generate('mandatary_show', [
                    'slug' => $mandatary->getSlug(),
                ]);
                $sent = $this->notificationHelper->addNotification($coach, $content, $url, $mandatary);

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

        $io->title(sprintf('[%s] Fin des rappels par notification', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
