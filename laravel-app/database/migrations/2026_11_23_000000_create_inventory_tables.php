<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aset')) {
            Schema::create('aset', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('kode_aset', 30)->unique();
                $table->string('nama_barang', 100);
                $table->string('kategori', 50);
                $table->string('lokasi', 100)->nullable();
                $table->unsignedInteger('jumlah')->default(1);
                $table->enum('kondisi', ['Baik', 'Rusak', 'Maintenance'])->default('Baik')->nullable();
                $table->date('tanggal_masuk')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('admin')) {
            Schema::create('admin', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('username', 50)->unique();
                $table->string('password_hash');
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedInteger('admin_id')->nullable()->index('idx_audit_admin_id');
                $table->string('username', 50);
                $table->enum('action', ['CREATE', 'UPDATE', 'DELETE']);
                $table->unsignedInteger('asset_id')->nullable()->index('idx_audit_asset_id');
                $table->string('asset_code', 30)->nullable();
                $table->string('asset_name', 100)->nullable();
                $table->text('description')->nullable();
                $table->timestamp('created_at')->useCurrent()->index('idx_audit_created_at');
                $table->index('action', 'idx_audit_action');
            });
        }
    }

    public function down(): void
    {
        // These tables may predate Laravel and contain the existing inventory data.
    }
};
