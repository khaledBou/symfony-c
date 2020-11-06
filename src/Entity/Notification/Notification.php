<?php

namespace App\Entity\Notification;

use App\Entity\User\ImportableUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Notification.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Notification\NotificationRepository")
 */
class Notification
{
    /**
     * Identifiant.
     *
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Utilisateur.
     *
     * @var ImportableUserInterface
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\AbstractUser", inversedBy="notifications")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotBlank
     */
    private $user;

    /**
     * Message.
     *
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Assert\NotBlank
     */
    private $message;

    /**
     * URL du contenu désigné par la notification.
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $url;

    /**
     * Date.
     *
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     *
     * @Assert\NotBlank
     */
    private $date;

    /**
     * Notification marquée comme lue.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     *
     * @Assert\NotNull
     */
    private $read = false;

    /**
     * Utilisateur à l'origine de la notification.
     *
     * @var ImportableUserInterface|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\AbstractUser")
     */
    private $initiator;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ImportableUserInterface
     */
    public function getUser(): ?ImportableUserInterface
    {
        return $this->user;
    }

    /**
     * @param ImportableUserInterface $user
     *
     * @return self
     */
    public function setUser(ImportableUserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface $date
     *
     * @return self
     */
    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRead(): ?bool
    {
        return $this->read;
    }

    /**
     * @param bool $read
     *
     * @return self
     */
    public function setRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    /**
     * @return ImportableUserInterface|null
     */
    public function getInitiator(): ?ImportableUserInterface
    {
        return $this->initiator;
    }

    /**
     * @param ImportableUserInterface|null $initiator
     *
     * @return self
     */
    public function setInitiator(?ImportableUserInterface $initiator): self
    {
        $this->initiator = $initiator;

        return $this;
    }
}
