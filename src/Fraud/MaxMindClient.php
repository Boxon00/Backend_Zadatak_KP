<?php

declare(strict_types=1);

namespace App\Fraud;

/**
 * Simulirani MaxMind minFraud klijent.
 *
 * Prava implementacija bi pozivala:
 *   https://minfraud.maxmind.com/minfraud/v2.0/score
 *
 * Simulaciona logika:
 *   - Emailovi sa "fraud", "spam", "fake"  → blokirani (+60)
 *   - IP iz opsega 192.0.2.x (TEST-NET-1)  → blokiran  (+60)
 *   - Sumnjivi TLD-ovi (.xyz, .top itd.)   → +30 poena
 *   - Prag za blokadu: risk score >= 50
 */
class MaxMindClient implements MaxMindClientInterface
{
    private const RISK_SCORE_THRESHOLD   = 50.0;
    private const BLOCKED_EMAIL_KEYWORDS = ['fraud', 'spam', 'fake'];
    private const BLOCKED_IP_PREFIXES    = ['192.0.2.'];

    public function isFraudulent(string $email, string $ipAddress): bool
    {
        return $this->calculateSimulatedRiskScore($email, $ipAddress)
               >= self::RISK_SCORE_THRESHOLD;
    }

    private function calculateSimulatedRiskScore(string $email, string $ipAddress): float
    {
        $score      = 0.0;
        $emailLower = strtolower($email);

        foreach (self::BLOCKED_EMAIL_KEYWORDS as $keyword) {
            if (str_contains($emailLower, $keyword)) {
                $score += 60.0;
                break;
            }
        }

        foreach (self::BLOCKED_IP_PREFIXES as $prefix) {
            if (str_starts_with($ipAddress, $prefix)) {
                $score += 60.0;
                break;
            }
        }

        foreach (['.xyz', '.top', '.click', '.loan'] as $tld) {
            if (str_ends_with($emailLower, $tld)) {
                $score += 30.0;
                break;
            }
        }

        return min($score, 100.0);
    }
}