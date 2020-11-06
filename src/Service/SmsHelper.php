<?php

namespace App\Service;

use App\Entity\User\ImportableUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gestion des SMS.
 */
class SmsHelper
{
    /**
     * @var SarbacaneApiHelper
     */
    private $sarbacaneApiHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * @param SarbacaneApiHelper     $sarbacaneApiHelper
     * @param TranslatorInterface    $translator
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(SarbacaneApiHelper $sarbacaneApiHelper, TranslatorInterface $translator, EntityManagerInterface $em, ParameterBagInterface $parameterBag)
    {
        $this->sarbacaneApiHelper = $sarbacaneApiHelper;
        $this->translator = $translator;
        $this->em = $em;
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');
    }

    /**
     * Envoie, éventuellement, un SMS pour un événement.
     *
     * Le SMS est envoyé au négociateur, généralement pour le féliciter d'un événement majeur.
     *
     * @param \App\Entity\Event\EventInterface $event
     * @param \DateTimeInterface|null          $scheduledDate Envoi immédiat si null
     *
     * @return bool
     */
    public function sendEventSms(\App\Entity\Event\EventInterface $event, ?\DateTimeInterface $scheduledDate = null): bool
    {
        $mandatary = $event->getMandatary();
        $coach = $mandatary->getCoach();

        /* Le SMS étant envoyé au nom du coach,
         * s'il n'y a pas de coach, on n'envoie pas de SMS. */
        if (null === $coach) {
            return false;
        }

        /* Si l'événement a déjà fait l'objet d'un SMS automatisé,
         * on ne fait pas de nouvel envoi. */
        if ($event->isSmsSent()) {
            return false;
        }

        /**
         * Date de l'événement en nombre de jours par rapport à maintenant :
         *
         * +n ou n : événement à venir dans n jours
         * -n : événement passé de n jours
         *
         * @var int
         */
        $days = (int) (new \DateTime())->diff($event->getDate())->format('%R%a');

        // Événement passé ?
        $isPastEvent = $event->getDate() <= new \DateTime();

        // Événement futur ?
        $isFutureEvent = $event->getDate() > new \DateTime();

        // Par défaut, pas de contenu donc pas de SMS
        $content = null;

        switch (get_class($event)) {
            case 'App\Entity\Event\NthTradeEvent':
                // Si l'événement est passé depuis 7 jours ou moins
                if ($isPastEvent && $days >= -7) {
                    $nth = $event->getNth();
                    if (1 === $nth) {
                        $content = $this->translator->trans(
                            "Bonjour %mandatary%.\nVous avez signé votre 1er mandat, je vous souhaite beaucoup de visites.\nA bientot,\n%coach%",
                            [
                                '%mandatary%' => $mandatary->getFirstName(),
                                '%coach%' => $coach,
                            ]
                        );
                    }
                }
                break;
            case 'App\Entity\Event\NthCompromiseEvent':
                // Si l'événement est passé depuis 7 jours ou moins
                if ($isPastEvent && $days >= -7) {
                    $nth = $event->getNth();
                    if (1 === $nth) {
                        $content = $this->translator->trans(
                            "Bonjour %mandatary%.\nFélicitations, votre 1er compromis est signé. Comment s'est passée cette signature ? Je suis disponible pour échanger sur cette étape importante.\nA bientot,\n%coach%",
                            [
                                '%mandatary%' => $mandatary->getFirstName(),
                                '%coach%' => $coach,
                            ]
                        );
                    }
                }
                break;
            case 'App\Entity\Event\NthSaleEvent':
                // Si l'événement est passé depuis 7 jours ou moins
                if ($isPastEvent && $days >= -7) {
                    $nth = $event->getNth();
                    if (1 === $nth) {
                        $content = $this->translator->trans(
                            "Bonjour %mandatary%.\nNouvelle étape, 1ère vente signée, un aboutissement après des semaines d'attente avec vos clients. Bravo.\n%coach%",
                            [
                                '%mandatary%' => $mandatary->getFirstName(),
                                '%coach%' => $coach,
                            ]
                        );
                    }
                }
                break;
            case 'App\Entity\Event\AppointmentEvent':
                // Si l'événement est dans le futur
                if ($isFutureEvent) {
                    $date = $event->getDate();
                    $content = $this->translator->trans(
                        "Bonjour %mandatary%.\nJe vous donne rendez-vous par téléphone le %date% à %time%.\nA bientot,\n%coach%",
                        [
                            '%mandatary%' => $mandatary->getFirstName(),
                            '%date%' => $date->format('d/m/Y'),
                            '%time%' => $date->format('H:i'),
                            '%coach%' => $coach,
                        ]
                    )
                    ;
                }
                break;
            case 'App\Entity\Event\BeginningBirthdayEvent':
                // Si l'événement est tout juste passé
                if ($isPastEvent && $days >= -1) {
                    $date = $event->getDate();
                    $beginDate = $mandatary->getBeginDate();
                    $years = (int) $date->diff($beginDate)->format('%y');
                    $content = $years > 1 ? $this->translator->trans(
                        "Bonjour %mandatary%.\nBravo, cela fait %years% ans que vous etes dans le réseau. Continuons l'aventure ensemble !\nTrès bon anniversaire, à bientot,\n%coach%",
                        [
                            '%mandatary%' => $mandatary->getFirstName(),
                            '%years%' => $years,
                            '%coach%' => $coach,
                        ]
                    ) : $this->translator->trans(
                        "Bonjour %mandatary%.\nBravo, cela fait 1 an que vous etes dans le réseau. Continuons l'aventure ensemble !\nTrès bon anniversaire, à bientot,\n%coach%",
                        [
                            '%mandatary%' => $mandatary->getFirstName(),
                            '%coach%' => $coach,
                        ]
                    );
                }
                break;
            case 'App\Entity\Event\BirthdayEvent':
                // Si l'événement est tout juste passé
                if ($isPastEvent && $days >= -1) {
                    $date = $event->getDate();
                    $content = $this->translator->trans(
                        "Bonjour %mandatary%.\nJe vous souhaite un joyeux anniversaire.\nTrès belle journée, à bientot,\n%coach%",
                        [
                            '%mandatary%' => $mandatary->getFirstName(),
                            '%coach%' => $coach,
                        ]
                    );
                }
                break;
            case 'App\Entity\Event\FeeGreaterThanEvent':
                // Si l'événement est tout juste passé
                if ($isPastEvent && $days >= -1) {
                    $date = $event->getDate();
                    $content = $this->translator->trans(
                        "Bonjour %mandatary%.\nJe vous félicite pour cette belle vente avec des honoraires hors taxe de %feeExclTax%€.\nTrès bonne journée, à bientot,\n%coach%",
                        [
                            '%mandatary%' => $mandatary->getFirstName(),
                            '%feeExclTax%' => $event->getFeeExclTax(),
                            '%coach%' => $coach,
                        ]
                    );
                }
                break;
        }

        // Sans contenu, pas de SMS
        if (null === $content) {
            return false;
        }

        $sent = $this->send($event->getMandatary(), $content, $scheduledDate);

        if ($sent) {
            // Indique que l'événement a fait l'objet d'un envoi de SMS automatisé
            $event->setSmsSent(true);
            $this->em->persist($event);
            $this->em->flush();
        }

        return $sent;
    }

    /**
     * Envoie un SMS.
     *
     * @see https://developers.mailify.com/#create-a-sms-campaign
     * @see https://developers.mailify.com/#import-recipients
     * @see https://developers.mailify.com/#send-campaign
     *
     * @param ImportableUserInterface $user
     * @param string                  $content       Limité à 450 caractères
     * @param \DateTimeInterface|null $scheduledDate Envoi immédiat si null
     *
     * @return bool
     */
    public function send(ImportableUserInterface $user, string $content, ?\DateTimeInterface $scheduledDate = null): bool
    {
        // Pas d'envoi de SMS en mode dev
        if ($this->isDev) {
            return false;
        }

        $sent = false;

        // Crée la campagne Sarbacane
        $res = $this->sarbacaneApiHelper->call('campaigns/sms', [
            'name' => sprintf("Coaching n°%s", uniqid()),
            'kind' => 'SMS_NOTIFICATION',
            'smsFrom' => "PROPRIVEES", // entre 3 et 11 caractères alpha-numériques
            'content' => $content, // max 450 caractères
        ]);

        if (isset($res->id)) {
            /**
             * Identifiant de la campagne Sarbacane.
             *
             * @var string
             */
            $sarbacaneCampaignId = $res->id;

            $phone = $user->getPhone();

            // Ajoute des destinataires à la campagne Sarbacane
            $res = $this->sarbacaneApiHelper->call(sprintf('campaigns/%s/recipients', $sarbacaneCampaignId), [
                [
                    'phone' => $phone,
                ],
            ]);

            if (isset($res[0]) && $phone === $res[0]->phone) {
                // Envoie la campagne Sarbacane
                $params = [];
                if (null !== $scheduledDate) {
                    $params['requestedSendDate'] = $scheduledDate->format('Y-m-d\TH:i:s\Z');
                }
                $this->sarbacaneApiHelper->call(sprintf('campaigns/%s/send', $sarbacaneCampaignId), $params);

                $sent = true;
            }
        }

        return $sent;
    }
}
