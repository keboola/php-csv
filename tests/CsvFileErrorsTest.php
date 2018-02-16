<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvFile;
use Keboola\Csv\Exception;
use PHPUnit\Framework\TestCase;

class CsvFileErrorsTest extends TestCase
{
    /**
     * @expectedException \Keboola\Csv\Exception
     * @expectedExceptionMessage Cannot open file
     */
    public function testNonExistentFile()
    {
        $csv = new CsvFile(__DIR__ . '/something.csv');
        $csv->getHeader();
    }

    public function testException()
    {
        try {
            $csv = new CsvFile(__DIR__ . '/something.csv');
            $csv->getHeader();
            self::fail("Mush throw exception.");
        } catch (Exception $e) {
            self::assertContains('Cannot open file', $e->getMessage());
            self::assertEquals(1, $e->getCode());
            self::assertEquals([], $e->getContextParams());
            self::assertEquals('fileNotExists', $e->getStringCode());
        }
    }

    /**
     * @expectedException \Keboola\Csv\Exception
     * @expectedExceptionMessage fopen( ): failed to open stream: Permission denied
     */
    public function testInvalidFileName1()
    {
        $csv = new CsvFile(" ");
        $csv->writeRow(['a', 'b']);
    }

    /**
     * @expectedException \Keboola\Csv\Exception
     * @expectedExceptionMessage fopen() expects parameter 1 to be a valid path, string given
     */
    public function testInvalidFileName2()
    {
        $csv = new CsvFile("\0");
        $csv->writeRow(['a', 'b']);
    }

    /**
     * @expectedException \Keboola\Csv\Exception
     * @expectedExceptionMessage Cannot write to file php://stdin
     */
    public function testInvalidFileName3()
    {
        $csv = new CsvFile('php://stdin');
        $csv->writeRow(['a', 'b']);
    }

    /**
     * @dataProvider invalidDelimiters
     * @expectedException \Keboola\Csv\InvalidArgumentException
     * @param string $delimiter
     */
    public function testInvalidDelimiterShouldThrowException($delimiter)
    {
        new CsvFile(__DIR__ . '/data/test-input.csv', $delimiter);
    }

    public function invalidDelimiters()
    {
        return [
            ['aaaa'],
            ['ob g'],
            [''],
        ];
    }

    /**
     * @dataProvider invalidEnclosures
     * @expectedException \Keboola\Csv\InvalidArgumentException
     * @param string $enclosure
     */
    public function testInvalidEnclosureShouldThrowException($enclosure)
    {
        new CsvFile(__DIR__ . '/data/test-input.csv', ",", $enclosure);
    }

    public function invalidEnclosures()
    {
        return [
            ['aaaa'],
            ['ob g'],
        ];
    }

    /**
     * @expectedException \Keboola\Csv\Exception
     * @expectedExceptionMessage Cannot write array into a column
     */
    public function testNonStringWrite()
    {
        $fileName = __DIR__ . '/data/_out.csv';
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $csvFile = new CsvFile($fileName);

        $row = [['nested']];

        $csvFile->writeRow($row);
    }

    /**
     * @expectedException \Keboola\Csv\InvalidArgumentException
     * @expectedExceptionMessage Number of lines to skip must be a positive integer
     */
    public function testInvalidSkipLines1()
    {
        new CsvFile(
            'dummy',
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            'invalid'
        );
    }

    /**
     * @expectedException \Keboola\Csv\InvalidArgumentException
     * @expectedExceptionMessage Number of lines to skip must be a positive integer
     */
    public function testInvalidSkipLines2()
    {
        new CsvFile(
            'dummy',
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            -123
        );
    }

    /**
     * @expectedException \Keboola\Csv\Exception
     * @expectedExceptionMessage Failed to detect line break: Cannot open file
     */
    public function testInvalidNewLines()
    {
        $csvFile = new CsvFile(__DIR__ . DIRECTORY_SEPARATOR . 'non-existent');
        $csvFile->next();
    }
}
