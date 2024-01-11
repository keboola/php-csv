<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvOptions;
use Keboola\Csv\CsvReader;
use Keboola\Csv\CsvWriter;
use Keboola\Csv\Exception;
use Keboola\Csv\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CsvReadTest extends TestCase
{

    public function testExistingFileShouldBeCreated()
    {
        self::assertInstanceOf(CsvReader::class, new CsvReader(__DIR__ . '/data/test-input.csv'));
    }

    public function testAccessors()
    {
        $csvFile = new CsvReader(__DIR__ . '/data/test-input.csv');
        self::assertEquals('"', $csvFile->getEnclosure());
        self::assertEquals('', $csvFile->getEscapedBy());
        self::assertEquals(',', $csvFile->getDelimiter());
    }

    public function testColumnsCount()
    {
        $csv = new CsvReader(__DIR__ . '/data/test-input.csv');
        self::assertEquals(9, $csv->getColumnsCount());
    }

    public function testNewlineDetectionEdgecaseWithCrLf()
    {
        $this->expectNotToPerformAssertions();
        // this used to throw "Invalid line break. Please use unix \n or win \r\n line breaks." before the fix
        new CsvReader(__DIR__ . '/data/test-input-edgecase.crlf.csv');
    }

    /**
     * @dataProvider validCsvFiles
     * @param string $fileName
     * @param string $delimiter
     */
    public function testRead($fileName, $delimiter)
    {
        $csvFile = new CsvReader(__DIR__ . '/data/' . $fileName, $delimiter, '"');

        $expected = [
            'id',
            'idAccount',
            'date',
            'totalFollowers',
            'followers',
            'totalStatuses',
            'statuses',
            'kloutScore',
            'timestamp',
        ];
        self::assertEquals($expected, $csvFile->getHeader());
    }

    public function validCsvFiles()
    {
        return [
            ['test-input.csv', ','],
            ['test-input.win.csv', ','],
            ['test-input.tabs.csv', "\t"],
            ['test-input.tabs.csv', '	'],
        ];
    }

    public function testParse()
    {
        $csvFile = new CsvReader(__DIR__ . '/data/escaping.csv', ',', '"');

        $rows = [];
        foreach ($csvFile as $row) {
            $rows[] = $row;
        }

        $expected = [
            [
                'col1', 'col2',
            ],
            [
                'line without enclosure', 'second column',
            ],
            [
                'enclosure " in column', 'hello \\',
            ],
            [
                'line with enclosure', 'second column',
            ],
            [
                'column with enclosure ", and comma inside text', 'second column enclosure in text "',
            ],
            [
                "columns with\nnew line", "columns with\ttab",
            ],
            [
                "Columns with WINDOWS\r\nnew line", 'second',
            ],
            [
                'column with \n \t \\\\', 'second col',
            ],
        ];

        self::assertEquals($expected, $rows);
    }

    public function testParseEscapedBy()
    {
        $csvFile = new CsvReader(__DIR__ . '/data/escapingEscapedBy.csv', ',', '"', '\\');

        $expected = [
            [
                'col1', 'col2',
            ],
            [
                'line without enclosure', 'second column',
            ],
            [
                'enclosure \" in column', 'hello \\\\',
            ],
            [
                'line with enclosure', 'second column',
            ],
            [
                'column with enclosure \", and comma inside text', 'second column enclosure in text \"',
            ],
            [
                "columns with\nnew line", "columns with\ttab",
            ],
            [
                "Columns with WINDOWS\r\nnew line", 'second',
            ],
            [
                'column with \n \t \\\\', 'second col',
            ],
        ];

        self::assertEquals($expected, iterator_to_array($csvFile));
    }

    /**
     * @dataProvider bomProvider
     */
    public function testUtf8BOM($bomFile)
    {
        $csvFile = new CsvReader(__DIR__ . '/data/bom/' . $bomFile . '.csv');
        self::assertEquals(['id', 'name',], $csvFile->getHeader());
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

    public function testParseMacLineEndsInField()
    {
        $csvFile = new CsvReader(__DIR__ . '/data/test-input.lineBreaks.csv', ',', '"', '\\');

        $expected = [
            [
                'test',
                "some text\rwith\r\\r line breaks\rinside\rbut\rrows\rare\rusing \\n \\\"line\\\" break\r",
            ],
            [
                'name', 'data',
            ],
        ];

        self::assertEquals($expected, iterator_to_array($csvFile));
    }


    public function testEmptyCsv(): void
    {
        $csvFile = new CsvReader(__DIR__ . '/data/test-input.empty.csv', ',', '"');
        self::assertEquals([], $csvFile->getHeader());
    }

    public function testEmptyHeader(): void
    {
        $csvFile = new CsvReader(__DIR__ . '/data/test-input.emptyHeader.csv', ',', '"');
        self::assertEquals([], $csvFile->getHeader());
    }

    public function testInitInvalidFileShouldThrowException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot open file');
        new CsvReader(__DIR__ . '/data/dafadfsafd.csv');
    }

    /**
     * @param string $file
     * @param string $lineBreak
     * @param string $lineBreakAsText
     * @dataProvider validLineBreaksData
     */
    public function testLineEndingsDetection($file, $lineBreak, $lineBreakAsText)
    {
        $csvFile = new CsvReader(__DIR__ . '/data/' . $file);
        self::assertEquals($lineBreak, $csvFile->getLineBreak());
        self::assertEquals($lineBreakAsText, $csvFile->getLineBreakAsText());
    }

    public function validLineBreaksData()
    {
        return [
            ['test-input.csv', "\n", '\n'],
            ['test-input.win.csv', "\r\n", '\r\n'],
            ['escaping.csv', "\n", '\n'],
            ['just-header.csv', "\n", '\n'], // default
        ];
    }

    public function testInvalidLineBreak()
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid line break. Please use unix \n or win \r\n line breaks.');
        new CsvReader(__DIR__ . '/data/test-input.mac.csv');
    }

    public function testIterator()
    {
        $csvFile = new CsvReader(__DIR__ . '/data/test-input.csv');

        $expected = [
            'id',
            'idAccount',
            'date',
            'totalFollowers',
            'followers',
            'totalStatuses',
            'statuses',
            'kloutScore',
            'timestamp',
        ];

        // header line
        $csvFile->rewind();
        self::assertEquals($expected, $csvFile->current());

        // first line
        $csvFile->next();
        self::assertTrue($csvFile->valid());

        // second line
        $csvFile->next();
        self::assertTrue($csvFile->valid());

        // file end
        $csvFile->next();
        self::assertFalse($csvFile->valid());
    }

    public function testSkipsHeaders()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader(
            $fileName,
            CsvOptions::DEFAULT_DELIMITER,
            CsvOptions::DEFAULT_ENCLOSURE,
            CsvOptions::DEFAULT_ESCAPED_BY,
            1,
        );
        self::assertEquals(['id', 'isImported'], $csvFile->getHeader());
        self::assertEquals([
            ['15', '0'],
            ['18', '0'],
            ['19', '0'],
        ], iterator_to_array($csvFile));
    }

    public function testSkipNoLines()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader(
            $fileName,
            CsvOptions::DEFAULT_DELIMITER,
            CsvOptions::DEFAULT_ENCLOSURE,
            CsvOptions::DEFAULT_ESCAPED_BY,
            0,
        );
        self::assertEquals(['id', 'isImported'], $csvFile->getHeader());
        self::assertEquals([
            ['id', 'isImported'],
            ['15', '0'],
            ['18', '0'],
            ['19', '0'],
        ], iterator_to_array($csvFile));
    }

    public function testSkipsMultipleLines()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader(
            $fileName,
            CsvOptions::DEFAULT_DELIMITER,
            CsvOptions::DEFAULT_ENCLOSURE,
            CsvOptions::DEFAULT_ESCAPED_BY,
            3,
        );
        self::assertEquals(['id', 'isImported'], $csvFile->getHeader());
        self::assertEquals([
            ['19', '0'],
        ], iterator_to_array($csvFile));
    }

    public function testSkipsOverflow()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader(
            $fileName,
            CsvOptions::DEFAULT_DELIMITER,
            CsvOptions::DEFAULT_ENCLOSURE,
            CsvOptions::DEFAULT_ESCAPED_BY,
            100,
        );
        self::assertEquals(['id', 'isImported'], $csvFile->getHeader());
        self::assertEquals([], iterator_to_array($csvFile));
    }

    public function testException()
    {
        try {
            $csv = new CsvReader(__DIR__ . '/nonexistent.csv');
            $csv->getHeader();
            self::fail('Must throw exception.');
        } catch (Exception $e) {
            self::assertStringContainsString('Cannot open file', $e->getMessage());
            self::assertEquals(1, $e->getCode());
        }
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
        new CsvReader(__DIR__ . '/data/test-input.csv', $delimiter);
    }

    public function invalidDelimiterProvider()
    {
        return [
            ['aaaa', 'Delimiter must be a single character. "aaaa" received'],
            ['🎁', 'Delimiter must be a single character. "\ud83c\udf81" received'],
            [",\n", 'Delimiter must be a single character. ",\n" received'],
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
        new CsvReader(__DIR__ . '/data/test-input.csv', ',', $enclosure);
    }

    public function invalidEnclosureProvider()
    {
        return [
            ['aaaa', 'Enclosure must be a single character. "aaaa" received'],
            ['ob g', 'Enclosure must be a single character. "ob g" received'],
        ];
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
        new CsvReader(
            'dummy',
            CsvOptions::DEFAULT_DELIMITER,
            CsvOptions::DEFAULT_ENCLOSURE,
            CsvOptions::DEFAULT_ENCLOSURE,
            $skipLines,
        );
    }

    public function invalidSkipLinesProvider()
    {
        return [
            ['invalid', 'Number of lines to skip must be a positive integer. "invalid" received.'],
            [-123, 'Number of lines to skip must be a positive integer. "-123" received.'],
        ];
    }

    public function testValidWithoutRewind()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader($fileName);
        self::assertTrue($csvFile->valid());
    }

    public function testHeaderNoRewindOnGetHeader()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader($fileName);
        $csvFile->rewind();
        self::assertEquals(['id', 'isImported'], $csvFile->current());
        $csvFile->next();
        self::assertEquals(['15', '0'], $csvFile->current());
        self::assertEquals(['id', 'isImported'], $csvFile->getHeader());
        self::assertEquals(['15', '0'], $csvFile->current());
    }

    public function testLineBreakWithoutRewind()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvReader($fileName);
        self::assertEquals("\n", $csvFile->getLineBreak());
        self::assertEquals(['id', 'isImported'], $csvFile->current());
    }

    public function testWriteReadInTheMiddle()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $writer = new CsvWriter($fileName);
        $reader = new CsvReader($fileName);
        self::assertEquals([], $reader->getHeader());
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                '1', 'first',
            ],
            [
                '2', 'second',
            ],
        ];

        $writer->writeRow($rows[0]);
        $reader->next();
        self::assertEquals(false, $reader->current(), 'Reader must be at end of file');
        $writer->writeRow($rows[1]);
        $writer->writeRow($rows[2]);
        $reader->rewind();
        $reader->next();
        self::assertEquals(['1', 'first'], $reader->current());
        $reader->next();
        self::assertEquals(['2', 'second'], $reader->current());
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"' ,
                    '"1","first"',
                    '"2","second"',
                    '',
                ],
            ),
            $data,
        );
    }

    public function testReadPointer()
    {
        $fileName = __DIR__ . '/data/simple.csv';
        $file = fopen($fileName, 'r');
        $csvFile = new CsvReader($file);
        self::assertEquals(['id', 'isImported'], $csvFile->getHeader());
        self::assertEquals([
            ['id', 'isImported'],
            ['15', '0'],
            ['18', '0'],
            ['19', '0'],
        ], iterator_to_array($csvFile));
        // check that the file pointer remains valid
        unset($csvFile);
        rewind($file);
        $data = fread($file, 13);
        self::assertEquals('id,isImported', $data);
    }

    public function testInvalidPointer()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $file = fopen($fileName, 'w');
        $csvFile = new CsvReader($file);
        self::assertEquals([], $csvFile->getHeader());
        self::assertEquals([], iterator_to_array($csvFile));
    }

    public function testInvalidFile()
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('Invalid file: array');
        new CsvReader(['bad']);
    }

    /**
     * @dataProvider getPerformanceTestInputs
     * @param string $fileContent
     * @param int $expectedRows
     * @param float $maxDuration
     */
    public function testPerformance($fileContent, $expectedRows, $maxDuration)
    {
        self::markTestSkipped(
            'Run this test only manually. Because the duration is very different in local CI environment.',
        );

        try {
            $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('perf-test');
            file_put_contents($fileName, $fileContent);
            $startTime = microtime(true);
            $reader = new CsvReader($fileName);
            $rows = 0;
            foreach ($reader as $line) {
                $rows++;
            }
            $duration = microtime(true) - $startTime;
            self::assertSame($expectedRows, $rows);
            self::assertLessThanOrEqual($maxDuration, $duration);
        } finally {
            @unlink($fileName);
        }
    }

    public function getPerformanceTestInputs()
    {
        yield '1M-simple-rows' => [
            str_repeat("abc,def,\"xyz\"\n", 1000000),
            1000000,
            8.0,
        ];

        yield '1M-empty-lines-n' => [
            str_repeat("\n", 1000000),
            1000000,
            8.0,
        ];

        yield '1M-no-separators' => [
            str_repeat(md5('abc') . "\n", 1000000),
            1000000,
            8.0,
        ];
    }
}
