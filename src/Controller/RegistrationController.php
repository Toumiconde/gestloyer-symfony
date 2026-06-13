<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use App\Entity\Proprietaire;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use App\Service\ActivityLogService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher,
        ActivityLogService $activityLogService
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email     = trim((string) $request->request->get('email'));
            $password  = (string) $request->request->get('password');
            $type      = (string) $request->request->get('type');
            $nom       = trim((string) $request->request->get('nom'));
            $prenom    = trim((string) $request->request->get('prenom'));
            $telephone = trim((string) $request->request->get('telephone'));

            // Validations
            if ($nom === '' || $prenom === '') {
                $this->addFlash('error', 'Veuillez renseigner votre nom et prénom.');
                return $this->redirectToRoute('app_register');
            }

            if ($telephone === '') {
                $this->addFlash('error', 'Veuillez renseigner votre numéro de téléphone.');
                return $this->redirectToRoute('app_register');
            }

            if ($email === '' || !str_contains($email, '@') || strlen($password) < 6) {
                $this->addFlash('error', 'Email invalide ou mot de passe trop court (min 6 caractères).');
                return $this->redirectToRoute('app_register');
            }

            if (!in_array($type, ['locataire', 'proprietaire'], true)) {
                $this->addFlash('error', 'Veuillez choisir un type de compte.');
                return $this->redirectToRoute('app_register');
            }

            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing) {
                $this->addFlash('error', "Un compte existe déjà avec l'email $email.");
                return $this->redirectToRoute('app_register');
            }

            $role = $type === 'locataire' ? RoleUtilisateur::LOCATAIRE : RoleUtilisateur::PROPRIETAIRE;

            $user = new User();
            $user->setEmail($email);
            $user->setRole($role);
            $user->setIsActive(true);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setTelephone($telephone);
            $user->setPassword($hasher->hashPassword($user, $password));



                        // Crée le lien Proprietaire pour les propriétaires
            if ($role === RoleUtilisateur::PROPRIETAIRE) {
                                $proprietaire = new Proprietaire();
                $proprietaire->setUser($user);
                // Populate required fields on Proprietaire
                $proprietaire->setNom($nom);
                $proprietaire->setPrenom($prenom);
                $proprietaire->setTelephone($telephone);
                $em->persist($proprietaire);
            }

            $em->persist($user);
            $em->flush();

            // Authentifier automatiquement l'utilisateur fraîchement créé
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));

            // Redirection selon le rôle
            if ($role === RoleUtilisateur::PROPRIETAIRE) {
                return $this->redirectToRoute('app_dashboard_proprietaire');
            }
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('security/register.html.twig');
    }

}
