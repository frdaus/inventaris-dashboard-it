<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->string('entity_type', 20)->default('asset')->after('action');
            }

            if (! Schema::hasColumn('audit_logs', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('asset_id');
            }
        });

        if (Schema::hasColumn('audit_logs', 'entity_id')
            && Schema::hasColumn('audit_logs', 'asset_id')) {
            DB::table('audit_logs')
                ->whereNull('entity_id')
                ->whereNotNull('asset_id')
                ->update(['entity_id' => DB::raw('asset_id')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $columns = array_values(array_filter(
            ['entity_type', 'entity_id'],
            fn (string $column): bool => Schema::hasColumn('audit_logs', $column),
        ));

        if ($columns !== []) {
            Schema::table('audit_logs', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
