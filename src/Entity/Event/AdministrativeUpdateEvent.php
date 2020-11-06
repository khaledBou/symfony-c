<?php

namespace App\Entity\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * Changement administratif.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Event\AdministrativeUpdateEventRepository")
 */
class AdministrativeUpdateEvent extends AbstractEvent
{
    /**
     * Ancien RSAC valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $oldValidRsac;

    /**
     * Nouveau RSAC valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $newValidRsac;

    /**
     * Ancien SIRET valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $oldValidSiret;

    /**
     * Nouveau SIRET valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $newValidSiret;

    /**
     * Ancien RCP valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $oldValidRcp;

    /**
     * Nouveau RCP valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $newValidRcp;

    /**
     * Ancienne habilitation CCI valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $oldValidCci;

    /**
     * Nouvelle habilitation CCI valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $newValidCci;

    /**
     * Ancien numéro de TVA valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $oldValidTva;

    /**
     * Nouveau numéro de TVA valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $newValidTva;

    /**
     * Ancienne convention d'adhésion ou ancien contrat de portage valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $oldValidPortage;

    /**
     * Nouvelle convention d'adhésion ou nouveau contrat de portage valide.
     *
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $newValidPortage;

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_ADMINISTRATIVE_UPDATE;
    }

    /**
     * @return bool|null
     */
    public function getOldValidRsac(): ?bool
    {
        return $this->oldValidRsac;
    }

    /**
     * @param bool|null $oldValidRsac
     *
     * @return self
     */
    public function setOldValidRsac(?bool $oldValidRsac): self
    {
        $this->oldValidRsac = $oldValidRsac;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewValidRsac(): ?bool
    {
        return $this->newValidRsac;
    }

    /**
     * @param bool|null $newValidRsac
     *
     * @return self
     */
    public function setNewValidRsac(?bool $newValidRsac): self
    {
        $this->newValidRsac = $newValidRsac;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getOldValidSiret(): ?bool
    {
        return $this->oldValidSiret;
    }

    /**
     * @param bool|null $oldValidSiret
     *
     * @return self
     */
    public function setOldValidSiret(?bool $oldValidSiret): self
    {
        $this->oldValidSiret = $oldValidSiret;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewValidSiret(): ?bool
    {
        return $this->newValidSiret;
    }

    /**
     * @param bool|null $newValidSiret
     *
     * @return self
     */
    public function setNewValidSiret(?bool $newValidSiret): self
    {
        $this->newValidSiret = $newValidSiret;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getOldValidRcp(): ?bool
    {
        return $this->oldValidRcp;
    }

    /**
     * @param bool|null $oldValidRcp
     *
     * @return self
     */
    public function setOldValidRcp(?bool $oldValidRcp): self
    {
        $this->oldValidRcp = $oldValidRcp;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewValidRcp(): ?bool
    {
        return $this->newValidRcp;
    }

    /**
     * @param bool|null $newValidRcp
     *
     * @return self
     */
    public function setNewValidRcp(?bool $newValidRcp): self
    {
        $this->newValidRcp = $newValidRcp;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getOldValidCci(): ?bool
    {
        return $this->oldValidCci;
    }

    /**
     * @param bool|null $oldValidCci
     *
     * @return self
     */
    public function setOldValidCci(?bool $oldValidCci): self
    {
        $this->oldValidCci = $oldValidCci;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewValidCci(): ?bool
    {
        return $this->newValidCci;
    }

    /**
     * @param bool|null $newValidCci
     *
     * @return self
     */
    public function setNewValidCci(?bool $newValidCci): self
    {
        $this->newValidCci = $newValidCci;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getOldValidTva(): ?bool
    {
        return $this->oldValidTva;
    }

    /**
     * @param bool|null $oldValidTva
     *
     * @return self
     */
    public function setOldValidTva(?bool $oldValidTva): self
    {
        $this->oldValidTva = $oldValidTva;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewValidTva(): ?bool
    {
        return $this->newValidTva;
    }

    /**
     * @param bool|null $newValidTva
     *
     * @return self
     */
    public function setNewValidTva(?bool $newValidTva): self
    {
        $this->newValidTva = $newValidTva;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getOldValidPortage(): ?bool
    {
        return $this->oldValidPortage;
    }

    /**
     * @param bool|null $oldValidPortage
     *
     * @return self
     */
    public function setOldValidPortage(?bool $oldValidPortage): self
    {
        $this->oldValidPortage = $oldValidPortage;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewValidPortage(): ?bool
    {
        return $this->newValidPortage;
    }

    /**
     * @param bool|null $newValidPortage
     *
     * @return self
     */
    public function setNewValidPortage(?bool $newValidPortage): self
    {
        $this->newValidPortage = $newValidPortage;

        return $this;
    }
}
