<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Évaluation Freshdesk.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\FreshdeskFeedbackEventRepository")
 */
class FreshdeskFeedbackEvent extends AbstractEvent
{
    // Niveaux de satisfaction
    const RATING_EXTREMELY_HAPPY = 103,
          RATING_VERY_HAPPY = 102,
          RATING_HAPPY = 101,
          RATING_NEUTRAL = 100,
          RATING_UNHAPPY = -101,
          RATING_VERY_UNHAPPY = -102,
          RATING_EXTREMELY_UNHAPPY = -103;

    /**
     * Niveau de satifaction.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\Choice(
     *     choices = {
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_EXTREMELY_HAPPY,
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_VERY_HAPPY,
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_HAPPY,
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_NEUTRAL,
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_UNHAPPY,
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_VERY_UNHAPPY,
     *         App\Entity\Event\FreshdeskFeedbackEvent::RATING_EXTREMELY_UNHAPPY,
     *     },
     * )
     */
    private $rating;

    /**
     * Commentaire.
     *
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * Identifiant du ticket Freshdesk.
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Length(
     *     max = 255,
     * )
     */
    private $ticketId;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_FRESHDESK_FEEDBACK;
    }

    /**
     * @return int
     */
    public function getRating(): ?int
    {
        return $this->rating;
    }

    /**
     * @param int $rating
     *
     * @return self
     */
    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     *
     * @return self
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTicketId(): ?string
    {
        return $this->ticketId;
    }

    /**
     * @param string|null $ticketId
     *
     * @return self
     */
    public function setTicketId(?string $ticketId): self
    {
        $this->ticketId = $ticketId;

        return $this;
    }
}
