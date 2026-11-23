<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\MaintenanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        $assets = Asset::query()
            ->orderBy('kode_aset')
            ->get(['id', 'kode_aset', 'nama_barang']);
        $records = MaintenanceRecord::query()
            ->with('asset')
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('maintenance.index', compact('assets', 'records'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:aset,id'],
            'maintenance_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'technician' => ['nullable', 'string', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['required', Rule::in(MaintenanceRecord::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($data): void {
            $asset = Asset::query()->lockForUpdate()->findOrFail($data['asset_id']);

            $record = MaintenanceRecord::create([
                ...$data,
                'asset_code' => $asset->kode_aset,
                'asset_name' => $asset->nama_barang,
            ]);

            AuditLog::query()->create([
                'admin_id' => Auth::id(),
                'username' => Auth::user()?->username ?? 'unknown',
                'action' => 'CREATE',
                'entity_type' => 'maintenance',
                'entity_id' => $record->id,
                'asset_id' => $asset->id,
                'asset_code' => $record->asset_code,
                'asset_name' => $record->asset_name,
                'description' => 'Mencatat pemeliharaan: '.$record->description
                    .' (status: '.$record->status.').',
            ]);
        });

        return redirect()
            ->route('maintenance.index')
            ->with('success', 'Riwayat pemeliharaan berhasil dicatat.');
    }

    public function updateStatus(Request $request, MaintenanceRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(MaintenanceRecord::STATUSES)],
        ]);

        DB::transaction(function () use ($data, $record): void {
            $lockedRecord = MaintenanceRecord::query()
                ->lockForUpdate()
                ->findOrFail($record->id);
            $previousStatus = $lockedRecord->status;

            if ($previousStatus === $data['status']) {
                return;
            }

            $lockedRecord->update($data);

            AuditLog::query()->create([
                'admin_id' => Auth::id(),
                'username' => Auth::user()?->username ?? 'unknown',
                'action' => 'UPDATE',
                'entity_type' => 'maintenance',
                'entity_id' => $lockedRecord->id,
                'asset_id' => $lockedRecord->asset_id,
                'asset_code' => $lockedRecord->asset_code,
                'asset_name' => $lockedRecord->asset_name,
                'description' => 'Status pemeliharaan diubah dari '.$previousStatus
                    .' menjadi '.$lockedRecord->status.'.',
            ]);
        });

        return redirect()
            ->route('maintenance.index')
            ->with('success', 'Status pemeliharaan berhasil diperbarui.');
    }
}
