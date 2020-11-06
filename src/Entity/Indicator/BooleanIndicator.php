<?php

namespace App\Entity\Indicator;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Indicateur booléen.
 *
 * @ORM\Entity
 */
class BooleanIndicator extends AbstractIndicator
{
    /**
     * Valeur.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull
     */
    private $value = false;

    /**
     * @return bool
     */
    public function getValue(): ?bool
    {
        return $this->value;
    }

    /**
     * @param bool $value
     *
     * @return self
     */
    public function setValue(bool $value): self
    {
        $this->value = $value;

        return $this;
    }
}
