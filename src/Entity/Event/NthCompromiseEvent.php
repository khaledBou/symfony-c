<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Énième compromis.
 *
 * @ORM\Entity
 */
class NthCompromiseEvent extends AbstractEvent
{
    /**
     * Énième compromis.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $nth;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_NTH_COMPROMISE;
    }

    /**
     * @return int
     */
    public function getNth(): ?int
    {
        return $this->nth;
    }

    /**
     * @param int $nth
     *
     * @return self
     */
    public function setNth(int $nth): self
    {
        $this->nth = $nth;

        return $this;
    }
}
