<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un compte administrateur',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un compte administrateur');

        // Vérification du nombre maximal de comptes
        $count = count($this->userRepository->findAll());
        if ($count >= 2) {
            $io->error('Le nombre maximum de comptes admin (2) est déjà atteint.');
            return Command::FAILURE;
        }

        $username = $io->ask('Nom d\'utilisateur (identifiant de connexion)');
        if (empty($username)) {
            $io->error('Le nom d\'utilisateur ne peut pas être vide.');
            return Command::FAILURE;
        }
        if ($this->userRepository->findOneBy(['username' => $username])) {
            $io->error(sprintf('Un utilisateur avec le nom "%s" existe déjà.', $username));
            return Command::FAILURE;
        }

        $email = $io->ask('Adresse email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresse email invalide.');
            return Command::FAILURE;
        }
        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('Un utilisateur avec l\'email "%s" existe déjà.', $email));
            return Command::FAILURE;
        }

        $password = $io->askHidden('Mot de passe (masqué)');
        if (empty($password) || strlen($password) < 8) {
            $io->error('Le mot de passe doit contenir au moins 8 caractères.');
            return Command::FAILURE;
        }

        $confirm = $io->askHidden('Confirmer le mot de passe');
        if ($password !== $confirm) {
            $io->error('Les mots de passe ne correspondent pas.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Compte admin "%s" créé avec succès (%d/2).', $username, $count + 1));

        return Command::SUCCESS;
    }
}
