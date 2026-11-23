<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Aset</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
</head>
<body>
    <main class="main" style="margin-left:0; max-width:800px;">
        <section class="panel">
            <h1>Edit Data Aset</h1>
            <p class="subtitle">Perbarui informasi inventaris.</p>

            @if (session('success'))
                <div class="notice success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="notice error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="notice error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('update_aset', $asset->id) }}" class="asset-form">
                @csrf
                @method('PUT')
                <label>Kode Aset *
                    <input name="kode_aset" maxlength="30"
                           value="{{ old('kode_aset', $asset->kode_aset) }}" required>
                </label>

                <label>Nama Barang *
                    <input name="nama_barang" maxlength="100"
                           value="{{ old('nama_barang', $asset->nama_barang) }}" required>
                </label>

                <label>Kategori *
                    <input name="kategori" maxlength="50"
                           value="{{ old('kategori', $asset->kategori) }}" required>
                </label>

                <label>Pola Pencatatan *
                    <select name="tracking_mode" required>
                        <option value="individual"
                            @selected(old('tracking_mode', $asset->tracking_mode) === 'individual')>
                            Per unit
                        </option>
                        <option value="bulk"
                            @selected(old('tracking_mode', $asset->tracking_mode) === 'bulk')>
                            Berdasarkan jumlah
                        </option>
                    </select>
                </label>

                <div class="form-row">
                    <label>Merek
                        <input name="merek" maxlength="100"
                               value="{{ old('merek', $asset->merek) }}">
                    </label>
                    <label>Model
                        <input name="model" maxlength="100"
                               value="{{ old('model', $asset->model) }}">
                    </label>
                </div>

                <label>Nomor Seri
                    <input name="nomor_seri" maxlength="100"
                           value="{{ old('nomor_seri', $asset->nomor_seri) }}">
                    <small>Isi hanya untuk pencatatan per unit.</small>
                </label>

                <label>Lokasi
                    <input name="lokasi" maxlength="100"
                           value="{{ old('lokasi', $asset->lokasi) }}">
                </label>

                <div class="form-row">
                    <label>Pemegang
                        <input name="nama_pemegang" maxlength="100"
                               value="{{ old('nama_pemegang', $asset->nama_pemegang) }}">
                    </label>
                    <label>Departemen
                        <input name="departemen" maxlength="100"
                               value="{{ old('departemen', $asset->departemen) }}">
                    </label>
                </div>

                <div class="form-row">
                    <label>Jumlah *
                        <input type="number" name="jumlah" min="1"
                               value="{{ old('jumlah', $asset->jumlah) }}" required>
                    </label>

                    <label>Kondisi
                        <select name="kondisi">
                            @foreach (['Baik', 'Rusak', 'Maintenance'] as $option)
                                <option value="{{ $option }}"
                                    @selected(old('kondisi', $asset->kondisi) === $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="form-row">
                    <label>Tanggal Pembelian
                        <input type="date" name="tanggal_pembelian"
                               value="{{ old('tanggal_pembelian', $asset->tanggal_pembelian?->format('Y-m-d')) }}">
                    </label>
                    <label>Garansi Sampai
                        <input type="date" name="garansi_sampai"
                               value="{{ old('garansi_sampai', $asset->garansi_sampai?->format('Y-m-d')) }}">
                    </label>
                </div>

                <label>Tanggal Masuk
                    <input type="date" name="tanggal_masuk"
                           value="{{ old('tanggal_masuk', $asset->tanggal_masuk?->format('Y-m-d')) }}">
                </label>

                <button type="submit" class="primary-button">
                    Simpan Perubahan
                </button>
                <a href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
            </form>
        </section>
    </main>
</body>
</html>
