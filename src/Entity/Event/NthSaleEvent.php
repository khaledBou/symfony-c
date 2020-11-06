<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Énième vente.
 *
 * @ORM\Entity
 */
class NthSaleEvent extends AbstractEvent
{
    /**
     * Énième vente.
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
        return self::TYPE_NTH_SALE;
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
