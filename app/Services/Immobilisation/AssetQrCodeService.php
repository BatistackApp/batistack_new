<?php

namespace App\Services\Immobilisation;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Response;

class AssetQrCodeService
{
    public static function generateStream(string $token): Response
    {
        $url = url('/terrain/scan-materiel?token='.$token);
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 6,
            'imageBase64' => true,
        ]);

        $qrData = (new QRCode($options))->render($url);
        $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $qrData));

        return response($decoded, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-scan-'.$token.'.png"',
        ]);
    }
}
