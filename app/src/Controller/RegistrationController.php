<?php

namespace App\Controller;

use App\Document\Etudiant;
use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController extends AbstractController
{
    private const NIVEAUX_AUTORISES = ['L1', 'L2', 'L3', 'M1', 'M2'];

    #[Route('/inscription-compte', name: 'app_register')]
    public function register(Request $request, DocumentManager $documentManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $errors = [];
        $data = ['nom' => '', 'prenom' => '', 'email' => '', 'niveau' => ''];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Jeton de sécurité invalide, veuillez réessayer.';
            }

            $data['nom'] = trim((string) $request->request->get('nom'));
            $data['prenom'] = trim((string) $request->request->get('prenom'));
            $data['email'] = mb_strtolower(trim((string) $request->request->get('email')));
            $data['niveau'] = (string) $request->request->get('niveau');
            $password = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if ('' === $data['nom'] || '' === $data['prenom']) {
                $errors[] = 'Le nom et le prénom sont obligatoires.';
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse email invalide.';
            }
            if (!in_array($data['niveau'], self::NIVEAUX_AUTORISES, true)) {
                $errors[] = 'Niveau invalide.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if (!$errors && ('' !== $data['email'])) {
                $emailPris = $documentManager->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']])
                    ?? $documentManager->getRepository(Etudiant::class)->findOneBy(['email' => $data['email']]);

                if (null !== $emailPris) {
                    $errors[] = 'Un compte existe déjà avec cet email.';
                }
            }

            if (!$errors) {
                $etudiant = new Etudiant();
                $etudiant->setNom($data['nom']);
                $etudiant->setPrenom($data['prenom']);
                $etudiant->setEmail($data['email']);
                $etudiant->setNiveau($data['niveau']);
                $etudiant->setActif(true);
                $documentManager->persist($etudiant);
                $documentManager->flush();

                $utilisateur = new Utilisateur();
                $utilisateur->setEmail($data['email']);
                $utilisateur->setRole('ETUDIANT');
                $utilisateur->setStatut('EN_ATTENTE');
                $utilisateur->setProfilId($etudiant->getId());
                $utilisateur->setMotDePasse($passwordHasher->hashPassword($utilisateur, $password));
                $documentManager->persist($utilisateur);
                $documentManager->flush();

                $this->addFlash('success', 'Compte créé avec succès, vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'data' => $data,
        ]);
    }
}
