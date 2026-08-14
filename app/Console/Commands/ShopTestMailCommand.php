<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class ShopTestMailCommand extends Command
{
    protected $signature = 'shop:test-mail {email? : Empfänger (Standard: erste gültige aus order_notification_email / SHOP_ORDER_NOTIFICATION_EMAIL)}';

    protected $description = 'Sendet eine Test-E-Mail mit dem aktuellen MAIL_MAILER (log/smtp/sendmail/resend)';

    public function handle(): int
    {
        $to = $this->argument('email');
        if (! is_string($to) || $to === '') {
            $to = \App\Livewire\Shop\CheckoutPage::resolveShopOrderNotificationEmail();
        }
        if (! is_string($to) || $to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Keine gültige E-Mail. Nutzung: php artisan shop:test-mail deine@mail.de');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $this->info(sprintf('Sende Test-Mail an %s (Mailer: %s)…', $to, $mailer));

        try {
            Mail::raw('Shop Test-Mail — wenn du das liest, funktioniert der Versand.', function (Message $message) use ($to) {
                $message->to($to)->subject('Shop Test-Mail '.config('app.name'));
            });
        } catch (\Throwable $e) {
            $this->error('Fehler: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER=log — Nachricht steht in storage/logs/laravel.log, nicht im Posteingang.');
        } elseif ($mailer === 'resend' && blank(config('services.resend.key'))) {
            $this->warn('RESEND_API_KEY fehlt — Versand wird sehr wahrscheinlich fehlschlagen.');
        } elseif (in_array($mailer, ['smtp', 'sendmail'], true)) {
            $this->info('Versand ausgelöst (Postfix/SMTP/sendmail). Prüfe Posteingang, Spam und mail.log auf der VM.');
        } else {
            $this->info('Versand ausgelöst. Prüfe den Posteingang (und Spam).');
        }

        return self::SUCCESS;
    }
}
