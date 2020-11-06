<?php

namespace App\Entity\User;

use App\Entity\Notification\Notification;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Utilisateur.
 *
 * @ORM\Entity(repositoryClass="App\Repository\User\AbstractUserRepository")
 * @ORM\Table(name="app_user")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "coach" = "Coach",
 *     "mandatary" = "Mandatary",
 * })
 *
 * @Constraints\UniqueEntity("email")
 */
abstract class AbstractUser implements ImportableUserInterface
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
     * Adresse e-mail.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=180, unique=true)
     *
     * @Assert\Length(
     *     max = 180,
     * )
     * @Assert\NotBlank
     */
    private $email;

    /**
     * Réseau d'appartenance de l'utilisateur (Proprietes-Privees, Immo-Reseau, …).
     *
     * @var string
     *
     * @ORM\Column(type="string", options={"default":"pp"})
     *
     * @Assert\Length(
     *     max = 255,
     * )
     * @Assert\NotBlank
     */
    private $network = 'pp';

    /**
     * Indique si l'utilisateur a été importé.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     *
     * @Assert\NotNull
     */
    private $imported = false;

    /**
     * Données brutes importées lors de l'avant dernier import.
     *
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $previouslyImportedData = [];

    /**
     * Données brutes importées lors du dernier import.
     *
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $currentlyImportedData = [];

    /**
     * Indique si l'utilisateur peut se connecter.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"1"})
     *
     * @Assert\NotNull
     */
    private $enabled = true;

    /**
     * Mot de passe Zimbra pour les utilisateurs l'utilisant,
     * pour permettra d'interagir par API avec leur agenda.
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $zimbraPassword;

    /**
     * Notifications.
     *
     * @var Collection\Notification[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Notification\Notification", mappedBy="user", orphanRemoval=true)
     * @ORM\OrderBy({"date" = "DESC"})
     */
    private $notifications;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return ImportableUserInterface
     */
    public function setEmail(string $email): ImportableUserInterface
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getNetwork(): string
    {
        return $this->network;
    }

    /**
     * @param string $network
     *
     * @return ImportableUserInterface
     */
    public function setNetwork(string $network): ImportableUserInterface
    {
        $this->network = $network;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImported(): ?bool
    {
        return $this->imported;
    }

    /**
     * @param bool $imported
     *
     * @return ImportableUserInterface
     */
    public function setImported(bool $imported): ImportableUserInterface
    {
        $this->imported = $imported;

        return $this;
    }

    /**
     * @return array
     */
    public function getPreviouslyImportedData(): ?array
    {
        return $this->previouslyImportedData;
    }

    /**
     * @param array $previouslyImportedData
     *
     * @return ImportableUserInterface
     */
    public function setPreviouslyImportedData(array $previouslyImportedData): ImportableUserInterface
    {
        $this->previouslyImportedData = $previouslyImportedData;

        return $this;
    }

    /**
     * @return array
     */
    public function getCurrentlyImportedData(): ?array
    {
        return $this->currentlyImportedData;
    }

    /**
     * @param array $currentlyImportedData
     *
     * @return ImportableUserInterface
     */
    public function setCurrentlyImportedData(array $currentlyImportedData): ImportableUserInterface
    {
        $this->currentlyImportedData = $currentlyImportedData;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return ImportableUserInterface
     */
    public function setEnabled(bool $enabled): ImportableUserInterface
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZimbraPassword(): ?string
    {
        return $this->zimbraPassword;
    }

    /**
     * @param string|null $zimbraPassword
     *
     * @return self
     */
    public function setZimbraPassword(?string $zimbraPassword): self
    {
        $this->zimbraPassword = $zimbraPassword;

        return $this;
    }

    /**
     * @return Collection|Notification[]
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    /**
     * @param Notification $notification
     *
     * @return self
     */
    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    /**
     * @param Notification $notification
     *
     * @return self
     */
    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->contains($notification)) {
            $this->notifications->removeElement($notification);
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }
}
