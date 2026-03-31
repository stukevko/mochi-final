<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Setting::query()->where('key', 'font_family')->exists()) {
            Setting::query()->create([
                'key' => 'font_family',
                'value' => 'Inter',
                'type' => 'string',
                'group' => 'theme',
            ]);
        }
    }

    public function down(): void
    {
        Setting::query()->where('key', 'font_family')->delete();
    }
};
