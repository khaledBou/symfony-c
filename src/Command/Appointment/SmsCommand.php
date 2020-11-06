<?php

namespace App\Command\Appointment;

use App\Entity\Event\AppointmentEvent;
use App\Entity\User\Coach;
use App\Entity\User\Mandatary;
use App\Service\SarbacaneApiHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Commande de rappel pour les rendez-vous des négociateurs,
 * par SMS via Sarbacane.
 *
 * @see https://developers.sarbacane.com
 */
class SmsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:appointment:sms';

    /**
     * @var SarbacaneApiHelper
     */
    private $sarbacaneApiHelper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * @param SarbacaneApiHelper     $sarbacaneApiHelper
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(SarbacaneApiHelper $sarbacaneApiHelper, EntityManagerInterface $em, TranslatorInterface $translator, ParameterBagInterface $parameterBag)
    {
        parent::__construct();

        $this->sarbacaneApiHelper = $sarbacaneApiHelper;
        $this->em = $em;
        $this->translator = $translator;
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Envoie les rappels de rendez-vous par SMS')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début des rappels de rendez-vous par SMS', (new \DateTime())->format('d/m/Y H:i:s')));

        if (!$this->isDev) {
            // Les rappels de RDV à envoyer
            $events = $this
                ->em
                ->getRepository(AppointmentEvent::class)
                ->findToComeUpWithoutReminderSms()
            ;

            /**
             * Nombre de rappels de RDV à envoyer.
             *
             * @var int
             */
            $eventsCount = count($events);

            if ($eventsCount > 0) {
                /**
                 * Nombre de rappels de RDV envoyés.
                 *
                 * @var int
                 */
                $sentCount = 0;

                $progressBar = new ProgressBar($output, $eventsCount);

                foreach ($events as $event) {
                    // @var Mandatary
                    $mandatary = $event->getMandatary();
                    // @var \DateTime
                    $date = $event->getDate();
                    // @var Coach
                    $coach = $event->getCoach();

                    // Crée la campagne Sarbacane
                    $res = $this->sarbacaneApiHelper->call('campaigns/sms', [
                        'name' => sprintf("Rappel de rendez-vous coaching n°%s", uniqid()),
                        'kind' => 'SMS_NOTIFICATION',
                        'smsFrom' => "PROPRIVEES", // entre 3 et 11 caractères alpha-numériques
                        'content' => $this->translator->trans(
                            "Bonjour %mandatary%, ceci est un rappel pour votre rendez-vous le %date% à %time% avec votre coach %coach% qui vous appellera.",
                            [
                                '%mandatary%' => $mandatary->getFirstName(),
                                '%date%' => $date->format('d/m/Y'),
                                '%time%' => $date->format('H:i'),
                                '%coach%' => $coach,
                            ]
                        ), // max 450 caractères
                    ]);

                    if (isset($res->id)) {
                        /**
                         * Identifiant de la campagne Sarbacane.
                         *
                         * @var string
                         */
                        $sarbacaneCampaignId = $res->id;

                        $phone = $mandatary->getPhone();

                        // Ajoute des destinataires à la campagne Sarbacane
                        $res = $this->sarbacaneApiHelper->call(sprintf('campaigns/%s/recipients', $sarbacaneCampaignId), [
                            [
                                'phone' => $phone,
                            ],
                        ]);

                        if (isset($res[0]) && $phone === $res[0]->phone) {
                            // Envoie la campagne Sarbacane
                            $this->sarbacaneApiHelper->call(sprintf('campaigns/%s/send', $sarbacaneCampaignId), []);

                            // Indique que le rendez-vous a fait l'objet d'un envoi de SMS automatisé
                            $event->setReminderSmsSent(true);
                            $this->em->persist($event);
                            $sentCount++;
                        }
                    }

                    $progressBar->advance();
                }

                $this->em->flush();

                $progressBar->finish();
                $io->newLine();
                $io->newLine();

                $io->writeln(sprintf('Nombre de rappels de rendez-vous envoyés : %s/%s', $sentCount, $eventsCount));
            } else {
                $io->writeln('Aucun rappel de rendez-vous à envoyer.');
            }
        } else {
            $io->writeln('Mode dev : aucun rappel de rendez-vous ne sera envoyé.');
        }

        $io->title(sprintf('[%s] Fin des rappels de rendez-vous par SMS', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
