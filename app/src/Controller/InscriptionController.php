<?php

namespace App\Controller;

use App\Document\Inscription;
use App\Document\Formation;
use App\Document\Etudiant;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class InscriptionController extends AbstractController
{
    // ADMIN : afficher toutes les inscriptions
    #[Route('/admin/inscriptions', name: 'admin_inscriptions')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DocumentManager $dm): Response
    {
        $inscriptions = $dm
            ->getRepository(Inscription::class)
            ->findAll();

        return $this->render('inscription/index.html.twig', [
            'inscriptions' => $inscriptions
        ]);
    }


    // ETUDIANT : s'inscrire à une formation
    #[Route('/etudiant/inscription/{id}', name: 'etudiant_inscription')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function inscrire(
        Formation $formation,
        DocumentManager $dm
    ): Response {

        // Un compte non encore validé par l'admin peut voir son espace mais pas s'inscrire.
        $utilisateur = $this->getUser();
        if ($utilisateur instanceof Utilisateur && strtoupper($utilisateur->getStatut()) !== 'ACTIF') {
            $this->addFlash(
                'danger',
                'Votre compte est en cours de validation. Vous pourrez vous inscrire aux formations une fois validé par un administrateur.'
            );

            return $this->redirectToRoute('etudiant_catalogue');
        }

        $etudiant = $this->getEtudiantCourant($dm);

        if ($etudiant === null) {
            $this->addFlash('danger', 'Votre profil étudiant est introuvable.');

            return $this->redirectToRoute('etudiant_catalogue');
        }

        // Vérifier si déjà inscrit
        $existe = $dm
            ->getRepository(Inscription::class)
            ->findOneBy([
                'etudiant' => $etudiant,
                'formation' => $formation
            ]);


        if ($existe) {

            $this->addFlash(
                'danger',
                'Vous êtes déjà inscrit à cette formation.'
            );

            return $this->redirectToRoute('etudiant_catalogue');
        }



        // Vérifier la capacité maximale
        $nombreInscrits = count(
            $dm
            ->getRepository(Inscription::class)
            ->findBy([
                'formation'=>$formation
            ])
        );


        if ($nombreInscrits >= $formation->getCapaciteMax()) {

            $this->addFlash(
                'danger',
                'La capacité maximale est atteinte.'
            );

            return $this->redirectToRoute('etudiant_catalogue');
        }



        // Création inscription

        $inscription = new Inscription();

        $inscription->setEtudiant($etudiant);
        $inscription->setFormation($formation);
        $inscription->setDateInscription(new \DateTimeImmutable());
        $inscription->setStatut('ACTIVE');


        $dm->persist($inscription);
        $dm->flush();



        $this->addFlash(
            'success',
            'Inscription réussie.'
        );


        return $this->redirectToRoute(
            'etudiant_inscriptions'
        );
    }




    // ETUDIANT : annuler une inscription
    #[Route('/etudiant/inscription/annuler/{id}', name:'annuler_inscription', methods: ['POST'])]
    #[IsGranted('ROLE_ETUDIANT')]
    public function annuler(
        Inscription $inscription,
        DocumentManager $dm,
        Request $request
    ): Response {

        if (!$this->isCsrfTokenValid('annuler_inscription', (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $etudiant = $this->getEtudiantCourant($dm);

        // Sécurité :
        // un étudiant ne peut annuler que ses propres inscriptions

        if ($etudiant === null || $inscription->getEtudiant() === null
            || $inscription->getEtudiant()->getId() !== $etudiant->getId()) {

            throw $this->createAccessDeniedException();
        }



        $dm->remove($inscription);
        $dm->flush();



        $this->addFlash(
            'success',
            'Inscription annulée.'
        );


        return $this->redirectToRoute(
            'etudiant_inscriptions'
        );
    }


    // ETUDIANT : consulter ses propres inscriptions
    #[Route('/etudiant/inscriptions', name: 'etudiant_inscriptions')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function mesInscriptions(DocumentManager $dm): Response
    {
        $etudiant = $this->getEtudiantCourant($dm);

        $inscriptions = $etudiant !== null
            ? $dm->getRepository(Inscription::class)->findBy(['etudiant' => $etudiant])
            : [];

        return $this->render('inscription/mes_inscriptions.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }




    // FORMATEUR : voir les étudiants d'une formation

    #[Route('/formateur/formation/{id}/etudiants',
        name:'formateur_etudiants')]
    #[IsGranted('ROLE_FORMATEUR')]
    public function etudiantsFormation(
        Formation $formation,
        DocumentManager $dm
    ): Response {

        // Sécurité :
        // un formateur ne peut consulter que les étudiants de ses propres formations
        $utilisateur = $this->getUser();
        $formateurId = $utilisateur instanceof Utilisateur ? $utilisateur->getProfilId() : null;

        if ($formation->getFormateur() === null || $formation->getFormateur()->getId() !== $formateurId) {
            throw $this->createAccessDeniedException();
        }

        $inscriptions = $dm
            ->getRepository(Inscription::class)
            ->findBy([
                'formation'=>$formation
            ]);



        return $this->render(
            'formateur/etudiants.html.twig',
            [
                'formation' => $formation,
                'inscriptions'=>$inscriptions
            ]
        );
    }

    private function getEtudiantCourant(DocumentManager $dm): ?Etudiant
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur || $utilisateur->getProfilId() === null) {
            return null;
        }

        return $dm->getRepository(Etudiant::class)->find($utilisateur->getProfilId());
    }
}
