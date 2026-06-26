<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class ShopGoLiveCheckCommand extends Command
{
    protected $signature = 'shop:go-live-check';

    protected $description = 'Prueft Go-Live-Readiness fuer Environment, Payments, Mail, Webhooks und Storage';

    public function handle(): int
    {
        $rows = [];
        $allOk = true;

        $appDebug = (bool) config('app.debug', true);
        $envOk = ! $appDebug;
        $rows[] = $this->row('Environment', 'APP_DEBUG=false', $envOk, $envOk ? 'OK' : 'APP_DEBUG ist true');
        $allOk = $allOk && $envOk;

        $sumupToken = (string) config('services.sumup.token', '');
        $sumupMerchantCode = (string) config('services.sumup.merchant_code', '');

        $sumupOk = $sumupToken !== '' && $sumupMerchantCode !== '';
        $paymentsOk = $sumupOk;

        $paymentDetail = sprintf(
            'SumUp token: %s, merchant code: %s',
            $sumupToken !== '' ? 'yes' : 'no',
            $sumupMerchantCode !== '' ? 'yes' : 'no',
        );
        $rows[] = $this->row('Payments', 'Live-Keys vorhanden', $paymentsOk, $paymentDetail);
        $allOk = $allOk && $paymentsOk;

        $mailDriver = strtolower((string) config('mail.default', ''));
        $mailOk = $mailDriver !== '' && $mailDriver !== 'log';
        $rows[] = $this->row('Mail', 'Kein log-Mailer', $mailOk, $mailDriver === '' ? 'mail.default leer' : 'mail.default='.$mailDriver);
        $allOk = $allOk && $mailOk;

        $webhooksOk = $this->checkWebhookCsrfExclusion();
        $rows[] = $this->row('Webhooks', '/webhooks/payment/* CSRF-frei', $webhooksOk['ok'], $webhooksOk['detail']);
        $allOk = $allOk && $webhooksOk['ok'];

        $storagePath = public_path('storage');
        $storageOk = is_link($storagePath) || is_dir($storagePath);
        $rows[] = $this->row('Storage', 'public/storage vorhanden', $storageOk, $storageOk ? $storagePath : 'storage:link fehlt');
        $allOk = $allOk && $storageOk;

        $this->newLine();
        $this->table(['Bereich', 'Check', 'Status', 'Details'], $rows);
        $this->newLine();

        if ($allOk) {
            $this->info('Shop is ready for take-off! 🚀');

            return self::SUCCESS;
        }

        $this->error('Go-Live-Check fehlgeschlagen. Bitte die Punkte oben korrigieren.');

        return self::FAILURE;
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function checkWebhookCsrfExclusion(): array
    {
        $stripeRoute = Route::getRoutes()->getByName('webhooks.payment.stripe');
        $paypalRoute = Route::getRoutes()->getByName('webhooks.payment.paypal');

        if (! $stripeRoute || ! $paypalRoute) {
            return ['ok' => false, 'detail' => 'Webhook-Routen fehlen'];
        }

        $urisOk = $stripeRoute->uri() === 'webhooks/payment/stripe' && $paypalRoute->uri() === 'webhooks/payment/paypal';
        if (! $urisOk) {
            return ['ok' => false, 'detail' => 'Webhook-URIs stimmen nicht'];
        }

        $bootstrapPath = base_path('bootstrap/app.php');
        $bootstrapContent = @file_get_contents($bootstrapPath);
        $hasExclusion = is_string($bootstrapContent) && str_contains($bootstrapContent, 'webhooks/payment/*');

        return [
            'ok' => $hasExclusion,
            'detail' => $hasExclusion
                ? 'CSRF-Ausnahme in bootstrap/app.php gefunden'
                : 'CSRF-Ausnahme fuer webhooks/payment/* fehlt',
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function row(string $area, string $check, bool $ok, string $detail): array
    {
        return [$area, $check, $ok ? 'PASS' : 'FAIL', $detail];
    }
}
