<?php

namespace App\Policies;

use App\Models\User;

/**
 * Politique d'accès pour la gestion des utilisateurs.
 *
 * Règles :
 *   viewAny / create / delete → admin uniquement
 *   view / update             → admin ou soi-même
 */
class UserPolicy
{
    /**
     * Lister tous les utilisateurs.
     */
    public function viewAny(User $auth): bool
    {
        return $auth->isAdmin();
    }

    /**
     * Créer un utilisateur.
     */
    public function create(User $auth): bool
    {
        return $auth->isAdmin();
    }

    /**
     * Voir / éditer la fiche d'un utilisateur.
     * Un admin peut voir n'importe qui ; un analyste ne peut voir que son propre profil.
     */
    public function view(User $auth, User $user): bool
    {
        return $auth->isAdmin() || $auth->id === $user->id;
    }

    /**
     * Mettre à jour nom / email / rôle / mot de passe.
     * Même règle que view.
     */
    public function update(User $auth, User $user): bool
    {
        return $auth->isAdmin() || $auth->id === $user->id;
    }

    /**
     * Supprimer un utilisateur.
     * Admin uniquement (les guards "dernier admin" et "auto-suppression"
     * restent dans le controller, la policy ne fait que vérifier le rôle).
     */
    public function delete(User $auth, User $user): bool
    {
        return $auth->isAdmin();
    }
}
