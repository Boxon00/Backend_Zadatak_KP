<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\Mail\MailerInterface;
use App\Logger\UserLogger;

class RegistrationService
{
    public function __construct(
        private UserRepository  $userRepository,
        private MailerInterface $mailer,
        private UserLogger      $userLogger
    ) {}

    /**
     * Registruje novog korisnika:
     *   1. Hashira lozinku (bcrypt)
     *   2. Upisuje korisnika u bazu
     *   3. Šalje welcome email
     *   4. Loguje akciju 'register'
     *
     * @return int ID novog korisnika
     */
    public function register(string $email, string $plainPassword): int
    {
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        $userId = $this->userRepository->create($email, $hashedPassword);

        $this->mailer->send(
            $email,
            'Dobro došli',
            'Dobro došli na naš sajt. Potrebno je samo da potvrdite email adresu.'
        );

        $this->userLogger->log($userId, 'register');

        return $userId;
    }
}