<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entrée dans le réseau.
 *
 * @ORM\Entity
 */
class BeginningEvent extends AbstractEvent
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_BEGINNING;
    }
}
