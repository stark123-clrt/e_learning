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

        if ('EN_ATTENTE' === $statut) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est en attente de validation par un administrateur.'
            );
        }

        if ('ACTIF' !== $statut) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n\'est pas actif. Contactez un administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
