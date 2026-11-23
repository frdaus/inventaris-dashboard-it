<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;

class AssetLabelController extends Controller
{
    public function show(int $asset_id): View
    {
        $asset = Asset::findOrFail($asset_id);
        abort_unless(
            preg_match('/^[\x20-\x7E]+$/D', $asset->kode_aset) === 1,
            422,
            'Kode aset lama ini tidak dapat dicetak sebagai barcode Code 128.',
        );

        $qrCode = (new QrCode($asset->kode_aset))
            ->setSize(240)
            ->setMargin(8)
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium);
        $qrCodeDataUri = (new PngWriter)->write($qrCode)->getDataUri();
        $barcodeSvg = (new BarcodeGeneratorSVG)->getBarcode(
            $asset->kode_aset,
            BarcodeGenerator::TYPE_CODE_128,
            1.5,
            54,
        );
        $barcodeDataUri = 'data:image/svg+xml;base64,'.base64_encode($barcodeSvg);

        return view('labels.asset', compact('asset', 'qrCodeDataUri', 'barcodeDataUri'));
    }
}
