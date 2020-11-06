<?php

namespace App\Entity\Event;

use App\Entity\Event\AbstractEvent;
use Doctrine\ORM\Mapping as ORM;

/**
 * Énième vente réalisée sur une énième année pour un nombre d'années dans le réseau.
 *
 * @ORM\Entity
 */
class NthSaleNthYearEvent extends AbstractEvent
{
    /**
     * @ORM\Column(type="integer")
     */
    private $nthSale;

    /**
     * @ORM\Column(type="integer")
     */
    private $nthYear;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_NTH_SALE_NTH_YEAR;
    }

    /**
     * @return int|null
     */
    public function getNthSale(): ?int
    {
        return $this->nthSale;
    }

    /**
     * @param int $nthSale
     *
     * @return self
     */
    public function setNthSale(int $nthSale): self
    {
        $this->nthSale = $nthSale;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getNthYear(): ?int
    {
        return $this->nthYear;
    }

    /**
     * @param int $nthYear
     *
     * @return self
     */
    public function setNthYear(int $nthYear): self
    {
        $this->nthYear = $nthYear;

        return $this;
    }
}
