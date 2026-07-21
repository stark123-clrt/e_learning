<?php

namespace App\Security;

use App\Document\Etudiant;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly DocumentManager $documentManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'connect_google_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = mb_strtolower((string) $googleUser->getEmail());

                $repository = $this->documentManager->getRepository(Utilisateur::class);
                $utilisateur = $repository->findOneBy(['email' => $email]);

                if ($utilisateur instanceof Utilisateur) {
                    return $utilisateur;
                }

                // Première connexion : on crée un compte étudiant lié.
                $etudiant = new Etudiant();
                $etudiant->setNom((string) ($googleUser->getName() ?: $email));
                $etudiant->setPrenom((string) ($googleUser->getFirstName() ?: ''));
                $etudiant->setEmail($email);
                $etudiant->setNiveau('');
                $etudiant->setActif(true);
                $this->documentManager->persist($etudiant);
                $this->documentManager->flush();

                $utilisateur = new Utilisateur();
                $utilisateur->setEmail($email);
                $utilisateur->setRole('ETUDIANT');
                $utilisateur->setStatut('EN_ATTENTE');
                $utilisateur->setProfilId($etudiant->getId());
                // Mot de passe aléatoire : le compte se connecte via Google, pas par formulaire.
                $utilisateur->setMotDePasse(
                    $this->passwordHasher->hashPassword($utilisateur, bin2hex(random_bytes(16)))
                );
                $this->documentManager->persist($utilisateur);
                $this->documentManager->flush();

                return $utilisateur;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $route = 'app_home';

        if ($user instanceof Utilisateur) {
            $route = match (strtoupper($user->getRole())) {
                'ADMIN' => 'app_dashboard_admin',
                'FORMATEUR' => 'app_dashboard_formateur',
                'ETUDIANT' => 'app_dashboard_etudiant',
                default => 'app_home',
            };
        }

        return new RedirectResponse($this->urlGenerator->generate($route));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add('error', 'La connexion avec Google a échoué. Veuillez réessayer.');

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
