<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class ActivityController extends Controller
{
    private const FIELD_LABELS = [
        'kode_aset' => 'Kode aset',
        'nama_barang' => 'Nama barang',
        'kategori' => 'Kategori',
        'lokasi' => 'Lokasi',
        'jumlah' => 'Jumlah',
        'kondisi' => 'Kondisi',
        'tanggal_masuk' => 'Tanggal masuk',
        'tracking_mode' => 'Pola pencatatan',
        'merek' => 'Merek',
        'model' => 'Model',
        'nomor_seri' => 'Nomor seri',
        'nama_pemegang' => 'Pemegang',
        'departemen' => 'Departemen',
        'tanggal_pembelian' => 'Tanggal pembelian',
        'garansi_sampai' => 'Garansi sampai',
    ];

    public function index(): View
    {
        $activities = AuditLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        $activities->getCollection()->transform(
            fn (AuditLog $activity): AuditLog => $this->present($activity),
        );

        return view('activity.index', compact('activities'));
    }

    private function present(AuditLog $activity): AuditLog
    {
        $description = $activity->description;
        $summary = $description ?: 'Tidak ada rincian tambahan.';
        $changes = [];
        $decoded = null;

        if (is_string($description) && str_starts_with(ltrim($description), '{')) {
            $decoded = json_decode($description, true);
        }

        if (is_array($decoded) && isset($decoded['before'], $decoded['after'])
            && is_array($decoded['before']) && is_array($decoded['after'])) {
            foreach ($decoded['before'] as $field => $before) {
                if (! array_key_exists($field, $decoded['after'])) {
                    continue;
                }

                $after = $decoded['after'][$field];
                if ($before === $after) {
                    continue;
                }

                $changes[] = [
                    'label' => self::FIELD_LABELS[$field] ?? str($field)->replace('_', ' ')->title()->toString(),
                    'before' => $this->formatValue($before),
                    'after' => $this->formatValue($after),
                ];
            }

            $summary = 'Data aset diperbarui.';
        } elseif (is_array($decoded) && isset($decoded['deleted']) && is_array($decoded['deleted'])) {
            foreach (self::FIELD_LABELS as $field => $label) {
                if (! array_key_exists($field, $decoded['deleted'])) {
                    continue;
                }

                $changes[] = [
                    'label' => $label,
                    'before' => $this->formatValue($decoded['deleted'][$field]),
                    'after' => '—',
                ];
            }

            $summary = 'Aset dihapus; data terakhirnya tersimpan dalam audit.';
        }

        $activity->setAttribute('audit_summary', $summary);
        $activity->setAttribute('audit_changes', $changes);
        $activity->setAttribute('action_label', match ($activity->action) {
            'CREATE' => 'Ditambahkan',
            'UPDATE' => 'Diperbarui',
            'DELETE' => 'Dihapus',
            default => $activity->action,
        });
        $activity->setAttribute('entity_label', match ($activity->entity_type) {
            'maintenance' => 'Pemeliharaan',
            default => 'Aset',
        });

        return $activity;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string) $value;
    }
}
