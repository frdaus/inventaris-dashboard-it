<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory Dashboard</title>
    <link rel="stylesheet"
          href="{{ asset('assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><span>IT Inventaris</span></div>
        <p class="menu-label">MENU UTAMA</p>
        <a class="nav active" href="{{ route('dashboard') }}" aria-current="page">
            <span class="nav-icon" aria-hidden="true">▦</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a class="nav" href="{{ route('maintenance.index') }}">
            <span class="nav-icon" aria-hidden="true">🔧</span>
            <span class="nav-label">Kelola Pemeliharaan</span>
        </a>
        <a class="nav" href="{{ route('activity.index') }}">
            <span class="nav-icon" aria-hidden="true">◷</span>
            <span class="nav-label">Riwayat Aktivitas</span>
        </a>
        <div class="sidebar-note">
            <strong>Asset Management</strong>
            <p>Monitoring inventaris IT dalam satu dashboard.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="topbar-copy">
                    <p class="eyebrow">ASSET MANAGEMENT</p>
                    <h1>Dashboard Inventaris</h1>
                    <p class="subtitle">Ringkasan dan pengelolaan aset IT</p>
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
        @if (session('error'))
            <div class="notice error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        <section class="stats-section" aria-label="Ringkasan inventaris">
            <p class="stats-live-status" id="stats-live-status" role="status" aria-live="polite">
                <span class="live-indicator" aria-hidden="true"></span>
                <span class="stats-live-copy">Memperbarui otomatis tiap 30 detik</span>
            </p>
            <div class="stats" data-stats-url="{{ route('dashboard.statistics') }}">
                <article class="stat-card stat-total">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="13" rx="2"></rect>
                            <path d="M8 21h8m-4-4v4"></path>
                        </svg>
                    </div>
                    <div class="stat-copy">
                        <p>Total Aset</p>
                        <h2 data-stat-value="total_unit">{{ $stats->total_unit }}</h2>
                        <small><span data-stat-value="total_jenis">{{ $stats->total_jenis }}</span> jenis aset terdaftar</small>
                    </div>
                    <svg class="stat-sparkline" viewBox="0 0 160 54" preserveAspectRatio="none" aria-hidden="true">
                        <path class="spark-area" d="M0 49 C18 45 21 50 38 41 S60 46 78 31 S101 38 116 25 S139 28 160 4 L160 54 L0 54 Z"></path>
                        <path class="spark-line" d="M0 49 C18 45 21 50 38 41 S60 46 78 31 S101 38 116 25 S139 28 160 4"></path>
                    </svg>
                </article>
                <article class="stat-card stat-active">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="m5 12 4 4L19 6"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    </div>
                    <div class="stat-copy">
                        <p>Aset Aktif</p>
                        <h2 data-stat-value="unit_baik">{{ $stats->unit_baik }}</h2>
                        <small>Unit kondisi baik</small>
                    </div>
                    <svg class="stat-sparkline" viewBox="0 0 160 54" preserveAspectRatio="none" aria-hidden="true">
                        <path class="spark-area" d="M0 50 C18 47 26 48 40 39 S61 44 77 30 S97 34 114 23 S139 27 160 5 L160 54 L0 54 Z"></path>
                        <path class="spark-line" d="M0 50 C18 47 26 48 40 39 S61 44 77 30 S97 34 114 23 S139 27 160 5"></path>
                    </svg>
                </article>
                <article class="stat-card stat-repair">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18a2.1 2.1 0 0 0 3 3l5.3-5.3a5 5 0 0 0 6.4-6.4L14 13l-3-3 3.7-3.7Z"></path>
                        </svg>
                    </div>
                    <div class="stat-copy">
                        <p>Dalam Perbaikan</p>
                        <h2 data-stat-value="unit_maintenance">{{ $stats->unit_maintenance }}</h2>
                        <small>Unit dalam pemeliharaan</small>
                    </div>
                    <svg class="stat-sparkline" viewBox="0 0 160 54" preserveAspectRatio="none" aria-hidden="true">
                        <path class="spark-area" d="M0 50 C20 47 23 49 40 39 S59 43 77 31 S98 36 114 24 S140 27 160 6 L160 54 L0 54 Z"></path>
                        <path class="spark-line" d="M0 50 C20 47 23 49 40 39 S59 43 77 31 S98 36 114 24 S140 27 160 6"></path>
                    </svg>
                </article>
                <article class="stat-card stat-inactive">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"></path>
                        </svg>
                    </div>
                    <div class="stat-copy">
                        <p>Tidak Aktif</p>
                        <h2 data-stat-value="unit_rusak">{{ $stats->unit_rusak }}</h2>
                        <small>Unit kondisi rusak</small>
                    </div>
                    <svg class="stat-sparkline" viewBox="0 0 160 54" preserveAspectRatio="none" aria-hidden="true">
                        <path class="spark-area" d="M0 50 C18 48 25 46 39 40 S61 44 77 31 S98 35 115 24 S139 28 160 5 L160 54 L0 54 Z"></path>
                        <path class="spark-line" d="M0 50 C18 48 25 46 39 40 S61 44 77 31 S98 35 115 24 S139 28 160 5"></path>
                    </svg>
                </article>
            </div>
        </section>

        <section class="content-grid">
            <article class="panel chart-panel">
                <div class="panel-heading">
                    <div>
                        <h3>Aset per Kategori</h3>
                        <p>Jumlah unit berdasarkan kategori</p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </article>

            <article class="panel form-panel">
                <div class="panel-heading">
                    <div>
                        <h3>Tambah Aset</h3>
                        <p>Masukkan data inventaris baru</p>
                    </div>
                </div>

                <form action="{{ route('tambah_aset') }}" method="POST" class="asset-form">
                    @csrf
                    <label>Kode Aset *
                        <input name="kode_aset" maxlength="30"
                               value="{{ old('kode_aset') }}"
                               placeholder="Contoh: AST-001" required>
                    </label>
                    <label>Nama Barang *
                        <input name="nama_barang" maxlength="100"
                               value="{{ old('nama_barang') }}"
                               placeholder="Contoh: Laptop Dell" required>
                    </label>
                    <label>Kategori *
                        <input name="kategori" maxlength="50"
                               value="{{ old('kategori') }}"
                               placeholder="Laptop, Network, Server..." required>
                    </label>
                    <label>Pola Pencatatan *
                        <select name="tracking_mode" required>
                            <option value="individual"
                                @selected(old('tracking_mode', 'individual') === 'individual')>
                                Per unit
                            </option>
                            <option value="bulk"
                                @selected(old('tracking_mode') === 'bulk')>
                                Berdasarkan jumlah
                            </option>
                        </select>
                    </label>
                    <div class="form-row">
                        <label>Merek
                            <input name="merek" maxlength="100"
                                   value="{{ old('merek') }}"
                                   placeholder="Contoh: Dell">
                        </label>
                        <label>Model
                            <input name="model" maxlength="100"
                                   value="{{ old('model') }}"
                                   placeholder="Contoh: Latitude 5450">
                        </label>
                    </div>
                    <label>Nomor Seri
                        <input name="nomor_seri" maxlength="100"
                               value="{{ old('nomor_seri') }}"
                               placeholder="Khusus pencatatan per unit">
                        <small>Nomor seri hanya untuk pencatatan per unit.</small>
                    </label>
                    <label>Lokasi
                        <input name="lokasi" maxlength="100"
                               value="{{ old('lokasi') }}"
                               placeholder="Contoh: Ruang IT">
                    </label>
                    <div class="form-row">
                        <label>Pemegang
                            <input name="nama_pemegang" maxlength="100"
                                   value="{{ old('nama_pemegang') }}"
                                   placeholder="Nama pengguna">
                        </label>
                        <label>Departemen
                            <input name="departemen" maxlength="100"
                                   value="{{ old('departemen') }}"
                                   placeholder="Contoh: Finance">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>Jumlah *
                            <input type="number" name="jumlah" min="1"
                                   value="{{ old('jumlah', 1) }}" required>
                        </label>
                        <label>Kondisi
                            <select name="kondisi">
                                @foreach (['Baik', 'Rusak', 'Maintenance'] as $condition)
                                    <option @selected(old('kondisi', 'Baik') === $condition)>
                                        {{ $condition }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-row">
                        <label>Tanggal Pembelian
                            <input type="date" name="tanggal_pembelian"
                                   value="{{ old('tanggal_pembelian') }}">
                        </label>
                        <label>Garansi Sampai
                            <input type="date" name="garansi_sampai"
                                   value="{{ old('garansi_sampai') }}">
                        </label>
                    </div>
                    <label>Tanggal Masuk
                        <input type="date" name="tanggal_masuk"
                               value="{{ old('tanggal_masuk') }}">
                    </label>
                    <button class="primary-button" type="submit">
                        + Simpan Aset
                    </button>
                </form>
            </article>
        </section>

        <section class="panel table-panel">
            <div class="table-header">
                <div>
                    <h3>Data Inventaris</h3>
                    <p>Daftar aset dari database MySQL</p>
                </div>

                <form class="search-form" method="GET" action="{{ route('dashboard') }}">
                    <div class="filter-field">
                        <label for="filter-q">Cari Aset</label>
                        <input id="filter-q" type="search" name="q"
                               value="{{ $search }}"
                               placeholder="Kode, nama, seri, pemegang...">
                    </div>
                    <div class="filter-field">
                        <label for="filter-kategori">Kategori</label>
                        <select id="filter-kategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $item)
                                <option value="{{ $item->kategori }}"
                                    @selected($kategori_filter === $item->kategori)>
                                    {{ $item->kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="filter-tracking">Pola Pencatatan</label>
                        <select id="filter-tracking" name="tracking_mode">
                            <option value="">Semua Pola</option>
                            <option value="individual"
                                @selected($tracking_mode_filter === 'individual')>
                                Per unit
                            </option>
                            <option value="bulk"
                                @selected($tracking_mode_filter === 'bulk')>
                                Berdasarkan jumlah
                            </option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="filter-kondisi">Kondisi</label>
                        <select id="filter-kondisi" name="kondisi">
                            <option value="">Semua Kondisi</option>
                            @foreach (['Baik', 'Rusak', 'Maintenance'] as $condition)
                                <option value="{{ $condition }}"
                                    @selected($kondisi_filter === $condition)>
                                    {{ $condition }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="secondary-button filter-button">
                            Cari / Filter
                        </button>
                        <a class="secondary-button export-button"
                           href="{{ route('export_excel', [
                               'q' => $search,
                               'kategori' => $kategori_filter,
                               'kondisi' => $kondisi_filter,
                               'tracking_mode' => $tracking_mode_filter,
                           ]) }}">
                            <span aria-hidden="true">📥</span> Export Excel
                        </a>
                    </div>
                    @if ($search || $kategori_filter || $kondisi_filter || $tracking_mode_filter)
                        <a class="clear-link" href="{{ route('dashboard') }}">Reset</a>
                    @endif
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Aset</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Pencatatan</th>
                            <th>Merek / Model</th>
                            <th>Nomor Seri</th>
                            <th>Lokasi</th>
                            <th>Jumlah</th>
                            <th>Kondisi</th>
                            <th>Pemegang / Departemen</th>
                            <th>Garansi Sampai</th>
                            <th>Tanggal Masuk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            <tr>
                                <td class="asset-code">{{ $asset->kode_aset }}</td>
                                <td>{{ $asset->nama_barang }}</td>
                                <td>{{ $asset->kategori }}</td>
                                <td>
                                    {{ $asset->tracking_mode === 'individual' ? 'Per unit' : 'Berdasarkan jumlah' }}
                                </td>
                                <td>{{ trim(($asset->merek ?? '').' '.($asset->model ?? '')) ?: '-' }}</td>
                                <td>{{ $asset->nomor_seri ?: '-' }}</td>
                                <td>{{ $asset->lokasi ?: '-' }}</td>
                                <td>{{ $asset->jumlah }}</td>
                                <td>
                                    <span class="status status-{{ strtolower($asset->kondisi ?? '') }}">
                                        {{ $asset->kondisi ?? 'Belum diisi' }}
                                    </span>
                                </td>
                                <td>
                                    {{ $asset->nama_pemegang ?: '-' }}
                                    @if ($asset->departemen)
                                        <br><small>{{ $asset->departemen }}</small>
                                    @endif
                                </td>
                                <td>{{ $asset->garansi_sampai?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $asset->tanggal_masuk?->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="{{ route('asset.label', $asset->id) }}"
                                           class="label-button" target="_blank" rel="noopener"
                                           aria-label="Cetak label {{ $asset->kode_aset }}">Label</a>
                                        <a href="{{ route('edit_aset', $asset->id) }}"
                                           class="edit-button">Edit</a>
                                        <form method="POST"
                                              action="{{ route('hapus_aset', $asset->id) }}"
                                              onsubmit="return confirm('Yakin ingin menghapus aset ini?')">
                                            @csrf
                                            <button type="submit" class="delete-button">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="empty-state">
                                    @if ($search || $kategori_filter || $kondisi_filter || $tracking_mode_filter)
                                        Tidak ada aset yang cocok dengan pencarian.
                                    @else
                                        Belum ada data inventaris. Tambahkan aset
                                        menggunakan formulir di atas.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer pagination-footer">
                <p>Menampilkan {{ $start_item }}–{{ $end_item }} dari {{ $total_assets }} aset</p>
                <nav class="pagination" aria-label="Navigasi halaman">
                    @if ($page > 1)
                        <a href="{{ route('dashboard', array_merge(request()->query(), ['page' => $page - 1])) }}"
                           class="page-link">‹ Sebelumnya</a>
                    @else
                        <span class="page-link disabled">‹ Sebelumnya</span>
                    @endif
                    <span class="page-indicator">
                        Halaman {{ $page }} dari {{ $total_pages }}
                    </span>
                    @if ($page < $total_pages)
                        <a href="{{ route('dashboard', array_merge(request()->query(), ['page' => $page + 1])) }}"
                           class="page-link">Berikutnya ›</a>
                    @else
                        <span class="page-link disabled">Berikutnya ›</span>
                    @endif
                </nav>
            </div>
        </section>

        <footer>IT Inventory Dashboard · Laravel</footer>
    </main>

    <script>
        const categoryData = @json($category_data);
    </script>
    <script src="{{ asset('assets/dashboard.js') }}?v={{ filemtime(public_path('assets/dashboard.js')) }}"></script>
</body>
</html>
