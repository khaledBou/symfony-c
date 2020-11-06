<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Rendez-vous.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\AppointmentEventRepository")
 */
class AppointmentEvent extends AbstractEvent
{
    /**
     * Objet.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $subject;

    /**
     * Description.
     *
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * Durée.
     *
     * @var \DateInterval
     *
     * @ORM\Column(type="dateinterval")
     *
     * @Assert\NotBlank
     */
    private $duration;

    /**
     * Identifiant de l'événement dans l'agenda Google Calendar,
     * pour les utilisateurs utilisant cet agenda.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $googleCalendarId;

    /**
     * Identifiant de l'événement dans l'agenda Zimbra,
     * pour les utilisateurs utilisant cet agenda.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $zimbraCalendarId;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_APPOINTMENT;
    }

    /**
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return self
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \DateInterval
     */
    public function getDuration(): ?\DateInterval
    {
        return $this->duration;
    }

    /**
     * @param \DateInterval $duration
     *
     * @return self
     */
    public function setDuration(\DateInterval $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGoogleCalendarId(): ?string
    {
        return $this->googleCalendarId;
    }

    /**
     * @param string|null $googleCalendarId
     *
     * @return self
     */
    public function setGoogleCalendarId(?string $googleCalendarId): self
    {
        $this->googleCalendarId = $googleCalendarId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZimbraCalendarId(): ?string
    {
        return $this->zimbraCalendarId;
    }

    /**
     * @param string|null $zimbraCalendarId
     *
     * @return self
     */
    public function setZimbraCalendarId(?string $zimbraCalendarId): self
    {
        $this->zimbraCalendarId = $zimbraCalendarId;

        return $this;
    }
}
