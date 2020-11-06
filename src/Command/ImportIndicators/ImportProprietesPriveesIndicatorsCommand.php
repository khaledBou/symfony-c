<?php

namespace App\Command\ImportIndicators;

use App\Entity\Indicator;

/**
 * Commande d'import des indicateurs des négociateurs Proprietes-Privees.
 */
class ImportProprietesPriveesIndicatorsCommand extends AbstractImportIndicatorsCommand
{
    /**
     * L'identifiant du réseau pour lequel importer les indicateurs.
     *
     * @var string
     */
    const NETWORK = 'pp';

    /**
     * @var string
     */
    protected static $defaultName = 'app:indicator:import:pp';

    /**
     * Remplit l'indicateur "Étapes Booster réalisées".
     *
     * @param IndicatorInterface $indicator
     *
     * @return IndicatorInterface
     */
    public function fillBoosterIndicator(Indicator\IndicatorInterface $indicator): Indicator\IndicatorInterface
    {
        $currentlyImportedData = $indicator->getMandatary()->getCurrentlyImportedData();

        if ($currentlyImportedData) {
            /**
             * @var Indicator\TrainingProgramIndicator
             */
            $indicator
                ->setCompletedMissions($currentlyImportedData['boosterCompletion'])
            ;
        }

        return $indicator;
    }

    /**
     * Remplit l'indicateur "Autonome en publication".
     *
     * @param IndicatorInterface $indicator
     *
     * @return IndicatorInterface
     */
    public function fillAutonomePublicationIndicator(Indicator\IndicatorInterface $indicator): Indicator\IndicatorInterface
    {
        $currentlyImportedData = $indicator->getMandatary()->getCurrentlyImportedData();

        if ($currentlyImportedData) {
            /**
             * @var Indicator\BooleanIndicator
             */
            $indicator
                ->setValue('AUTONOME' === $currentlyImportedData['niveau'])
            ;
        }

        return $indicator;
    }

    /**
     * Remplit l'indicateur "Démarches administratives".
     *
     * @param IndicatorInterface $indicator
     *
     * @return IndicatorInterface
     */
    public function fillAdministrativeIndicator(Indicator\IndicatorInterface $indicator): Indicator\IndicatorInterface
    {
        $currentlyImportedData = $indicator->getMandatary()->getCurrentlyImportedData();

        if ($currentlyImportedData) {
            /**
             * @var Indicator\AdministrativeIndicator
             */
            $indicator
                ->setValidRsac(null !== $currentlyImportedData['rsac'] ? $currentlyImportedData['rsac'] : false)
                ->setValidSiret(null !== $currentlyImportedData['siret'] ? $currentlyImportedData['siret'] : false)
                ->setValidRcp(null !== $currentlyImportedData['rcp'] ? $currentlyImportedData['rcp'] : false)
                ->setValidCci(null !== $currentlyImportedData['cci'] ? $currentlyImportedData['cci'] : false)
                ->setValidTva(null !== $currentlyImportedData['tva'] ? $currentlyImportedData['tva'] : false)
                ->setValidPortage(null !== $currentlyImportedData['convention_adhesion_portage'] ? $currentlyImportedData['convention_adhesion_portage'] : false)
            ;
        }

        return $indicator;
    }

    /**
     * Remplit l'indicateur "Suspendu".
     *
     * @param IndicatorInterface $indicator
     *
     * @return IndicatorInterface
     */
    public function fillSuspendedIndicator(Indicator\IndicatorInterface $indicator): Indicator\IndicatorInterface
    {
        $currentlyImportedData = $indicator->getMandatary()->getCurrentlyImportedData();

        if ($currentlyImportedData) {
            /**
             * @var Indicator\BooleanIndicator
             */
            $indicator
                ->setValue(null === $currentlyImportedData['suspendu'] ? false : $currentlyImportedData['suspendu'])
            ;
        }

        return $indicator;
    }

    /**
     * Remplit l'indicateur "Résilié".
     *
     * @param IndicatorInterface $indicator
     *
     * @return IndicatorInterface
     */
    public function fillResignedIndicator(Indicator\IndicatorInterface $indicator): Indicator\IndicatorInterface
    {
        $currentlyImportedData = $indicator->getMandatary()->getCurrentlyImportedData();

        if ($currentlyImportedData) {
            /**
             * @var Indicator\BooleanIndicator
             */
            $indicator
                ->setValue(null !== $currentlyImportedData['date_sortie'])
            ;
        }

        return $indicator;
    }

    /**
     * Remplit l'indicateur "En situation d'impayé".
     *
     * @param IndicatorInterface $indicator
     *
     * @return IndicatorInterface
     */
    public function fillUnpaidIndicator(Indicator\IndicatorInterface $indicator): Indicator\IndicatorInterface
    {
        $currentlyImportedData = $indicator->getMandatary()->getCurrentlyImportedData();

        if ($currentlyImportedData) {
            /**
             * @var Indicator\BooleanIndicator
             */
            $indicator
                ->setValue(null === $currentlyImportedData['impaye'] ? false : $currentlyImportedData['impaye'])
            ;
        }

        return $indicator;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import des indicateurs des négociateurs Proprietes-Privees')
        ;
    }
}
