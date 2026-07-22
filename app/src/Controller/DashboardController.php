<?php

namespace App\Controller;

use App\Document\Cours;
use App\Document\Etudiant;
use App\Document\Formateur;
use App\Document\Formation;
use App\Document\Inscription;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    private const PALETTE = ['var(--indigo-500)', 'var(--success-500)', 'var(--warning-500)', '#A855F7', 'var(--gray-500)', 'var(--error-500)'];

    #[Route('/admin/dashboard', name: 'app_dashboard_admin')]
    public function admin(DocumentManager $dm): Response
    {
        $etudiants = $dm->getRepository(Etudiant::class)->findAll();
        $formations = $dm->getRepository(Formation::class)->findAll();
        $inscriptions = $dm->getRepository(Inscription::class)->findAll();
        $totalFormateurs = count($dm->getRepository(Formateur::class)->findAll());

        $formationById = [];
        $countByCategorie = [];
        $prixSum = 0.0;
        foreach ($formations as $f) {
            $formationById[$f->getId()] = $f;
            $cat = $f->getCategorie() ?: 'Autre';
            $countByCategorie[$cat] = ($countByCategorie[$cat] ?? 0) + 1;
            $prixSum += $f->getPrix();
        }

        $etudiantById = [];
        foreach ($etudiants as $e) {
            $etudiantById[$e->getId()] = $e;
        }

        $countByFormation = [];
        $notesGlobal = [];
        $noteByEtudiant = [];
        foreach ($inscriptions as $i) {
            $formation = $i->getFormation();
            if (null !== $formation) {
                $countByFormation[$formation->getId()] = ($countByFormation[$formation->getId()] ?? 0) + 1;
            }
            $note = $i->getNote();
            if (null !== $note) {
                $notesGlobal[] = $note;
                $etudiant = $i->getEtudiant();
                if (null !== $etudiant) {
                    $noteByEtudiant[$etudiant->getId()]['sum'] = ($noteByEtudiant[$etudiant->getId()]['sum'] ?? 0) + $note;
                    $noteByEtudiant[$etudiant->getId()]['count'] = ($noteByEtudiant[$etudiant->getId()]['count'] ?? 0) + 1;
                }
            }
        }

        arsort($countByFormation);
        $maxInscriptions = $countByFormation ? max($countByFormation) : 1;

        // Graphique en barres : inscriptions par formation (top 6)
        $barChartData = [];
        foreach (array_slice($countByFormation, 0, 6, true) as $fid => $count) {
            $titre = isset($formationById[$fid]) ? $formationById[$fid]->getTitre() : '—';
            $barChartData[] = [
                'label' => $titre,
                'count' => $count,
                'height' => (int) round($count / $maxInscriptions * 100),
            ];
        }

        // Top 5 des formations les plus inscrites
        $topFormations = [];
        $rang = 1;
        foreach (array_slice($countByFormation, 0, 5, true) as $fid => $count) {
            $topFormations[] = [
                'rank' => $rang++,
                'label' => isset($formationById[$fid]) ? $formationById[$fid]->getTitre() : '—',
                'count' => $count,
                'width' => (int) round($count / $maxInscriptions * 100),
            ];
        }

        // Donut : formations par catégorie
        arsort($countByCategorie);
        $totalFormations = count($formations);
        $donutData = [];
        $gradientParts = [];
        $cumul = 0.0;
        $idx = 0;
        foreach ($countByCategorie as $categorie => $count) {
            $couleur = self::PALETTE[$idx % count(self::PALETTE)];
            $part = $totalFormations > 0 ? $count / $totalFormations * 100 : 0;
            $gradientParts[] = sprintf('%s %.2f%% %.2f%%', $couleur, $cumul, $cumul + $part);
            $cumul += $part;
            $donutData[] = ['label' => $categorie, 'count' => $count, 'color' => $couleur];
            ++$idx;
        }
        $donutGradient = $gradientParts
            ? 'conic-gradient(' . implode(', ', $gradientParts) . ')'
            : 'conic-gradient(var(--gray-200) 0 100%)';

        // Top 5 des meilleurs étudiants (par moyenne)
        $moyennes = [];
        foreach ($noteByEtudiant as $eid => $data) {
            $moyennes[$eid] = $data['sum'] / $data['count'];
        }
        arsort($moyennes);
        $topStudents = [];
        $rang = 1;
        foreach (array_slice($moyennes, 0, 5, true) as $eid => $moyenne) {
            $etudiant = $etudiantById[$eid] ?? null;
            $nom = $etudiant ? trim($etudiant->getPrenom() . ' ' . $etudiant->getNom()) : '—';
            $topStudents[] = [
                'rank' => $rang++,
                'name' => $nom,
                'initiales' => $this->initiales($nom),
                'moyenne' => $this->fmtNote($moyenne),
            ];
        }

        return $this->render('dashboard/admin.html.twig', [
            'totalEtudiants' => count($etudiants),
            'totalFormateurs' => $totalFormateurs,
            'totalFormations' => $totalFormations,
            'totalInscriptions' => count($inscriptions),
            'barChartData' => $barChartData,
            'donutData' => $donutData,
            'donutGradient' => $donutGradient,
            'avgPrice' => $totalFormations > 0 ? number_format($prixSum / $totalFormations, 0, ',', ' ') : '0',
            'avgNote' => $notesGlobal ? $this->fmtNote(array_sum($notesGlobal) / count($notesGlobal)) : '—',
            'topFormations' => $topFormations,
            'topStudents' => $topStudents,
        ]);
    }

    #[Route('/formateur/dashboard', name: 'app_dashboard_formateur')]
    public function formateur(DocumentManager $dm): Response
    {
        $formateur = $this->profilCourant($dm, Formateur::class);

        $mesFormations = [];
        if ($formateur instanceof Formateur) {
            $mesFormations = $dm->getRepository(Formation::class)->findBy(['formateur' => $formateur]);
        }

        $formationIds = array_map(static fn (Formation $f) => $f->getId(), $mesFormations);

        $etudiantsUniques = [];
        $notes = [];
        if ($formationIds) {
            foreach ($dm->getRepository(Inscription::class)->findAll() as $i) {
                $formation = $i->getFormation();
                if (null === $formation || !in_array($formation->getId(), $formationIds, true)) {
                    continue;
                }
                $etudiant = $i->getEtudiant();
                if (null !== $etudiant) {
                    $etudiantsUniques[$etudiant->getId()] = true;
                }
                if (null !== $i->getNote()) {
                    $notes[] = $i->getNote();
                }
            }
        }

        $formationsActives = 0;
        foreach ($mesFormations as $f) {
            if ('OUVERTE' === strtoupper($f->getStatut())) {
                ++$formationsActives;
            }
        }

        return $this->render('dashboard/formateur.html.twig', [
            'formationsActives' => $formationsActives,
            'totalEtudiants' => count($etudiantsUniques),
            'noteMoyenne' => $notes ? $this->fmtNote(array_sum($notes) / count($notes)) : '—',
            'formations' => $mesFormations,
        ]);
    }

    #[Route('/etudiant/dashboard', name: 'app_dashboard_etudiant')]
    public function etudiant(DocumentManager $dm): Response
    {
        $etudiant = $this->profilCourant($dm, Etudiant::class);
        $prenom = $etudiant instanceof Etudiant ? $etudiant->getPrenom() : '';

        $mesInscriptions = [];
        if ($etudiant instanceof Etudiant) {
            $mesInscriptions = $dm->getRepository(Inscription::class)->findBy(['etudiant' => $etudiant]);
        }

        $notes = [];
        $prochaine = null;
        $prochaineDate = null;
        $apercus = [];
        $maintenant = new \DateTimeImmutable();
        foreach ($mesInscriptions as $i) {
            if (null !== $i->getNote()) {
                $notes[] = $i->getNote();
            }
            $formation = $i->getFormation();
            if (null !== $formation) {
                // Aperçu = 1re vidéo de cours de la formation (comme dans le catalogue).
                if (!array_key_exists($formation->getId(), $apercus)) {
                    $premierCours = $dm->getRepository(Cours::class)->findOneBy(['formation' => $formation], ['ordre' => 'asc']);
                    $apercus[$formation->getId()] = $premierCours?->getVideoUrl();
                }
                if ($formation->getDateDebut() > $maintenant
                    && (null === $prochaineDate || $formation->getDateDebut() < $prochaineDate)) {
                    $prochaineDate = $formation->getDateDebut();
                    $prochaine = $formation;
                }
            }
        }

        return $this->render('dashboard/etudiant.html.twig', [
            'prenom' => $prenom,
            'formationsSuivies' => count($mesInscriptions),
            'moyenne' => $notes ? $this->fmtNote(array_sum($notes) / count($notes)) : '—',
            'prochaineFormation' => $prochaine,
            'inscriptions' => $mesInscriptions,
            'apercus' => $apercus,
        ]);
    }

    /**
     * @template T of object
     * @param class-string<T> $classe
     * @return T|null
     */
    private function profilCourant(DocumentManager $dm, string $classe): ?object
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur || null === $utilisateur->getProfilId()) {
            return null;
        }

        return $dm->getRepository($classe)->find($utilisateur->getProfilId());
    }

    private function initiales(string $nom): string
    {
        $mots = preg_split('/\s+/', trim($nom)) ?: [];
        $initiales = '';
        foreach (array_slice($mots, 0, 2) as $mot) {
            $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
        }

        return $initiales ?: '?';
    }

    private function fmtNote(float $note): string
    {
        return number_format($note, 1, ',', ' ');
    }
}
