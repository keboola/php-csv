<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvReader;
use Keboola\Csv\UTF8BOMHelper;
use PHPUnit\Framework\TestCase;

class UTF8BOMHelperTest extends TestCase
{
    /**
     * @dataProvider bomProvider
     * @param string $bomFile
     */
    public function testDetectAndRemoveBOM($bomFile)
    {
        $file = __DIR__ . '/data/bom/' . $bomFile . '.csv';
        $reader = new CsvReader($file);
        $firstLine = $reader->current();
        $this->assertNotSame(['id', 'name'], $firstLine);
        $this->assertSame(['id', 'name'], UTF8BOMHelper::detectAndRemoveBOM($firstLine));
    }

    public function bomProvider()
    {
        return [
            ['utf32BigEndianBom'],
            ['utf32LittleEndianBom'],
            ['utf16BigEndianBom'],
            ['utf16LittleEndianBom'],
            ['utf8Bom'],
        ];
    }
}
