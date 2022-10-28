<?php

namespace Andruby\Pay\Services;

use PHPQRCode\QRcode;

/**
 * @method static QRService instance()
 *
 * Class QRService
 * @package Andruby\Pay\Services
 */
class QRService
{
    public static function __callStatic($method, $params): QRService
    {
        return new self();
    }

    /**
     * 生成二维码
     * @param $content
     * @param $fileName
     * @param string $type
     * @param int $size
     * @return string
     */
    public function qrcode($content, $fileName, $type = 'qrcode', $size = 6)
    {
        $path = storage_path('app/public/' . $type . '/');
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }

        $qrFile = $path . $fileName;
        QRcode::png($content, $qrFile, $size);

        // $header = public_path('images/logo.jpeg');
        // QRcode::addHeader($header, $qrFile);

        return env('APP_URL') . '/storage/' . $type . '/' . $fileName;
    }

}
