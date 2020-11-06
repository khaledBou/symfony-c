<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plus de nouveau compromis.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\CompromiseShortfallEventRepository")
 */
class CompromiseShortfallEvent extends AbstractEvent
{
    /**
     * Nombre de jours écoulés depuis le dernier compromis.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $daysSinceLastCompromise;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_COMPROMISE_SHORTFALL;
    }

    /**
     * @return int
     */
    public function getDaysSinceLastCompromise(): ?int
    {
        return $this->daysSinceLastCompromise;
    }

    /**
     * @param int $daysSinceLastCompromise
     *
     * @return self
     */
    public function setDaysSinceLastCompromise(int $daysSinceLastCompromise): self
    {
        $this->daysSinceLastCompromise = $daysSinceLastCompromise;

        return $this;
    }
}
