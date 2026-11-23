<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        Schema::table('admin', function (Blueprint $table): void {
            if (! Schema::hasColumn('admin', 'role')) {
                $table->string('role', 20)->default('admin')->after('password_hash');
            }

            if (! Schema::hasColumn('admin', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin')) {
            return;
        }

        $columns = array_values(array_filter(
            ['role', 'is_active'],
            fn (string $column): bool => Schema::hasColumn('admin', $column),
        ));

        if ($columns !== []) {
            Schema::table('admin', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
