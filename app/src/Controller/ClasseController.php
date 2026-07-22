<?php

namespace App\Controller;

use App\Document\Classe;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClasseController extends AbstractController
{
    #[Route('/admin/classes', name: 'admin_classes')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DocumentManager $dm): Response
    {
        $classes = $dm->getRepository(Classe::class)->findBy([], ['nom' => 'asc']);

        return $this->render('classe/index.html.twig', [
            'classes' => $classes,
        ]);
    }

    #[Route('/admin/classes/new', name: 'admin_classe_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $classeData = ['nom' => '', 'niveau' => '', 'description' => ''];

        if ($request->isMethod('POST')) {
            [$classeData, $errors] = $this->lireEtValider($request, $dm, null);

            if (!$errors) {
                $classe = new Classe();
                $classe->setNom($classeData['nom']);
                $classe->setNiveau($classeData['niveau']);
                $classe->setDescription($classeData['description']);
                $dm->persist($classe);
                $dm->flush();

                $this->addFlash('success', 'Classe créée avec succès.');

                return $this->redirectToRoute('admin_classes');
            }
        }

        return $this->render('classe/form.html.twig', [
            'classe' => null,
            'classeData' => $classeData,
            'errors' => $errors,
            'formAction' => 'Créer',
            'formRoute' => 'admin_classe_new',
        ]);
    }

    #[Route('/admin/classes/{id}/edit', name: 'admin_classe_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Classe $classe, Request $request, DocumentManager $dm): Response
    {
        $errors = [];
        $classeData = [
            'nom' => $classe->getNom(),
            'niveau' => $classe->getNiveau(),
            'description' => $classe->getDescription(),
        ];

        if ($request->isMethod('POST')) {
            [$classeData, $errors] = $this->lireEtValider($request, $dm, $classe);

            if (!$errors) {
                $classe->setNom($classeData['nom']);
                $classe->setNiveau($classeData['niveau']);
                $classe->setDescription($classeData['description']);
                $dm->flush();

                $this->addFlash('success', 'Classe mise à jour avec succès.');

                return $this->redirectToRoute('admin_classes');
            }
        }

        return $this->render('classe/form.html.twig', [
            'classe' => $classe,
            'classeData' => $classeData,
            'errors' => $errors,
            'formAction' => 'Modifier',
            'formRoute' => 'admin_classe_edit',
        ]);
    }

    #[Route('/admin/classes/{id}/delete', name: 'admin_classe_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Classe $classe, Request $request, DocumentManager $dm): Response
    {
        if (!$this->isCsrfTokenValid('delete_classe_' . $classe->getId(), (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('admin_classes');
        }

        $dm->remove($classe);
        $dm->flush();

        $this->addFlash('success', 'Classe supprimée.');

        return $this->redirectToRoute('admin_classes');
    }

    /**
     * @return array{0: array{nom: string, niveau: string, description: string}, 1: string[]}
     */
    private function lireEtValider(Request $request, DocumentManager $dm, ?Classe $classeCourante): array
    {
        $errors = [];

        if (!$this->isCsrfTokenValid('classe_form', (string) $request->request->get('_csrf_token'))) {
            $errors[] = 'Jeton de sécurité invalide.';
        }

        $classeData = [
            'nom' => trim((string) $request->request->get('nom')),
            'niveau' => trim((string) $request->request->get('niveau')),
            'description' => trim((string) $request->request->get('description')),
        ];

        if ('' === $classeData['nom']) {
            $errors[] = 'Le nom de la classe est obligatoire.';
        }

        if ('' !== $classeData['nom']) {
            $existante = $dm->getRepository(Classe::class)->findOneBy(['nom' => $classeData['nom']]);
            if (null !== $existante && (null === $classeCourante || $existante->getId() !== $classeCourante->getId())) {
                $errors[] = 'Une classe porte déjà ce nom.';
            }
        }

        return [$classeData, $errors];
    }
}
