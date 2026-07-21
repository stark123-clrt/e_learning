<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(): Response
    {
        // L'authentification est interceptée par le GoogleAuthenticator.
        // Cette méthode ne devrait jamais être exécutée directement.
        throw new \LogicException('Cette route est gérée par le GoogleAuthenticator.');
    }
}
