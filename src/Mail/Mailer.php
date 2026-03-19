<?php

declare(strict_types=1);

namespace App\Mail;

class Mailer implements MailerInterface
{
    private string $fromAddress;

    public function __construct(string $fromAddress = 'adm@kupujemprodajem.com')
    {
        $this->fromAddress = $fromAddress;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $headers = implode("\r\n", [
            'From: ' . $this->fromAddress,
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ]);

        return @mail($to, $subject, $body, $headers);
    }
}