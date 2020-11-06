<?php

namespace App\Command\ImportEvents;

use App\Entity\Event;
use App\Entity\User\Mandatary;

/**
 * Commande d'import des événements des négociateurs Rezoximo.
 */
class ImportRezoximoEventsCommand extends AbstractImportEventsCommand
{
    /**
     * L'identifiant du réseau pour lequel importer les événements.
     *
     * @var string
     */
    const NETWORK = 'rz';

    /**
     * Correspondance entre les contrats du Google Sheets et ceux de l'application.
     *
     * @var array
     */
    const CONTRACTS = [
        'agent commercial (auto entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (auto-entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro-entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (auto entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (auto-entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro-entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (normal)' => Mandatary::CONTRACT_AGENT_COMMERCIAL,
        'agent commercial' => Mandatary::CONTRACT_AGENT_COMMERCIAL,
        'portage salarial' => Mandatary::CONTRACT_PORTAGE_SALARIAL,
    ];

    /**
     * @var string
     */
    protected static $defaultName = 'app:event:import:rz';

    /**
     * Crée les événements "Entrée dans le réseau".
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
     * Crée les événements "Changement de contrat".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
     */
    public function createContractUpdateEvent(string $eventEntity, Mandatary $mandatary): array
    {
        $events = [];

        $previouslyImportedData = $mandatary->getPreviouslyImportedData();

        if ($previouslyImportedData) {
            // Contrat précédent
            $contractString = !empty($previouslyImportedData['regime']) ?
                              sprintf('%s (%s)', $previouslyImportedData['statut'], $previouslyImportedData['regime']) :
                              $previouslyImportedData['statut'];
            $oldContract = isset(self::CONTRACTS[$contractString]) ? self::CONTRACTS[$contractString] : null;

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
     * Crée les événements "Changement de pack".
     *
     * @param string    $eventEntity
     * @param Mandatary $mandatary
     *
     * @return EventInterface[]
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
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import des événements des négociateurs Rezoximo')
        ;
    }
}
