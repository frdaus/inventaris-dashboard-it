<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory Dashboard</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><span>IT Inventaris</span></div>
        <p class="menu-label">MENU UTAMA</p>
        <a class="nav active" href="{{ route('dashboard') }}">▦ Dashboard</a>
        <div class="sidebar-note">
            <strong>Asset Management</strong>
            <p>Monitoring inventaris IT dalam satu dashboard.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-brand">
                <div class="avatar" aria-hidden="true">IT</div>
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

        <section class="stats">
            <article class="stat-card">
                <div class="stat-icon purple">▣</div>
                <p>Total Unit Aset</p>
                <h2>{{ $stats->total_unit }}</h2>
                <small>{{ $stats->total_jenis }} jenis aset terdaftar</small>
            </article>
            <article class="stat-card">
                <div class="stat-icon green">✓</div>
                <p>Kondisi Baik</p>
                <h2>{{ $stats->unit_baik }}</h2>
                <small>Unit dalam kondisi baik</small>
            </article>
            <article class="stat-card">
                <div class="stat-icon red">!</div>
                <p>Kondisi Rusak</p>
                <h2>{{ $stats->unit_rusak }}</h2>
                <small>Unit membutuhkan tindak lanjut</small>
            </article>
            <article class="stat-card">
                <div class="stat-icon orange">⚙</div>
                <p>Maintenance</p>
                <h2>{{ $stats->unit_maintenance }}</h2>
                <small>Unit dalam pemeliharaan</small>
            </article>
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
                    <label>Lokasi
                        <input name="lokasi" maxlength="100"
                               value="{{ old('lokasi') }}"
                               placeholder="Contoh: Ruang IT">
                    </label>
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
                               placeholder="Kode, nama, atau lokasi...">
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
                    <button type="submit" class="secondary-button">
                        Cari / Filter
                    </button>
                    <a class="secondary-button export-button"
                       href="{{ route('export_excel', [
                           'q' => $search,
                           'kategori' => $kategori_filter,
                           'kondisi' => $kondisi_filter,
                       ]) }}">
                        <span aria-hidden="true">📥</span> Export Excel
                    </a>
                    @if ($search || $kategori_filter || $kondisi_filter)
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
                            <th>Lokasi</th>
                            <th>Jumlah</th>
                            <th>Kondisi</th>
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
                                <td>{{ $asset->lokasi ?: '-' }}</td>
                                <td>{{ $asset->jumlah }}</td>
                                <td>
                                    <span class="status status-{{ strtolower($asset->kondisi ?? '') }}">
                                        {{ $asset->kondisi ?? 'Belum diisi' }}
                                    </span>
                                </td>
                                <td>{{ $asset->tanggal_masuk?->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    <div style="display:flex; gap:6px;">
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
                                <td colspan="8" class="empty-state">
                                    @if ($search || $kategori_filter || $kondisi_filter)
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
    <script src="{{ asset('assets/dashboard.js') }}"></script>
</body>
</html>
