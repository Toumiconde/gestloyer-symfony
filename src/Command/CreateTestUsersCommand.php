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
    name: 'app:create-test-users',
    description: 'Crée un utilisateur de test pour chaque rôle (Gestionnaire, Comptable, Propriétaire, Locataire)',
)]
class CreateTestUsersCommand extends Command
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

        $usersData = [
            [
                'email' => 'gestionnaire@gestloyer.com',
                'role' => RoleUtilisateur::GESTIONNAIRE,
            ],
            [
                'email' => 'comptable@gestloyer.com',
                'role' => RoleUtilisateur::COMPTABLE,
            ],
            [
                'email' => 'proprietaire@gestloyer.com',
                'role' => RoleUtilisateur::PROPRIETAIRE,
            ],
            [
                'email' => 'locataire@gestloyer.com',
                'role' => RoleUtilisateur::LOCATAIRE,
            ]
        ];

        $password = 'test1234';

        foreach ($usersData as $data) {
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            
            if ($existingUser) {
                $io->warning(sprintf("L'utilisateur %s existe déjà.", $data['email']));
                continue;
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setRole($data['role']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $io->text(sprintf("Création de l'utilisateur %s avec le rôle %s", $data['email'], $data['role']->value));
        }

        $this->entityManager->flush();

        $io->success('Tous les comptes de test ont été créés avec succès !');
        $io->note("Le mot de passe pour tous les comptes est : " . $password);

        return Command::SUCCESS;
    }
}
