<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'aset';

    protected $fillable = [
        'kode_aset',
        'nama_barang',
        'kategori',
        'lokasi',
        'jumlah',
        'kondisi',
        'tanggal_masuk',
        'tracking_mode',
        'merek',
        'model',
        'nomor_seri',
        'nama_pemegang',
        'departemen',
        'tanggal_pembelian',
        'garansi_sampai',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date:Y-m-d',
            'tanggal_pembelian' => 'date:Y-m-d',
            'garansi_sampai' => 'date:Y-m-d',
        ];
    }
}
