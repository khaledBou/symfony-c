<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Relance destinée à un négociateur.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\MandataryReminderEventRepository")
 */
class MandataryReminderEvent extends AbstractEvent
{
    // Moyen de relance
    const WAY_SMS = 0, // SMS
          WAY_EMAIL = 1; // e-mail

    /**
     * Moyen de relance (SMS, mail, …).
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\Event\MandataryReminderEvent::WAY_SMS,
     *         App\Entity\Event\MandataryReminderEvent::WAY_EMAIL,
     *     },
     * )
     */
    private $way;

    /**
     * Contenu de la relance.
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
     * Flag pour indiquer si la relance a déjà été envoyée.
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
        return self::TYPE_MANDATARY_REMINDER;
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
