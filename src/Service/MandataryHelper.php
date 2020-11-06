<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Indicator;
use App\Entity\User\Mandatary;
use App\Service\FreshdeskApiHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gestion des négociateurs.
 */
class MandataryHelper
{
    /**
     * Nombre de jours à partir duquel considérer que le négociateur :
     * - ne rentre plus de mandats
     * - ne signe plus de compromis
     * - ne fait plus de ventes
     *
     * @var int
     */
    const TRADE_SHORTFALL_DELAY = 30,
          COMPROMISE_SHORTFALL_DELAY = 60,
          SALE_SHORTFALL_DELAY = 90;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FreshdeskApiHelper
     */
    private $freshdeskApiHelper;

    /**
     * @var array
     */
    private $config;

    /**
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @param FreshdeskApiHelper     $freshdeskApiHelper
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, FreshdeskApiHelper $freshdeskApiHelper, ParameterBagInterface $parameterBag)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->freshdeskApiHelper = $freshdeskApiHelper;
        $this->config = $parameterBag->get('app');
    }

    /**
     * Récupère les identifiants Freshdesk de l'ensemble des négociateurs.
     *
     * @see https://developers.freshdesk.com/api/#contacts
     *
     * @return array Les clés sont les adresses e-mail des négociateurs, les valeurs sont leurs identifiants Freshdesk
     */
    public function getFreshdeskUserIds(): array
    {
        $userIds = [];

        $page = 1;

        // Appel de l'API Freshdesk tant qu'elle nous renvoie des contacts
        while (1 === $page || !empty($contacts)) { // permet le démarrage, et la sortie (notamment en cas d'erreur)
            $contacts = $this->freshdeskApiHelper->call('contacts', [
                'per_page' => 100,
                'page' => $page,
            ]);

            foreach ($contacts as $contact) {
                if ('NÉGOCIATEUR' === $contact->job_title) { // @codingStandardsIgnoreLine
                    $userIds[$contact->email] = $contact->id;
                }
            }

            $page++;
        }

        return $userIds;
    }

    /**
     * Récupère les évaluations Freshdesk laissées depuis la date $sinceDate,
     * pour l'ensemble des négociateurs.
     *
     * Le tableau retourné peut contenir des évaluations laissées
     * par des contacts Freshdesk autres que des négociateurs.
     *
     * @see https://developers.freshdesk.com/api/#satisfaction-ratings
     *
     * @param \DateTimeInterface|null $sinceDate
     *
     * @return array Les clés sont les identifiants Freshdesk des négociateurs
     */
    public function getFreshdeskFeedbacks(?\DateTimeInterface $sinceDate = null): array
    {
        $feedbacks = [];

        // Appel de l'API Freshdesk
        $satisfactionRatings = $this->freshdeskApiHelper->call(
            'surveys/satisfaction_ratings',
            null !== $sinceDate ? ['created_since' => $sinceDate->format('Y-m-d\TH:i:s\Z')] : []
        );

        if (null !== $satisfactionRatings) {
            foreach ($satisfactionRatings as $satisfactionRating) {
                // @codingStandardsIgnoreStart
                if (isset($satisfactionRating->ratings->default_question)) {
                    if (!isset($feedbacks[$satisfactionRating->user_id])) {
                        $feedbacks[$satisfactionRating->user_id] = [];
                    }
                    $feedbacks[$satisfactionRating->user_id][] = [
                        'date' => new \DateTime($satisfactionRating->created_at),
                        /* Correspond aux niveaux de satisfaction définis
                         * en constantes de App\Entity\Event\FreshdeskFeedbackEvent. */
                        'rating' => (int) $satisfactionRating->ratings->default_question,
                        'comment' => $satisfactionRating->feedback ? (string) $satisfactionRating->feedback : null,
                        'ticket_id' => (string) $satisfactionRating->ticket_id,
                    ];
                }
                // @codingStandardsIgnoreEnd
            }
        }

        return $feedbacks;
    }

    /**
     * Construit une liste d'alertes à afficher sur la fiche d'un négociateur.
     *
     * @param Mandatary $mandatary
     *
     * @return string[] Le tableau de retour doit comporter des clés textuelles
     */
    public function getAlerts(Mandatary $mandatary): array
    {
        $alerts = [];

        // Lance une alerte si le négociateur n'a pas de coach
        if (null === $mandatary->getCoach()) {
            $alerts['no_coach'] = $this->translator->trans("Négociateur sans coach.");
        }

        /* Lance une alerte si le négociateur est entré dans le réseau depuis plus d'un mois
         * et qu'il n'a pas validé toutes ses démarches administratives. */
        if ($this->begunMoreThanOneMonthAgo($mandatary)) {
            if ($this->hasAdministrativeDefault($mandatary)) {
                $alerts['administrative_default'] = $this->translator->trans("Défaut administratif détecté.");
            }
        }

        // Lance une alerte si le négociateur a bien plus de mandats que de ventes
        $tradesCount = $mandatary->getTradesCount();
        $salesCount = $mandatary->getSalesCount();

        if ($salesCount > 0) {
            $salesRatio = $tradesCount / $salesCount;
            if ($salesRatio >= 15) {
                $alerts['sales_ratio'] = $this->translator->trans("Ce négociateur a %ratio% fois plus de mandats que de ventes.", [
                    '%ratio%' => floor($salesRatio),
                ]);
            }
        } elseif ($tradesCount >= 15) {
            $alerts['sales_ratio'] = $this->translator->trans("Ce négociateur a %count% mandats mais n'a fait aucune vente.", [
                '%count%' => $tradesCount,
            ]);
        }

        return $alerts;
    }

    /**
     * Détermine le statut de suivi d'un négociateur.
     *
     * Renvoie :
     * - Mandatary::SUPPORT_STATUS_GOOD si dernier contact (RDV ou appel téléphonique) date d'il y a moins d'1 mois
     * - Mandatary::SUPPORT_STATUS_FAIR si dernier contact date d'il y a moins de 2 mois
     * - Mandatary::SUPPORT_STATUS_BAD si dernier contact date de plus de 2 mois
     *
     * @param Mandatary $mandatary
     *
     * @return int
     */
    public function getSupportStatus(Mandatary $mandatary): int
    {
        // Valeur de retour
        $supportStatus = Mandatary::SUPPORT_STATUS_BAD;

        /**
         * Dernière prise de contact (la plus récente).
         *
         * @var Event\AppointmentEvent|Event\CallEvent|null
         */
        $latestEvent = $this->em->getRepository(Event\AbstractEvent::class)->findLatestContact($mandatary);

        // Si un contact a déjà eu lieu
        if (null !== $latestEvent) {
            /**
             * Nombre de jours écoulés depuis ce contact.
             *
             * @var int
             */
            $days = (int) (new \DateTime())->diff($latestEvent->getDate())->format('%a');

            if ($days <= 30) { // était-ce il y a moins de 30 jours ?
                $supportStatus = Mandatary::SUPPORT_STATUS_GOOD;
            } elseif ($days <= 60) {  // ou il y a moins de 60 jours ?
                $supportStatus = Mandatary::SUPPORT_STATUS_FAIR;
            }
        }

        return $supportStatus;
    }

    /**
     * Récupère les statistiques calculées pour un négociateur.
     *
     * @param Mandatary $mandatary
     *
     * @return array
     */
    public function getProcessedStats(Mandatary $mandatary): array
    {
        // Valeur de retour
        $stats = [
            'contacts_count' => 0,
            'contacts_count_for_2_months' => 0,
            'next_appointment' => null,
            'latest_mandatary_reminder' => null,
        ];

        /**
         * Nombre d'événements pouvant être considérés comme des prise de contacts.
         *
         * @var int
         */
        $stats['contacts_count'] = $this
            ->em
            ->getRepository(Event\AbstractEvent::class)
            ->countContacts($mandatary)
        ;

        /**
         * Nombre d'événements pouvant être considérés comme des prise de contacts,
         * survenus dans les deux derniers mois.
         *
         * @var int
         */
        $stats['contacts_count_for_2_months'] = $this
            ->em
            ->getRepository(Event\AbstractEvent::class)
            ->countContacts($mandatary, 60)
        ;

        /**
         * Prochain rendez-vous prévu.
         *
         * @var Event\AppointmentEvent|null
         */
        $stats['next_appointment'] = $this
            ->em
            ->getRepository(Event\AppointmentEvent::class)
            ->findNext($mandatary)
        ;

        /**
         * Dernier rappel envoyé (le plus récent).
         *
         * @var Event\MandataryReminderEvent|null
         */
        $stats['latest_mandatary_reminder'] = $this
            ->em
            ->getRepository(Event\MandataryReminderEvent::class)
            ->findLatest($mandatary)
        ;


        return $stats;
    }

    /**
     * Indique si le négociateur est autonome en publication.
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    public function isAutonomePublication(Mandatary $mandatary): bool
    {
        /**
         * @var Indicator\BooleanIndicator|null
         */
        $autonomePublicationIndicator = $this->em
            ->getRepository(Indicator\BooleanIndicator::class)
            ->findOneBy([
                'mandatary' => $mandatary,
                'key' => 'autonome_publication',
            ])
        ;

        return null !== $autonomePublicationIndicator && $autonomePublicationIndicator->getValue();
    }

    /**
     * Indique si le négociateur est suspendu ou résilié.
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    public function isSuspendedOrResigned(Mandatary $mandatary): bool
    {
        /**
         * @var Indicator\BooleanIndicator|null
         */
        $suspendedIndicator = $this->em
            ->getRepository(Indicator\BooleanIndicator::class)
            ->findOneBy([
                'mandatary' => $mandatary,
                'key' => 'suspended',
            ])
        ;
        $isSuspended = null !== $suspendedIndicator && $suspendedIndicator->getValue();

        /**
         * @var Indicator\BooleanIndicator|null
         */
        $resignedIndicator = $this->em
            ->getRepository(Indicator\BooleanIndicator::class)
            ->findOneBy([
                'mandatary' => $mandatary,
                'key' => 'resigned',
            ])
        ;
        $isResigned = null !== $resignedIndicator && $resignedIndicator->getValue();

        return $isSuspended || $isResigned;
    }

    /**
     * Indique si le négociateur ne rentre plus de mandats.
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    public function hasTradeShortfall(Mandatary $mandatary): bool
    {
        // Date du dernier mandat
        $tradesDates = $mandatary->getTradesDates();
        $latestTradeDate = end($tradesDates);

        return false !== $latestTradeDate ?
            $this->hasShortfall($latestTradeDate, self::TRADE_SHORTFALL_DELAY)
            : false
        ;
    }

    /**
     * Indique si le négociateur ne signe plus de compromis.
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    public function hasCompromiseShortfall(Mandatary $mandatary): bool
    {
        // Date du dernier compromis
        $compromisesDates = $mandatary->getCompromisesDates();
        $latestCompromiseDate = end($compromisesDates);

        return false !== $latestCompromiseDate ?
            $this->hasShortfall($latestCompromiseDate, self::COMPROMISE_SHORTFALL_DELAY) :
            false
        ;
    }

    /**
     * Indique si le négociateur ne fait plus de ventes.
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    public function hasSaleShortfall(Mandatary $mandatary): bool
    {
        // Date de la dernière vente
        $salesDates = $mandatary->getSalesDates();
        $latestSaleDate = end($salesDates);

        return false !== $latestSaleDate ?
            $this->hasShortfall($latestSaleDate, self::SALE_SHORTFALL_DELAY)
            : false
        ;
    }

    /**
     * Méthode interne pour déterminer si une date $date est passée depuis $days jours ou moins,
     * afin de savoir si un négociateur :
     * - ne rentre plus de mandats
     * - ne signe plus de compromis
     * - ne fait plus de ventes
     *
     * @param \DateTime $date La date du dernier mandat, compromis ou vente
     * @param int       $days Nombre de jours
     *
     * @return bool
     */
    private function hasShortfall(\DateTime $date, int $days): bool
    {
        // Nombre de jours depuis la date
        return (int) $date->diff(new \DateTime())->format('%a') >= $days;
    }

    /**
     * Indique si le négociateur est dans le réseau depuis au moins un mois (30 jours).
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    private function begunMoreThanOneMonthAgo(Mandatary $mandatary): bool
    {
        return (int) $mandatary->getBeginDate()->diff(new \DateTime())->format('%a') > 30;
    }

    /**
     * Indique la présence d'un défaut administratif pour un négociateur.
     *
     * @param Mandatary $mandatary
     *
     * @return bool
     */
    private function hasAdministrativeDefault(Mandatary $mandatary): bool
    {
        // @var string|null
        $contract = $mandatary->getContract();

        /**
         * Les pièces administratives à fournir,
         * selon le type de contrat du négociateur.
         *
         * @var string[]
         */
        $todos = isset(Mandatary::ADMINISTRATIVE_DOCUMENTS[$contract]) ?
                       Mandatary::ADMINISTRATIVE_DOCUMENTS[$contract] : [];

        /**
         * L'indicateur administratif du négociateur.
         *
         * C'est lui qui contient l'état de validation des pièces administratives.
         *
         * @var Indicator\AdministrativeIndicator|null
         */
        $administrativeIndicator = $this->em
            ->getRepository(Indicator\AdministrativeIndicator::class)
            ->findOneByMandatary($mandatary)
        ;

        // Les méthodes de validation de l'indicateur administratif
        $methods = [
            'rsac' => 'isValidRsac',
            'siret' => 'isValidSiret',
            'rcp' => 'isValidRcp',
            'cci' => 'isValidCci',
            'tva' => 'isValidTva',
            'portage' => 'isValidPortage',
        ];

        // Valeur de retour
        $hasAdministrativeDefault = false;

        if (null !== $administrativeIndicator) {
            // Pour chaque pièce administrative à fournir
            foreach ($todos as $todo) {
                // Recherche de la valeur dans l'indicateur administratif
                if (isset($methods[$todo])) {
                    $method = $methods[$todo];
                    if (!$administrativeIndicator->$method()) {
                        $hasAdministrativeDefault = true;
                    }
                } else {
                    $hasAdministrativeDefault = true;
                }
                if ($hasAdministrativeDefault) {
                    break;
                }
            }
        }

        return $hasAdministrativeDefault;
    }
}
