<?php

namespace App\Controller;

use App\Document\Etudiant;
use App\Document\Inscription;
use App\Document\Utilisateur;
use App\Service\InvitationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EtudiantController extends AbstractController
{
    private const NIVEAUX = ['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2'];

    #[Route('/admin/etudiants', name: 'app_etudiant')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, DocumentManager $dm): Response
    {
        $recherche = trim((string) $request->query->get('q', ''));
        $niveau = trim((string) $request->query->get('niveau', ''));

        $qb = $dm->getRepository(Etudiant::class)->createQueryBuilder();

        if ('' !== $recherche) {
            $regex = new \MongoDB\BSON\Regex(preg_quote($recherche), 'i');
            $qb->addOr($qb->expr()->field('nom')->equals($regex))
                ->addOr($qb->expr()->field('prenom')->equals($regex))
                ->addOr($qb->expr()->field('email')->equals($regex));
        }
        if (in_array($niveau, self::NIVEAUX, true)) {
            $qb->field('niveau')->equals($niveau);
        }

        $etudiants = $qb->sort('nom', 'asc')->getQuery()->execute();

        return $this->render('etudiant/index.html.twig', [
            'etudiants' => $etudiants,
            'niveaux' => self::NIVEAUX,
            'recherche' => $recherche,
            'niveauSelectionne' => $niveau,
        ]);
    }

    #[Route('/admin/etudiants/new', name: 'app_etudiant_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, DocumentManager $dm, InvitationService $invitations): Response
    {
        $errors = [];
        $data = ['nom' => '', 'prenom' => '', 'email' => '', 'niveau' => ''];

        if ($request->isMethod('POST')) {
            [$data, $errors] = $this->lireEtValider($request, $dm, null);

            if (!$errors && $invitations->emailDejaUtilise($data['email'])) {
                $errors[] = 'Un compte utilise déjà cet email.';
            }

            if (!$errors) {
                $etudiant = new Etudiant();
                $etudiant->setNom($data['nom']);
                $etudiant->setPrenom($data['prenom']);
                $etudiant->setEmail($data['email']);
                $etudiant->setNiveau($data['niveau']);
                $etudiant->setActif(true);
                $dm->persist($etudiant);
                $dm->flush();

                $invitations->inviter($data['email'], 'ETUDIANT', $etudiant->getId(), trim($data['prenom'] . ' ' . $data['nom']));

                $this->addFlash('success', 'Étudiant ajouté. Un email d\'invitation lui a été envoyé pour définir son mot de passe.');

                return $this->redirectToRoute('app_etudiant');
            }
        }

        return $this->render('etudiant/form.html.twig', [
            'etudiant' => null,
            'etudiantData' => $data,
            'niveaux' => self::NIVEAUX,
            'errors' => $errors,
            'formAction' => 'Ajouter',
            'formRoute' => 'app_etudiant_new',
        ]);
    }

    #[Route('/admin/etudiants/{id}/edit', name: 'app_etudiant_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Etudiant $etudiant, Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $data = [
            'nom' => $etudiant->getNom(),
            'prenom' => $etudiant->getPrenom(),
            'email' => $etudiant->getEmail(),
            'niveau' => $etudiant->getNiveau(),
        ];

        if ($request->isMethod('POST')) {
            [$data, $errors] = $this->lireEtValider($request, $dm, $etudiant);

            if (!$errors) {
                $etudiant->setNom($data['nom']);
                $etudiant->setPrenom($data['prenom']);
                $etudiant->setEmail($data['email']);
                $etudiant->setNiveau($data['niveau']);
                $dm->flush();

                $this->addFlash('success', 'Étudiant mis à jour.');

                return $this->redirectToRoute('app_etudiant');
            }
        }

        return $this->render('etudiant/form.html.twig', [
            'etudiant' => $etudiant,
            'etudiantData' => $data,
            'niveaux' => self::NIVEAUX,
            'errors' => $errors,
            'formAction' => 'Modifier',
            'formRoute' => 'app_etudiant_edit',
        ]);
    }

    #[Route('/admin/etudiants/{id}/delete', name: 'app_etudiant_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Etudiant $etudiant, Request $request, DocumentManager $dm): Response
    {
        if (!$this->isCsrfTokenValid('delete_etudiant_' . $etudiant->getId(), (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_etudiant');
        }

        // Supprime aussi ses inscriptions et son compte de connexion.
        foreach ($dm->getRepository(Inscription::class)->findBy(['etudiant' => $etudiant]) as $inscription) {
            $dm->remove($inscription);
        }
        $compte = $dm->getRepository(Utilisateur::class)->findOneBy(['profilId' => $etudiant->getId()]);
        if ($compte instanceof Utilisateur) {
            $dm->remove($compte);
        }
        $dm->remove($etudiant);
        $dm->flush();

        $this->addFlash('success', 'Étudiant supprimé.');

        return $this->redirectToRoute('app_etudiant');
    }

    #[Route('/admin/etudiants/{id}', name: 'app_etudiant_show', requirements: ['id' => '[a-f0-9]{24}'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Etudiant $etudiant, DocumentManager $dm): Response
    {
        $inscriptions = $dm->getRepository(Inscription::class)->findBy(['etudiant' => $etudiant]);

        $notes = [];
        foreach ($inscriptions as $i) {
            if (null !== $i->getNote()) {
                $notes[] = $i->getNote();
            }
        }
        $moyenne = $notes ? number_format(array_sum($notes) / count($notes), 1, ',', ' ') : null;

        return $this->render('etudiant/show.html.twig', [
            'etudiant' => $etudiant,
            'inscriptions' => $inscriptions,
            'moyenne' => $moyenne,
        ]);
    }

    /**
     * @return array{0: array{nom: string, prenom: string, email: string, niveau: string}, 1: string[]}
     */
    private function lireEtValider(Request $request, DocumentManager $dm, ?Etudiant $courant): array
    {
        $errors = [];

        if (!$this->isCsrfTokenValid('etudiant_form', (string) $request->request->get('_csrf_token'))) {
            $errors[] = 'Jeton de sécurité invalide.';
        }

        $data = [
            'nom' => trim((string) $request->request->get('nom')),
            'prenom' => trim((string) $request->request->get('prenom')),
            'email' => mb_strtolower(trim((string) $request->request->get('email'))),
            'niveau' => trim((string) $request->request->get('niveau')),
        ];

        if ('' === $data['nom'] || '' === $data['prenom']) {
            $errors[] = 'Le nom et le prénom sont obligatoires.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        if (!in_array($data['niveau'], self::NIVEAUX, true)) {
            $errors[] = 'Niveau invalide.';
        }

        if (!$errors && '' !== $data['email']) {
            $existant = $dm->getRepository(Etudiant::class)->findOneBy(['email' => $data['email']]);
            if (null !== $existant && (null === $courant || $existant->getId() !== $courant->getId())) {
                $errors[] = 'Un étudiant utilise déjà cet email.';
            }
        }

        return [$data, $errors];
    }
}
