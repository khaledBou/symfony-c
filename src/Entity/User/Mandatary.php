<?php

namespace App\Entity\User;

use App\Entity\Indicator\IndicatorInterface;
use App\Entity\Event\EventInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Négociateur.
 *
 * @ORM\Entity(repositoryClass="App\Repository\User\MandataryRepository")
 *
 * @Constraints\UniqueEntity(
 *     fields={"email", "slug"}
 * )
 */
class Mandatary extends AbstractUser
{
    // Contrats
    const CONTRACT_MICRO_ENTREPRENEUR = 0, // micro-entrepreneur (agent commercial)
          CONTRACT_AGENT_COMMERCIAL = 1, // agent commercial (agent commercial TNS)
          CONTRACT_PORTAGE_SALARIAL = 2, // portage salarial
          CONTRACT_CONCESSIONNAIRE = 3; // concessionnaire

    // Besoin d'accompagnement
    const CARE_LEVEL_LOW = 0, // léger
          CARE_LEVEL_MEDIUM = 1, // modéré
          CARE_LEVEL_HIGH = 2; // intensif

    // État de suivi
    const SUPPORT_STATUS_BAD = 0, // mauvais
          SUPPORT_STATUS_FAIR = 1, // moyen
          SUPPORT_STATUS_GOOD = 2; // bon

    // Potentiel commercial
    const POTENTIAL_VERY_LOW = 0, // très faible
          POTENTIAL_LOW = 1, // faible
          POTENTIAL_MEDIUM = 2, // moyen
          POTENTIAL_HIGH = 3, // haut
          POTENTIAL_VERY_HIGH = 4; // très haut

    // Indique les pièces administratives à fournir selon le type de contrat (voir ci-dessus)
    const ADMINISTRATIVE_DOCUMENTS = [
        0 => ['rsac', 'siret', 'rcp', 'cci'],
        1 => ['rsac', 'siret', 'rcp', 'cci', 'tva'],
        2 => ['cci', 'portage'],
        3 => ['rsac', 'siret', 'rcp', 'cci', 'tva'],
    ];

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
     * Slug.
     *
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     * @Assert\NotBlank
     */
    private $slug;

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
     * Civilité.
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
    private $civility;

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
     * Date de naissance.
     *
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="date")
     *
     * @Assert\NotBlank
     */
    private $birthDate;

    /**
     * Date d'entrée dans le réseau.
     *
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="date")
     *
     * @Assert\NotBlank
     */
    private $beginDate;

    /**
     * Date de résiliation éventuelle.
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $terminationDate;

    /**
     * Code postal de la ville de secteur.
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
    private $zipCode;

    /**
     * Nom de la ville de secteur.
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
    private $city;

    /**
     * Barème.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $bareme;

    /**
     * Contrat éventuel : micro-entrepreneur (agent commercial), agent commercial (agent commercial TNS) ou portage salarial.
     *
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\User\Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
     *         App\Entity\User\Mandatary::CONTRACT_AGENT_COMMERCIAL,
     *         App\Entity\User\Mandatary::CONTRACT_PORTAGE_SALARIAL,
     *         App\Entity\User\Mandatary::CONTRACT_CONCESSIONNAIRE,
     *     },
     * )
     */
    private $contract;

    /**
     * Activités (résidentiel, business, prestige, location).
     *
     * @var string[]
     *
     * @ORM\Column(type="array")
     */
    private $activities = [];

    /**
     * Pack éventuel.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $pack;

    /**
     * Besoin d'accompagnement du négociateur.
     *
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\User\Mandatary::CARE_LEVEL_LOW,
     *         App\Entity\User\Mandatary::CARE_LEVEL_MEDIUM,
     *         App\Entity\User\Mandatary::CARE_LEVEL_HIGH,
     *     },
     * )
     */
    private $careLevel;

    /**
     * État de suivi par le coach.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\User\Mandatary::SUPPORT_STATUS_BAD,
     *         App\Entity\User\Mandatary::SUPPORT_STATUS_FAIR,
     *         App\Entity\User\Mandatary::SUPPORT_STATUS_GOOD,
     *     },
     * )
     */
    private $supportStatus = self::SUPPORT_STATUS_BAD;

    /**
     * Potentiel commercial.
     *
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\User\Mandatary::POTENTIAL_VERY_LOW,
     *         App\Entity\User\Mandatary::POTENTIAL_LOW,
     *         App\Entity\User\Mandatary::POTENTIAL_MEDIUM,
     *         App\Entity\User\Mandatary::POTENTIAL_HIGH,
     *         App\Entity\User\Mandatary::POTENTIAL_VERY_HIGH,
     *     },
     * )
     */
    private $potential;

    /**
     * Confirmé.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $skilled;

    /**
     * Tempérament de développeur.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $couldBeDeveloper;

    /**
     * Tempérament d'animateur.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $couldBeAnimator;

    /**
     * Tempérament de formateur.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $couldBeTrainer;

    /**
     * URL fiche CRM.
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
    private $crmUrl;

    /**
     * URL mini-site.
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
    private $websiteUrl;

    /**
     * Identifiant Freshdesk du négociateur.
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $freshdeskUserId;

    /**
     * Animateur éventuel.
     *
     * @var Mandatary|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Mandatary", inversedBy="animatedMandataries", cascade={"persist"})
     */
    private $animator;

    /**
     * Animés éventuels.
     *
     * @var Collection|Mandatary[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\User\Mandatary", mappedBy="animator")
     * @ORM\OrderBy({"lastName" = "ASC", "firstName" = "ASC"})
     */
    private $animatedMandataries;

    /**
     * Tuteur éventuel.
     *
     * @var Mandatary|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Mandatary", inversedBy="tutoredMandataries", cascade={"persist"})
     */
    private $tutor;

    /**
     * Filleuls éventuels.
     *
     * @var Collection|Mandatary[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\User\Mandatary", mappedBy="tutor")
     * @ORM\OrderBy({"lastName" = "ASC", "firstName" = "ASC"})
     */
    private $tutoredMandataries;

    /**
     * Date de début du tutorat.
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tutoringStartDate;

    /**
     * Date de fin du tutorat.
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tutoringEndDate;

    /**
     * Coach.
     *
     * @var Coach|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Coach", inversedBy="mandataries")
     */
    private $coach;

    /**
     * Historique du chiffre d'affaires.
     *
     * La clé 0 correspondant au chiffre d'affaires de l'année N, la clé 1 pour N-1, etc…
     *
     * @var int[]
     *
     * @ORM\Column(type="array")
     */
    private $salesRevenueHistory;

    /**
     * Chiffre d'affaires global.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $salesRevenue;

    /**
     * Nombre de contacts avec le coach.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $contactsCount = 0;

    /**
     * Nombre de connexions au CRM.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $crmLoginsCount = 0;

    /**
     * Date de la dernière connexion au CRM.
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastCrmLoginDate;

    /**
     * Dates des mandats.
     *
     * @var \DateTimeInterface[]
     *
     * @ORM\Column(type="array")
     */
    private $tradesDates = [];

    /**
     * Nombre de mandats en cours.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $tradesCount = 0;

    /**
     * Nombre de mandats exclusifs en cours.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $exclusiveTradesCount = 0;

    /**
     * Dates des compromis (signés).
     *
     * @var \DateTimeInterface[]
     *
     * @ORM\Column(type="array")
     */
    private $compromisesDates = [];

    /**
     * Nombre de compromis signés.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    private $compromisesCount = 0;

    /**
     * Dates des ventes.
     *
     * @var \DateTimeInterface[]
     *
     * @ORM\Column(type="array")
     */
    private $salesDates = [];

    /**
     * Nombre de ventes.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     *
     * @Assert\NotBlank
     */
    private $salesCount = 0;

    /**
     * Autonome en publication.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $autonomePublication;

    /**
     * Indicateurs.
     *
     * @var IndicatorInterface[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Indicator\AbstractIndicator", mappedBy="mandatary", orphanRemoval=true, cascade={"persist"})
     */
    private $indicators;

    /**
     * Événements.
     *
     * @var EventInterface[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Event\AbstractEvent", mappedBy="mandatary", orphanRemoval=true, cascade={"persist"})
     */
    private $events;

    /**
     * Indique si le négociateur ne rentre plus de mandats.
     *
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull
     */
    private $tradeShortfall = false;

    /**
     * Indique si le négociateur ne signe plus de compromis.
     *
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull
     */
    private $compromiseShortfall = false;

    /**
     * Indique si le négociateur ne fait plus de ventes.
     *
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull
     */
    private $saleShortfall = false;

    /**
     * Indique si le négociateur est suspendu ou résilié.
     *
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull
     */
    private $suspendedOrResigned = false;

    /**
     * Honoraire hors taxe
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $feeExclTax = [];

    /**
     * @return void
     */
    public function __construct()
    {
        $this->animatedMandataries = new ArrayCollection();
        $this->tutoredMandataries = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->indicators = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf("%s %s", $this->firstName, $this->lastName);
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
     * @param string $slug
     *
     * @return self
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): ?string
    {
        return $this->slug;
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
     * @return string
     */
    public function getCivility(): ?string
    {
        return $this->civility;
    }

    /**
     * @param string $civility
     *
     * @return self
     */
    public function setCivility(string $civility): self
    {
        $this->civility = $civility;

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
     * @return \DateTimeInterface
     */
    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    /**
     * @param \DateTimeInterface $birthDate
     *
     * @return self
     */
    public function setBirthDate(\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getBeginDate(): ?\DateTimeInterface
    {
        return $this->beginDate;
    }

    /**
     * @param \DateTimeInterface $beginDate
     *
     * @return self
     */
    public function setBeginDate(\DateTimeInterface $beginDate): self
    {
        $this->beginDate = $beginDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->terminationDate;
    }

    /**
     * @param \DateTimeInterface|null $terminationDate
     *
     * @return self
     */
    public function setTerminationDate(?\DateTimeInterface $terminationDate): self
    {
        $this->terminationDate = $terminationDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     *
     * @return self
     */
    public function setZipCode(string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return self
     */
    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getBareme(): ?string
    {
        return $this->bareme;
    }

    /**
     * @param string $bareme
     *
     * @return self
     */
    public function setBareme(string $bareme): self
    {
        $this->bareme = $bareme;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getContract(): ?int
    {
        return $this->contract;
    }

    /**
     * @param int|null $contract
     *
     * @return self
     */
    public function setContract(?int $contract): self
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getActivities(): ?array
    {
        return $this->activities;
    }

    /**
     * @param string[] $activities
     *
     * @return self
     */
    public function setActivities(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPack(): ?string
    {
        return $this->pack;
    }

    /**
     * @param string|null $pack
     *
     * @return self
     */
    public function setPack(?string $pack): self
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCareLevel(): ?int
    {
        return $this->careLevel;
    }

    /**
     * @param int|null $careLevel
     *
     * @return self
     */
    public function setCareLevel(?int $careLevel): self
    {
        $this->careLevel = $careLevel;

        return $this;
    }

    /**
     * @return int
     */
    public function getSupportStatus(): int
    {
        return $this->supportStatus;
    }

    /**
     * @param int $supportStatus
     *
     * @return self
     */
    public function setSupportStatus(int $supportStatus): self
    {
        $this->supportStatus = $supportStatus;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPotential(): ?int
    {
        return $this->potential;
    }

    /**
     * @param int|null $potential
     *
     * @return self
     */
    public function setPotential(?int $potential): self
    {
        $this->potential = $potential;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isSkilled(): ?bool
    {
        return $this->skilled;
    }

    /**
     * @param bool|null $skilled
     *
     * @return self
     */
    public function setSkilled(bool $skilled): self
    {
        $this->skilled = $skilled;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCouldBeDeveloper(): ?bool
    {
        return $this->couldBeDeveloper;
    }

    /**
     * @param bool|null $couldBeDeveloper
     *
     * @return self
     */
    public function setCouldBeDeveloper(?bool $couldBeDeveloper): self
    {
        $this->couldBeDeveloper = $couldBeDeveloper;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCouldBeAnimator(): ?bool
    {
        return $this->couldBeAnimator;
    }

    /**
     * @param bool|null $couldBeAnimator
     *
     * @return self
     */
    public function setCouldBeAnimator(?bool $couldBeAnimator): self
    {
        $this->couldBeAnimator = $couldBeAnimator;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCouldBeTrainer(): ?bool
    {
        return $this->couldBeTrainer;
    }

    /**
     * @param bool|null $couldBeTrainer
     *
     * @return self
     */
    public function setCouldBeTrainer(?bool $couldBeTrainer): self
    {
        $this->couldBeTrainer = $couldBeTrainer;

        return $this;
    }

    /**
     * @return string
     */
    public function getCrmUrl(): ?string
    {
        return $this->crmUrl;
    }

    /**
     * @param string $crmUrl
     *
     * @return self
     */
    public function setCrmUrl(string $crmUrl): self
    {
        $this->crmUrl = $crmUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    /**
     * @param string $websiteUrl
     *
     * @return self
     */
    public function setWebsiteUrl(string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFreshdeskUserId(): ?string
    {
        return $this->freshdeskUserId;
    }

    /**
     * @param string|null $freshdeskUserId
     *
     * @return self
     */
    public function setFreshdeskUserId(?string $freshdeskUserId): self
    {
        $this->freshdeskUserId = $freshdeskUserId;

        return $this;
    }

    /**
     * @return Mandatary|null
     */
    public function getAnimator(): ?Mandatary
    {
        return $this->animator;
    }

    /**
     * @param Mandatary $animator
     *
     * @return self
     */
    public function setAnimator(?Mandatary $animator): self
    {
        $this->animator = $animator;

        return $this;
    }

    /**
     * @return Collection|Mandatary[]
     */
    public function getAnimatedMandataries(): Collection
    {
        return $this->animatedMandataries;
    }

    /**
     * @return Mandatary|null
     */
    public function getTutor(): ?self
    {
        return $this->tutor;
    }

    /**
     * @param Mandatary|null $tutor
     *
     * @return self
     */
    public function setTutor(?self $tutor): self
    {
        $this->tutor = $tutor;

        return $this;
    }

    /**
     * @return Collection|Mandatary[]
     */
    public function getTutoredMandataries(): Collection
    {
        return $this->tutoredMandataries;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getTutoringStartDate(): ?\DateTimeInterface
    {
        return $this->tutoringStartDate;
    }

    /**
     * @param \DateTimeInterface|null $tutoringStartDate
     *
     * @return self
     */
    public function setTutoringStartDate(?\DateTimeInterface $tutoringStartDate): self
    {
        $this->tutoringStartDate = $tutoringStartDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getTutoringEndDate(): ?\DateTimeInterface
    {
        return $this->tutoringEndDate;
    }

    /**
     * @param \DateTimeInterface|null $tutoringEndDate
     *
     * @return self
     */
    public function setTutoringEndDate(?\DateTimeInterface $tutoringEndDate): self
    {
        $this->tutoringEndDate = $tutoringEndDate;

        return $this;
    }

    /**
     * @return Coach
     */
    public function getCoach(): ?Coach
    {
        return $this->coach;
    }

    /**
     * @param Coach $coach
     *
     * @return self
     */
    public function setCoach(?Coach $coach): self
    {
        $this->coach = $coach;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getSalesRevenueHistory(): ?array
    {
        return $this->salesRevenueHistory;
    }

    /**
     * @param int[] $salesRevenueHistory
     *
     * @return self
     */
    public function setSalesRevenueHistory(array $salesRevenueHistory): self
    {
        $this->salesRevenueHistory = $salesRevenueHistory;

        return $this;
    }

    /**
     * @return int
     */
    public function getSalesRevenue(): ?int
    {
        return $this->salesRevenue;
    }

    /**
     * @param int $salesRevenue
     *
     * @return self
     */
    public function setSalesRevenue(int $salesRevenue): self
    {
        $this->salesRevenue = $salesRevenue;

        return $this;
    }

    /**
     * @return int
     */
    public function getContactsCount(): ?int
    {
        return $this->contactsCount;
    }

    /**
     * @param int $contactsCount
     *
     * @return self
     */
    public function setContactsCount(int $contactsCount): self
    {
        $this->contactsCount = $contactsCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getCrmLoginsCount(): ?int
    {
        return $this->crmLoginsCount;
    }

    /**
     * @param int $crmLoginsCount
     *
     * @return self
     */
    public function setCrmLoginsCount(int $crmLoginsCount): self
    {
        $this->crmLoginsCount = $crmLoginsCount;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastCrmLoginDate(): ?\DateTimeInterface
    {
        return $this->lastCrmLoginDate;
    }

    /**
     * @param \DateTimeInterface|null $lastCrmLoginDate
     *
     * @return self
     */
    public function setLastCrmLoginDate(?\DateTimeInterface $lastCrmLoginDate): self
    {
        $this->lastCrmLoginDate = $lastCrmLoginDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface[]
     */
    public function getTradesDates(): ?array
    {
        return $this->tradesDates;
    }

    /**
     * @param \DateTimeInterface[] $tradesDates
     *
     * @return self
     */
    public function setTradesDates(array $tradesDates): self
    {
        $this->tradesDates = $tradesDates;

        return $this;
    }

    /**
     * @return int
     */
    public function getTradesCount(): ?int
    {
        return $this->tradesCount;
    }

    /**
     * @param int $tradesCount
     *
     * @return self
     */
    public function setTradesCount(int $tradesCount): self
    {
        $this->tradesCount = $tradesCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getExclusiveTradesCount(): ?int
    {
        return $this->exclusiveTradesCount;
    }

    /**
     * @param int $exclusiveTradesCount
     *
     * @return self
     */
    public function setExclusiveTradesCount(int $exclusiveTradesCount): self
    {
        $this->exclusiveTradesCount = $exclusiveTradesCount;

        return $this;
    }

    /**
     * @return \DateTimeInterface[]
     */
    public function getCompromisesDates(): ?array
    {
        return $this->compromisesDates;
    }

    /**
     * @param \DateTimeInterface[] $compromisesDates
     *
     * @return self
     */
    public function setCompromisesDates(array $compromisesDates): self
    {
        $this->compromisesDates = $compromisesDates;

        return $this;
    }

    /**
     * @return int
     */
    public function getCompromisesCount(): ?int
    {
        return $this->compromisesCount;
    }

    /**
     * @param int $compromisesCount
     *
     * @return self
     */
    public function setCompromisesCount(int $compromisesCount): self
    {
        $this->compromisesCount = $compromisesCount;

        return $this;
    }

    /**
     * @return \DateTimeInterface[]
     */
    public function getSalesDates(): ?array
    {
        return $this->salesDates;
    }

    /**
     * @param \DateTimeInterface[] $salesDates
     *
     * @return self
     */
    public function setSalesDates(array $salesDates): self
    {
        $this->salesDates = $salesDates;

        return $this;
    }

    /**
     * @return float
     */
    public function getSalesCount(): ?float
    {
        return $this->salesCount;
    }

    /**
     * @param float $salesCount
     *
     * @return self
     */
    public function setSalesCount(float $salesCount): self
    {
        $this->salesCount = $salesCount;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isAutonomePublication(): ?bool
    {
        return $this->autonomePublication;
    }

    /**
     * @param bool|null $autonomePublication
     *
     * @return self
     */
    public function setAutonomePublication(?bool $autonomePublication): self
    {
        $this->autonomePublication = $autonomePublication;

        return $this;
    }

    /**
     * @return Collection|IndicatorInterface[]
     */
    public function getIndicators(): Collection
    {
        return $this->indicators;
    }

    /**
     * @param IndicatorInterface $indicator
     *
     * @return self
     */
    public function addIndicator(IndicatorInterface $indicator): self
    {
        if (!$this->indicators->contains($indicator)) {
            $this->indicators[] = $indicator;
            $indicator->setMandatary($this);
        }

        return $this;
    }

    /**
     * @param IndicatorInterface $indicator
     *
     * @return self
     */
    public function removeIndicator(IndicatorInterface $indicator): self
    {
        if ($this->indicators->contains($indicator)) {
            $this->indicators->removeElement($indicator);
        }

        return $this;
    }

    /**
     * @return Collection|EventInterface[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /**
     * @param EventInterface $event
     *
     * @return self
     */
    public function addEvent(EventInterface $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setMandatary($this);
        }

        return $this;
    }

    /**
     * @param EventInterface $event
     *
     * @return self
     */
    public function removeEvent(EventInterface $event): self
    {
        if ($this->events->contains($event)) {
            $this->events->removeElement($event);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasTradeShortfall(): ?bool
    {
        return $this->tradeShortfall;
    }

    /**
     * @param bool $tradeShortfall
     *
     * @return self
     */
    public function setTradeShortfall(bool $tradeShortfall): self
    {
        $this->tradeShortfall = $tradeShortfall;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCompromiseShortfall(): ?bool
    {
        return $this->compromiseShortfall;
    }

    /**
     * @param bool $compromiseShortfall
     *
     * @return self
     */
    public function setCompromiseShortfall(bool $compromiseShortfall): self
    {
        $this->compromiseShortfall = $compromiseShortfall;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSaleShortfall(): ?bool
    {
        return $this->saleShortfall;
    }

    /**
     * @param bool $saleShortfall
     *
     * @return self
     */
    public function setSaleShortfall(bool $saleShortfall): self
    {
        $this->saleShortfall = $saleShortfall;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuspendedOrResigned(): ?bool
    {
        return $this->suspendedOrResigned;
    }

    /**
     * @param bool $suspendedOrResigned
     *
     * @return self
     */
    public function setSuspendedOrResigned(bool $suspendedOrResigned): self
    {
        $this->suspendedOrResigned = $suspendedOrResigned;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getFeeExclTax(): ?array
    {
        return $this->feeExclTax;
    }

    /**
     * @param array|null $feeExclTax
     *
     * @return self
     */
    public function setFeeExclTax(?array $feeExclTax): self
    {
        $this->feeExclTax = $feeExclTax;

        return $this;
    }
}
