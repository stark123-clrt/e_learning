<?php

namespace App\Controller;

use App\Document\Cours;
use App\Document\Etudiant;
use App\Document\Formation;
use App\Document\Inscription;
use App\Document\Progression;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CoursController extends AbstractController
{
    // ADMIN : gérer les cours d'une formation (liste + ajout)
    #[Route('/admin/formations/{id}/cours', name: 'admin_formation_cours')]
    #[IsGranted('ROLE_ADMIN')]
    public function gerer(Formation $formation, Request $request, DocumentManager $dm): Response
    {
        $errors = $this->traiterAjoutCours($formation, $request, $dm, 'admin_formation_cours');
        if ($errors instanceof Response) {
            return $errors;
        }

        return $this->render('cours/gerer.html.twig', [
            'formation' => $formation,
            'cours' => $dm->getRepository(Cours::class)->findBy(['formation' => $formation], ['ordre' => 'asc']),
            'errors' => $errors,
            'shell' => 'layout/_shell_admin.html.twig',
            'navActif' => 'formations',
            'addRoute' => 'admin_formation_cours',
            'deleteRoute' => 'admin_cours_delete',
            'backUrl' => $this->generateUrl('admin_formation_show', ['id' => $formation->getId()]),
        ]);
    }

    // FORMATEUR : gérer les cours de SES formations
    #[Route('/formateur/formation/{id}/cours', name: 'formateur_formation_cours')]
    #[IsGranted('ROLE_FORMATEUR')]
    public function gererFormateur(Formation $formation, Request $request, DocumentManager $dm): Response
    {
        $this->verifierProprietaire($dm, $formation);

        $errors = $this->traiterAjoutCours($formation, $request, $dm, 'formateur_formation_cours');
        if ($errors instanceof Response) {
            return $errors;
        }

        return $this->render('cours/gerer.html.twig', [
            'formation' => $formation,
            'cours' => $dm->getRepository(Cours::class)->findBy(['formation' => $formation], ['ordre' => 'asc']),
            'errors' => $errors,
            'shell' => 'layout/_shell_formateur.html.twig',
            'navActif' => 'formations',
            'addRoute' => 'formateur_formation_cours',
            'deleteRoute' => 'formateur_cours_delete',
            'backUrl' => $this->generateUrl('formateur_formations'),
        ]);
    }

    #[Route('/formateur/cours/{id}/delete', name: 'formateur_cours_delete', methods: ['POST'])]
    #[IsGranted('ROLE_FORMATEUR')]
    public function deleteFormateur(Cours $cours, Request $request, DocumentManager $dm): Response
    {
        $formation = $cours->getFormation();
        if (null !== $formation) {
            $this->verifierProprietaire($dm, $formation);
        }
        if ($this->isCsrfTokenValid('delete_cours_' . $cours->getId(), (string) $request->request->get('_csrf_token'))) {
            foreach ($dm->getRepository(Progression::class)->findBy(['cours' => $cours]) as $p) {
                $dm->remove($p);
            }
            $dm->remove($cours);
            $dm->flush();
            $this->addFlash('success', 'Cours supprimé.');
        }

        return $this->redirectToRoute('formateur_formation_cours', ['id' => $formation?->getId()]);
    }

    #[Route('/admin/cours/{id}/delete', name: 'admin_cours_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Cours $cours, Request $request, DocumentManager $dm): Response
    {
        $formationId = $cours->getFormation()?->getId();
        if ($this->isCsrfTokenValid('delete_cours_' . $cours->getId(), (string) $request->request->get('_csrf_token'))) {
            foreach ($dm->getRepository(Progression::class)->findBy(['cours' => $cours]) as $p) {
                $dm->remove($p);
            }
            $dm->remove($cours);
            $dm->flush();
            $this->addFlash('success', 'Cours supprimé.');
        }

        return $this->redirectToRoute('admin_formation_cours', ['id' => $formationId]);
    }

    // ETUDIANT : suivre les cours d'une formation (doit être inscrit)
    #[Route('/etudiant/formation/{id}/cours', name: 'etudiant_formation_cours')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function suivre(Formation $formation, DocumentManager $dm): Response
    {
        $etudiant = $this->etudiantCourant($dm);
        if (!$etudiant instanceof Etudiant || !$this->estInscrit($dm, $etudiant, $formation)) {
            throw $this->createAccessDeniedException('Vous devez être inscrit à cette formation.');
        }

        $cours = $dm->getRepository(Cours::class)->findBy(['formation' => $formation], ['ordre' => 'asc']);
        $termines = [];
        foreach ($dm->getRepository(Progression::class)->findBy(['etudiant' => $etudiant]) as $p) {
            if (null !== $p->getCours()) {
                $termines[$p->getCours()->getId()] = true;
            }
        }
        $nbTermines = 0;
        foreach ($cours as $c) {
            if (isset($termines[$c->getId()])) {
                ++$nbTermines;
            }
        }

        return $this->render('cours/suivre.html.twig', [
            'formation' => $formation,
            'cours' => $cours,
            'termines' => $termines,
            'progression' => $cours ? (int) round($nbTermines / count($cours) * 100) : 0,
        ]);
    }

    #[Route('/etudiant/cours/{id}/terminer', name: 'etudiant_cours_terminer', methods: ['POST'])]
    #[IsGranted('ROLE_ETUDIANT')]
    public function terminer(Cours $cours, Request $request, DocumentManager $dm): Response
    {
        $etudiant = $this->etudiantCourant($dm);
        $formation = $cours->getFormation();

        if (!$etudiant instanceof Etudiant || null === $formation || !$this->estInscrit($dm, $etudiant, $formation)) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('terminer_' . $cours->getId(), (string) $request->request->get('_csrf_token'))) {
            return $this->redirectToRoute('etudiant_formation_cours', ['id' => $formation->getId()]);
        }

        $existe = $dm->getRepository(Progression::class)->findOneBy(['etudiant' => $etudiant, 'cours' => $cours]);
        if (null === $existe) {
            $progression = new Progression();
            $progression->setEtudiant($etudiant);
            $progression->setCours($cours);
            $dm->persist($progression);
            $dm->flush();
        }
        $this->addFlash('success', 'Cours marqué comme terminé.');

        return $this->redirectToRoute('etudiant_formation_cours', ['id' => $formation->getId()]);
    }

    /**
     * @return string[]|Response Liste d'erreurs, ou une redirection en cas de succès.
     */
    private function traiterAjoutCours(Formation $formation, Request $request, DocumentManager $dm, string $redirectRoute): array|Response
    {
        if (!$request->isMethod('POST')) {
            return [];
        }

        $errors = [];
        if (!$this->isCsrfTokenValid('cours_form', (string) $request->request->get('_csrf_token'))) {
            $errors[] = 'Jeton de sécurité invalide.';
        }
        $titre = trim((string) $request->request->get('titre'));
        $videoUrl = trim((string) $request->request->get('videoUrl'));
        $description = trim((string) $request->request->get('description'));
        $ordre = (int) $request->request->get('ordre');

        if ('' === $titre) {
            $errors[] = 'Le titre du cours est obligatoire.';
        }
        if ('' === $videoUrl) {
            $errors[] = 'Le lien de la vidéo est obligatoire.';
        }

        if (!$errors) {
            $cours = new Cours();
            $cours->setTitre($titre);
            $cours->setDescription($description);
            $cours->setVideoUrl($videoUrl);
            $cours->setOrdre($ordre > 0 ? $ordre : 1);
            $cours->setFormation($formation);
            $dm->persist($cours);
            $dm->flush();
            $this->addFlash('success', 'Cours ajouté.');

            return $this->redirectToRoute($redirectRoute, ['id' => $formation->getId()]);
        }

        return $errors;
    }

    private function verifierProprietaire(DocumentManager $dm, Formation $formation): void
    {
        $u = $this->getUser();
        $formateurId = $u instanceof Utilisateur ? $u->getProfilId() : null;
        if (null === $formation->getFormateur() || $formation->getFormateur()->getId() !== $formateurId) {
            throw $this->createAccessDeniedException('Cette formation ne vous est pas attribuée.');
        }
    }

    private function estInscrit(DocumentManager $dm, Etudiant $etudiant, Formation $formation): bool
    {
        return null !== $dm->getRepository(Inscription::class)->findOneBy([
            'etudiant' => $etudiant,
            'formation' => $formation,
        ]);
    }

    private function etudiantCourant(DocumentManager $dm): ?Etudiant
    {
        $u = $this->getUser();
        if (!$u instanceof Utilisateur || null === $u->getProfilId()) {
            return null;
        }

        return $dm->getRepository(Etudiant::class)->find($u->getProfilId());
    }
}
