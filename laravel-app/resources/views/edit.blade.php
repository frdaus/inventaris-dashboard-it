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

                <label>Lokasi
                    <input name="lokasi" maxlength="100"
                           value="{{ old('lokasi', $asset->lokasi) }}">
                </label>

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
