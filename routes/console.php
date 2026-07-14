<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mochi:reset-admin {--email=admin@mochicards.test} {--password=password}', function (): void {
    $email = (string) $this->option('email');
    $password = (string) $this->option('password');

    User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => 'Mochi Admin',
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ],
    );

    $this->info("Filament-Admin gesetzt: {$email} (Passwort wie angegeben — danach ggf. Passkey im Profil neu einrichten).");
})->purpose('Legt den Admin-Benutzer an oder setzt Passwort/Rolle zurück (Klartext-Passwort wird vom Model gehasht).');
