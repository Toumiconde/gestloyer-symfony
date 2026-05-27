<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use App\Repository\ActivityLogRepository;
use App\Repository\BienRepository;
use App\Repository\ContratRepository;
use App\Repository\IncidentRepository;
use App\Repository\PaiementRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use App\Service\MessagingService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // =========================================================
    // PANEL PRINCIPAL
    // =========================================================

    #[Route('/', name: 'app_admin_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        BienRepository $bienRepository,
        ContratRepository $contratRepository,
        PaiementRepository $paiementRepository,
        IncidentRepository $incidentRepository
    ): Response {
        $stats = [
            'total_utilisateurs' => $userRepository->count([]),
            'utilisateurs_actifs' => $userRepository->count(['isActive' => true]),
            'total_biens'         => $bienRepository->count([]),
            'total_contrats'      => $contratRepository->count([]),
            'total_paiements'     => $paiementRepository->count([]),
            'incidents_ouverts'   => $incidentRepository->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.statut NOT IN (:statuts)')
                ->setParameter('statuts', [\App\Enum\StatutIncident::RESOLU, \App\Enum\StatutIncident::CLOTURE])
                ->getQuery()->getSingleScalarResult(),
        ];

        // Répartition des utilisateurs par rôle
        $parRole = [];
        foreach (RoleUtilisateur::cases() as $role) {
            $parRole[$role->value] = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.role = :role')
                ->setParameter('role', $role)
                ->getQuery()->getSingleScalarResult();
        }

        $derniers_utilisateurs = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $derniers_incidents = $incidentRepository->findBy([], ['dateDeclaration' => 'DESC'], 5);
        $derniers_paiements = $paiementRepository->findBy([], ['mois' => 'DESC'], 5);

        // Revenu global (total des paiements validés)
        $revenus_globaux = $paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantVerse)')
            ->where('p.statut = :statut')
            ->setParameter('statut', \App\Enum\StatutPaiement::VALIDE)
            ->getQuery()->getSingleScalarResult() ?? 0;

        return $this->render('admin/index.html.twig', [
            'stats'                 => $stats,
            'par_role'              => $parRole,
            'derniers_utilisateurs' => $derniers_utilisateurs,
            'derniers_incidents'    => $derniers_incidents,
            'derniers_paiements'    => $derniers_paiements,
            'revenus_globaux'       => $revenus_globaux,
        ]);
    }

    // =========================================================
    // GESTION DES UTILISATEURS
    // =========================================================

    #[Route('/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
    public function listUsers(UserRepository $userRepository, Request $request): Response
    {
        $role   = $request->query->get('role');
        $actif  = $request->query->get('actif');
        $search = $request->query->get('q');

        $qb = $userRepository->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');

        if ($role) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }
        if ($actif !== null && $actif !== '') {
            $qb->andWhere('u.isActive = :actif')->setParameter('actif', (bool) $actif);
        }
        if ($search) {
            $qb->andWhere('u.email LIKE :q')->setParameter('q', '%'.$search.'%');
        }

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users'  => $users,
            'roles'  => RoleUtilisateur::cases(),
            'role'   => $role,
            'actif'  => $actif,
            'search' => $search,
        ]);
    }

    #[Route('/utilisateurs/nouveau', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ActivityLogService $activityLogService
    ): Response {
        if ($request->isMethod('POST')) {
            $email    = trim((string) $request->request->get('email'));
            $password = $request->request->get('password');
            $role     = RoleUtilisateur::from($request->request->get('role'));
            $isActive = (bool) $request->request->get('isActive', true);

            if ($email === '' || $password === null || strlen($password) < 6) {
                $this->addFlash('error', 'Veuillez renseigner un email valide et un mot de passe (minimum 6 caracteres).');
                return $this->redirectToRoute('app_admin_user_new');
            }

            // Vérifier doublon
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                $this->addFlash('error', "Un utilisateur avec l'email $email existe déjà.");
                return $this->redirectToRoute('app_admin_user_new');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setRole($role);
            $user->setIsActive($isActive);
            $user->setPassword($hasher->hashPassword($user, $password));

            $em->persist($user);
            $em->flush();
            $actor = $this->getUser();
            $activityLogService->log(
                action: 'USER_CREATE',
                actor: $actor instanceof User ? $actor : null,
                targetEmail: $user->getEmail(),
                details: 'Creation d\'un nouveau compte utilisateur'
            );

            $this->addFlash('success', "Utilisateur $email créé avec succès.");
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/new.html.twig', [
            'roles' => RoleUtilisateur::cases(),
        ]);
    }

    #[Route('/utilisateurs/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function showUser(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/utilisateurs/{id}/editer', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository,
        ActivityLogService $activityLogService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $newRole = RoleUtilisateur::from($request->request->get('role'));
            $newActive = (bool) $request->request->get('isActive', false);

            if ($email === '') {
                $this->addFlash('error', 'L\'email ne peut pas etre vide.');
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }

            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                $this->addFlash('error', "Un utilisateur avec l'email $email existe deja.");
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }

            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && $user === $currentUser && (!$newActive || $newRole !== RoleUtilisateur::ADMIN)) {
                $this->addFlash('error', 'Vous ne pouvez pas vous retirer vos propres droits administrateur.');
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }

            if (
                $user->getRole() === RoleUtilisateur::ADMIN
                && ($newRole !== RoleUtilisateur::ADMIN || !$newActive)
                && $userRepository->countActiveAdminsExcluding($user) === 0
            ) {
                $this->addFlash('error', 'Impossible: il doit toujours rester au moins un administrateur actif.');
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }

            $user->setEmail($email);
            $user->setRole($newRole);
            $user->setIsActive($newActive);

            $newPassword = $request->request->get('password');
            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
            }

            $em->flush();
            $actor = $this->getUser();
            $activityLogService->log(
                action: 'USER_UPDATE',
                actor: $actor instanceof User ? $actor : null,
                targetEmail: $user->getEmail(),
                details: 'Mise a jour du profil utilisateur'
            );
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user'  => $user,
            'roles' => RoleUtilisateur::cases(),
        ]);
    }

    #[Route('/utilisateurs/{id}/toggle-actif', name: 'app_admin_user_toggle', methods: ['POST'])]
    public function toggleUser(User $user, EntityManagerInterface $em, Request $request, UserRepository $userRepository, ActivityLogService $activityLogService): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Empêcher l'admin de se désactiver lui-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous désactiver vous-même.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (
            $user->getRole() === RoleUtilisateur::ADMIN
            && $user->isIsActive()
            && $userRepository->countActiveAdminsExcluding($user) === 0
        ) {
            $this->addFlash('error', 'Impossible de desactiver le dernier administrateur actif.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsActive(!$user->isIsActive());
        $em->flush();
        $actor = $this->getUser();
        $activityLogService->log(
            action: 'USER_TOGGLE_ACTIVE',
            actor: $actor instanceof User ? $actor : null,
            targetEmail: $user->getEmail(),
            details: $user->isIsActive() ? 'Compte active' : 'Compte desactive'
        );

        $etat = $user->isIsActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Utilisateur {$user->getEmail()} $etat.");
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/utilisateurs/{id}/changer-role', name: 'app_admin_user_change_role', methods: ['POST'])]
    public function changeRole(User $user, EntityManagerInterface $em, Request $request, UserRepository $userRepository, ActivityLogService $activityLogService): Response
    {
        if (!$this->isCsrfTokenValid('role'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        // Empêcher de changer son propre rôle
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('app_admin_users');
        }

        $newRole = RoleUtilisateur::from($request->request->get('role'));

        if (
            $user->getRole() === RoleUtilisateur::ADMIN
            && $newRole !== RoleUtilisateur::ADMIN
            && $user->isIsActive()
            && $userRepository->countActiveAdminsExcluding($user) === 0
        ) {
            $this->addFlash('error', 'Impossible de retrograder le dernier administrateur actif.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setRole($newRole);
        $em->flush();
        $actor = $this->getUser();
        $activityLogService->log(
            action: 'USER_CHANGE_ROLE',
            actor: $actor instanceof User ? $actor : null,
            targetEmail: $user->getEmail(),
            details: 'Nouveau role: ' . $newRole->value
        );

        $this->addFlash('success', "Rôle de {$user->getEmail()} changé en {$newRole->value}.");
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/utilisateurs/{id}/supprimer', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $em, Request $request, UserRepository $userRepository, ActivityLogService $activityLogService): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (
            $user->getRole() === RoleUtilisateur::ADMIN
            && $user->isIsActive()
            && $userRepository->countActiveAdminsExcluding($user) === 0
        ) {
            $this->addFlash('error', 'Impossible d\'archiver le dernier administrateur actif.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (!$user->isIsActive()) {
            $this->addFlash('success', "L'utilisateur {$user->getEmail()} est deja archive.");
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsActive(false);
        $em->flush();
        $actor = $this->getUser();
        $activityLogService->log(
            action: 'USER_ARCHIVE',
            actor: $actor instanceof User ? $actor : null,
            targetEmail: $user->getEmail(),
            details: 'Archivage utilisateur (desactivation)'
        );

        $this->addFlash('success', "Utilisateur {$user->getEmail()} archive (compte desactive).");
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/utilisateurs/{id}/reset-password', name: 'app_admin_user_reset_password', methods: ['POST'])]
    public function resetUserPassword(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailerInterface $mailer,
        ActivityLogService $activityLogService
    ): Response {
        if (!$this->isCsrfTokenValid('reset_password'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $temporaryPassword = strtoupper(bin2hex(random_bytes(4))).'!A1';
        $user->setPassword($hasher->hashPassword($user, $temporaryPassword));
        $em->flush();
        $actor = $this->getUser();
        $activityLogService->log(
            action: 'USER_RESET_PASSWORD',
            actor: $actor instanceof User ? $actor : null,
            targetEmail: $user->getEmail(),
            details: 'Reinitialisation du mot de passe utilisateur'
        );

        $email = (new Email())
            ->to($user->getEmail())
            ->subject('GESTLOYER - Reinitialisation de votre mot de passe')
            ->text(
                "Bonjour,\n\n".
                "Votre mot de passe GESTLOYER a ete reinitialise par un administrateur.\n\n".
                "Nouveau mot de passe temporaire : {$temporaryPassword}\n\n".
                "Par mesure de securite, connectez-vous et modifiez ce mot de passe des que possible.\n\n".
                "Ceci est un message automatique, merci de ne pas y repondre."
            );

        try {
            $mailer->send($email);
            $this->addFlash(
                'success',
                "Mot de passe reinitialise pour {$user->getEmail()}. Un email avec le mot de passe temporaire a ete envoye."
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'error',
                "Mot de passe reinitialise mais l'envoi de l'email a echoue. Mot de passe temporaire: {$temporaryPassword}"
            );
        }

        return $this->redirectToRoute('app_admin_users');
    }

    // =========================================================
    // PARAMÈTRES & ARCHIVES
    // =========================================================

    #[Route('/parametres', name: 'app_admin_parametres', methods: ['GET', 'POST'])]
    public function parametres(
        UserRepository $userRepository,
        ActivityLogRepository $activityLogRepository,
        \App\Repository\AgenceConfigRepository $agenceConfigRepository,
        Request $request,
        EntityManagerInterface $em,
        ActivityLogService $activityLogService
    ): Response {
        // --- 1. Gestion de la Configuration de l'Agence ---
        $agenceConfig = $agenceConfigRepository->findOneBy([]);
        if (!$agenceConfig) {
            $agenceConfig = new \App\Entity\AgenceConfig();
            // Valeurs par défaut
            $agenceConfig->setNom('GESTLOYER Agency');
            $agenceConfig->setEmail('contact@gestloyer.com');
        }

        $form = $this->createForm(\App\Form\AgenceConfigType::class, $agenceConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoFile = $form->get('logo')->getData();
            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/logos',
                        $newFilename
                    );
                    $agenceConfig->setLogo($newFilename);
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo.');
                }
            }

            $em->persist($agenceConfig);
            $em->flush();
            $actor = $this->getUser();
            $activityLogService->log(
                action: 'AGENCY_SETTINGS_UPDATE',
                actor: $actor instanceof User ? $actor : null,
                details: 'Mise a jour des parametres de l\'agence'
            );

            $this->addFlash('success', 'Les paramètres de l\'agence ont été enregistrés.');
            return $this->redirectToRoute('app_admin_parametres');
        }

        // --- 2. Gestion des Archives ---
        // On récupère le rôle sélectionné depuis l'URL, sinon par défaut on met LOCATAIRE
        $selectedRole = $request->query->get('role', RoleUtilisateur::LOCATAIRE->value);

        // On récupère les archives (utilisateurs inactifs) pour le rôle donné
        $archives = $userRepository->createQueryBuilder('u')
            ->where('u.isActive = false')
            ->andWhere('u.role = :role')
            ->setParameter('role', $selectedRole)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // --- 3. Collaborateurs (Gestionnaires & Comptables) ---
        $collaborateurs = $userRepository->createQueryBuilder('u')
            ->where('u.isActive = true')
            ->andWhere('u.role IN (:roles)')
            ->setParameter('roles', [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])
            ->orderBy('u.role', 'ASC')
            ->addOrderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        try {
            $recentConnections = $activityLogRepository->findRecentConnections(30);
            $recentUserActions = $activityLogRepository->findRecentUserActions(30);
            $recentCsvHistory = $activityLogRepository->findRecentCsvHistory(30);
        } catch (TableNotFoundException) {
            $recentConnections = [];
            $recentUserActions = [];
            $recentCsvHistory = [];
        }

        return $this->render('admin/parametres.html.twig', [
            'roles' => RoleUtilisateur::cases(),
            'selectedRole' => $selectedRole,
            'archives' => $archives,
            'collaborateurs' => $collaborateurs,
            'form' => $form->createView(),
            'recentConnections' => $recentConnections,
            'recentUserActions' => $recentUserActions,
            'recentCsvHistory' => $recentCsvHistory,
        ]);
    }

    #[Route('/parametres/manuel/telecharger', name: 'app_admin_manual_download', methods: ['GET'])]
    public function downloadManual(): Response
    {
        $content = $this->renderView('admin/manual_download.txt.twig');
        $filename = sprintf('gestloyer-manuel-utilisation-%s.txt', (new \DateTimeImmutable())->format('Ymd'));

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    #[Route('/historique/{id}', name: 'app_admin_history_show', methods: ['GET'])]
    public function showHistory(\App\Entity\ActivityLog $activityLog, ActivityLogRepository $activityLogRepository): Response
    {
        $sessionId = $activityLog->getSessionId();
        $items = $sessionId ? $activityLogRepository->findBySessionId($sessionId) : [$activityLog];

        return $this->render('admin/history_show.html.twig', [
            'root' => $activityLog,
            'items' => $items,
            'sessionId' => $sessionId,
        ]);
    }

    #[Route('/notifications/poll', name: 'app_admin_notifications_poll', methods: ['GET'])]
    public function pollNotifications(ActivityLogRepository $activityLogRepository): JsonResponse
    {
        try {
            $count = $activityLogRepository->countUnseen();
            $latest = $activityLogRepository->findLatestUnseen(6);
        } catch (TableNotFoundException) {
            $count = 0;
            $latest = [];
        }

        $data = [
            'count' => $count,
            'latest' => array_map(static function (\App\Entity\ActivityLog $l): array {
                return [
                    'id' => $l->getId(),
                    'createdAt' => $l->getCreatedAt()->format('Y-m-d H:i:s'),
                    'action' => $l->getAction(),
                    'actorEmail' => $l->getActorEmail(),
                    'details' => $l->getDetails(),
                ];
            }, $latest),
        ];

        return new JsonResponse($data);
    }

    #[Route('/notifications/mark-seen', name: 'app_admin_notifications_mark_seen', methods: ['POST'])]
    public function markNotificationsSeen(Request $request, ActivityLogRepository $activityLogRepository): Response
    {
        if (!$this->isCsrfTokenValid('admin_notifications_seen', (string) $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false], 403);
        }

        try {
            $activityLogRepository->markAllSeen();
        } catch (TableNotFoundException) {
            // ignore
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/parametres/messages/envoyer', name: 'app_admin_parametres_send_message', methods: ['POST'])]
    public function sendAdminMessage(
        Request $request,
        UserRepository $userRepository,
        PaiementRepository $paiementRepository,
        MessagingService $messagingService,
        ActivityLogService $activityLogService
    ): Response {
        if (!$this->isCsrfTokenValid('admin_send_message', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_parametres');
        }

        $mode = (string) $request->request->get('mode', '');
        $subject = trim((string) $request->request->get('subject', ''));
        $body = trim((string) $request->request->get('body', ''));
        $targetEmail = trim((string) $request->request->get('targetEmail', ''));

        if ($subject === '' || $body === '') {
            $this->addFlash('error', 'Sujet et message sont obligatoires.');
            return $this->redirectToRoute('app_admin_parametres');
        }

        $recipients = [];

        if ($mode === 'all_locataires') {
            $users = $userRepository->createQueryBuilder('u')
                ->where('u.isActive = true')
                ->andWhere('u.role = :role')
                ->setParameter('role', \App\Enum\RoleUtilisateur::LOCATAIRE)
                ->orderBy('u.id', 'DESC')
                ->getQuery()->getResult();

            foreach ($users as $u) {
                if ($u instanceof \App\Entity\User && $u->getEmail()) {
                    $recipients[] = $u->getEmail();
                }
            }
        } elseif ($mode === 'one') {
            if ($targetEmail === '') {
                $this->addFlash('error', 'Veuillez renseigner un email destinataire.');
                return $this->redirectToRoute('app_admin_parametres');
            }
            $recipients[] = $targetEmail;
        } elseif ($mode === 'late_locataires') {
            $mois = new \DateTimeImmutable('first day of this month 00:00:00');
            $late = $paiementRepository->findLocatairesEnRetardPourMois($mois);
            foreach ($late as $row) {
                if (($row['email'] ?? '') !== '') {
                    $recipients[] = $row['email'];
                }
            }
        } else {
            $this->addFlash('error', 'Mode d’envoi invalide.');
            return $this->redirectToRoute('app_admin_parametres');
        }

        $recipients = array_values(array_unique(array_filter($recipients)));

        if (count($recipients) === 0) {
            $this->addFlash('error', 'Aucun destinataire trouvé.');
            return $this->redirectToRoute('app_admin_parametres');
        }

        $sent = 0;
        $failed = 0;
        foreach ($recipients as $to) {
            try {
                $messagingService->sendEmail($to, $subject, $body);
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $actor = $this->getUser();
        $activityLogService->log(
            action: 'MESSAGE_SEND',
            actor: $actor instanceof \App\Entity\User ? $actor : null,
            details: sprintf('mode=%s, sent=%d, failed=%d', $mode, $sent, $failed)
        );

        if ($failed > 0) {
            $this->addFlash('error', sprintf('Messages envoyés: %d, échecs: %d.', $sent, $failed));
        } else {
            $this->addFlash('success', sprintf('Message envoyé à %d destinataire(s).', $sent));
        }

        return $this->redirectToRoute('app_admin_parametres');
    }
}
