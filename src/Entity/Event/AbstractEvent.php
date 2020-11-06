<?php

namespace App\Entity\Event;

use App\Entity\User\Coach;
use App\Entity\User\Mandatary;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Événement.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\AbstractEventRepository")
 * @ORM\Table(name="event")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "activities_update" = "ActivitiesUpdateEvent",
 *     "administrative_update" = "AdministrativeUpdateEvent",
 *     "appointment" = "AppointmentEvent",
 *     "beginning" = "BeginningEvent",
 *     "beginning_birthday" = "BeginningBirthdayEvent",
 *     "birthday" = "BirthdayEvent",
 *     "call" = "CallEvent",
 *     "coach_reminder" = "CoachReminderEvent",
 *     "comment" = "CommentEvent",
 *     "compromise_shortfall" = "CompromiseShortfallEvent",
 *     "contract_update" = "ContractUpdateEvent",
 *     "emailing" = "EmailingEvent",
 *     "freshdesk_feedback" = "FreshdeskFeedbackEvent",
 *     "mandatary_reminder" = "MandataryReminderEvent",
 *     "nth_compromise" = "NthCompromiseEvent",
 *     "nth_sale" = "NthSaleEvent",
 *     "nth_trade" = "NthTradeEvent",
 *     "pack_update" = "PackUpdateEvent",
 *     "sale_shortfall" = "SaleShortfallEvent",
 *     "sms" = "SmsEvent",
 *     "termination" = "TerminationEvent",
 *     "trade_shortfall" = "TradeShortfallEvent",
 *     "training_program_mission" = "TrainingProgramMissionEvent",
 *     "fee_greater_than" = "FeeGreaterThanEvent",
 *     "nth_sale_nth_year" = "NthSaleNthYearEvent",
 * })
 */
abstract class AbstractEvent implements EventInterface
{
    // Types d'événements
    const TYPE_ACTIVITIES_UPDATE = 'activities_update',
          TYPE_ADMINISTRATIVE_UPDATE = 'administrative_update',
          TYPE_APPOINTMENT = 'appointment',
          TYPE_BEGINNING = 'beginning',
          TYPE_BEGINNING_BIRTHDAY = 'beginning_birthday',
          TYPE_BIRTHDAY = 'birthday',
          TYPE_CALL = 'call',
          TYPE_COACH_REMINDER = 'coach_reminder',
          TYPE_COMMENT = 'comment',
          TYPE_COMPROMISE_SHORTFALL = 'compromise_shortfall',
          TYPE_CONTRACT_UPDATE = 'contract_update',
          TYPE_EMAILING = 'emailing',
          TYPE_FRESHDESK_FEEDBACK = 'freshdesk_feedback',
          TYPE_MANDATARY_REMINDER = 'mandatary_reminder',
          TYPE_NTH_COMPROMISE = 'nth_compromise',
          TYPE_NTH_SALE = 'nth_sale',
          TYPE_NTH_TRADE = 'nth_trade',
          TYPE_PACK_UPDATE = 'pack_update',
          TYPE_SALE_SHORTFALL = 'sale_shortfall',
          TYPE_SMS = 'sms',
          TYPE_TERMINATION = 'termination',
          TYPE_TRADE_SHORTFALL = 'trade_shortfall',
          TYPE_TRAINING_PROGRAM_MISSION = 'training_program_mission',
          TYPE_FEE_GRATER_THAN = 'fee_greater_than',
          TYPE_NTH_SALE_NTH_YEAR = 'nth_sale_nth_year';

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
     * Négociateur.
     *
     * @var Mandatary
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Mandatary", inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotBlank
     */
    private $mandatary;

    /**
     * Coach à l'origine de l'événement.
     *
     * Une valeur nulle indique que l'événement est à l'initiative du négociateur.
     *
     * @var Coach|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Coach")
     */
    private $coach;

    /**
     * Initiateur externe.
     *
     * Permet de définir librement l'initiateur de l'événement,
     * sans désigner nécessairement un coach ou un négociateur de l'application.
     *
     * Une valeur nulle indique que l'événement est à l'initiative du coach.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $initiator;

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
     * Indique si l'événement est supprimable.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     *
     * @Assert\NotNull
     */
    private $deletable = false;

    /**
     * Indique si l'événement a fait l'objet d'un envoi de SMS automatisé au négociateur.
     *
     * Les SMS automatisés sont ceux envoyés sans aucune action du coach.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     *
     * @Assert\NotNull
     */
    private $smsSent = false;

    /**
     * Indique si l'événement a fait l'objet d'un envoi d'un SMS de rappel automatisé au négociateur.
     *
     * Les SMS automatisés sont ceux envoyés sans aucune action du coach.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     *
     * @Assert\NotNull
     */
    private $reminderSmsSent = false;

    /**
     * @return string
     */
    abstract public function getType(): string;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Mandatary|null
     */
    public function getMandatary(): ?Mandatary
    {
        return $this->mandatary;
    }

    /**
     * @param Mandatary $mandatary
     *
     * @return self
     */
    public function setMandatary(Mandatary $mandatary): EventInterface
    {
        $this->mandatary = $mandatary;

        return $this;
    }

    /**
     * @return Coach|null
     */
    public function getCoach(): ?Coach
    {
        return $this->coach;
    }

    /**
     * @param Coach|null $coach
     *
     * @return self
     */
    public function setCoach(?Coach $coach): EventInterface
    {
        $this->coach = $coach;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInitiator(): ?string
    {
        return $this->initiator;
    }

    /**
     * @param string|null $initiator
     *
     * @return self
     */
    public function setInitiator(?string $initiator): self
    {
        $this->initiator = $initiator;

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
    public function setDate(\DateTimeInterface $date): EventInterface
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeletable(): ?bool
    {
        return $this->deletable;
    }

    /**
     * @param bool $deletable
     *
     * @return self
     */
    public function setDeletable(bool $deletable): self
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSmsSent(): ?bool
    {
        return $this->smsSent;
    }

    /**
     * @param bool $smsSent
     *
     * @return self
     */
    public function setSmsSent(bool $smsSent): self
    {
        $this->smsSent = $smsSent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReminderSmsSent(): ?bool
    {
        return $this->reminderSmsSent;
    }

    /**
     * @param bool $reminderSmsSent
     *
     * @return self
     */
    public function setReminderSmsSent(bool $reminderSmsSent): self
    {
        $this->reminderSmsSent = $reminderSmsSent;

        return $this;
    }
}
