<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'tracking_mode',
        'merek',
        'model',
        'nomor_seri',
        'nama_pemegang',
        'departemen',
        'tanggal_pembelian',
        'garansi_sampai',
    ];

    public function up(): void
    {
        $existingColumns = array_fill_keys(Schema::getColumnListing('aset'), true);
        $missingColumns = array_values(array_diff(self::COLUMNS, array_keys($existingColumns)));

        if ($missingColumns !== []) {
            Schema::table('aset', function (Blueprint $table) use ($missingColumns): void {
                if (in_array('tracking_mode', $missingColumns, true)) {
                    $table->enum('tracking_mode', ['individual', 'bulk'])
                        ->default('bulk');
                }

                foreach (['merek', 'model', 'nomor_seri', 'nama_pemegang', 'departemen'] as $column) {
                    if (! in_array($column, $missingColumns, true)) {
                        continue;
                    }

                    $definition = $table->string($column, 100)->nullable();
                    if ($column === 'nomor_seri') {
                        $definition->unique('aset_nomor_seri_unique');
                    }
                }

                foreach (['tanggal_pembelian', 'garansi_sampai'] as $column) {
                    if (! in_array($column, $missingColumns, true)) {
                        continue;
                    }

                    $table->date($column)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            self::COLUMNS,
            fn (string $column): bool => Schema::hasColumn('aset', $column),
        ));

        if ($columns !== []) {
            Schema::table('aset', function (Blueprint $table) use ($columns): void {
                if (in_array('nomor_seri', $columns, true)) {
                    $table->dropUnique('aset_nomor_seri_unique');
                }
                $table->dropColumn($columns);
            });
        }
    }
};
