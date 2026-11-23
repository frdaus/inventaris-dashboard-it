<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Asset;
use App\Services\LegacyPasswordVerifier;
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

        $stats = DB::table('aset')
            ->selectRaw('COUNT(*) AS total_jenis')
            ->selectRaw('COALESCE(SUM(jumlah), 0) AS total_unit')
            ->selectRaw("COALESCE(SUM(CASE WHEN kondisi = 'Baik' THEN jumlah ELSE 0 END), 0) AS unit_baik")
            ->selectRaw("COALESCE(SUM(CASE WHEN kondisi = 'Rusak' THEN jumlah ELSE 0 END), 0) AS unit_rusak")
            ->selectRaw("COALESCE(SUM(CASE WHEN kondisi = 'Maintenance' THEN jumlah ELSE 0 END), 0) AS unit_maintenance")
            ->first();

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
                        ->orWhere('lokasi', 'like', $like);
                });
            })
            ->when($categoryFilter !== '', fn ($query) => $query->where('kategori', $categoryFilter))
            ->when($conditionFilter !== '', fn ($query) => $query->where('kondisi', $conditionFilter))
            ->orderByDesc('id');

        $totalAssets = (clone $assetsQuery)->count();
        $perPage = 10;
        $totalPages = max(1, (int) ceil($totalAssets / $perPage));
        $page = min(max(1, $request->integer('page', 1)), $totalPages);
        $assets = $assetsQuery->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->only(['q', 'kategori', 'kondisi']));

        return view('index', [
            'stats' => $stats,
            'assets' => $assets,
            'category_data' => $categoryData,
            'categories' => $categories,
            'search' => $search,
            'kategori_filter' => $categoryFilter,
            'kondisi_filter' => $conditionFilter,
            'page' => $page,
            'per_page' => $perPage,
            'total_assets' => $totalAssets,
            'total_pages' => $totalPages,
            'start_item' => $totalAssets ? $assets->firstItem() : 0,
            'end_item' => $assets->lastItem() ?? 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'kode_aset' => ['required', 'string', 'max:30', 'unique:aset,kode_aset'],
            'nama_barang' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'lokasi' => ['nullable', 'string', 'max:100'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'kondisi' => ['required', Rule::in(self::CONDITIONS)],
            'tanggal_masuk' => ['nullable', 'date'],
        ], [
            'kode_aset.unique' => 'Kode aset sudah digunakan. Gunakan kode yang berbeda.',
            'jumlah.min' => 'Jumlah harus berupa bilangan bulat minimal 1.',
            'kondisi.in' => 'Pilihan kondisi tidak valid.',
        ]);

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

        $validator = Validator::make($request->all(), [
            'kode_aset' => ['required', 'string', 'max:30', Rule::unique('aset', 'kode_aset')->ignore($asset->id)],
            'nama_barang' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'lokasi' => ['nullable', 'string', 'max:100'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'kondisi' => ['required', Rule::in(self::CONDITIONS)],
            'tanggal_masuk' => ['nullable', 'date'],
        ], [
            'kode_aset.unique' => 'Kode aset sudah digunakan aset lain.',
            'jumlah.min' => 'Jumlah harus minimal 1.',
            'kondisi.in' => 'Kondisi aset tidak valid.',
        ]);

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

        $assets = Asset::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $like = '%'.$search.'%';
                    $searchQuery->where('kode_aset', 'like', $like)
                        ->orWhere('nama_barang', 'like', $like)
                        ->orWhere('kategori', 'like', $like)
                        ->orWhere('lokasi', 'like', $like);
                });
            })
            ->when($categoryFilter !== '', fn ($query) => $query->where('kategori', $categoryFilter))
            ->when($conditionFilter !== '', fn ($query) => $query->where('kondisi', $conditionFilter))
            ->orderByDesc('id')
            ->get([
                'kode_aset',
                'nama_barang',
                'kategori',
                'lokasi',
                'jumlah',
                'kondisi',
                'tanggal_masuk',
            ]);

        $workbook = new Spreadsheet;
        $sheet = $workbook->getActiveSheet();
        $sheet->setTitle('Data Inventaris');
        $sheet->fromArray([
            'Kode Aset',
            'Nama Barang',
            'Kategori',
            'Lokasi',
            'Jumlah',
            'Kondisi',
            'Tanggal Masuk',
        ], null, 'A1');

        foreach (['A1:G1'] as $range) {
            $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1F3A5F');
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        foreach ($assets as $index => $asset) {
            $sheet->fromArray([
                $asset->kode_aset,
                $asset->nama_barang,
                $asset->kategori,
                $asset->lokasi ?? '',
                $asset->jumlah,
                $asset->kondisi ?? '',
                $asset->tanggal_masuk?->format('Y-m-d') ?? '',
            ], null, 'A'.($index + 2));
        }

        foreach (['A' => 18, 'B' => 28, 'C' => 20, 'D' => 24, 'E' => 12, 'F' => 18, 'G' => 18] as $column => $width) {
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
        DB::table('audit_logs')->insert([
            'admin_id' => Auth::id(),
            'username' => Auth::user()?->username ?? 'unknown',
            'action' => $action,
            'asset_id' => $asset->id,
            'asset_code' => $asset->kode_aset,
            'asset_name' => $asset->nama_barang,
            'description' => $description,
        ]);
    }
}
