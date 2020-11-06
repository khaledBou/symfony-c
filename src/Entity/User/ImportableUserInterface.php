<?php

namespace App\Entity\User;

/**
 * Définit le comportement d'un utilisateur pouvant être importé.
 */
interface ImportableUserInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getEmail(): ?string;

    /**
     * @param string $email
     *
     * @return self
     */
    public function setEmail(string $email): self;

    /**
     * @return string
     */
    public function getNetwork(): string;

    /**
     * @param string $network
     *
     * @return self
     */
    public function setNetwork(string $network): self;

    /**
     * @return bool
     */
    public function isImported(): ?bool;

    /**
     * @param bool $imported
     *
     * @return self
     */
    public function setImported(bool $imported): self;

    /**
     * @return bool
     */
    public function isEnabled(): ?bool;

    /**
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnabled(bool $enabled): self;
}
