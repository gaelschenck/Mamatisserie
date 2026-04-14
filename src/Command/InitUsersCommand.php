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
    name: 'app:init-users',
    description: 'Crée les deux comptes administrateurs par défaut (à exécuter une seule fois)',
)]
class InitUsersCommand extends Command
{
    private const USERS = [
        ['username' => 'gael',  'email' => 'gaelschenck@hotmail.fr',  'password' => 'admin'],
        ['username' => 'chloe', 'email' => 'chloeberard81@gmail.com',   'password' => 'admin'],
    ];

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

        $io->title('Initialisation des comptes administrateurs');

        $created = 0;

        foreach (self::USERS as $data) {
            if ($this->userRepository->findOneBy(['email' => $data['email']])
                || $this->userRepository->findOneBy(['username' => $data['username']])) {
                $io->warning(sprintf('Compte "%s" (%s) déjà existant — ignoré.', $data['username'], $data['email']));
                continue;
            }

            $user = new User();
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));

            $this->em->persist($user);
            $io->text(sprintf('✓ Compte créé : %s (%s)', $data['username'], $data['email']));
            $created++;
        }

        if ($created > 0) {
            $this->em->flush();
            $io->success(sprintf('%d compte(s) créé(s). Pensez à changer les mots de passe !', $created));
        } else {
            $io->info('Aucun nouveau compte créé.');
        }

        return Command::SUCCESS;
    }
}
