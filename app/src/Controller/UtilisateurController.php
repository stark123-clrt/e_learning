<?php

namespace App\Controller;

use App\Document\Etudiant;
use App\Document\Formateur;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UtilisateurController extends AbstractController
{
    #[Route('/admin/comptes', name: 'app_utilisateur')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DocumentManager $dm): Response
    {
        $utilisateurs = $dm->getRepository(Utilisateur::class)->findAll();

        $comptes = [];
        foreach ($utilisateurs as $utilisateur) {
            $comptes[] = [
                'utilisateur' => $utilisateur,
                'nom' => $this->resoudreNom($dm, $utilisateur),
            ];
        }

        // Les comptes en attente en premier.
        usort($comptes, static function (array $a, array $b): int {
            $poids = static fn (string $statut): int => 'EN_ATTENTE' === strtoupper($statut) ? 0 : 1;

            return $poids($a['utilisateur']->getStatut()) <=> $poids($b['utilisateur']->getStatut());
        });

        return $this->render('utilisateur/index.html.twig', [
            'comptes' => $comptes,
        ]);
    }

    #[Route('/admin/comptes/{id}/valider', name: 'app_utilisateur_valider', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function valider(Utilisateur $utilisateur, Request $request, DocumentManager $dm): Response
    {
        if (!$this->isCsrfTokenValid('valider_compte_' . $utilisateur->getId(), (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_utilisateur');
        }

        $utilisateur->setStatut('ACTIF');
        $dm->flush();

        $this->addFlash('success', 'Compte validé : ' . $utilisateur->getEmail());

        return $this->redirectToRoute('app_utilisateur');
    }

    #[Route('/admin/comptes/{id}/supprimer', name: 'app_utilisateur_supprimer', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimer(Utilisateur $utilisateur, Request $request, DocumentManager $dm): Response
    {
        if (!$this->isCsrfTokenValid('supprimer_compte_' . $utilisateur->getId(), (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_utilisateur');
        }

        // On ne supprime pas son propre compte.
        if ($this->getUser() === $utilisateur) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('app_utilisateur');
        }

        $email = $utilisateur->getEmail();
        $dm->remove($utilisateur);
        $dm->flush();

        $this->addFlash('success', 'Compte supprimé : ' . $email);

        return $this->redirectToRoute('app_utilisateur');
    }

    private function resoudreNom(DocumentManager $dm, Utilisateur $utilisateur): string
    {
        $profilId = $utilisateur->getProfilId();
        if (null === $profilId) {
            return $utilisateur->getEmail();
        }

        $role = strtoupper($utilisateur->getRole());

        if ('ETUDIANT' === $role) {
            $etudiant = $dm->getRepository(Etudiant::class)->find($profilId);
            if ($etudiant instanceof Etudiant) {
                return trim($etudiant->getPrenom() . ' ' . $etudiant->getNom());
            }
        }

        if ('FORMATEUR' === $role) {
            $formateur = $dm->getRepository(Formateur::class)->find($profilId);
            if ($formateur instanceof Formateur) {
                return trim($formateur->getPrenom() . ' ' . $formateur->getNom());
            }
        }

        return $utilisateur->getEmail();
    }
}
