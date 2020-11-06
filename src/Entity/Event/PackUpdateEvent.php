<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Changement de pack.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\PackUpdateEventRepository")
 */
class PackUpdateEvent extends AbstractEvent
{
    /**
     * Ancien pack.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $oldPack;

    /**
     * Nouveau pack.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $newPack;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_PACK_UPDATE;
    }

    /**
     * @return string|null
     */
    public function getOldPack(): ?string
    {
        return $this->oldPack;
    }

    /**
     * @param string|null $oldPack
     *
     * @return self
     */
    public function setOldPack(?string $oldPack): self
    {
        $this->oldPack = $oldPack;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNewPack(): ?string
    {
        return $this->newPack;
    }

    /**
     * @param string|null $newPack
     *
     * @return self
     */
    public function setNewPack(?string $newPack): self
    {
        $this->newPack = $newPack;

        return $this;
    }
}
