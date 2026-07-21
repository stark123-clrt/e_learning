<?php

namespace App\Controller;

use App\Document\Etudiant;
use App\Document\Formateur;
use App\Document\Formation;
use App\Document\Inscription;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class StatistiqueController extends AbstractController
{
    #[Route('/admin/statistiques', name: 'admin_statistiques')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(DocumentManager $dm): Response
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
            if (null !== $i->getFormation()) {
                $fid = $i->getFormation()->getId();
                $countByFormation[$fid] = ($countByFormation[$fid] ?? 0) + 1;
            }
            if (null !== $i->getNote()) {
                $notesGlobal[] = $i->getNote();
                if (null !== $i->getEtudiant()) {
                    $eid = $i->getEtudiant()->getId();
                    $noteByEtudiant[$eid]['sum'] = ($noteByEtudiant[$eid]['sum'] ?? 0) + $i->getNote();
                    $noteByEtudiant[$eid]['count'] = ($noteByEtudiant[$eid]['count'] ?? 0) + 1;
                }
            }
        }

        arsort($countByFormation);
        $maxInscr = $countByFormation ? max($countByFormation) : 1;

        $inscriptionsParFormation = [];
        foreach ($countByFormation as $fid => $count) {
            $inscriptionsParFormation[] = [
                'titre' => isset($formationById[$fid]) ? $formationById[$fid]->getTitre() : '—',
                'count' => $count,
                'width' => (int) round($count / $maxInscr * 100),
            ];
        }
        $topFormations = array_slice($inscriptionsParFormation, 0, 5);

        arsort($countByCategorie);

        $moyennes = [];
        foreach ($noteByEtudiant as $eid => $d) {
            $moyennes[$eid] = $d['sum'] / $d['count'];
        }
        arsort($moyennes);
        $topStudents = [];
        $rang = 1;
        foreach (array_slice($moyennes, 0, 5, true) as $eid => $moy) {
            $e = $etudiantById[$eid] ?? null;
            $topStudents[] = [
                'rang' => $rang++,
                'nom' => $e ? trim($e->getPrenom() . ' ' . $e->getNom()) : '—',
                'moyenne' => number_format($moy, 1, ',', ' '),
            ];
        }

        return $this->render('statistique/index.html.twig', [
            'totalEtudiants' => count($etudiants),
            'totalFormateurs' => $totalFormateurs,
            'totalFormations' => count($formations),
            'totalInscriptions' => count($inscriptions),
            'prixMoyen' => $formations ? number_format($prixSum / count($formations), 0, ',', ' ') : '0',
            'noteMoyenne' => $notesGlobal ? number_format(array_sum($notesGlobal) / count($notesGlobal), 1, ',', ' ') : '—',
            'inscriptionsParFormation' => $inscriptionsParFormation,
            'formationsParCategorie' => $countByCategorie,
            'topFormations' => $topFormations,
            'topStudents' => $topStudents,
        ]);
    }
}
