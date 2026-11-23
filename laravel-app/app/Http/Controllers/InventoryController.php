<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Services\LegacyPasswordVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    private const CONDITIONS = ['Baik', 'Rusak', 'Maintenance'];

    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request, LegacyPasswordVerifier $legacyPasswords): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);
        $admin = Admin::where('username', trim($credentials['username']))->first();

        if (! $admin || ! $legacyPasswords->verify($credentials['password'], $admin->password_hash)) {
            return back()
                ->withErrors(['login' => 'Username atau password salah.'])
                ->onlyInput('username');
        }

        if (! str_starts_with($admin->password_hash, '$2y$')
            && ! str_starts_with($admin->password_hash, '$argon2i$')
            && ! str_starts_with($admin->password_hash, '$argon2id$')) {
            $admin->password_hash = Hash::make($credentials['password']);
            $admin->save();
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $categoryFilter = trim((string) $request->query('kategori', ''));
        $conditionFilter = (string) $request->query('kondisi', '');
        if (! in_array($conditionFilter, self::CONDITIONS, true)) {
            $conditionFilter = '';
        }
        $trackingModeFilter = (string) $request->query('tracking_mode', '');
        if (! in_array($trackingModeFilter, ['individual', 'bulk'], true)) {
            $trackingModeFilter = '';
        }

        $stats = $this->inventoryStats();
        $recentActivities = AuditLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get([
                'id',
                'username',
                'action',
                'entity_type',
                'asset_code',
                'asset_name',
                'created_at',
            ]);

        $categoryData = DB::table('aset')
            ->select('kategori')
            ->selectRaw('SUM(jumlah) AS total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get();

        $categories = DB::table('aset')
            ->select('kategori')
            ->whereNotNull('kategori')
            ->where('kategori', '<>', '')
            ->distinct()
            ->orderBy('kategori')
            ->get();

        $assetsQuery = Asset::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $like = '%'.$search.'%';
                    $searchQuery->where('kode_aset', 'like', $like)
                        ->orWhere('nama_barang', 'like', $like)
                        ->orWhere('kategori', 'like', $like)
                        ->orWhere('lokasi', 'like', $like)
                        ->orWhere('merek', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('nomor_seri', 'like', $like)
                        ->orWhere('nama_pemegang', 'like', $like)
                        ->orWhere('departemen', 'like', $like);
                });
            })
            ->when($categoryFilter !== '', fn ($query) => $query->where('kategori', $categoryFilter))
            ->when($conditionFilter !== '', fn ($query) => $query->where('kondisi', $conditionFilter))
            ->when($trackingModeFilter !== '', fn ($query) => $query->where('tracking_mode', $trackingModeFilter))
            ->orderByDesc('id');

        $totalAssets = (clone $assetsQuery)->count();
        $perPage = 10;
        $totalPages = max(1, (int) ceil($totalAssets / $perPage));
        $page = min(max(1, $request->integer('page', 1)), $totalPages);
        $assets = $assetsQuery->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->only(['q', 'kategori', 'kondisi', 'tracking_mode']));

        return view('index', [
            'stats' => $stats,
            'recent_activities' => $recentActivities,
            'assets' => $assets,
            'category_data' => $categoryData,
            'categories' => $categories,
            'search' => $search,
            'kategori_filter' => $categoryFilter,
            'kondisi_filter' => $conditionFilter,
            'tracking_mode_filter' => $trackingModeFilter,
            'page' => $page,
            'per_page' => $perPage,
            'total_assets' => $totalAssets,
            'total_pages' => $totalPages,
            'start_item' => $totalAssets ? $assets->firstItem() : 0,
            'end_item' => $assets->lastItem() ?? 0,
        ]);
    }

    public function statistics(): JsonResponse
    {
        return response()
            ->json($this->inventoryStats())
            ->header('Cache-Control', 'no-store, private');
    }

    private function inventoryStats(): object
    {
        return DB::table('aset')
            ->selectRaw('COUNT(*) AS total_jenis')
            ->selectRaw('COALESCE(SUM(jumlah), 0) AS total_unit')
            ->selectRaw("COALESCE(SUM(CASE WHEN kondisi = 'Baik' THEN jumlah ELSE 0 END), 0) AS unit_baik")
            ->selectRaw("COALESCE(SUM(CASE WHEN kondisi = 'Rusak' THEN jumlah ELSE 0 END), 0) AS unit_rusak")
            ->selectRaw("COALESCE(SUM(CASE WHEN kondisi = 'Maintenance' THEN jumlah ELSE 0 END), 0) AS unit_maintenance")
            ->first();
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'tracking_mode' => $request->input('tracking_mode', 'bulk'),
        ]);
        $validator = $this->assetValidator($request);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $asset = DB::transaction(function () use ($data): Asset {
            $asset = Asset::create($data);
            $this->writeAuditLog('CREATE', $asset, 'Menambahkan aset baru.');

            return $asset;
        });

        return redirect()->route('dashboard')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function destroy(int $asset_id): RedirectResponse
    {
        $asset = DB::transaction(function () use ($asset_id): ?Asset {
            $asset = Asset::query()->lockForUpdate()->find($asset_id);
            if (! $asset) {
                return null;
            }

            $description = json_encode(
                ['deleted' => $asset->getAttributes()],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
            $this->writeAuditLog('DELETE', $asset, $description);
            $asset->delete();

            return $asset;
        });

        if (! $asset) {
            return redirect()->route('dashboard')->with('error', 'Aset tidak ditemukan.');
        }

        return redirect()->route('dashboard')->with('success', 'Aset berhasil dihapus.');
    }

    public function edit(int $asset_id)
    {
        $asset = Asset::find($asset_id);
        abort_if(! $asset, 404, 'Aset tidak ditemukan.');

        return view('edit', compact('asset'));
    }

    public function update(Request $request, int $asset_id): RedirectResponse
    {
        $asset = Asset::find($asset_id);
        abort_if(! $asset, 404, 'Aset tidak ditemukan.');

        $request->merge([
            'tracking_mode' => $request->input('tracking_mode', $asset->tracking_mode),
        ]);
        $validator = $this->assetValidator($request, $asset);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        DB::transaction(function () use ($asset_id, $data): void {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset_id);
            $before = $lockedAsset->getAttributes();
            $lockedAsset->update($data);
            $after = $lockedAsset->fresh()->getAttributes();
            $description = json_encode(
                ['before' => $before, 'after' => $after],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
            $this->writeAuditLog('UPDATE', $lockedAsset, $description);
        });

        return redirect()->route('dashboard')->with('success', 'Data aset berhasil diperbarui.');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $categoryFilter = trim((string) $request->query('kategori', ''));
        $conditionFilter = (string) $request->query('kondisi', '');
        if (! in_array($conditionFilter, self::CONDITIONS, true)) {
            $conditionFilter = '';
        }
        $trackingModeFilter = (string) $request->query('tracking_mode', '');
        if (! in_array($trackingModeFilter, ['individual', 'bulk'], true)) {
            $trackingModeFilter = '';
        }

        $assets = Asset::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $like = '%'.$search.'%';
                    $searchQuery->where('kode_aset', 'like', $like)
                        ->orWhere('nama_barang', 'like', $like)
                        ->orWhere('kategori', 'like', $like)
                        ->orWhere('lokasi', 'like', $like)
                        ->orWhere('merek', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('nomor_seri', 'like', $like)
                        ->orWhere('nama_pemegang', 'like', $like)
                        ->orWhere('departemen', 'like', $like);
                });
            })
            ->when($categoryFilter !== '', fn ($query) => $query->where('kategori', $categoryFilter))
            ->when($conditionFilter !== '', fn ($query) => $query->where('kondisi', $conditionFilter))
            ->when($trackingModeFilter !== '', fn ($query) => $query->where('tracking_mode', $trackingModeFilter))
            ->orderByDesc('id')
            ->get([
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
            ]);

        $workbook = new Spreadsheet;
        $sheet = $workbook->getActiveSheet();
        $sheet->setTitle('Data Inventaris');
        $sheet->fromArray([
            'Kode Aset',
            'Nama Barang',
            'Kategori',
            'Pencatatan',
            'Merek',
            'Model',
            'Nomor Seri',
            'Lokasi',
            'Jumlah',
            'Kondisi',
            'Pemegang',
            'Departemen',
            'Tanggal Pembelian',
            'Garansi Sampai',
            'Tanggal Masuk',
        ], null, 'A1');

        $sheet->getStyle('A1:O1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:O1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1F3A5F');
        $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ($assets as $index => $asset) {
            $sheet->fromArray([
                $asset->kode_aset,
                $asset->nama_barang,
                $asset->kategori,
                $asset->tracking_mode === 'individual' ? 'Per unit' : 'Berdasarkan jumlah',
                $asset->merek ?? '',
                $asset->model ?? '',
                $asset->nomor_seri ?? '',
                $asset->lokasi ?? '',
                $asset->jumlah,
                $asset->kondisi ?? '',
                $asset->nama_pemegang ?? '',
                $asset->departemen ?? '',
                $asset->tanggal_pembelian?->format('Y-m-d') ?? '',
                $asset->garansi_sampai?->format('Y-m-d') ?? '',
                $asset->tanggal_masuk?->format('Y-m-d') ?? '',
            ], null, 'A'.($index + 2));
        }

        foreach ([
            'A' => 18,
            'B' => 28,
            'C' => 20,
            'D' => 20,
            'E' => 18,
            'F' => 18,
            'G' => 24,
            'H' => 24,
            'I' => 12,
            'J' => 18,
            'K' => 24,
            'L' => 22,
            'M' => 18,
            'N' => 18,
            'O' => 18,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return response()->streamDownload(
            static function () use ($workbook): void {
                (new Xlsx($workbook))->save('php://output');
                $workbook->disconnectWorksheets();
            },
            'data_inventaris.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function writeAuditLog(string $action, Asset $asset, ?string $description): void
    {
        AuditLog::query()->create([
            'admin_id' => Auth::id(),
            'username' => Auth::user()?->username ?? 'unknown',
            'action' => $action,
            'entity_type' => 'asset',
            'entity_id' => $asset->id,
            'asset_id' => $asset->id,
            'asset_code' => $asset->kode_aset,
            'asset_name' => $asset->nama_barang,
            'description' => $description,
        ]);
    }

    private function assetValidator(Request $request, ?Asset $asset = null): \Illuminate\Validation\Validator
    {
        $serialRule = Rule::unique('aset', 'nomor_seri');
        if ($asset) {
            $serialRule->ignore($asset->id);
        }

        $trackingMode = $request->input('tracking_mode');

        return Validator::make($request->all(), [
            'tracking_mode' => ['required', Rule::in(['individual', 'bulk'])],
            'kode_aset' => [
                'required',
                'string',
                'max:30',
                'regex:/^[\x20-\x7E]+$/D',
                Rule::unique('aset', 'kode_aset')->ignore($asset?->id),
            ],
            'nama_barang' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'merek' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'nomor_seri' => [
                'nullable',
                'string',
                'max:100',
                $serialRule,
                Rule::prohibitedIf($trackingMode === 'bulk'),
            ],
            'lokasi' => ['nullable', 'string', 'max:100'],
            'jumlah' => [
                'required',
                'integer',
                'min:1',
                Rule::when($trackingMode === 'individual', ['in:1']),
            ],
            'kondisi' => ['required', Rule::in(self::CONDITIONS)],
            'nama_pemegang' => ['nullable', 'string', 'max:100'],
            'departemen' => ['nullable', 'string', 'max:100'],
            'tanggal_pembelian' => ['nullable', 'date'],
            'garansi_sampai' => [
                'nullable',
                'date',
                Rule::when($request->filled('tanggal_pembelian'), ['after_or_equal:tanggal_pembelian']),
            ],
            'tanggal_masuk' => ['nullable', 'date'],
        ], [
            'tracking_mode.in' => 'Pilih pencatatan per unit atau berdasarkan jumlah.',
            'kode_aset.unique' => $asset
                ? 'Kode aset sudah digunakan aset lain.'
                : 'Kode aset sudah digunakan. Gunakan kode yang berbeda.',
            'kode_aset.regex' => 'Kode aset hanya boleh berisi karakter ASCII agar QR dan barcode dapat dicetak.',
            'jumlah.min' => $asset
                ? 'Jumlah harus minimal 1.'
                : 'Jumlah harus berupa bilangan bulat minimal 1.',
            'jumlah.in' => 'Aset per unit harus memiliki jumlah tepat 1.',
            'kondisi.in' => $asset ? 'Kondisi aset tidak valid.' : 'Pilihan kondisi tidak valid.',
            'nomor_seri.unique' => 'Nomor seri sudah terdaftar pada aset lain.',
            'nomor_seri.prohibited' => 'Nomor seri hanya dapat diisi untuk pencatatan per unit.',
            'garansi_sampai.after_or_equal' => 'Tanggal garansi tidak boleh sebelum tanggal pembelian.',
        ]);
    }
}
