<?php

namespace App\Command;

use App\Repository\PaiementRepository;
use App\Service\ActivityLogService;
use App\Service\MessagingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-late-payment-reminders',
    description: 'Envoie un rappel de paiement aux locataires en retard (fin de mois).'
)]
class SendLatePaymentRemindersCommand extends Command
{
    public function __construct(
        private PaiementRepository $paiementRepository,
        private MessagingService $messagingService,
        private ActivityLogService $activityLogService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Envoie même si on est pas en fin de mois')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Sujet du message', 'Rappel : paiement de loyer')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'Corps du message', "Bonjour,\n\nNous constatons un retard de paiement sur votre loyer du mois en cours.\nMerci de régulariser avant la fin du mois.\n\nCordialement,\nGESTLOYER")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = (bool) $input->getOption('force');

        $today = new \DateTimeImmutable('now');
        $lastDay = (int) $today->format('t');
        $isEndOfMonth = (int) $today->format('j') === $lastDay;
        if (!$force && !$isEndOfMonth) {
            $output->writeln('Pas fin de mois: rien à envoyer (utiliser --force pour forcer).');
            return Command::SUCCESS;
        }

        $mois = new \DateTimeImmutable('first day of this month 00:00:00');
        $late = $this->paiementRepository->findLocatairesEnRetardPourMois($mois);

        if (count($late) === 0) {
            $output->writeln('Aucun locataire en retard détecté.');
            return Command::SUCCESS;
        }

        $subject = (string) $input->getOption('subject');
        $body = (string) $input->getOption('body');

        $sent = 0;
        $failed = 0;
        foreach ($late as $row) {
            $email = (string) ($row['email'] ?? '');
            if ($email === '') {
                continue;
            }
            try {
                $this->messagingService->sendEmail($email, $subject, $body);
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $this->activityLogService->log(
            action: 'AUTO_LATE_PAYMENT_REMINDER',
            actor: null,
            details: sprintf('sent=%d, failed=%d, mois=%s', $sent, $failed, $mois->format('Y-m'))
        );

        $output->writeln(sprintf('Terminé. Envoyés=%d, échecs=%d', $sent, $failed));
        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

