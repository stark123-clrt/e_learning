<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreviewController extends AbstractController
{
    #[Route('/', name: 'app_preview_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/login', name: 'app_preview_login')]
    public function login(): Response
    {
        return $this->render('security/login.html.twig');
    }

    #[Route('/inscription-compte', name: 'app_preview_register')]
    public function register(): Response
    {
        return $this->render('security/register.html.twig');
    }

    #[Route('/admin/etudiants', name: 'app_preview_admin_etudiants')]
    public function adminEtudiants(): Response
    {
        return $this->render('etudiant/index.html.twig');
    }

    #[Route('/etudiant/1', name: 'app_preview_etudiant_show')]
    public function etudiantShow(): Response
    {
        return $this->render('etudiant/show.html.twig');
    }

    #[Route('/etudiant/catalogue', name: 'app_preview_etudiant_catalogue')]
    public function etudiantCatalogue(): Response
    {
        return $this->render('formation/catalogue.html.twig');
    }

    #[Route('/etudiant/catalogue/1', name: 'app_preview_etudiant_catalogue_show')]
    public function etudiantCatalogueShow(): Response
    {
        return $this->render('formation/catalogue_show.html.twig');
    }

    #[Route('/etudiant/inscriptions', name: 'app_preview_etudiant_inscriptions')]
    public function etudiantInscriptions(): Response
    {
        return $this->render('inscription/mes_inscriptions.html.twig');
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

    #[Route('/admin/formateurs', name: 'app_preview_admin_formateurs')]
    public function adminFormateurs(): Response
    {
        return $this->render('formateur/index.html.twig');
    }

    #[Route('/formateur/1', name: 'app_preview_formateur_show')]
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

    #[Route('/admin/formations', name: 'app_preview_admin_formations')]
    public function adminFormations(): Response
    {
        return $this->render('formation/index.html.twig');
    }

    #[Route('/formation/1', name: 'app_preview_formation_show')]
    public function formationShow(): Response
    {
        return $this->render('formation/show.html.twig');
    }

    #[Route('/admin/inscriptions', name: 'app_preview_admin_inscriptions')]
    public function adminInscriptions(): Response
    {
        return $this->render('inscription/index.html.twig');
    }

    #[Route('/admin/comptes', name: 'app_preview_admin_comptes')]
    public function adminComptes(): Response
    {
        return $this->render('utilisateur/index.html.twig');
    }

    #[Route('/admin/statistiques', name: 'app_preview_admin_statistiques')]
    public function adminStatistiques(): Response
    {
        return $this->render('statistique/index.html.twig');
    }
}
