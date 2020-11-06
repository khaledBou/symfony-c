<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plus de nouveau mandat.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\TradeShortfallEventRepository")
 */
class TradeShortfallEvent extends AbstractEvent
{
    /**
     * Nombre de jours écoulés depuis le dernier mandat.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $daysSinceLastTrade;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_TRADE_SHORTFALL;
    }

    /**
     * @return int
     */
    public function getDaysSinceLastTrade(): ?int
    {
        return $this->daysSinceLastTrade;
    }

    /**
     * @param int $daysSinceLastTrade
     *
     * @return self
     */
    public function setDaysSinceLastTrade(int $daysSinceLastTrade): self
    {
        $this->daysSinceLastTrade = $daysSinceLastTrade;

        return $this;
    }
}
