<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Anniversaire d'entrée dans le réseau.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\BeginningBirthdayEventRepository")
 */
class BeginningBirthdayEvent extends AbstractEvent
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_BEGINNING_BIRTHDAY;
    }
}
