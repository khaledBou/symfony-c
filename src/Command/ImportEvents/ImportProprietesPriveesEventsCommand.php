<?php

namespace App\Command\ImportEvents;

use App\Entity\Event;
use App\Entity\Indicator;
use App\Entity\User\Mandatary;
use App\Service\MandataryHelper;

/**
 * Commande d'import des événements des négociateurs Proprietes-Privees.
 */
class ImportProprietesPriveesEventsCommand extends AbstractImportEventsCommand
{
    /**
     * L'identifiant du réseau pour lequel importer les événements.
     *
     * @var string
     */
    const NETWORK = 'pp';

    /**
     * Correspondance entre les contrats renvoyés par l'API et ceux de l'application.
     *
     * @var array
     */
    const CONTRACTS = [
        'AUTO ENTREPRENEUR' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'AGENT COMMERCIAL (TNS)' => Mandatary::CONTRACT_AGENT_COMMERCIAL,
        'PORTAGE' => Mandatary::CONTRACT_PORTAGE_SALARIAL,
        'GÉRANT MAJORITAIRE' => Mandatary::CONTRACT_CONCESSIONNAIRE,
    ];

    /**
     * Prix des honoraires hors taxes triés du plus grand au plus petit.
     *
     * @var int[]
     */
    const FEE_PRICE_MIN  = [
        15000,
    ];

    /**
     * Enième année dans le réseau => Enième vente
     *
     * @var array
     */
    const NTH_YEAR_NTH_SALE  = [
        0 => 8, // 8ème vente sur la première année
    ];

    /**
     * @var string
     */
    protected static $defaultName = 'app:event:import:pp';

    /**
     * Crée les événements "Changement d'activités".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createActivitiesUpdateEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $previouslyImportedData = $mandatary->getPreviouslyImportedData();

        if ($previouslyImportedData) {
            // Activités précédentes
            $oldActivities = [];
            $oldRawActivities = null !== $previouslyImportedData['activites'] ? json_decode($previouslyImportedData['activites']) : [];
            foreach ($oldRawActivities as $rawActivity) {
                $oldActivities[] = $rawActivity->name;
            }

            // Activités actuelles
            $newActivities = $mandatary->getActivities();

            // S'il existe une différence
            if (!empty(array_diff($oldActivities, $newActivities))) {
                /**
                 * @var Event\ActivitiesUpdateEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate((new \DateTime())->setTime(0, 0, 0))
                    ->setOldActivities($oldActivities)
                    ->setNewActivities($newActivities)
                ;

                /**
                 * Dernier changement d'activités.
                 *
                 * @var Event\ActivitiesUpdateEvent
                 */
                $latestEvent = $this->em
                    ->getRepository(Event\ActivitiesUpdateEvent::class)
                    ->findLatest($mandatary)
                ;

                // Vérifie que le dernier changement d'activités était différent, s'il en existe un
                if (null === $latestEvent || (
                    $latestEvent->getOldActivities() !== $event->getOldActivities() ||
                    $latestEvent->getNewActivities() !== $event->getNewActivities()
                )) {
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Changement d'activités".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createAdministrativeUpdateEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $previouslyImportedData = $mandatary->getPreviouslyImportedData();

        if ($previouslyImportedData) {
            /**
             * L'indicateur administratif du négociateur.
             *
             * C'est lui qui contient l'état actuel de validation des pièces administratives.
             *
             * @var Indicator\AdministrativeIndicator
             */
            $administrativeIndicator = $this->em
                ->getRepository(Indicator\AdministrativeIndicator::class)
                ->findOneByMandatary($mandatary)
            ;

            if (null !== $administrativeIndicator) {
                // Valeurs précédentes
                $oldValues = [
                    'rsac' => null !== $previouslyImportedData['rsac'] ? $previouslyImportedData['rsac'] : false,
                    'siret' => null !== $previouslyImportedData['siret'] ? $previouslyImportedData['siret'] : false,
                    'rcp' => null !== $previouslyImportedData['rcp'] ? $previouslyImportedData['rcp'] : false,
                    'cci' => null !== $previouslyImportedData['cci'] ? $previouslyImportedData['cci'] : false,
                    'tva' => null !== $previouslyImportedData['tva'] ? $previouslyImportedData['tva'] : false,
                    'portage' => null !== $previouslyImportedData['convention_adhesion_portage'] ? $previouslyImportedData['convention_adhesion_portage'] : false,
                ];

                // Valeurs actuelles
                $newValues = [
                    'rsac' => $administrativeIndicator->isValidRsac(),
                    'siret' => $administrativeIndicator->isValidSiret(),
                    'rcp' => $administrativeIndicator->isValidRcp(),
                    'cci' => $administrativeIndicator->isValidCci(),
                    'tva' => $administrativeIndicator->isValidTva(),
                    'portage' => $administrativeIndicator->isValidPortage(),
                ];

                // S'il existe une différence
                if (!empty(array_diff_assoc($oldValues, $newValues))) {
                    /**
                     * @var Event\AdministrativeUpdateEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate((new \DateTime())->setTime(0, 0, 0))
                        ->setOldValidRsac($oldValues['rsac'])
                        ->setNewValidRsac($newValues['rsac'])
                        ->setOldValidSiret($oldValues['siret'])
                        ->setNewValidSiret($newValues['siret'])
                        ->setOldValidRcp($oldValues['rcp'])
                        ->setNewValidRcp($newValues['rcp'])
                        ->setOldValidCci($oldValues['cci'])
                        ->setNewValidCci($newValues['cci'])
                        ->setOldValidTva($oldValues['tva'])
                        ->setNewValidTva($newValues['tva'])
                        ->setOldValidPortage($oldValues['portage'])
                        ->setNewValidPortage($newValues['portage'])
                    ;

                    /**
                     * Dernier changement administratif.
                     *
                     * @var Event\AdministrativeUpdateEvent
                     */
                    $latestEvent = $this->em
                        ->getRepository(Event\AdministrativeUpdateEvent::class)
                        ->findLatest($mandatary)
                    ;

                    // Vérifie que le dernier changement administratif était différent, s'il en existe un
                    if (null === $latestEvent || (
                        $latestEvent->getOldValidRsac() !== $event->getOldValidRsac() ||
                        $latestEvent->getNewValidRsac() !== $event->getNewValidRsac() ||
                        $latestEvent->getOldValidRsac() !== $event->getOldValidRsac() ||
                        $latestEvent->getNewValidRsac() !== $event->getNewValidRsac() ||
                        $latestEvent->getOldValidRsac() !== $event->getOldValidRsac() ||
                        $latestEvent->getNewValidRsac() !== $event->getNewValidRsac() ||
                        $latestEvent->getOldValidRsac() !== $event->getOldValidRsac() ||
                        $latestEvent->getNewValidRsac() !== $event->getNewValidRsac() ||
                        $latestEvent->getOldValidRsac() !== $event->getOldValidRsac() ||
                        $latestEvent->getNewValidRsac() !== $event->getNewValidRsac() ||
                        $latestEvent->getOldValidRsac() !== $event->getOldValidRsac() ||
                        $latestEvent->getNewValidRsac() !== $event->getNewValidRsac()
                    )) {
                        $events[] = $event;
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Changement administratif".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createBeginningEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        /**
         * L'événement, unique pour ce négociateur.
         *
         * @var Event\BeginningEvent|null
         */
        $beginningEvent = $this->em
            ->getRepository(Event\BeginningEvent::class)
            ->findOneByMandatary($mandatary)
        ;

        if (null === $beginningEvent) {
            /**
             * @var Event\BeginningEvent
             */
            $event = (new $eventEntity())
                ->setMandatary($mandatary)
                ->setDate($mandatary->getBeginDate())
            ;
            $events[] = $event;
        }

        return $events;
    }

    /**
     * Crée les événements "Anniversaire d'entrée dans le réseau".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createBeginningBirthdayEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $beginningDate = $mandatary->getBeginDate();
        $todayDate = new \DateTime();

        $beginningDateYear = (int) $beginningDate->format('Y');
        $todayDateYear = (int) $todayDate->format('Y');

        // Si c'est la date anniversaire et qu'au moins un an s'est passé
        if ($beginningDate->format('m-d') === $todayDate->format('m-d') && $beginningDateYear < $todayDateYear) {
            /**
             * L'événement, unique pour ce négociateur aujourd'hui.
             *
             * @var Event\BeginningBirthdayEvent|null
             */
            $beginningBirthdayEvent = $this->em
                ->getRepository(Event\BeginningBirthdayEvent::class)
                ->findOneByMandataryAtDate($mandatary, $todayDate)
            ;

            if (null === $beginningBirthdayEvent) {
                /**
                 * @var Event\BeginningBirthdayEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate($todayDate->setTime(0, 0, 0))
                ;
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Anniversaire de naissance".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createBirthdayEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $birthDate = $mandatary->getBirthDate();
        $todayDate = new \DateTime();

        $birthDateYear = (int) $birthDate->format('Y');
        $todayDateYear = (int) $todayDate->format('Y');

        // Si c'est la date anniversaire et qu'au moins un an s'est passé
        if ($birthDate->format('m-d') === $todayDate->format('m-d') && $birthDateYear < $todayDateYear) {
            /**
             * L'événement, unique pour ce négociateur aujourd'hui.
             *
             * @var Event\BirthdayEvent|null
             */
            $birthdayEvent = $this->em
                ->getRepository(Event\BirthdayEvent::class)
                ->findOneByMandataryAtDate($mandatary, $todayDate)
            ;

            if (null === $birthdayEvent) {
                /**
                 * @var Event\BirthdayEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate($todayDate->setTime(0, 0, 0))
                ;
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Plus de nouveau compromis".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createCompromiseShortfallEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates des compromis
        $compromisesDates = $mandatary->getCompromisesDates();

        if (!empty($compromisesDates)) {
            // Dates du jour et dernier compromis
            $todayDate = new \DateTime();
            $latestCompromiseDate = end($compromisesDates);

            // Nombre de jours depuis le dernier compromis
            $days = (int) $latestCompromiseDate->diff($todayDate)->format('%a');

            if ($latestCompromiseDate < $todayDate && MandataryHelper::COMPROMISE_SHORTFALL_DELAY === $days) {
                /**
                 * L'événement, unique pour ce négociateur aujourd'hui.
                 *
                 * @var Event\CompromiseShortfallEvent|null
                 */
                $existingEvent = $this->em
                    ->getRepository(Event\CompromiseShortfallEvent::class)
                    ->findOneByMandataryAtDate($mandatary, $todayDate)
                ;

                if (null === $existingEvent) {
                    /**
                     * @var Event\CompromiseShortfallEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate((new \DateTime())->setTime(0, 0, 0))
                        ->setDaysSinceLastCompromise($days)
                    ;
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Changement de contrat".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createContractUpdateEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $previouslyImportedData = $mandatary->getPreviouslyImportedData();

        if ($previouslyImportedData) {
            // Contrat précédent
            $regime = $previouslyImportedData['regime'];
            $oldContract = isset(self::CONTRACTS[$regime]) ? self::CONTRACTS[$regime] : null;

            // Contract actuel
            $newContract = $mandatary->getContract();

            // S'il existe une différence
            if ($newContract !== $oldContract) {
                /**
                 * @var Event\ContractUpdateEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate((new \DateTime())->setTime(0, 0, 0))
                    ->setOldContract($oldContract)
                    ->setNewContract($newContract)
                ;

                /**
                 * Dernier changement de contrat.
                 *
                 * @var Event\ContractUpdateEvent
                 */
                $latestEvent = $this->em
                    ->getRepository(Event\ContractUpdateEvent::class)
                    ->findLatest($mandatary)
                ;

                // Vérifie que le dernier changement de contrat était différent, s'il en existe un
                if (null === $latestEvent || (
                    $latestEvent->getOldContract() !== $event->getOldContract() ||
                    $latestEvent->getNewContract() !== $event->getNewContract()
                )) {
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Évaluation Freshdesk".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createFreshdeskFeedbackEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $currentlyImportedData = $mandatary->getCurrentlyImportedData();

        // Boucle sur les évaluations Freshdesk laissées depuis le dernier import des négociateurs
        foreach ($currentlyImportedData['freshdeskFeedbacks'] as $feedback) {
            /**
             * L'événement qui aurait pu avoir été déjà créé,
             * dans le cas où l'import des événements aurait été joué plusieurs fois de suite
             * et sans qu'un import des négociateurs ait été joué entre temps.
             *
             * @var Event\FreshdeskFeedbackEvent|null
             */
            $existingEvent = $this->em
                ->getRepository(Event\FreshdeskFeedbackEvent::class)
                ->findOneBy([
                    'mandatary' => $mandatary,
                    'date' => $feedback['date'],
                    'rating' => $feedback['rating'],
                    'comment' => $feedback['comment'],
                    'ticketId' => $feedback['ticket_id'],
                ])
            ;

            if (null === $existingEvent) {
                /**
                 * @var Event\FreshdeskFeedbackEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate($feedback['date'])
                    ->setRating($feedback['rating'])
                    ->setComment($feedback['comment'])
                    ->setTicketId($feedback['ticket_id'])
                ;
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Énième compromis".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createNthCompromiseEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates des compromis
        $compromisesDates = $mandatary->getCompromisesDates();

        $nths = [1, 2, 5]; // premier, deuxième et cinquième
        foreach ($nths as $nth) {
            if (isset($compromisesDates[$nth-1])) {
                $nthCompromiseEvent = $this->em
                    ->getRepository(Event\NthCompromiseEvent::class)
                    ->findOneBy([
                        'mandatary' => $mandatary,
                        'nth' => $nth,
                    ])
                ;

                if (null === $nthCompromiseEvent) {
                    $compromiseDate = $compromisesDates[$nth-1];

                    /**
                     * @var Event\NthCompromiseEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate($compromiseDate)
                        ->setNth($nth)
                    ;
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Énième vente".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createNthSaleEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates des ventes
        $salesDates = $mandatary->getSalesDates();

        $nths = [1, 2, 5]; // premier, deuxième et cinquième
        foreach ($nths as $nth) {
            if (isset($salesDates[$nth-1])) {
                $nthSaleEvent = $this->em
                    ->getRepository(Event\NthSaleEvent::class)
                    ->findOneBy([
                        'mandatary' => $mandatary,
                        'nth' => $nth,
                    ])
                ;

                if (null === $nthSaleEvent) {
                    $saleDate = $salesDates[$nth-1];

                    /**
                     * @var Event\NthSaleEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate($saleDate)
                        ->setNth($nth)
                    ;
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Énième mandat".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createNthTradeEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates des mandats
        $tradesDates = $mandatary->getTradesDates();

        $nths = [1, 2, 5]; // premier, deuxième et cinquième
        foreach ($nths as $nth) {
            if (isset($tradesDates[$nth-1])) {
                $nthSaleEvent = $this->em
                    ->getRepository(Event\NthTradeEvent::class)
                    ->findOneBy([
                        'mandatary' => $mandatary,
                        'nth' => $nth,
                    ])
                ;

                if (null === $nthSaleEvent) {
                    $tradeDate = $tradesDates[$nth-1];

                    /**
                     * @var Event\NthTradeEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate($tradeDate)
                        ->setNth($nth)
                    ;
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Changement de pack".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createPackUpdateEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $previouslyImportedData = $mandatary->getPreviouslyImportedData();

        if ($previouslyImportedData) {
            // Pack précédent
            $oldPack = $previouslyImportedData['pack'];

            // Pack actuel
            $newPack = $mandatary->getPack();

            // S'il existe une différence
            if ($newPack !== $oldPack) {
                /**
                 * @var Event\PackUpdateEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate((new \DateTime())->setTime(0, 0, 0))
                    ->setOldPack($oldPack)
                    ->setNewPack($newPack)
                ;

                /**
                 * Dernier changement de pack.
                 *
                 * @var Event\PackUpdateEvent
                 */
                $latestEvent = $this->em
                    ->getRepository(Event\PackUpdateEvent::class)
                    ->findLatest($mandatary)
                ;

                // Vérifie que le dernier changement de pack était différent, s'il en existe un
                if (null === $latestEvent || (
                    $latestEvent->getOldPack() !== $event->getOldPack() ||
                    $latestEvent->getNewPack() !== $event->getNewPack()
                )) {
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Plus de nouvelle vente".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createSaleShortfallEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates des ventes
        $salesDates = $mandatary->getSalesDates();

        if (!empty($salesDates)) {
            // Dates du jour et de la dernière vente
            $todayDate = new \DateTime();
            $latestSaleDate = end($salesDates);

            // Nombre de jours depuis la dernière vente
            $days = (int) $latestSaleDate->diff($todayDate)->format('%a');

            if ($latestSaleDate < $todayDate && MandataryHelper::SALE_SHORTFALL_DELAY === $days) {
                /**
                 * L'événement, unique pour ce négociateur aujourd'hui.
                 *
                 * @var Event\SaleShortfallEvent|null
                 */
                $existingEvent = $this->em
                    ->getRepository(Event\SaleShortfallEvent::class)
                    ->findOneByMandataryAtDate($mandatary, $todayDate)
                ;

                if (null === $existingEvent) {
                    /**
                     * @var Event\SaleShortfallEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate((new \DateTime())->setTime(0, 0, 0))
                        ->setDaysSinceLastSale($days)
                    ;
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Résiliation".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createTerminationEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Date de résiliation éventuelle
        $terminationDate = $mandatary->getTerminationDate();

        if (null !== $terminationDate) {
            /**
             * L'événement, unique pour ce négociateur.
             *
             * @var Event\TerminationEvent|null
             */
            $terminationEvent = $this->em
                ->getRepository(Event\TerminationEvent::class)
                ->findOneByMandatary($mandatary)
            ;

            if (null === $terminationEvent) {
                /**
                 * @var Event\TerminationEvent
                 */
                $event = (new $eventEntity())
                    ->setMandatary($mandatary)
                    ->setDate($terminationDate)
                ;
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Plus de nouveau mandat".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createTradeShortfallEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates des mandats
        $tradesDates = $mandatary->getTradesDates();

        if (!empty($tradesDates)) {
            // Dates du jour et du dernier mandat
            $todayDate = new \DateTime();
            $latestTradeDate = end($tradesDates);

            // Nombre de jours depuis le dernier mandat
            $days = (int) $latestTradeDate->diff($todayDate)->format('%a');

            if ($latestTradeDate < $todayDate && MandataryHelper::TRADE_SHORTFALL_DELAY === $days) {
                /**
                 * L'événement, unique pour ce négociateur aujourd'hui.
                 *
                 * @var Event\TradeShortfallEvent|null
                 */
                $existingEvent = $this->em
                    ->getRepository(Event\TradeShortfallEvent::class)
                    ->findOneByMandataryAtDate($mandatary, $todayDate)
                ;

                if (null === $existingEvent) {
                    /**
                     * @var Event\TradeShortfallEvent
                     */
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate((new \DateTime())->setTime(0, 0, 0))
                        ->setDaysSinceLastTrade($days)
                    ;
                    $events[] = $event;
                }
            }
        }

        return $events;
    }

    /**
     * Crée les événements "Validation d'une étape de formation".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createTrainingProgramMissionEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $previouslyImportedData = $mandatary->getPreviouslyImportedData();

        if ($previouslyImportedData) {
            /**
             * L'indicateur de réalisation du programme de formation du négociateur.
             *
             * C'est lui qui contient l'état actuel de réalisation des missions du programme de formation.
             *
             * @var Indicator\TrainingProgramIndicator
             */
            $trainingProgramIndicator = $this->em
                ->getRepository(Indicator\TrainingProgramIndicator::class)
                ->findOneByMandatary($mandatary)
            ;

            if (null !== $trainingProgramIndicator) {
                // Missions précédentes
                $oldMissions = $previouslyImportedData['boosterCompletion'];

                // Missions actuelles
                $newMissions = $trainingProgramIndicator->getCompletedMissions();
                $addedMissions = array_diff($newMissions, $oldMissions);

                // S'il existe une différence
                if (!empty($addedMissions)) {
                    foreach ($addedMissions as $addedMission) {
                        /**
                         * L'événement qui aurait pu avoir été déjà créé,
                         * dans le cas où l'import des événements aurait été joué plusieurs fois de suite
                         * et sans qu'un import des négociateurs ait été joué entre temps.
                         *
                         * @var Event\TrainingProgramMissionEvent|null
                         */
                        $existingEvent = $this->em
                            ->getRepository(Event\TrainingProgramMissionEvent::class)
                            ->findOneBy([
                                'mandatary' => $mandatary,
                                'mission' => $addedMission,
                            ])
                        ;

                        if (null === $existingEvent) {
                            /**
                             * @var Event\TrainingProgramMissionEvent
                             */
                            $event = (new $eventEntity())
                                ->setMandatary($mandatary)
                                ->setDate((new \DateTime())->setTime(0, 0, 0))
                                ->setMission($addedMission)
                            ;
                            $events[] = $event;
                        }
                    }
                }
            }
        }

        return $events;
    }


    /**
     * Crée les événements lorsque les honoraires sont supérieurs à
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createFeeGreaterThanEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        // Dates du jour et de la dernière vente
        $todayDate = new \DateTime();

        // Array combine date => honoraire hors charges
        $feeExclTaxWithDate = array();
        // Si deux ventes le même jour : https://www.php.net/manual/fr/function.array-combine.php#111668
        foreach ($mandatary->getSalesDates() as $i => $k) {
            $feeExclTaxWithDate[$k->format('d-m-Y')][] = $mandatary->getFeeExclTax()[$i];
        }


        if (!empty($feeExclTaxWithDate[$todayDate->format('d-m-Y')])) {
            foreach ($feeExclTaxWithDate[$todayDate->format('d-m-Y')] as $item) {
                foreach (self::FEE_PRICE_MIN as $fee) {
                    // Si le prix d'une vente du jour est supérieur à l'un des minimum demander
                    if ($item >= $fee) {
                        $event = (new $eventEntity())
                            ->setMandatary($mandatary)
                            ->setDate((new \DateTime())->setTime(0, 0, 0))
                            ->setFeeExclTax($item)
                            ->setFeePriceMin($fee);
                        $events[] = $event;
                    }
                }
            }
        }

        return $events;
    }


    /**
     * Crée les événements lorsque l'on atteint la enième vente sur la enième année d'activité
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     *
     * @throws \Exception
     */
    public function createNthSaleNthYearEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];
        $today = new \DateTime();

        $yearInNetwork = $mandatary->getBeginDate();

        // Array combine date => nombre de vente
        $nbSalesByYear = array();

        // Additionnement des ventes sur l'année : https://www.php.net/manual/fr/function.array-combine.php#111668
        foreach ($mandatary->getSalesDates() as $i => $k) {
            if (empty($nbSalesByYear[$k->format('Y')])) {
                $nbSalesByYear[$k->format('Y')] = 1;
            } else {
                $nbSalesByYear[$k->format('Y')] = $nbSalesByYear[$k->format('Y')] + 1;
            }
        }

        foreach (self::NTH_YEAR_NTH_SALE as $time => $sale) {
            if (!empty($nbSalesByYear[$today->format('Y')])) {
                if (($today->diff($yearInNetwork)->y  === $time)
                    && ($nbSalesByYear[$today->format('Y')] === $sale)
                ) {
                    $event = (new $eventEntity())
                        ->setMandatary($mandatary)
                        ->setDate((new \DateTime())->setTime(0, 0, 0))
                        ->setNthYear($time)
                        ->setNthSale($sale);
                    $events[] = $event;
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
        $this
            ->setDescription('Import des événements des négociateurs Proprietes-Privees')
        ;
    }
}
