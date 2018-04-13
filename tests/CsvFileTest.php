<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvFile;
use Keboola\Csv\Exception;
use PHPUnit\Framework\TestCase;

class CsvFileTest extends TestCase
{

    public function testExistingFileShouldBeCreated()
    {
        self::assertInstanceOf(CsvFile::class, new CsvFile(__DIR__ . '/data/test-input.csv'));
    }

    public function testAccessors()
    {
        $csvFile = new CsvFile(__DIR__ . '/data/test-input.csv');
        self::assertEquals('test-input.csv', $csvFile->getBasename());
        self::assertEquals("\"", $csvFile->getEnclosure());
        self::assertEquals("", $csvFile->getEscapedBy());
        self::assertEquals(",", $csvFile->getDelimiter());
    }

    public function testColumnsCount()
    {
        $csv = new CsvFile(__DIR__ . '/data/test-input.csv');

        self::assertEquals(9, $csv->getColumnsCount());
    }

    /**
     * @dataProvider validCsvFiles
     * @param string $fileName
     * @param string $delimiter
     */
    public function testRead($fileName, $delimiter)
    {
        $csvFile = new CsvFile(__DIR__ . '/data/' . $fileName, $delimiter, '"');

        $expected = [
            "id",
            "idAccount",
            "date",
            "totalFollowers",
            "followers",
            "totalStatuses",
            "statuses",
            "kloutScore",
            "timestamp",
        ];
        self::assertEquals($expected, $csvFile->getHeader());
    }

    public function validCsvFiles()
    {
        return [
            ['test-input.csv', ','],
            ['test-input.win.csv', ','],
            ['test-input.tabs.csv', "\t"],
            ['test-input.tabs.csv', "	"],
        ];
    }

    public function testParse()
    {
        $csvFile = new CsvFile(__DIR__ . '/data/escaping.csv', ",", '"');

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
                "Columns with WINDOWS\r\nnew line", "second",
            ],
            [
                'column with \n \t \\\\', 'second col',
            ],
        ];

        self::assertEquals($expected, $rows);
    }

    public function testParseEscapedBy()
    {
        $csvFile = new CsvFile(__DIR__ . '/data/escapingEscapedBy.csv', ",", '"', '\\');

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
                "Columns with WINDOWS\r\nnew line", "second",
            ],
            [
                'column with \n \t \\\\', 'second col',
            ],
        ];

        self::assertEquals($expected, iterator_to_array($csvFile));
    }

    public function testEmptyHeader()
    {
        $csvFile = new CsvFile(__DIR__ . '/data/test-input.empty.csv', ',', '"');

        self::assertEquals([], $csvFile->getHeader());
    }

    public function testInitInvalidFileShouldNotThrowException()
    {
        try {
            new CsvFile(__DIR__ . '/data/dafadfsafd.csv');
        } catch (\Exception $e) {
            self::fail('Exception should not be thrown');
        }
    }

    /**
     * @param string $file
     * @param string $lineBreak
     * @param string $lineBreakAsText
     * @dataProvider validLineBreaksData
     */
    public function testLineEndingsDetection($file, $lineBreak, $lineBreakAsText)
    {
        $csvFile = new CsvFile(__DIR__ . '/data/' . $file);
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

    /**
     * @expectedException \Keboola\Csv\InvalidArgumentException
     * @dataProvider invalidLineBreaksData
     * @param string $file
     */
    public function testInvalidLineBreak($file)
    {
        $csvFile = new CsvFile(__DIR__ . '/data/' . $file);
        $csvFile->validateLineBreak();
    }

    public function invalidLineBreaksData()
    {
        return [
            ['test-input.mac.csv'],
        ];
    }

    public function testWrite()
    {
        $fileName = __DIR__ . '/data/_out.csv';
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $csvFile = new CsvFile($fileName);

        $rows = [
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
                'column with \n \t \\\\', 'second col',
            ]
        ];

        foreach ($rows as $row) {
            $csvFile->writeRow($row);
        }
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"',
                    '"line without enclosure","second column"',
                    '"enclosure "" in column","hello \\"',
                    '"line with enclosure","second column"',
                    '"column with enclosure "", and comma inside text","second column enclosure in text """',
                    "\"columns with\nnew line\",\"columns with\ttab\"",
                    '"column with \\n \\t \\\\","second col"',
                    '',
                ]
            ),
            $data
        );
        @unlink($fileName);
    }

    public function testWriteInvalidObject()
    {
        $fileName = __DIR__ . '/data/_out.csv';
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $csvFile = new CsvFile($fileName);

        $rows = [
            [
                'col1', 'col2',
            ],
            [
                '1', new \stdClass(),
            ],
        ];

        $csvFile->writeRow($rows[0]);
        self::expectException(Exception::class);
        self::expectExceptionMessage("Cannot write object into a column");
        $csvFile->writeRow($rows[1]);
        @unlink($fileName);
    }

    public function testWriteValidObject()
    {
        $fileName = __DIR__ . '/data/_out.csv';
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $csvFile = new CsvFile($fileName);
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                '1', new StringObject(),
            ],
        ];

        $csvFile->writeRow($rows[0]);
        $csvFile->writeRow($rows[1]);
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"' ,
                    '"1","me string"',
                    '',
                ]
            ),
            $data
        );
        @unlink($fileName);
    }

    public function testIterator()
    {
        $csvFile = new CsvFile(__DIR__ . '/data/test-input.csv');

        $expected = [
            "id",
            "idAccount",
            "date",
            "totalFollowers",
            "followers",
            "totalStatuses",
            "statuses",
            "kloutScore",
            "timestamp",
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

        $csvFile = new CsvFile(
            $fileName,
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            1
        );
        self::assertEquals([
            ['15', '0'],
            ['18', '0'],
            ['19', '0'],
        ], iterator_to_array($csvFile));
    }

    public function testSkipNoLines()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvFile(
            $fileName,
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            0
        );
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

        $csvFile = new CsvFile(
            $fileName,
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            3
        );
        self::assertEquals([
            ['19', '0'],
        ], iterator_to_array($csvFile));
    }

    public function testSkipsOverflow()
    {
        $fileName = __DIR__ . '/data/simple.csv';

        $csvFile = new CsvFile(
            $fileName,
            CsvFile::DEFAULT_DELIMITER,
            CsvFile::DEFAULT_ENCLOSURE,
            CsvFile::DEFAULT_ENCLOSURE,
            100
        );
        self::assertEquals([], iterator_to_array($csvFile));
    }
}
