<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-users',
    description: 'Crée les utilisateurs Admin, Gestionnaire et Comptable par défaut',
)]
class CreateUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $password = 'password123';

        $users = [
            [
                'email' => 'admin@gestloyer.com',
                'role' => RoleUtilisateur::ADMIN,
                'roles' => ['ROLE_ADMIN']
            ],
            [
                'email' => 'gestionnaire@gestloyer.com',
                'role' => RoleUtilisateur::GESTIONNAIRE,
                'roles' => ['ROLE_GESTIONNAIRE']
            ],
            [
                'email' => 'comptable@gestloyer.com',
                'role' => RoleUtilisateur::COMPTABLE,
                'roles' => ['ROLE_COMPTABLE']
            ]
        ];

        foreach ($users as $userData) {
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            
            if ($existingUser) {
                $io->warning(sprintf('L\'utilisateur %s existe déjà. Son mot de passe va être réinitialisé.', $userData['email']));
                $user = $existingUser;
            } else {
                $user = new User();
                $user->setEmail($userData['email']);
            }

            $user->setRole($userData['role']);
            
            // On a besoin de bypasser la logique isProfileComplete si nécessaire
            $user->setNom('Staff');
            $user->setPrenom(ucfirst(strtolower($userData['role']->name)));
            $user->setTelephone('000000000');

            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        $io->success('Les utilisateurs ont été créés/mis à jour avec succès !');
        $io->section('Identifiants de connexion :');
        $io->text('Tous les comptes ont le même mot de passe : password123');
        $io->text('- Admin : admin@gestloyer.com');
        $io->text('- Gestionnaire : gestionnaire@gestloyer.com');
        $io->text('- Comptable : comptable@gestloyer.com');

        return Command::SUCCESS;
    }
}
