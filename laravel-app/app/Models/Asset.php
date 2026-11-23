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
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date:Y-m-d',
        ];
    }
}
