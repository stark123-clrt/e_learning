<?php

namespace App\Service;

use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class InvitationService
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
        #[Autowire('%env(MAILER_FROM)%')] private readonly string $mailerFrom,
    ) {
    }

    /**
     * Crée un compte utilisateur (actif car invité par l'admin), lié à un profil,
     * puis envoie un email d'invitation avec un lien pour définir le mot de passe.
     */
    public function inviter(string $email, string $role, ?string $profilId, string $nomComplet): Utilisateur
    {
        $email = mb_strtolower(trim($email));

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($email);
        $utilisateur->setRole(strtoupper($role));
        $utilisateur->setStatut('ACTIF');
        $utilisateur->setProfilId($profilId);
        // Mot de passe aléatoire : remplacé par l'utilisateur via le lien d'invitation.
        $utilisateur->setMotDePasse($this->passwordHasher->hashPassword($utilisateur, bin2hex(random_bytes(16))));

        $token = bin2hex(random_bytes(32));
        $utilisateur->setResetToken($token);
        $utilisateur->setResetTokenExpiresAt(new \DateTimeImmutable('+7 days'));

        $this->documentManager->persist($utilisateur);
        $this->documentManager->flush();

        $lien = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $mail = (new Email())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('Votre accès à la plateforme e-learning')
            ->html($this->twig->render('emails/invitation.html.twig', [
                'nomComplet' => $nomComplet,
                'roleLabel' => $this->roleLabel($role),
                'lien' => $lien,
            ]));

        $this->mailer->send($mail);

        return $utilisateur;
    }

    public function emailDejaUtilise(string $email): bool
    {
        $email = mb_strtolower(trim($email));

        return null !== $this->documentManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
    }

    private function roleLabel(string $role): string
    {
        return match (strtoupper($role)) {
            'FORMATEUR' => 'formateur',
            'ETUDIANT' => 'étudiant',
            default => 'utilisateur',
        };
    }
}
