<?php

namespace App\Controller;

use App\Document\Etudiant;
use App\Document\Formateur;
use App\Document\Formation;
use App\Document\Inscription;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EspaceController extends AbstractController
{
    // ---------- Espace formateur ----------

    #[Route('/formateur/formations', name: 'formateur_formations')]
    #[IsGranted('ROLE_FORMATEUR')]
    public function mesFormations(DocumentManager $dm): Response
    {
        $formateur = $this->formateurCourant($dm);
        $formations = [];
        if ($formateur instanceof Formateur) {
            foreach ($dm->getRepository(Formation::class)->findBy(['formateur' => $formateur]) as $f) {
                $formations[] = [
                    'formation' => $f,
                    'nbInscrits' => count($dm->getRepository(Inscription::class)->findBy(['formation' => $f])),
                ];
            }
        }

        return $this->render('formateur/mes_formations.html.twig', ['formations' => $formations]);
    }

    #[Route('/formateur/notes', name: 'formateur_notes')]
    #[IsGranted('ROLE_FORMATEUR')]
    public function formateurNotes(DocumentManager $dm): Response
    {
        $formateur = $this->formateurCourant($dm);
        $blocs = [];
        if ($formateur instanceof Formateur) {
            foreach ($dm->getRepository(Formation::class)->findBy(['formateur' => $formateur]) as $f) {
                $blocs[] = [
                    'formation' => $f,
                    'inscriptions' => $dm->getRepository(Inscription::class)->findBy(['formation' => $f]),
                ];
            }
        }

        return $this->render('formateur/notes.html.twig', ['blocs' => $blocs]);
    }

    #[Route('/formateur/notes/{id}/enregistrer', name: 'formateur_notes_save', methods: ['POST'])]
    #[IsGranted('ROLE_FORMATEUR')]
    public function enregistrerNotes(Formation $formation, Request $request, DocumentManager $dm): Response
    {
        $formateur = $this->formateurCourant($dm);

        // Règle de gestion : un formateur ne note que les étudiants de ses formations.
        if (!$formateur instanceof Formateur || $formation->getFormateur()?->getId() !== $formateur->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('notes_' . $formation->getId(), (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('formateur_notes');
        }

        $notes = $request->request->all('notes');
        $erreur = false;
        foreach ($notes as $inscriptionId => $valeur) {
            $inscription = $dm->getRepository(Inscription::class)->find($inscriptionId);
            if (!$inscription instanceof Inscription || $inscription->getFormation()?->getId() !== $formation->getId()) {
                continue;
            }

            $valeur = trim((string) $valeur);
            if ('' === $valeur) {
                $inscription->setNote(null);
                continue;
            }
            $valeur = str_replace(',', '.', $valeur);
            if (!is_numeric($valeur) || (float) $valeur < 0 || (float) $valeur > 20) {
                $erreur = true;
                continue;
            }
            $inscription->setNote((float) $valeur);
        }
        $dm->flush();

        if ($erreur) {
            $this->addFlash('danger', 'Certaines notes ont été ignorées (elles doivent être comprises entre 0 et 20).');
        } else {
            $this->addFlash('success', 'Notes enregistrées.');
        }

        return $this->redirectToRoute('formateur_notes');
    }

    #[Route('/formateur/moyennes', name: 'formateur_moyennes')]
    #[IsGranted('ROLE_FORMATEUR')]
    public function formateurMoyennes(DocumentManager $dm): Response
    {
        $formateur = $this->formateurCourant($dm);
        $lignes = [];
        if ($formateur instanceof Formateur) {
            foreach ($dm->getRepository(Formation::class)->findBy(['formateur' => $formateur]) as $f) {
                $notes = [];
                foreach ($dm->getRepository(Inscription::class)->findBy(['formation' => $f]) as $i) {
                    if (null !== $i->getNote()) {
                        $notes[] = $i->getNote();
                    }
                }
                $lignes[] = [
                    'titre' => $f->getTitre(),
                    'nbNotes' => count($notes),
                    'moyenne' => $notes ? $this->fmt(array_sum($notes) / count($notes)) : null,
                ];
            }
        }

        return $this->render('formateur/moyennes.html.twig', ['lignes' => $lignes]);
    }

    // ---------- Espace étudiant ----------

    #[Route('/etudiant/notes', name: 'etudiant_notes')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantNotes(DocumentManager $dm): Response
    {
        $etudiant = $this->etudiantCourant($dm);
        $inscriptions = $etudiant instanceof Etudiant
            ? $dm->getRepository(Inscription::class)->findBy(['etudiant' => $etudiant])
            : [];

        return $this->render('etudiant/mes_notes.html.twig', ['inscriptions' => $inscriptions]);
    }

    #[Route('/etudiant/moyenne', name: 'etudiant_moyenne')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function etudiantMoyenne(DocumentManager $dm): Response
    {
        $etudiant = $this->etudiantCourant($dm);
        $inscriptions = $etudiant instanceof Etudiant
            ? $dm->getRepository(Inscription::class)->findBy(['etudiant' => $etudiant])
            : [];

        $notes = [];
        $details = [];
        foreach ($inscriptions as $i) {
            if (null !== $i->getNote()) {
                $notes[] = $i->getNote();
                $details[] = [
                    'formation' => $i->getFormation() ? $i->getFormation()->getTitre() : '—',
                    'note' => $i->getNote(),
                    'hauteur' => (int) round($i->getNote() / 20 * 100),
                ];
            }
        }

        return $this->render('etudiant/ma_moyenne.html.twig', [
            'moyenne' => $notes ? $this->fmt(array_sum($notes) / count($notes)) : '—',
            'details' => $details,
        ]);
    }

    // ---------- Helpers ----------

    private function formateurCourant(DocumentManager $dm): ?Formateur
    {
        $u = $this->getUser();
        if (!$u instanceof Utilisateur || null === $u->getProfilId()) {
            return null;
        }

        return $dm->getRepository(Formateur::class)->find($u->getProfilId());
    }

    private function etudiantCourant(DocumentManager $dm): ?Etudiant
    {
        $u = $this->getUser();
        if (!$u instanceof Utilisateur || null === $u->getProfilId()) {
            return null;
        }

        return $dm->getRepository(Etudiant::class)->find($u->getProfilId());
    }

    private function fmt(float $note): string
    {
        return number_format($note, 1, ',', ' ');
    }
}
