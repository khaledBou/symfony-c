<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Validation d'une mission du programme de formation (Booster, Starter de l'Immo, …).
 *
 * @ORM\Entity
 */
class TrainingProgramMissionEvent extends AbstractEvent
{
    /**
     * Mission validée.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $mission;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_TRAINING_PROGRAM_MISSION;
    }

    /**
     * @return string
     */
    public function getMission(): ?string
    {
        return $this->mission;
    }

    /**
     * @param string $mission
     *
     * @return self
     */
    public function setMission(string $mission): self
    {
        $this->mission = $mission;

        return $this;
    }
}
