<?php

namespace App\Command\ImportUsers;

use App\Entity\User\ImportableUserInterface;

/**
 * Définit le comportement des commandes d'import des utilisateurs.
 */
interface ImportUsersCommandInterface
{
    /**
     * Récupère l'entité utilisateur si elle existe déjà.
     *
     * @param array|\stdClass $userData L'utilisateur en provenance de la source externe
     *
     * @return ImportableUserInterface|null
     */
    public function getUserFromRepository($userData): ?ImportableUserInterface;

    /**
     * Récupère les utilisateurs depuis une source externe (API, fichier, …).
     *
     * @return array[]|\stdClass[]
     */
    public function getUsersData(): array;

    /**
     * Remplit les attributs de l'entité utilisateur.
     *
     * @param ImportableUserInterface $user     L'entité utilisateur
     * @param array|\stdClass         $userData L'utilisateur en provenance de la source externe
     *
     * @return ImportableUserInterface
     */
    public function setUserAttributes(ImportableUserInterface $user, $userData): ImportableUserInterface;
}
