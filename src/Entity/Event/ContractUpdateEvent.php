<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Changement de contrat.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\ContractUpdateEventRepository")
 */
class ContractUpdateEvent extends AbstractEvent
{
    /**
     * Ancien contrat.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $oldContract;

    /**
     * Nouveau contrat.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $newContract;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_CONTRACT_UPDATE;
    }

    /**
     * @return string|null
     */
    public function getOldContract(): ?string
    {
        return $this->oldContract;
    }

    /**
     * @param string|null $oldContract
     *
     * @return self
     */
    public function setOldContract(?string $oldContract): self
    {
        $this->oldContract = $oldContract;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNewContract(): ?string
    {
        return $this->newContract;
    }

    /**
     * @param string|null $newContract
     *
     * @return self
     */
    public function setNewContract(?string $newContract): self
    {
        $this->newContract = $newContract;

        return $this;
    }
}
