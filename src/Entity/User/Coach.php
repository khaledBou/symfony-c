<?php

namespace App\Entity\User;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Coach.
 *
 * @ORM\Entity(repositoryClass="App\Repository\User\CoachRepository")
 */
class Coach extends AbstractUser implements UserInterface
{
    /**
     * Rôles.
     *
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * Mot de passe hashé.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $password;

    /**
     * Identifiant dans Keycloak,
     * au cas où l'utilisateur ait utilisé ce mode de connexion.
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $keycloakId;

    /**
     * Prénom.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     * @Assert\NotBlank
     */
    private $firstName;

    /**
     * Nom.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     * @Assert\NotBlank
     */
    private $lastName;

    /**
     * Téléphone.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     * @Assert\NotBlank
     */
    private $phone;

    /**
     * Nom du fichier image faisant office d'avatar.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $avatar;

    /**
     * Négociateurs coachés.
     *
     * @var Collection|Mandatary[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\User\Mandatary", mappedBy="coach")
     * @ORM\OrderBy({"lastName" = "ASC", "firstName" = "ASC"})
     */
    private $mandataries;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->notifications = new ArrayCollection();
        $this->mandataries = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf("%s %s", $this->firstName, $this->lastName);
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     *
     * @return string
     */
    public function getUsername(): string
    {
        return (string) $this->getEmail();
    }

    /**
     * @see UserInterface
     *
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     *
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKeycloakId(): ?string
    {
        return $this->keycloakId;
    }

    /**
     * @param string $keycloakId
     *
     * @return self
     */
    public function setKeycloakId(string $keycloakId): self
    {
        $this->keycloakId = $keycloakId;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return self
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return self
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     *
     * @return self
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * @param string|null $avatar
     *
     * @return self
     */
    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection|Mandatary[]
     */
    public function getMandataries(): Collection
    {
        return $this->mandataries;
    }

    /**
     * @param Mandatary $mandatary
     *
     * @return self
     */
    public function addMandatary(Mandatary $mandatary): self
    {
        if (!$this->mandataries->contains($mandatary)) {
            $this->mandataries[] = $mandatary;
            $mandatary->setCoach($this);
        }

        return $this;
    }

    /**
     * @param Mandatary $mandatary
     *
     * @return self
     */
    public function removeMandatary(Mandatary $mandatary): self
    {
        if ($this->mandataries->contains($mandatary)) {
            $this->mandataries->removeElement($mandatary);
            // set the owning side to null (unless already changed)
            if ($mandatary->getCoach() === $this) {
                $mandatary->setCoach(null);
            }
        }

        return $this;
    }
}
