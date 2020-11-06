<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Anniversaire de naissance.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\BirthdayEventRepository")
 */
class BirthdayEvent extends AbstractEvent
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_BIRTHDAY;
    }
}
