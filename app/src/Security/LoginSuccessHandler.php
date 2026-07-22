<?php

namespace App\Security;

use App\Document\Utilisateur;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
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
}
