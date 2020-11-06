<?php

namespace App\Entity\Indicator;

use Doctrine\ORM\Mapping as ORM;

/**
 * Indicateur de situation administrative.
 *
 * @ORM\Entity
 */
class AdministrativeIndicator extends AbstractIndicator
{
    /**
     * RSAC valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $validRsac = false;

    /**
     * SIRET valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $validSiret = false;

    /**
     * RCP valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $validRcp = false;

    /**
     * Habilitation CCI valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $validCci = false;

    /**
     * Numéro de TVA valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $validTva = false;

    /**
     * Convention d'adhésion ou contrat de portage valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $validPortage = false;

    /**
     * @return bool|null
     */
    public function isValidRsac(): ?bool
    {
        return $this->validRsac;
    }

    /**
     * @param bool|null $validRsac
     *
     * @return self
     */
    public function setValidRsac(?bool $validRsac): self
    {
        $this->validRsac = $validRsac;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValidSiret(): ?bool
    {
        return $this->validSiret;
    }

    /**
     * @param bool|null $validSiret
     *
     * @return self
     */
    public function setValidSiret(?bool $validSiret): self
    {
        $this->validSiret = $validSiret;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValidRcp(): ?bool
    {
        return $this->validRcp;
    }

    /**
     * @param bool|null $validRcp
     *
     * @return self
     */
    public function setValidRcp(?bool $validRcp): self
    {
        $this->validRcp = $validRcp;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValidCci(): ?bool
    {
        return $this->validCci;
    }

    /**
     * @param bool|null $validCci
     *
     * @return self
     */
    public function setValidCci(?bool $validCci): self
    {
        $this->validCci = $validCci;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValidTva(): ?bool
    {
        return $this->validTva;
    }

    /**
     * @param bool|null $validTva
     *
     * @return self
     */
    public function setValidTva(?bool $validTva): self
    {
        $this->validTva = $validTva;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isValidPortage(): ?bool
    {
        return $this->validPortage;
    }

    /**
     * @param bool|null $validPortage
     *
     * @return self
     */
    public function setValidPortage(?bool $validPortage): self
    {
        $this->validPortage = $validPortage;

        return $this;
    }
}
