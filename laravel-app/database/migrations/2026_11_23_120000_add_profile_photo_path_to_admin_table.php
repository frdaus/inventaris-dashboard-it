<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin') && ! Schema::hasColumn('admin', 'profile_photo_path')) {
            Schema::table('admin', function (Blueprint $table): void {
                $table->string('profile_photo_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin') && Schema::hasColumn('admin', 'profile_photo_path')) {
            Schema::table('admin', function (Blueprint $table): void {
                $table->dropColumn('profile_photo_path');
            });
        }
    }
};
