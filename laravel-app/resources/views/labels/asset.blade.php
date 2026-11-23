<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label {{ $asset->kode_aset }} | IT Inventory</title>
    <link rel="stylesheet"
          href="{{ asset('assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
</head>
<body class="label-page-body">
    <main class="label-page">
        <div class="label-controls">
            <a class="secondary-button" href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
            <button type="button" class="primary-button" onclick="window.print()">Cetak Label</button>
        </div>

        <article class="asset-label" aria-label="Label aset {{ $asset->kode_aset }}">
            <div class="label-qr">
                <img src="{{ $qrCodeDataUri }}" alt="QR Code untuk {{ $asset->kode_aset }}">
            </div>
            <div class="label-info">
                <p class="label-brand">IT INVENTARIS</p>
                <h1>{{ $asset->nama_barang }}</h1>
                <strong class="label-asset-code">{{ $asset->kode_aset }}</strong>
                <img class="label-barcode" src="{{ $barcodeDataUri }}"
                     alt="Barcode Code 128 untuk {{ $asset->kode_aset }}">
                <small class="label-barcode-value">{{ $asset->kode_aset }}</small>
            </div>
        </article>
    </main>
</body>
</html>
