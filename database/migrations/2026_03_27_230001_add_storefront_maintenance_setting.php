<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Setting::query()->where('key', 'storefront_maintenance')->exists()) {
            Setting::query()->create([
                'key' => 'storefront_maintenance',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
            ]);
        }
    }

    public function down(): void
    {
        Setting::query()->where('key', 'storefront_maintenance')->delete();
    }
};
