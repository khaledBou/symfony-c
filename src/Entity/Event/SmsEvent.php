<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SMS.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\SmsEventRepository")
 */
class SmsEvent extends AbstractEvent
{
    /**
     * Contenu du SMS.
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
     * Flag pour indiquer si le SMS a déjà été envoyé.
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
        return self::TYPE_SMS;
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
