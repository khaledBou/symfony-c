<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Rappel destiné à un coach.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\CoachReminderEventRepository")
 */
class CoachReminderEvent extends AbstractEvent
{
    // Moyen de rappel
    const WAY_NOTIFICATION = 0, // notification
          WAY_EMAIL = 1; // e-mail

    /**
     * Moyen de rappel (notification, …).
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\Event\CoachReminderEvent::WAY_NOTIFICATION,
     *         App\Entity\Event\CoachReminderEvent::WAY_EMAIL,
     *     },
     * )
     */
    private $way;

    /**
     * Contenu du rappel.
     *
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Assert\Length(
     *     max = 450,
     * )
     */
    private $content;

    /**
     * Flag pour indiquer si le rappel a déjà été envoyé.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     *
     * @Assert\NotNull
     */
    private $sent = false;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_COACH_REMINDER;
    }

    /**
     * @return int
     */
    public function getWay(): ?int
    {
        return $this->way;
    }

    /**
     * @param int $way
     *
     * @return self
     */
    public function setWay(int $way): self
    {
        $this->way = $way;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSent(): ?bool
    {
        return $this->sent;
    }

    /**
     * @param bool $sent
     *
     * @return self
     */
    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }
}
