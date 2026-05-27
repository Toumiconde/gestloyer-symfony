<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

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
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $type = (string) $request->request->get('type'); // locataire|proprietaire

            if ($email === '' || !str_contains($email, '@') || strlen($password) < 6) {
                $this->addFlash('error', 'Email invalide ou mot de passe trop court (min 6).');
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
            $user->setPassword($hasher->hashPassword($user, $password));

            $em->persist($user);
            $em->flush();

            $activityLogService->log(
                action: 'REGISTER',
                actor: null,
                targetEmail: $email,
                details: 'Inscription ' . $role->value
            );

            $this->addFlash('success', 'Compte créé. Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }
}

