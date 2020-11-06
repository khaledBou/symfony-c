<?php

namespace App\Entity\Indicator;

use App\Entity\User\Mandatary;

/**
 * Définit le comportement d'un indicateur.
 */
interface IndicatorInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @return Mandatary
     */
    public function getMandatary(): ?Mandatary;

    /**
     * @param Mandatary $mandatary
     *
     * @return self
     */
    public function setMandatary(Mandatary $mandatary): self;

    /**
     * @return string
     */
    public function getKey(): ?string;

    /**
     * @param string $key
     *
     * @return self
     */
    public function setKey(string $key): self;
}
