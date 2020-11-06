<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Appel téléphonique.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\CallEventRepository")
 */
class CallEvent extends AbstractEvent
{
    /**
     * Appel entrant.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull
     */
    private $incoming;

    /**
     * Compte-rendu de l'appel.
     *
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $report;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_CALL;
    }

    /**
     * @return bool
     */
    public function isIncoming(): ?bool
    {
        return $this->incoming;
    }

    /**
     * @param bool $incoming
     *
     * @return self
     */
    public function setIncoming(bool $incoming): self
    {
        $this->incoming = $incoming;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReport(): ?string
    {
        return $this->report;
    }

    /**
     * @param string|null $report
     *
     * @return self
     */
    public function setReport(?string $report): self
    {
        $this->report = $report;

        return $this;
    }
}
