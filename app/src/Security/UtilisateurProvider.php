<?php

namespace App\Security;

use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UtilisateurProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private readonly DocumentManager $documentManager)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $utilisateur = $this->documentManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['email' => mb_strtolower(trim($identifier))]);

        if (!$utilisateur instanceof Utilisateur) {
            throw new UserNotFoundException(sprintf('Aucun utilisateur trouvé pour l\'email "%s".', $identifier));
        }

        return $utilisateur;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Utilisateur::class === $class || is_subclass_of($class, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        $user->setMotDePasse($newHashedPassword);
        $this->documentManager->flush();
    }
}
