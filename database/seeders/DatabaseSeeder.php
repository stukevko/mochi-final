<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\ShopLink;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@mochicards.test'],
            [
                'name' => 'Mochi Admin',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        foreach (
            [
                ['title' => 'Impressum', 'slug' => 'impressum', 'body' => '<p>Angaben gemäß gesetzlicher Impressumspflicht (Platzhalter).</p>'],
                ['title' => 'Widerruf', 'slug' => 'widerruf', 'body' => '<p>Widerrufsbelehrung (Platzhalter).</p>'],
                ['title' => 'Datenschutz', 'slug' => 'datenschutz', 'body' => '<p>Datenschutzerklärung (Platzhalter).</p>'],
            ] as $row
        ) {
            CmsPage::query()->create($row);
        }

        ShopLink::query()->create([
            'label' => 'Zum Online-Shop',
            'url' => 'https://example.com',
            'sort_order' => 0,
        ]);

        SiteSetting::current();

        $this->call(PresentationSeeder::class);
        $this->call(DemoShopSeeder::class);
    }
}
