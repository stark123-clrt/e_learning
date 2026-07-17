<?php

namespace App\Command;

use App\Document\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Crée un compte utilisateur (admin, formateur ou étudiant) pour la démonstration')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du compte')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair')
            ->addArgument('role', InputArgument::REQUIRED, 'ADMIN, FORMATEUR ou ETUDIANT');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $password = (string) $input->getArgument('password');
        $role = strtoupper((string) $input->getArgument('role'));

        if (!in_array($role, ['ADMIN', 'FORMATEUR', 'ETUDIANT'], true)) {
            $io->error('Le rôle doit être ADMIN, FORMATEUR ou ETUDIANT.');

            return Command::FAILURE;
        }

        $existant = $this->documentManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if (null !== $existant) {
            $io->error(sprintf('Un utilisateur existe déjà avec l\'email "%s".', $email));

            return Command::FAILURE;
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($email);
        $utilisateur->setRole($role);
        $utilisateur->setStatut('ACTIF');
        $utilisateur->setMotDePasse($this->passwordHasher->hashPassword($utilisateur, $password));

        $this->documentManager->persist($utilisateur);
        $this->documentManager->flush();

        $io->success(sprintf('Compte %s créé : %s', $role, $email));

        return Command::SUCCESS;
    }
}
