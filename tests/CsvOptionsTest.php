<?php

declare(strict_types=1);

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvOptions;
use Keboola\Csv\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CsvOptionsTest extends TestCase
{
    public function testAccessors()
    {
        $csvFile = new CsvOptions();
        self::assertEquals('"', $csvFile->getEnclosure());
        self::assertEquals('', $csvFile->getEscapedBy());
        self::assertEquals(',', $csvFile->getDelimiter());
    }

    public function testInvalidDelimiter()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Delimiter must be a single character. ",," received');
        new CsvOptions(',,');
    }

    public function testInvalidDelimiterEmpty()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Delimiter cannot be empty.');
        new CsvOptions('');
    }

    public function testInvalidEnclosure()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Enclosure must be a single character. ",," received');
        new CsvOptions(CsvOptions::DEFAULT_DELIMITER, ',,');
    }
}
