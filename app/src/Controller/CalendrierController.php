<?php

namespace App\Controller;

use App\Document\Formation;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CalendrierController extends AbstractController
{
    private const MOIS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    #[Route('/calendrier', name: 'app_calendrier')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(DocumentManager $dm): Response
    {
        $utilisateur = $this->getUser();
        $role = $utilisateur instanceof Utilisateur ? strtoupper($utilisateur->getRole()) : '';
        $shell = match ($role) {
            'ADMIN' => 'layout/_shell_admin.html.twig',
            'FORMATEUR' => 'layout/_shell_formateur.html.twig',
            default => 'layout/_shell_etudiant.html.twig',
        };

        $formations = $dm->getRepository(Formation::class)->findBy([], ['dateDebut' => 'asc']);

        $parMois = [];
        foreach ($formations as $f) {
            $cle = $f->getDateDebut()->format('Y-m');
            if (!isset($parMois[$cle])) {
                $parMois[$cle] = [
                    'label' => self::MOIS[(int) $f->getDateDebut()->format('n')] . ' ' . $f->getDateDebut()->format('Y'),
                    'formations' => [],
                ];
            }
            $parMois[$cle]['formations'][] = $f;
        }

        return $this->render('calendrier/index.html.twig', [
            'shell' => $shell,
            'mois' => array_values($parMois),
        ]);
    }
}
