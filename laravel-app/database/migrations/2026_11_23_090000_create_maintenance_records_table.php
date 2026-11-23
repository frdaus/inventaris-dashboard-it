<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('maintenance_records')) {
            Schema::create('maintenance_records', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->integer('asset_id')->nullable();
                $table->string('asset_code', 30);
                $table->string('asset_name', 100);
                $table->date('maintenance_date');
                $table->text('description');
                $table->string('technician', 100)->nullable();
                $table->decimal('cost', 12, 2)->nullable();
                $table->string('status', 20)->default('Dijadwalkan');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'maintenance_date']);
            });
        }

        if (! $this->hasAssetForeignKey()) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE maintenance_records MODIFY asset_id INT NULL');
            }

            Schema::table('maintenance_records', function (Blueprint $table): void {
                $table->foreign('asset_id')
                    ->references('id')
                    ->on('aset')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }

    private function hasAssetForeignKey(): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA foreign_key_list(maintenance_records)'))
                ->contains(fn (object $key): bool => $key->from === 'asset_id'
                    && $key->table === 'aset'
                    && $key->to === 'id');
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'maintenance_records')
            ->where('COLUMN_NAME', 'asset_id')
            ->where('REFERENCED_TABLE_NAME', 'aset')
            ->exists();
    }
};
