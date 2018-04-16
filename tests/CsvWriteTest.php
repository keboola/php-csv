<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvFile;
use Keboola\Csv\Exception;
use PHPUnit\Framework\TestCase;

class CsvWriteTest extends TestCase
{
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

}
