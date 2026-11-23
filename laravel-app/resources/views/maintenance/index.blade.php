<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemeliharaan | IT Inventory</title>
    <link rel="stylesheet"
          href="{{ asset('assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><span>IT Inventaris</span></div>
        <p class="menu-label">MENU UTAMA</p>
        <a class="nav" href="{{ route('dashboard') }}">▦ Dashboard</a>
        <a class="nav active" href="{{ route('maintenance.index') }}">🔧 Kelola Pemeliharaan</a>
        <div class="sidebar-note">
            <strong>Asset Management</strong>
            <p>Catat pekerjaan dan riwayat pemeliharaan aset.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="avatar" aria-hidden="true">IT</div>
                <div class="topbar-copy">
                    <p class="eyebrow">ASSET MANAGEMENT</p>
                    <h1>Kelola Pemeliharaan</h1>
                    <p class="subtitle">Catat pekerjaan dan pantau riwayat pemeliharaan aset.</p>
                </div>
            </div>

            <div class="topbar-actions">
                <button type="button" id="theme-toggle"
                        class="secondary-button" aria-pressed="false">
                    🌙 Mode Gelap
                </button>
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="secondary-button">Logout</button>
                </form>
            </div>
        </header>

        @if (session('success'))
            <div class="notice success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <section class="panel maintenance-form-panel">
            <div class="panel-heading">
                <div>
                    <h3>Catat Pemeliharaan</h3>
                    <p>Riwayat ini tidak mengubah kondisi aset secara otomatis.</p>
                </div>
            </div>

            <form action="{{ route('maintenance.store') }}" method="POST"
                  class="asset-form maintenance-form">
                @csrf
                <label>Aset *
                    <select name="asset_id" required>
                        <option value="">Pilih aset</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}"
                                @selected((string) old('asset_id') === (string) $asset->id)>
                                {{ $asset->kode_aset }} — {{ $asset->nama_barang }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>Tanggal Pemeliharaan *
                    <input type="date" name="maintenance_date"
                           value="{{ old('maintenance_date', now()->toDateString()) }}" required>
                </label>
                <label>Uraian Pekerjaan *
                    <textarea name="description" rows="3" maxlength="5000"
                              placeholder="Jelaskan pemeriksaan atau perbaikan yang dilakukan"
                              required>{{ old('description') }}</textarea>
                </label>
                <div class="form-row">
                    <label>Teknisi / Vendor
                        <input name="technician" maxlength="100"
                               value="{{ old('technician') }}"
                               placeholder="Nama teknisi atau vendor">
                    </label>
                    <label>Biaya (Rp)
                        <input type="number" name="cost" min="0" max="9999999999.99"
                               step="0.01" value="{{ old('cost') }}" placeholder="Opsional">
                    </label>
                </div>
                <div class="form-row">
                    <label>Status *
                        <select name="status" required>
                            @foreach (\App\Models\MaintenanceRecord::STATUSES as $status)
                                <option value="{{ $status }}"
                                    @selected(old('status', 'Dijadwalkan') === $status)>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Catatan
                        <textarea name="notes" rows="2" maxlength="5000"
                                  placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                    </label>
                </div>
                <button class="primary-button" type="submit">+ Simpan Riwayat</button>
            </form>
        </section>

        <section class="panel maintenance-history-panel">
            <div class="maintenance-history-heading">
                <div>
                    <h3>Riwayat Pemeliharaan</h3>
                    <p>{{ $records->total() }} catatan tersimpan</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="maintenance-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Aset</th>
                            <th>Uraian Pekerjaan</th>
                            <th>Teknisi / Vendor</th>
                            <th>Biaya</th>
                            <th>Status</th>
                            <th>Perbarui Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td>{{ $record->maintenance_date->format('Y-m-d') }}</td>
                                <td>
                                    <strong>{{ $record->asset_code }}</strong>
                                    <br><small>{{ $record->asset_name }}</small>
                                </td>
                                <td class="maintenance-description">
                                    {{ $record->description }}
                                    @if ($record->notes)
                                        <br><small>{{ $record->notes }}</small>
                                    @endif
                                </td>
                                <td>{{ $record->technician ?: '—' }}</td>
                                <td>
                                    {{ $record->cost !== null
                                        ? 'Rp '.number_format((float) $record->cost, 2, ',', '.')
                                        : '—' }}
                                </td>
                                <td>
                                    <span class="maintenance-status status-{{ \Illuminate\Support\Str::slug($record->status) }}">
                                        {{ $record->status }}
                                    </span>
                                </td>
                                <td>
                                    <form class="maintenance-status-form" method="POST"
                                          action="{{ route('maintenance.status', $record) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" aria-label="Status untuk {{ $record->asset_code }}">
                                            @foreach (\App\Models\MaintenanceRecord::STATUSES as $status)
                                                <option value="{{ $status }}"
                                                    @selected($record->status === $status)>
                                                    {{ $status }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="secondary-button">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    Belum ada riwayat pemeliharaan. Catat pekerjaan pertama melalui formulir di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer pagination-footer">
                <p>Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}
                    dari {{ $records->total() }} riwayat</p>
                <nav class="pagination" aria-label="Navigasi riwayat pemeliharaan">
                    @if ($records->onFirstPage())
                        <span class="page-link disabled">‹ Sebelumnya</span>
                    @else
                        <a class="page-link"
                           href="{{ route('maintenance.index', ['page' => $records->currentPage() - 1]) }}">
                            ‹ Sebelumnya
                        </a>
                    @endif
                    <span class="page-indicator">
                        Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }}
                    </span>
                    @if ($records->hasMorePages())
                        <a class="page-link"
                           href="{{ route('maintenance.index', ['page' => $records->currentPage() + 1]) }}">
                            Berikutnya ›
                        </a>
                    @else
                        <span class="page-link disabled">Berikutnya ›</span>
                    @endif
                </nav>
            </div>
        </section>

        <footer>IT Inventory Dashboard · Laravel</footer>
    </main>

    <script src="{{ asset('assets/dashboard.js') }}?v={{ filemtime(public_path('assets/dashboard.js')) }}"></script>
</body>
</html>
