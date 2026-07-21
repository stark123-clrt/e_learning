<?php

namespace App\Controller;

use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function forgot(
        Request $request,
        DocumentManager $dm,
        MailerInterface $mailer,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(MAILER_FROM)%')] string $mailerFrom,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide.');

                return $this->redirectToRoute('app_forgot_password');
            }

            $email = mb_strtolower(trim((string) $request->request->get('email')));
            $utilisateur = $dm->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

            if ($utilisateur instanceof Utilisateur) {
                $token = bin2hex(random_bytes(32));
                $utilisateur->setResetToken($token);
                $utilisateur->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $dm->flush();

                $lien = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
                );

                $mail = (new Email())
                    ->from($mailerFrom)
                    ->to($utilisateur->getEmail())
                    ->subject('Réinitialisation de votre mot de passe — e-learning')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'lien' => $lien,
                    ]));

                $mailer->send($mail);
            }

            // Message identique que l'email existe ou non (sécurité).
            $this->addFlash('success', 'Si un compte existe pour cet email, un lien de réinitialisation vient d\'être envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot.html.twig');
    }

    #[Route('/definir-mot-de-passe/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        DocumentManager $dm,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $utilisateur = $dm->getRepository(Utilisateur::class)->findOneBy(['resetToken' => $token]);

        $tokenValide = $utilisateur instanceof Utilisateur
            && null !== $utilisateur->getResetTokenExpiresAt()
            && $utilisateur->getResetTokenExpiresAt() > new \DateTimeImmutable();

        if (!$tokenValide) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré. Veuillez refaire une demande.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Jeton de sécurité invalide.';
            }

            $password = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if (strlen($password) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if (!$errors) {
                $utilisateur->setMotDePasse($passwordHasher->hashPassword($utilisateur, $password));
                $utilisateur->setResetToken(null);
                $utilisateur->setResetTokenExpiresAt(null);
                $dm->flush();

                $this->addFlash('success', 'Votre mot de passe a été mis à jour. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset.html.twig', [
            'token' => $token,
            'errors' => $errors,
        ]);
    }
}
