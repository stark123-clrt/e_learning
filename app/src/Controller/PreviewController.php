<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreviewController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/admin/etudiants/1', name: 'app_preview_etudiant_show')]
    public function etudiantShow(): Response
    {
        return $this->render('etudiant/show.html.twig');
    }

    #[Route('/etudiant/catalogue/1', name: 'app_preview_etudiant_catalogue_show')]
    public function etudiantCatalogueShow(): Response
    {
        return $this->render('formation/catalogue_show.html.twig');
    }

    #[Route('/etudiant/notes', name: 'app_preview_etudiant_notes')]
    public function etudiantNotes(): Response
    {
        return $this->render('etudiant/mes_notes.html.twig');
    }

    #[Route('/etudiant/moyenne', name: 'app_preview_etudiant_moyenne')]
    public function etudiantMoyenne(): Response
    {
        return $this->render('etudiant/ma_moyenne.html.twig');
    }

    #[Route('/etudiant/profil', name: 'app_preview_etudiant_profil')]
    public function etudiantProfil(): Response
    {
        return $this->render('utilisateur/profil_etudiant.html.twig');
    }

    #[Route('/admin/formateurs/1', name: 'app_preview_formateur_show')]
    public function formateurShow(): Response
    {
        return $this->render('formateur/show.html.twig');
    }

    #[Route('/formateur/formations', name: 'app_preview_formateur_formations')]
    public function formateurFormations(): Response
    {
        return $this->render('formateur/mes_formations.html.twig');
    }

    #[Route('/formateur/notes', name: 'app_preview_formateur_notes')]
    public function formateurNotes(): Response
    {
        return $this->render('formateur/notes.html.twig');
    }

    #[Route('/formateur/moyennes', name: 'app_preview_formateur_moyennes')]
    public function formateurMoyennes(): Response
    {
        return $this->render('formateur/moyennes.html.twig');
    }

    #[Route('/formateur/profil', name: 'app_preview_formateur_profil')]
    public function formateurProfil(): Response
    {
        return $this->render('utilisateur/profil_formateur.html.twig');
    }

    #[Route('/admin/formations/1', name: 'app_preview_formation_show')]
    public function formationShow(): Response
    {
        return $this->render('formation/show.html.twig');
    }

    #[Route('/admin/statistiques', name: 'app_preview_admin_statistiques')]
    public function adminStatistiques(): Response
    {
        return $this->render('statistique/index.html.twig');
    }
}
