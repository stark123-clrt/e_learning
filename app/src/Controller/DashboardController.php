<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_dashboard_admin')]
    public function admin(): Response
    {
        return $this->render('dashboard/admin.html.twig');
    }

    #[Route('/formateur/dashboard', name: 'app_dashboard_formateur')]
    public function formateur(): Response
    {
        return $this->render('dashboard/formateur.html.twig');
    }

    #[Route('/etudiant/dashboard', name: 'app_dashboard_etudiant')]
    public function etudiant(): Response
    {
        return $this->render('dashboard/etudiant.html.twig');
    }
}
