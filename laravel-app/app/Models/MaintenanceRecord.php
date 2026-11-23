<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    public const STATUSES = ['Dijadwalkan', 'Berlangsung', 'Selesai'];

    protected $fillable = [
        'asset_id',
        'asset_code',
        'asset_name',
        'maintenance_date',
        'description',
        'technician',
        'cost',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date:Y-m-d',
            'cost' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
