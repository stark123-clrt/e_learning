<?php

namespace App\Controller;

use App\Document\Formation;
use App\Document\Inscription;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FormationController extends AbstractController
{

    // ADMIN : afficher toutes les formations
    #[Route('/admin/formations', name: 'admin_formations')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DocumentManager $dm): Response
    {

        $formations = $dm
            ->getRepository(Formation::class)
            ->findAll();


        return $this->render('formation/index.html.twig', [
            'formations' => $formations
        ]);
    }



    // ETUDIANT : catalogue des formations
    #[Route('/etudiant/catalogue', name:'etudiant_catalogue')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function catalogue(DocumentManager $dm): Response
    {

        $formations = $dm
            ->getRepository(Formation::class)
            ->findBy([
                'statut'=>'OUVERTE'
            ]);


        return $this->render('etudiant/catalogue.html.twig', [
            'formations'=>$formations
        ]);
    }





    // ADMIN : supprimer une formation
    #[Route('/admin/formation/delete/{id}', name:'admin_formation_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Formation $formation,
        DocumentManager $dm
    ): Response {


        // Vérifier les inscriptions existantes

        $inscriptions = $dm
            ->getRepository(Inscription::class)
            ->findBy([
                'formation'=>$formation
            ]);


        if(count($inscriptions) > 0){

            $this->addFlash(
                'danger',
                'Impossible de supprimer une formation avec des inscriptions.'
            );


            return $this->redirectToRoute(
                'admin_formations'
            );
        }



        $dm->remove($formation);
        $dm->flush();



        $this->addFlash(
            'success',
            'Formation supprimée.'
        );


        return $this->redirectToRoute(
            'admin_formations'
        );
    }

}