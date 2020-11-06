<?php

namespace App\Entity\Indicator;

use Doctrine\ORM\Mapping as ORM;

/**
 * Indicateur de réalisation du programme de formation (Booster, Starter de l'Immo, …).
 *
 * @ORM\Entity
 */
class TrainingProgramIndicator extends AbstractIndicator
{
    /**
     * Missions du programme réalisées.
     *
     * @var string[]
     *
     * @ORM\Column(type="array")
     */
    private $completedMissions = [];

    /**
     * @return string[]
     */
    public function getCompletedMissions(): ?array
    {
        return $this->completedMissions;
    }

    /**
     * @param string[] $completedMissions
     *
     * @return self
     */
    public function setCompletedMissions(array $completedMissions): self
    {
        $this->completedMissions = $completedMissions;

        return $this;
    }
}
