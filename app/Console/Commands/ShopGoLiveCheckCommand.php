<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\ShopViewIntegrity;
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

        $secureCookie = (bool) config('session.secure', false);
        $secureOk = ! app()->environment('production') || $secureCookie;
        $rows[] = $this->row('Security', 'SESSION_SECURE_COOKIE in Production', $secureOk, $secureOk ? 'OK' : 'Secure Cookies fehlen');
        $allOk = $allOk && $secureOk;

        $appUrl = (string) config('app.url', '');
        $httpsUrlOk = ! app()->environment('production') || str_starts_with($appUrl, 'https://');
        $rows[] = $this->row(
            'Security',
            'APP_URL mit https:// in Production',
            $httpsUrlOk,
            $httpsUrlOk ? 'OK' : 'APP_URL muss https://mochi-cards.de (o.ä.) sein — sonst Mixed Content'
        );
        $allOk = $allOk && $httpsUrlOk;

        $forceHttpsOk = ! app()->environment('production') || (bool) config('app.force_https', false);
        $rows[] = $this->row(
            'Security',
            'force_https aktiv in Production',
            $forceHttpsOk,
            $forceHttpsOk ? 'OK' : 'FORCE_HTTPS=true oder APP_URL=https://… setzen'
        );
        $allOk = $allOk && $forceHttpsOk;

        $turnstileOk = ! app()->environment('production') || \App\Services\TurnstileVerifier::secretConfigured();
        $rows[] = $this->row('Security', 'Turnstile in Production', $turnstileOk, $turnstileOk ? 'OK' : 'TURNSTILE_SECRET_KEY fehlt');
        $allOk = $allOk && $turnstileOk;

        $legalOk = $this->checkLegalTexts();
        $rows[] = $this->row('Legal', 'Rechtstexte ohne Platzhalter', $legalOk['ok'], $legalOk['detail']);
        $allOk = $allOk && $legalOk['ok'];

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

        if ($mailDriver === 'resend') {
            $resendKey = (string) config('services.resend.key', '');
            $resendOk = $resendKey !== '';
            $rows[] = $this->row('Mail', 'RESEND_API_KEY gesetzt', $resendOk, $resendOk ? 'OK' : 'RESEND_API_KEY fehlt');
            $allOk = $allOk && $resendOk;
        }

        $fromAddress = (string) config('mail.from.address', '');
        $fromOk = $fromAddress !== '' && filter_var($fromAddress, FILTER_VALIDATE_EMAIL)
            && ! str_contains(strtolower($fromAddress), 'example.com');
        $rows[] = $this->row(
            'Mail',
            'MAIL_FROM_ADDRESS produktiv',
            $fromOk,
            $fromOk ? $fromAddress : 'echte Absender-Adresse setzen (Domain bei Resend/SMTP verifizieren)'
        );
        $allOk = $allOk && $fromOk;

        $webhooksOk = $this->checkWebhookCsrfExclusion();
        $rows[] = $this->row('Webhooks', '/webhooks/payment/* CSRF-frei', $webhooksOk['ok'], $webhooksOk['detail']);
        $allOk = $allOk && $webhooksOk['ok'];

        $storagePath = public_path('storage');
        $storageOk = is_link($storagePath) || is_dir($storagePath);
        $rows[] = $this->row('Storage', 'public/storage vorhanden', $storageOk, $storageOk ? $storagePath : 'storage:link fehlt');
        $allOk = $allOk && $storageOk;

        $viewsOk = ShopViewIntegrity::allPass();
        $missingViews = collect(ShopViewIntegrity::checkBladeViews())
            ->filter(fn (array $row): bool => ! $row['ok'])
            ->pluck('view')
            ->all();
        $renderFails = collect(ShopViewIntegrity::checkRenderableSurfaces())
            ->filter(fn (array $row): bool => ! $row['ok'])
            ->pluck('surface')
            ->all();
        $viewDetail = $viewsOk
            ? 'shop:check-views OK'
            : trim(
                ($missingViews !== [] ? 'fehlend: '.implode(', ', $missingViews) : '')
                .($renderFails !== [] ? ' render: '.implode(', ', $renderFails) : '')
            );
        $rows[] = $this->row('Views', 'Kritische Templates/PDFs/Mails', $viewsOk, $viewDetail !== '' ? $viewDetail : 'FAIL');
        $allOk = $allOk && $viewsOk;

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

    /**
     * @return array{ok: bool, detail: string}
     */
    private function checkLegalTexts(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return ['ok' => false, 'detail' => 'Settings-Tabelle fehlt'];
        }

        $missing = [];
        foreach (['legal_impressum', 'legal_agb', 'legal_privacy', 'legal_widerruf'] as $key) {
            $html = strip_tags((string) (Setting::get($key) ?? ''));
            if ($html === '' || str_contains($html, 'Anwalt') || str_contains($html, 'Platzhalter')) {
                $missing[] = str_replace('legal_', '', $key);
            }
        }

        if ($missing === [] && \Illuminate\Support\Facades\Schema::hasTable('cms_pages')) {
            return ['ok' => true, 'detail' => 'Rechtstexte in Settings gepflegt'];
        }

        if ($missing !== []) {
            return [
                'ok' => false,
                'detail' => 'Admin → Rechtstexte: '.implode(', ', $missing),
            ];
        }

        return ['ok' => true, 'detail' => 'CMS-Fallback vorhanden'];
    }
}
