<?php

namespace App\Entity\Indicator;

use App\Entity\User\Mandatary;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Indicateur.
 *
 * @ORM\Entity
 * @ORM\Table(name="indicator")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "administrative" = "AdministrativeIndicator",
 *     "boolean" = "BooleanIndicator",
 *     "training_program" = "TrainingProgramIndicator",
 * })
 *
 * @Constraints\UniqueEntity(
 *     fields={"mandatary", "key"},
 *     errorPath="key",
 *     message="This key already exists for that mandatary."
 * )
 */
abstract class AbstractIndicator implements IndicatorInterface
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
     * Négociateur.
     *
     * @var Mandatary
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Mandatary", inversedBy="indicators")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Assert\NotBlank
     */
    private $mandatary;

    /**
     * Identifiant textuel (clé).
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
    private $key;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Mandatary
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
    public function setMandatary(Mandatary $mandatary): IndicatorInterface
    {
        $this->mandatary = $mandatary;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return self
     */
    public function setKey(string $key): IndicatorInterface
    {
        $this->key = $key;

        return $this;
    }
}
