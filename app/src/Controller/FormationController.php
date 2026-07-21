<?php

namespace App\Controller;

use App\Document\Classe;
use App\Document\Formateur;
use App\Document\Formation;
use App\Document\Inscription;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FormationController extends AbstractController
{
    private const CATEGORIES = ['Développement', 'Data', 'Design', 'Réseaux', 'Management', 'Cybersécurité'];
    private const STATUTS = ['OUVERTE', 'COMPLETE', 'TERMINEE', 'BROUILLON'];

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


        return $this->render('formation/catalogue.html.twig', [
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

    // ADMIN : créer une formation
    #[Route('/admin/formations/new', name: 'admin_formation_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $data = $this->donneesVides();

        if ($request->isMethod('POST')) {
            [$data, $errors, $formateur, $classe] = $this->lireEtValider($request, $dm);
            if (!$errors) {
                $formation = new Formation();
                $this->appliquer($formation, $data, $formateur, $classe);
                $dm->persist($formation);
                $dm->flush();
                $this->addFlash('success', 'Formation créée.');

                return $this->redirectToRoute('admin_formations');
            }
        }

        return $this->render('formation/form.html.twig', $this->contexteForm($dm, null, $data, $errors, 'Créer', 'admin_formation_new'));
    }

    // ADMIN : modifier une formation
    #[Route('/admin/formations/{id}/edit', name: 'admin_formation_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Formation $formation, Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $data = [
            'titre' => $formation->getTitre(),
            'description' => $formation->getDescription(),
            'categorie' => $formation->getCategorie(),
            'prix' => (string) $formation->getPrix(),
            'duree' => (string) $formation->getDuree(),
            'capaciteMax' => (string) $formation->getCapaciteMax(),
            'formateur' => $formation->getFormateur()?->getId() ?? '',
            'classe' => $formation->getClasse()?->getId() ?? '',
            'dateDebut' => $formation->getDateDebut()->format('Y-m-d'),
            'dateFin' => $formation->getDateFin()->format('Y-m-d'),
            'statut' => $formation->getStatut(),
        ];

        if ($request->isMethod('POST')) {
            [$data, $errors, $formateur, $classe] = $this->lireEtValider($request, $dm);
            if (!$errors) {
                $this->appliquer($formation, $data, $formateur, $classe);
                $dm->flush();
                $this->addFlash('success', 'Formation mise à jour.');

                return $this->redirectToRoute('admin_formation_show', ['id' => $formation->getId()]);
            }
        }

        return $this->render('formation/form.html.twig', $this->contexteForm($dm, $formation, $data, $errors, 'Modifier', 'admin_formation_edit'));
    }

    // ADMIN : fiche détail d'une formation
    #[Route('/admin/formations/{id}/show', name: 'admin_formation_show')]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Formation $formation, DocumentManager $dm): Response
    {
        $inscriptions = $dm->getRepository(Inscription::class)->findBy(['formation' => $formation]);

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
            'inscriptions' => $inscriptions,
        ]);
    }

    // ETUDIANT : détail d'une formation du catalogue
    #[Route('/etudiant/catalogue/{id}', name: 'etudiant_catalogue_show')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function catalogueShow(Formation $formation, DocumentManager $dm): Response
    {
        $nbInscrits = count($dm->getRepository(Inscription::class)->findBy(['formation' => $formation]));

        return $this->render('formation/catalogue_show.html.twig', [
            'formation' => $formation,
            'placesRestantes' => max(0, $formation->getCapaciteMax() - $nbInscrits),
        ]);
    }

    /** @return array<string,string> */
    private function donneesVides(): array
    {
        return [
            'titre' => '', 'description' => '', 'categorie' => '', 'prix' => '', 'duree' => '',
            'capaciteMax' => '', 'formateur' => '', 'classe' => '', 'dateDebut' => '', 'dateFin' => '', 'statut' => 'OUVERTE',
        ];
    }

    /**
     * @return array{0: array<string,string>, 1: string[], 2: ?Formateur, 3: ?Classe}
     */
    private function lireEtValider(Request $request, DocumentManager $dm): array
    {
        $errors = [];
        if (!$this->isCsrfTokenValid('formation_form', (string) $request->request->get('_csrf_token'))) {
            $errors[] = 'Jeton de sécurité invalide.';
        }

        $data = [
            'titre' => trim((string) $request->request->get('titre')),
            'description' => trim((string) $request->request->get('description')),
            'categorie' => trim((string) $request->request->get('categorie')),
            'prix' => trim((string) $request->request->get('prix')),
            'duree' => trim((string) $request->request->get('duree')),
            'capaciteMax' => trim((string) $request->request->get('capaciteMax')),
            'formateur' => trim((string) $request->request->get('formateur')),
            'classe' => trim((string) $request->request->get('classe')),
            'dateDebut' => trim((string) $request->request->get('dateDebut')),
            'dateFin' => trim((string) $request->request->get('dateFin')),
            'statut' => trim((string) $request->request->get('statut')),
        ];

        if ('' === $data['titre']) {
            $errors[] = 'Le titre est obligatoire.';
        }
        if (!in_array($data['categorie'], self::CATEGORIES, true)) {
            $errors[] = 'Catégorie invalide.';
        }
        if (!is_numeric($data['prix']) || (float) $data['prix'] < 0) {
            $errors[] = 'Prix invalide.';
        }
        if (!ctype_digit($data['duree']) || (int) $data['duree'] < 1) {
            $errors[] = 'Durée invalide (en semaines).';
        }
        if (!ctype_digit($data['capaciteMax']) || (int) $data['capaciteMax'] < 1) {
            $errors[] = 'Capacité invalide.';
        }
        if ('' === $data['dateDebut'] || '' === $data['dateFin']) {
            $errors[] = 'Les dates de début et de fin sont obligatoires.';
        }
        if (!in_array($data['statut'], self::STATUTS, true)) {
            $data['statut'] = 'OUVERTE';
        }

        $formateur = null;
        if ('' !== $data['formateur']) {
            $formateur = $dm->getRepository(Formateur::class)->find($data['formateur']);
        }
        $classe = null;
        if ('' !== $data['classe']) {
            $classe = $dm->getRepository(Classe::class)->find($data['classe']);
        }

        return [$data, $errors, $formateur, $classe];
    }

    /** @param array<string,string> $data */
    private function appliquer(Formation $formation, array $data, ?Formateur $formateur, ?Classe $classe): void
    {
        $formation->setClasse($classe);
        $formation->setTitre($data['titre']);
        $formation->setDescription($data['description']);
        $formation->setCategorie($data['categorie']);
        $formation->setPrix((float) $data['prix']);
        $formation->setDuree((int) $data['duree']);
        $formation->setCapaciteMax((int) $data['capaciteMax']);
        $formation->setFormateur($formateur);
        $formation->setDateDebut(new \DateTimeImmutable($data['dateDebut']));
        $formation->setDateFin(new \DateTimeImmutable($data['dateFin']));
        $formation->setStatut($data['statut']);
    }

    /**
     * @param array<string,string> $data
     * @param string[] $errors
     * @return array<string,mixed>
     */
    private function contexteForm(DocumentManager $dm, ?Formation $formation, array $data, array $errors, string $action, string $route): array
    {
        return [
            'formation' => $formation,
            'formationData' => $data,
            'errors' => $errors,
            'formAction' => $action,
            'formRoute' => $route,
            'categories' => self::CATEGORIES,
            'statuts' => self::STATUTS,
            'formateurs' => $dm->getRepository(Formateur::class)->findBy([], ['nom' => 'asc']),
            'classes' => $dm->getRepository(Classe::class)->findBy([], ['nom' => 'asc']),
        ];
    }
}