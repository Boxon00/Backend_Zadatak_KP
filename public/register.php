<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Http\Request;
use App\Http\JsonResponse;
use App\Validation\Validator;
use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\EmailFormatRule;
use App\Validation\Rules\MinLengthRule;
use App\Validation\Rules\PasswordMatchRule;
use App\Validation\Rules\UniqueEmailRule;
use App\Validation\Rules\MaxMindRule;
use App\Repository\UserRepository;
use App\Service\RegistrationService;
use App\Database\QueryBuilder;
use App\Database\Connection;
use App\Fraud\MaxMindClient;
use App\Mail\Mailer;
use App\Logger\UserLogger;

// Bootstrap request
$request = Request::fromGlobals();

// Bootstrap dependencies
$connection     = Connection::getInstance('127.0.0.1', 'my_user', 'my_password', 'my_db');
$queryBuilder   = new QueryBuilder($connection);
$userRepository = new UserRepository($queryBuilder);
$maxMindClient  = new MaxMindClient();
$mailer         = new Mailer();
$userLogger     = new UserLogger($queryBuilder);

// Build validator with all rules
$validator = new Validator([
    'email' => [
        new RequiredRule(),
        new EmailFormatRule(),
        new UniqueEmailRule($userRepository),
        new MaxMindRule($maxMindClient, $request->getIp()),
    ],
    'password' => [
        new RequiredRule(),
        new MinLengthRule(8),
    ],
    'password2' => [
        new RequiredRule(),
        new MinLengthRule(8),
        new PasswordMatchRule($request->get('password')),
    ],
]);

// Validate
$errors = $validator->validate($request->all());

if (!empty($errors)) {
    $firstError = array_key_first($errors);
    JsonResponse::error($errors[$firstError])->send();
    exit;
}

// Register user
$registrationService = new RegistrationService(
    $userRepository,
    $mailer,
    $userLogger
);

try {
    $userId = $registrationService->register(
        $request->get('email'),
        $request->get('password')
    );

    session_start();
    $_SESSION['userId'] = $userId;

    JsonResponse::success(['userId' => $userId])->send();
} catch (\Exception $e) {
    JsonResponse::error('registration_failed')->send();
}