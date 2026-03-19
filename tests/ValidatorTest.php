<?php

declare(strict_types=1);

use App\Validation\Validator;
use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\EmailFormatRule;
use App\Validation\Rules\MinLengthRule;
use App\Validation\Rules\PasswordMatchRule;
use App\Validation\Rules\UniqueEmailRule;
use App\Validation\Rules\MaxMindRule;
use App\Fraud\MaxMindClientInterface;
use App\Repository\UserRepository;
use App\Database\Expression;

require_once __DIR__ . '/../bootstrap.php';

// ── Minimalni test runner ─────────────────────────────────
$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  ✓ {$name}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  ✗ {$name}\n    → " . $e->getMessage() . "\n";
        $failed++;
    }
}

function assertEqual(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException(
            "Expected " . var_export($expected, true) . ", got " . var_export($actual, true)
        );
    }
}

function assertNull(mixed $value): void
{
    if ($value !== null) {
        throw new \RuntimeException("Expected null, got " . var_export($value, true));
    }
}

// ── RequiredRule ─────────────────────────────────────────
echo "\nRequiredRule\n";
test('passes for non-empty value', function () { assertNull((new RequiredRule())->validate('hello')); });
test('fails for empty string',     function () { assertEqual('required', (new RequiredRule())->validate('')); });
test('fails for null',             function () { assertEqual('required', (new RequiredRule())->validate(null)); });

// ── EmailFormatRule ──────────────────────────────────────
echo "\nEmailFormatRule\n";
test('passes for valid email',        function () { assertNull((new EmailFormatRule())->validate('user@example.com')); });
test('passes for subdomain email',    function () { assertNull((new EmailFormatRule())->validate('user@mail.example.co.uk')); });
test('fails for email without @',     function () { assertEqual('email_format', (new EmailFormatRule())->validate('notanemail')); });
test('fails for email without domain',function () { assertEqual('email_format', (new EmailFormatRule())->validate('user@')); });
test('fails for empty string',        function () { assertEqual('email_format', (new EmailFormatRule())->validate('')); });

// ── MinLengthRule ────────────────────────────────────────
echo "\nMinLengthRule\n";
test('passes when length equals minimum',  function () { assertNull((new MinLengthRule(8))->validate('12345678')); });
test('passes when length exceeds minimum', function () { assertNull((new MinLengthRule(8))->validate('123456789')); });
test('fails when length is below minimum', function () { assertEqual('min_length', (new MinLengthRule(8))->validate('1234567')); });
test('handles multibyte chars',            function () { assertNull((new MinLengthRule(4))->validate('čšžđć')); });

// ── PasswordMatchRule ────────────────────────────────────
echo "\nPasswordMatchRule\n";
test('passes when passwords match',       function () { assertNull((new PasswordMatchRule('secret123'))->validate('secret123')); });
test('fails when passwords do not match', function () { assertEqual('password_mismatch', (new PasswordMatchRule('secret123'))->validate('different')); });
test('fails when confirmation is empty',  function () { assertEqual('password_mismatch', (new PasswordMatchRule('secret123'))->validate('')); });

// ── MaxMindRule ──────────────────────────────────────────
echo "\nMaxMindRule (simulated)\n";
$cleanClient = new class implements MaxMindClientInterface {
    public function isFraudulent(string $e, string $ip): bool { return false; }
};
$fraudClient = new class implements MaxMindClientInterface {
    public function isFraudulent(string $e, string $ip): bool { return true; }
};
test('passes when MaxMind returns clean', function () use ($cleanClient) { assertNull((new MaxMindRule($cleanClient, '1.2.3.4'))->validate('user@example.com')); });
test('fails when MaxMind flags request',  function () use ($fraudClient) { assertEqual('fraud_detected', (new MaxMindRule($fraudClient, '1.2.3.4'))->validate('user@example.com')); });

// ── MaxMindClient simulacija ─────────────────────────────
echo "\nMaxMindClient simulation\n";
use App\Fraud\MaxMindClient;
test('flags email with "fraud"',    function () { assertEqual(true,  (new MaxMindClient())->isFraudulent('fraud@example.com', '1.2.3.4')); });
test('flags email with "spam"',     function () { assertEqual(true,  (new MaxMindClient())->isFraudulent('spam@example.com',  '1.2.3.4')); });
test('flags blocked IP 192.0.2.x', function () { assertEqual(true,  (new MaxMindClient())->isFraudulent('user@example.com',  '192.0.2.1')); });
test('passes clean email and IP',   function () { assertEqual(false, (new MaxMindClient())->isFraudulent('user@example.com',  '93.12.45.67')); });

// ── Validator integracija ────────────────────────────────
echo "\nValidator integration\n";
$stubRepo = new class extends UserRepository {
    public function __construct() {}
    public function emailExists(string $email): bool { return false; }
};
$takenRepo = new class extends UserRepository {
    public function __construct() {}
    public function emailExists(string $email): bool { return true; }
};

function mkv(object $repo, object $mm, string $pw): Validator {
    return new Validator([
        'email'     => [new RequiredRule(), new EmailFormatRule(), new UniqueEmailRule($repo), new MaxMindRule($mm, '1.2.3.4')],
        'password'  => [new RequiredRule(), new MinLengthRule(8)],
        'password2' => [new RequiredRule(), new MinLengthRule(8), new PasswordMatchRule($pw)],
    ]);
}

test('valid data passes without errors',              function () use ($stubRepo, $cleanClient) { assertEqual([], mkv($stubRepo, $cleanClient, 'password1')->validate(['email'=>'user@example.com','password'=>'password1','password2'=>'password1'])); });
test('missing email returns required',                function () use ($stubRepo, $cleanClient) { assertEqual('required',          mkv($stubRepo, $cleanClient, 'password1')->validate(['email'=>'','password'=>'password1','password2'=>'password1'])['email']); });
test('invalid format returns email_format',           function () use ($stubRepo, $cleanClient) { assertEqual('email_format',      mkv($stubRepo, $cleanClient, 'password1')->validate(['email'=>'bad','password'=>'password1','password2'=>'password1'])['email']); });
test('short password returns min_length',             function () use ($stubRepo, $cleanClient) { assertEqual('min_length',        mkv($stubRepo, $cleanClient, 'short')->validate(['email'=>'u@e.com','password'=>'short','password2'=>'short'])['password']); });
test('mismatched passwords returns password_mismatch',function () use ($stubRepo, $cleanClient) { assertEqual('password_mismatch', mkv($stubRepo, $cleanClient, 'password1')->validate(['email'=>'u@e.com','password'=>'password1','password2'=>'password2'])['password2']); });
test('duplicate email returns email_taken',           function () use ($takenRepo, $cleanClient) { assertEqual('email_taken',      mkv($takenRepo, $cleanClient, 'password1')->validate(['email'=>'taken@e.com','password'=>'password1','password2'=>'password1'])['email']); });
test('MaxMind flag returns fraud_detected',           function () use ($stubRepo, $fraudClient)  { assertEqual('fraud_detected',   mkv($stubRepo, $fraudClient,  'password1')->validate(['email'=>'u@e.com','password'=>'password1','password2'=>'password1'])['email']); });

// ── Expression ───────────────────────────────────────────
echo "\nExpression\n";
test('getValue returns correct value',   function () { assertEqual('NOW()',                   (new Expression('NOW()'))->getValue()); });
test('__toString returns correct value', function () { assertEqual('NOW() - INTERVAL 10 DAY', (string)(new Expression('NOW() - INTERVAL 10 DAY'))); });

// ── Rezultat ─────────────────────────────────────────────
echo "\n──────────────────────────────\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);