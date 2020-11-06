<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plus de nouvelle vente.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\SaleShortfallEventRepository")
 */
class SaleShortfallEvent extends AbstractEvent
{
    /**
     * Nombre de jours écoulés depuis la dernière vente.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $daysSinceLastSale;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_SALE_SHORTFALL;
    }

    /**
     * @return int
     */
    public function getDaysSinceLastSale(): ?int
    {
        return $this->daysSinceLastSale;
    }

    /**
     * @param int $daysSinceLastSale
     *
     * @return self
     */
    public function setDaysSinceLastSale(int $daysSinceLastSale): self
    {
        $this->daysSinceLastSale = $daysSinceLastSale;

        return $this;
    }
}
