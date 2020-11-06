<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Changement d'activités.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\ActivitiesUpdateEventRepository")
 */
class ActivitiesUpdateEvent extends AbstractEvent
{
    /**
     * Anciennes activités.
     *
     * @var string[]
     *
     * @ORM\Column(type="array")
     */
    private $oldActivities = [];

    /**
     * Nouvelles activités.
     *
     * @var string[]
     *
     * @ORM\Column(type="array")
     */
    private $newActivities = [];

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_ACTIVITIES_UPDATE;
    }

    /**
     * @return string[]
     */
    public function getOldActivities(): ?array
    {
        return $this->oldActivities;
    }

    /**
     * @param string[] $oldActivities
     *
     * @return self
     */
    public function setOldActivities(array $oldActivities): self
    {
        $this->oldActivities = $oldActivities;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getNewActivities(): ?array
    {
        return $this->newActivities;
    }

    /**
     * @param string[] $newActivities
     *
     * @return self
     */
    public function setNewActivities(array $newActivities): self
    {
        $this->newActivities = $newActivities;

        return $this;
    }
}
