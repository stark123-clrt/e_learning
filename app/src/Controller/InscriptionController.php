<?php

namespace App\Controller;

use App\Document\Inscription;
use App\Document\Formation;
use App\Document\Etudiant;
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

        $etudiant = $this->getUser();


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
        $inscription->setDateInscription(new \DateTime());
        $inscription->setStatut('active');


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
    #[Route('/etudiant/inscription/annuler/{id}', name:'annuler_inscription')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function annuler(
        Inscription $inscription,
        DocumentManager $dm
    ): Response {


        $etudiant = $this->getUser();


        // Sécurité :
        // un étudiant ne peut supprimer que ses inscriptions

        if ($inscription->getEtudiant() !== $etudiant) {

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




    // FORMATEUR : voir les étudiants d'une formation

    #[Route('/formateur/formation/{id}/etudiants',
        name:'formateur_etudiants')]
    #[IsGranted('ROLE_FORMATEUR')]
    public function etudiantsFormation(
        Formation $formation,
        DocumentManager $dm
    ): Response {


        $inscriptions = $dm
            ->getRepository(Inscription::class)
            ->findBy([
                'formation'=>$formation
            ]);



        return $this->render(
            'formateur/etudiants.html.twig',
            [
                'inscriptions'=>$inscriptions
            ]
        );
    }
}