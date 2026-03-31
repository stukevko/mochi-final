<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ShopEnsureAdminCommand extends Command
{
    protected $signature = 'shop:ensure-admin
                            {--email=admin@shop.de : Admin-E-Mail}
                            {--password=password : Neues Klartext-Passwort (wird per Model-Cast gehasht)}
                            {--name=Admin : Anzeigename}';

    protected $description = 'Setzt oder aktualisiert den Filament-Admin (Rolle admin, aktiv) — z. B. wenn Login unter /admin fehlschlägt';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');
        $name = (string) $this->option('name');

        $user = User::query()->where('email', $email)->first() ?? new User;

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ])->save();

        $this->info('Administrator gesetzt: '.$email);
        $this->line('Login: '.$email.' / (eingegebenes Passwort) unter /admin');

        return self::SUCCESS;
    }
}
