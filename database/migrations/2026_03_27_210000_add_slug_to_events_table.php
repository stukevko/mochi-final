<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });

        $rows = DB::table('events')->orderBy('id')->get();
        foreach ($rows as $row) {
            $base = Str::slug($row->title) ?: 'event';
            $slug = $base;
            $n = 1;
            while (DB::table('events')->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $slug = $base.'-'.$n++;
            }
            DB::table('events')->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
