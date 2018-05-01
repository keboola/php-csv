<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvWriter;
use Keboola\Csv\Exception;
use PHPUnit\Framework\TestCase;

class CsvWriteTest extends TestCase
{
    public function testNewFileShouldBeCreated()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        self::assertInstanceOf(CsvWriter::class, new CsvWriter($fileName));
    }

    public function testAccessors()
    {
        $csvFile = new CsvWriter(sys_get_temp_dir() . '/test-write.csv');
        self::assertEquals('"', $csvFile->getEnclosure());
        self::assertEquals(',', $csvFile->getDelimiter());
    }

    public function testWrite()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter($fileName);
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
    }

    public function testWriteInvalidObject()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter($fileName);

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
        self::expectExceptionMessage("Cannot write data into column: stdClass::");
        $csvFile->writeRow($rows[1]);
    }

    public function testWriteValidObject()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter($fileName);
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
    }

    /**
     * @dataProvider invalidFilenameProvider
     * @param string $filename
     * @param string $message
     */
    public function testInvalidFileName($filename, $message)
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage($message);
        new CsvWriter($filename);
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
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter($fileName);
        $row = [['nested']];
        self::expectException(Exception::class);
        self::expectExceptionMessage("Cannot write data into column: array");
        $csvFile->writeRow($row);
    }

    public function testWritePointer()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $pointer = fopen($fileName, 'w');
        $csvFile = new CsvWriter($pointer);
        $rows = [['col1', 'col2']];
        $csvFile->writeRow($rows[0]);
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"' ,
                    '',
                ]
            ),
            $data
        );
    }

    public function testInvalidPointer()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        touch($fileName);
        $pointer = fopen($fileName, 'r');
        $csvFile = new CsvWriter($pointer);
        $rows = [['col1', 'col2']];
        self::expectException(Exception::class);
        self::expectExceptionMessage('Cannot write to CSV file');
        $csvFile->writeRow($rows[0]);
    }

    public function testInvalidFile()
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('Invalid file: array');
        new CsvWriter(['dummy']);
    }

    public function testWriteLineBreak()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter(
            $fileName,
            CsvWriter::DEFAULT_DELIMITER,
            CsvWriter::DEFAULT_ENCLOSURE,
            'w',
            "\r\n"
        );
        $rows = [
            [
                'col1', 'col2',
            ],
            [
                'val1', 'val2',
            ],
        ];

        foreach ($rows as $row) {
            $csvFile->writeRow($row);
        }
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\r\n",
                [
                    '"col1","col2"',
                    '"val1","val2"',
                    '',
                ]
            ),
            $data
        );
    }
}
