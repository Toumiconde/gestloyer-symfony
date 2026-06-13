<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/poll', name: 'app_notifications_poll', methods: ['GET'])]
    public function poll(NotificationRepository $notificationRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $count = $notificationRepository->countUnseenForUser($user);
        $latest = $notificationRepository->findLatestUnseenForUser($user, 6);

        $data = [
            'count' => $count,
            'latest' => array_map(static function (\App\Entity\Notification $n): array {
                return [
                    'id' => $n->getId(),
                    'createdAt' => $n->getCreatedAt()->format('Y-m-d H:i:s'),
                    'title' => $n->getTitle(),
                    'message' => $n->getMessage(),
                    'type' => $n->getType(),
                ];
            }, $latest),
        ];

        return new JsonResponse($data);
    }

    #[Route('/mark-seen', name: 'app_notifications_mark_seen', methods: ['POST'])]
    public function markSeen(Request $request, NotificationRepository $notificationRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('notifications_seen', (string) $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false], 403);
        }

        $notificationRepository->markAllSeenForUser($user);

        return new JsonResponse(['ok' => true]);
    }
}
