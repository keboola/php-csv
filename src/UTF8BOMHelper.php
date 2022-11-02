<?php

declare(strict_types=1);

namespace Keboola\Csv;

class UTF8BOMHelper
{
    /**
     * @param array $header
     * @return array
     */
    public static function detectAndRemoveBOM($header)
    {
        if (!is_array($header) || empty($header) || $header[0] === null) {
            return $header;
        }
        $utf32BigEndianBom = chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF);
        $utf32LittleEndianBom = chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00);
        $utf16BigEndianBom = chr(0xFE) . chr(0xFF);
        $utf16LittleEndianBom = chr(0xFF) . chr(0xFE);
        $utf8Bom = chr(0xEF) . chr(0xBB) . chr(0xBF);

        foreach ([
                     $utf32BigEndianBom,
                     $utf32LittleEndianBom,
                     $utf16BigEndianBom,
                     $utf16LittleEndianBom,
                     $utf8Bom,
                 ] as $bomString) {
            if (strpos($header[0], $bomString) === 0) {
                $header[0] = trim(substr($header[0], strlen($bomString)), '"');
                break;
            }
        }

        return $header;
    }
}
