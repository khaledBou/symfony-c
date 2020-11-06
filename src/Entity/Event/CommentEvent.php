<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Commentaire.
 *
 * @ORM\Entity
 */
class CommentEvent extends AbstractEvent
{
    /**
     * Commentaire.
     *
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @Assert\NotBlank
     */
    private $comment;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_COMMENT;
    }

    /**
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return self
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
