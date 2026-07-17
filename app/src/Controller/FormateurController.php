<?php

namespace App\Controller;

use App\Document\Formateur;
use App\Document\Formation;
use App\Document\Inscription;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FormateurController extends AbstractController
{
    #[Route('/admin/formateurs', name: 'admin_formateurs')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DocumentManager $dm): Response
    {
        $formateurs = $dm
            ->getRepository(Formateur::class)
            ->findAll();

        $formationCounts = [];
        $formations = $dm
            ->getRepository(Formation::class)
            ->findAll();

        foreach ($formations as $formation) {
            $owner = $formation->getFormateur();
            if ($owner !== null) {
                $formationCounts[$owner->getId()] = ($formationCounts[$owner->getId()] ?? 0) + 1;
            }
        }

        return $this->render('formateur/index.html.twig', [
            'formateurs' => $formateurs,
            'formationCounts' => $formationCounts,
        ]);
    }

    #[Route('/admin/formateurs/new', name: 'admin_formateur_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $formateurData = [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'specialite' => '',
            'statut' => 'ACTIF',
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('formateur_form', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Jeton CSRF invalide.';
            }

            $formateurData = [
                'nom' => trim((string) $request->request->get('nom')),
                'prenom' => trim((string) $request->request->get('prenom')),
                'email' => mb_strtolower(trim((string) $request->request->get('email'))),
                'specialite' => trim((string) $request->request->get('specialite')),
                'statut' => strtoupper(trim((string) $request->request->get('statut'))),
            ];

            if ($formateurData['nom'] === '' || $formateurData['prenom'] === '' || $formateurData['email'] === '' || $formateurData['specialite'] === '') {
                $errors[] = 'Tous les champs sont obligatoires.';
            }
            if (!filter_var($formateurData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse email invalide.';
            }
            if (!in_array($formateurData['statut'], ['ACTIF', 'INACTIF'], true)) {
                $formateurData['statut'] = 'ACTIF';
            }

            if (!$errors) {
                $emailExists = $dm->getRepository(Formateur::class)->findOneBy(['email' => $formateurData['email']]);
                if ($emailExists !== null) {
                    $errors[] = 'Un formateur existe déjà avec cet email.';
                }
            }

            if (!$errors) {
                $formateur = new Formateur();
                $formateur->setNom($formateurData['nom']);
                $formateur->setPrenom($formateurData['prenom']);
                $formateur->setEmail($formateurData['email']);
                $formateur->setSpecialite($formateurData['specialite']);
                $formateur->setStatut($formateurData['statut']);

                $dm->persist($formateur);
                $dm->flush();

                $this->addFlash('success', 'Formateur créé avec succès.');

                return $this->redirectToRoute('admin_formateurs');
            }
        }

        return $this->render('formateur/form.html.twig', [
            'formateur' => null,
            'formateurData' => $formateurData,
            'errors' => $errors,
            'formAction' => 'Créer',
            'formRoute' => 'admin_formateur_new',
        ]);
    }

    #[Route('/admin/formateurs/{id}/edit', name: 'admin_formateur_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Formateur $formateur, Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $formateurData = [
            'nom' => $formateur->getNom(),
            'prenom' => $formateur->getPrenom(),
            'email' => $formateur->getEmail(),
            'specialite' => $formateur->getSpecialite(),
            'statut' => $formateur->getStatut(),
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('formateur_form', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Jeton CSRF invalide.';
            }

            $formateurData = [
                'nom' => trim((string) $request->request->get('nom')),
                'prenom' => trim((string) $request->request->get('prenom')),
                'email' => mb_strtolower(trim((string) $request->request->get('email'))),
                'specialite' => trim((string) $request->request->get('specialite')),
                'statut' => strtoupper(trim((string) $request->request->get('statut'))),
            ];

            if ($formateurData['nom'] === '' || $formateurData['prenom'] === '' || $formateurData['email'] === '' || $formateurData['specialite'] === '') {
                $errors[] = 'Tous les champs sont obligatoires.';
            }
            if (!filter_var($formateurData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse email invalide.';
            }
            if (!in_array($formateurData['statut'], ['ACTIF', 'INACTIF'], true)) {
                $formateurData['statut'] = 'ACTIF';
            }

            if (!$errors) {
                $existing = $dm->getRepository(Formateur::class)->findOneBy(['email' => $formateurData['email']]);
                if ($existing !== null && $existing->getId() !== $formateur->getId()) {
                    $errors[] = 'Un autre formateur utilise déjà cet email.';
                }
            }

            if (!$errors) {
                $formateur->setNom($formateurData['nom']);
                $formateur->setPrenom($formateurData['prenom']);
                $formateur->setEmail($formateurData['email']);
                $formateur->setSpecialite($formateurData['specialite']);
                $formateur->setStatut($formateurData['statut']);

                $dm->flush();

                $this->addFlash('success', 'Formateur mis à jour avec succès.');

                return $this->redirectToRoute('admin_formateur_show', ['id' => $formateur->getId()]);
            }
        }

        return $this->render('formateur/form.html.twig', [
            'formateur' => $formateur,
            'formateurData' => $formateurData,
            'errors' => $errors,
            'formAction' => 'Modifier',
            'formRoute' => 'admin_formateur_edit',
        ]);
    }

    #[Route('/admin/formateurs/{id}/delete', name: 'admin_formateur_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Formateur $formateur, Request $request, DocumentManager $dm): Response
    {
        if (!$this->isCsrfTokenValid('delete_formateur', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_formateur_show', ['id' => $formateur->getId()]);
        }

        $formations = $dm
            ->getRepository(Formation::class)
            ->findBy(['formateur' => $formateur]);

        if (count($formations) > 0) {
            $this->addFlash('danger', 'Impossible de supprimer un formateur qui a des formations. Supprimez ou réaffectez ses formations d’abord.');

            return $this->redirectToRoute('admin_formateur_show', ['id' => $formateur->getId()]);
        }

        $dm->remove($formateur);
        $dm->flush();

        $this->addFlash('success', 'Formateur supprimé.');

        return $this->redirectToRoute('admin_formateurs');
    }

    #[Route('/admin/formateurs/{id}/show', name: 'admin_formateur_show')]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Formateur $formateur, DocumentManager $dm): Response
    {
        $formations = $dm
            ->getRepository(Formation::class)
            ->findBy(['formateur' => $formateur]);

        $inscriptions = [];
        if (!empty($formations)) {
            $inscriptions = $dm
                ->getRepository(Inscription::class)
                ->createQueryBuilder()
                ->field('formation')->in($formations)
                ->getQuery()
                ->execute();
        }

        $noteSum = 0;
        $noteCount = 0;
        foreach ($inscriptions as $inscription) {
            $note = $inscription->getNote();
            if ($note !== null) {
                $noteSum += $note;
                $noteCount++;
            }
        }

        $noteMoyenne = $noteCount > 0
            ? round($noteSum / $noteCount, 1)
            : null;

        return $this->render('formateur/show.html.twig', [
            'formateur' => $formateur,
            'formations' => $formations,
            'noteMoyenne' => $noteMoyenne,
        ]);
    }
}
