<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Résiliation.
 *
 * @ORM\Entity
 */
class TerminationEvent extends AbstractEvent
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_TERMINATION;
    }
}
