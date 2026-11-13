<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Technische Einstellungen aus dem Admin (statt .env): SumUp, Postfach, Spam-Schutz, Sicherheit.
 *
 * Die Werte liegen in der Datenbank (Geheimnisse verschlüsselt) und überschreiben beim Start
 * die Konfiguration. Ist im Admin nichts eingetragen, gilt weiterhin die .env.
 */
final class RuntimeConfig
{
    public const GROUP = 'system';

    public const CACHE_KEY = 'runtime-config.v1';

    /** Schlüssel, die verschlüsselt gespeichert und im Formular nie angezeigt werden. */
    public const SECRETS = ['sumup_token', 'mail_password', 'turnstile_secret'];

    public const KEYS = [
        'sumup_token', 'sumup_merchant_code', 'sumup_sales_sync',
        'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
        'turnstile_site_key', 'turnstile_secret',
        'require_2fa',
    ];

    public static function apply(): void
    {
        try {
            $values = self::values();
        } catch (Throwable) {
            return; // Datenbank noch nicht eingerichtet → .env gilt.
        }

        $has = static fn (string $key): bool => isset($values[$key]) && $values[$key] !== '';

        if ($has('sumup_token')) {
            config(['services.sumup.token' => $values['sumup_token']]);
        }
        if ($has('sumup_merchant_code')) {
            config(['services.sumup.merchant_code' => $values['sumup_merchant_code']]);
        }
        if (isset($values['sumup_sales_sync'])) {
            config(['services.sumup.sales_sync_enabled' => $values['sumup_sales_sync'] === '1']);
        }

        if ($has('mail_host')) {
            $port = (int) ($values['mail_port'] ?? 587) ?: 587;
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $values['mail_host'],
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : null,
                'mail.mailers.smtp.username' => $values['mail_username'] ?? null,
                'mail.mailers.smtp.password' => $values['mail_password'] ?? null,
            ]);
        }
        if ($has('mail_from_address')) {
            config(['mail.from.address' => $values['mail_from_address']]);
        }
        if ($has('mail_from_name')) {
            config(['mail.from.name' => $values['mail_from_name']]);
        }

        if ($has('turnstile_site_key') && $has('turnstile_secret')) {
            config([
                'services.turnstile.site_key' => $values['turnstile_site_key'],
                'services.turnstile.secret' => $values['turnstile_secret'],
            ]);
        }

        if (isset($values['require_2fa'])) {
            config(['filament.require_2fa' => $values['require_2fa'] === '1']);
        }
    }

    /**
     * @return array<string, string>  Klartextwerte (Geheimnisse entschlüsselt), nur gesetzte Schlüssel.
     */
    public static function values(): array
    {
        /** @var array<string, array{0: ?string, 1: ?string}> $rows */
        $rows = Cache::rememberForever(self::CACHE_KEY, static fn (): array => Setting::query()
            ->where('group', self::GROUP)
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(static fn (Setting $row): array => [$row->key => [$row->value, $row->type]])
            ->all());

        $out = [];
        foreach ($rows as $key => [$value, $type]) {
            if ($value === null || ! in_array($key, self::KEYS, true)) {
                continue;
            }
            if ($type === 'encrypted') {
                try {
                    $value = Crypt::decryptString($value);
                } catch (Throwable) {
                    continue; // z. B. nach Umzug mit neuem APP_KEY: Wert neu eintragen.
                }
            }
            $out[$key] = (string) $value;
        }

        return $out;
    }

    public static function put(string $key, ?string $value): void
    {
        if (! in_array($key, self::KEYS, true)) {
            return;
        }

        $secret = in_array($key, self::SECRETS, true);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => self::GROUP,
                'type' => $secret ? 'encrypted' : 'text',
                'value' => $value === null || $value === '' ? null : ($secret ? Crypt::encryptString($value) : $value),
            ],
        );

        Cache::forget(self::CACHE_KEY);
    }
}
