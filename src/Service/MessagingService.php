<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MessagingService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $email = (new Email())
            ->from('no-reply@gestloyer.com')
            ->to($to)
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
    }
}

