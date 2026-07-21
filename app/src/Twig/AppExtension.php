<?php

namespace App\Twig;

use App\Document\Etudiant;
use App\Document\Formateur;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_user_name', [$this, 'nom']),
            new TwigFunction('current_user_initials', [$this, 'initiales']),
            new TwigFunction('current_user_role_label', [$this, 'roleLabel']),
            new TwigFunction('youtube_embed', [$this, 'youtubeEmbed']),
        ];
    }

    /**
     * Transforme un lien YouTube en URL intégrable (iframe). Renvoie null si non reconnu.
     */
    public function youtubeEmbed(string $url): ?string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        return null;
    }

    public function nom(): string
    {
        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return 'Invité';
        }

        $profil = $this->profil($utilisateur);
        if ($profil instanceof Etudiant || $profil instanceof Formateur) {
            $nom = trim($profil->getPrenom() . ' ' . $profil->getNom());
            if ('' !== $nom) {
                return $nom;
            }
        }

        // Admin ou profil manquant : on affiche la partie locale de l'email.
        $email = $utilisateur->getEmail();
        $local = strstr($email, '@', true) ?: $email;

        return ucwords(str_replace(['.', '_', '-'], ' ', $local));
    }

    public function initiales(): string
    {
        $nom = $this->nom();
        $mots = preg_split('/\s+/', trim($nom)) ?: [];
        $initiales = '';
        foreach (array_slice($mots, 0, 2) as $mot) {
            $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
        }

        return $initiales ?: '?';
    }

    public function roleLabel(): string
    {
        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return '';
        }

        return match (strtoupper($utilisateur->getRole())) {
            'ADMIN' => 'Administrateur',
            'FORMATEUR' => 'Formateur',
            'ETUDIANT' => 'Étudiant',
            default => ucfirst(strtolower($utilisateur->getRole())),
        };
    }

    private function profil(Utilisateur $utilisateur): ?object
    {
        if (null === $utilisateur->getProfilId()) {
            return null;
        }

        return match (strtoupper($utilisateur->getRole())) {
            'ETUDIANT' => $this->documentManager->getRepository(Etudiant::class)->find($utilisateur->getProfilId()),
            'FORMATEUR' => $this->documentManager->getRepository(Formateur::class)->find($utilisateur->getProfilId()),
            default => null,
        };
    }
}
