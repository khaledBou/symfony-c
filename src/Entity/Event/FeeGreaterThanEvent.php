<?php

namespace App\Entity\Event;

use App\Repository\Event\FeeGreaterThanEventRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pour des honoraires hors taxes supérieurs à
 *
 * @ORM\Entity(repositoryClass=FeeGreaterThanEventRepository::class)
 */
class FeeGreaterThanEvent extends AbstractEvent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Prix de l'honoraire hors taxes
     *
     * @ORM\Column(type="integer")
     */
    private $feeExclTax;

    /**
     * Prix des honoraires hors taxes minimum requis pour la création de l'évènement
     *
     * @ORM\Column(type="integer")
     */
    private $feePriceMin;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_FEE_GRATER_THAN;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getFeeExclTax(): ?int
    {
        return $this->feeExclTax;
    }

    /**
     * @param int $feeExclTax
     *
     * @return self
     */
    public function setFeeExclTax(int $feeExclTax): self
    {
        $this->feeExclTax = $feeExclTax;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFeePriceMin(): ?int
    {
        return $this->feePriceMin;
    }

    /**
     * @param int $feePriceMin
     *
     * @return self
     */
    public function setFeePriceMin(int $feePriceMin): self
    {
        $this->feePriceMin = $feePriceMin;

        return $this;
    }
}
