<?php

namespace App\Security;

use App\Document\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccountChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        $statut = strtoupper($user->getStatut());

        // Un compte EN_ATTENTE peut se connecter et consulter son espace, mais
        // ses actions sont limitées (il ne peut pas s'inscrire) tant que l'admin
        // ne l'a pas validé. Seuls les comptes désactivés sont refusés.
        if (!in_array($statut, ['ACTIF', 'EN_ATTENTE'], true)) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n\'est pas actif. Contactez un administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
