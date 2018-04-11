<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvFile;
use Keboola\Csv\Exception;
use Keboola\Csv\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CsvFileErrorsTest extends TestCase
{
    public function testException()
    {
        try {
            $csv = new CsvFile(__DIR__ . '/nonexistent.csv');
            $csv->getHeader();
            self::fail("Must throw exception.");
        } catch (Exception $e) {
            self::assertContains('Cannot open file', $e->getMessage());
            self::assertEquals(1, $e->getCode());
            self::assertEquals([], $e->getContextParams());
            self::assertEquals('fileNotExists', $e->getStringCode());
        }
    }

    /**
     * @dataProvider invalidFilenameProvider
     * @param string $filename
     * @param string $message
     */
    public function testInvalidFileName($filename, $message)
    {
        $csv = new CsvFile($filename);
        self::expectException(Exception::class);
        self::expectExceptionMessage($message);
        $csv->writeRow(['a', 'b']);
    }

    public function invalidFileNameProvider()
    {
        return [
            ["", 'Filename cannot be empty'],
            ["\0", 'fopen() expects parameter 1 to be a valid path, string given'],
        ];
    }

    /**
     * @dataProvider invalidDelimiterProvider
     * @param string $delimiter
     * @param string $message
     */
    public function testInvalidDelimiter($delimiter, $message)
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($message);
        new CsvFile(__DIR__ . '/data/test-input.csv', $delimiter);
    }

    public function invalidDelimiterProvider()
    {
        return [
            ['aaaa', 'Delimiter must be a single character. "aaaa" received'],
            ['ob g', 'Delimiter must be a single character. "ob g" received'],
            ['', 'Delimiter cannot be empty.'],
        ];
    }

    /**
     * @dataProvider invalidEnclosureProvider
     * @param string $enclosure
     * @param string $message
     */
    public function testInvalidEnclosureShouldThrowException($enclosure, $message)
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($message);
        new CsvFile(__DIR__ . '/data/test-input.csv', ",", $enclosure);
    }

    public function invalidEnclosureProvider()
    {
        return [
            ['aaaa', 'Enclosure must be a single character. "aaaa" received'],
            ['ob g', 'Enclosure must be a single character. "ob g" received'],
        ];
    }

    public function testNonStringWrite()
    {
        $fileName = __DIR__ . '/data/_out.csv';
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $csvFile = new CsvFile($fileName);
        $row = [['nested']];
        self::expectException(Exception::class);
        self::expectExceptionMessage("Cannot write array into a column");
        $csvFile->writeRow($row);
    }

    /**
     * @dataProvider invalidSkipLinesProvider
     * @param mixed $skipLines
     * @param string $message
     */
    public function testInvalidSkipLines($skipLines, $message)
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage($message);
        new CsvFile(
            'dummy',
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            $skipLines
        );
    }

    public function invalidSkipLinesProvider()
    {
        return [
            ['invalid', 'Number of lines to skip must be a positive integer. "invalid" received.'],
            [-123, 'Number of lines to skip must be a positive integer. "-123" received.']
        ];
    }

    public function testInvalidNewLines()
    {
        $csvFile = new CsvFile(__DIR__ . DIRECTORY_SEPARATOR . 'non-existent');
        self::expectException(Exception::class);
        self::expectExceptionMessage('Failed to detect line break: Cannot open file');
        $csvFile->next();
    }
}
