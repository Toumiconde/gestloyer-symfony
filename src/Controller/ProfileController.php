<?php

namespace App\Controller;

use App\Entity\Proprietaire;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Photo upload ──────────────────────────────────────────────
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                $oldPhoto = $user->getPhotoFilename();
                if ($oldPhoto) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $oldPhoto;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $photoFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                    $newFilename
                );

                $user->setPhotoFilename($newFilename);
            }

            // ── Mot de passe ──────────────────────────────────────────────
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            // ── Synchronisation avec l'entité Proprietaire ────────────────
            // Si l'utilisateur est un propriétaire, on synchronise ses données
            // vers l'entité Proprietaire pour que isProfileComplete() = true
            if ($user->getRole()->value === 'ROLE_PROPRIETAIRE') {
                $proprietaire = $user->getProprietaire();

                // Si le Proprietaire n'existe pas encore, on le crée
                if (!$proprietaire) {
                    $proprietaire = new Proprietaire();
                    $proprietaire->setUser($user);
                    $entityManager->persist($proprietaire);
                }

                // Synchroniser nom, prenom, telephone depuis User → Proprietaire
                if ($user->getNom()) {
                    $proprietaire->setNom($user->getNom());
                }
                if ($user->getPrenom()) {
                    $proprietaire->setPrenom($user->getPrenom());
                }
                if ($user->getTelephone()) {
                    $proprietaire->setTelephone($user->getTelephone());
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            // Rediriger vers le bon dashboard selon le rôle
            if ($user->getRole()->value === 'ROLE_PROPRIETAIRE') {
                return $this->redirectToRoute('app_dashboard_proprietaire');
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}