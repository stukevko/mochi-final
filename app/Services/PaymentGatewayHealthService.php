<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class PaymentGatewayHealthService
{
    public function check(PaymentGateway $gateway): HealthCheckResult
    {
        return match (strtolower($gateway->code)) {
            'stripe' => $this->checkStripe($gateway),
            'paypal' => $this->checkPayPal($gateway),
            default => $this->checkGeneric($gateway),
        };
    }

    private function checkStripe(PaymentGateway $gateway): HealthCheckResult
    {
        $secret = $gateway->getConfigValue('secret_key')
            ?? $gateway->getConfigValue('secret')
            ?? $gateway->getConfigValue('api_key');

        if (! is_string($secret) || trim($secret) === '') {
            return new HealthCheckResult(false, 'Kein Stripe-Secret (secret_key) hinterlegt.');
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$secret,
                ])
                ->get('https://api.stripe.com/v1/balance');
        } catch (ConnectionException $e) {
            $message = Str::of($e->getMessage());

            if ($message->contains('cURL error 60')) {
                return new HealthCheckResult(
                    false,
                    'Stripe: SSL-Zertifikat auf diesem Rechner fehlt/ungültig (cURL 60). Das ist ein lokales Server-Setup-Thema, nicht der Stripe-Key.'
                );
            }

            return new HealthCheckResult(false, 'Stripe-Verbindung fehlgeschlagen: '.Str::limit((string) $e->getMessage(), 180));
        }

        if ($response->successful()) {
            return new HealthCheckResult(true, 'Stripe API antwortet (Balance abrufbar).');
        }

        return new HealthCheckResult(false, 'Stripe: HTTP '.$response->status().' – '.Str::limit((string) $response->body(), 200));
    }

    private function checkPayPal(PaymentGateway $gateway): HealthCheckResult
    {
        $clientId = $gateway->getConfigValue('client_id');
        $secret = $gateway->getConfigValue('client_secret') ?? $gateway->getConfigValue('secret');

        if (! is_string($clientId) || trim($clientId) === '' || ! is_string($secret) || trim($secret) === '') {
            return new HealthCheckResult(false, 'PayPal: client_id und client_secret werden benötigt.');
        }

        $base = $gateway->is_test_mode
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->withBasicAuth($clientId, $secret)
                ->post($base.'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException $e) {
            $message = Str::of($e->getMessage());

            if ($message->contains('cURL error 60')) {
                return new HealthCheckResult(
                    false,
                    'PayPal: SSL-Zertifikat auf diesem Rechner fehlt/ungültig (cURL 60). Das ist ein lokales Server-Setup-Thema.'
                );
            }

            return new HealthCheckResult(false, 'PayPal-Verbindung fehlgeschlagen: '.Str::limit((string) $e->getMessage(), 180));
        }

        if ($response->successful()) {
            return new HealthCheckResult(true, 'PayPal OAuth-Token erhalten.');
        }

        return new HealthCheckResult(false, 'PayPal: HTTP '.$response->status().' – '.Str::limit((string) $response->body(), 200));
    }

    private function checkGeneric(PaymentGateway $gateway): HealthCheckResult
    {
        $config = $gateway->config ?? [];
        if ($config === []) {
            return new HealthCheckResult(false, 'Keine Konfiguration hinterlegt.');
        }

        $filled = collect($config)->filter(fn ($v) => filled($v))->count();

        return $filled > 0
            ? new HealthCheckResult(true, 'Konfiguration vorhanden ('.$filled.' Einträge). Live-API-Test für „'.$gateway->code.'“ ist nicht implementiert.')
            : new HealthCheckResult(false, 'Konfiguration leer oder nur Platzhalter.');
    }
}
