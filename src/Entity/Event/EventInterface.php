<?php

namespace App\Entity\Event;

use App\Entity\User\Coach;
use App\Entity\User\Mandatary;

/**
 * Définit le comportement d'un événement.
 */
interface EventInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @return Mandatary|null
     */
    public function getMandatary(): ?Mandatary;

    /**
     * @param Mandatary $mandatary
     *
     * @return self
     */
    public function setMandatary(Mandatary $mandatary): self;

    /**
     * @return Coach|null
     */
    public function getCoach(): ?Coach;

    /**
     * @param Coach|null $coach
     *
     * @return self
     */
    public function setCoach(?Coach $coach): self;

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): ?\DateTimeInterface;

    /**
     * @param \DateTimeInterface $date
     *
     * @return self
     */
    public function setDate(\DateTimeInterface $date): self;
}
