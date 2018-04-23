<?php

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvWriter;
use Keboola\Csv\Exception;
use PHPUnit\Framework\TestCase;

class CsvWriteTest extends TestCase
{
    public function testNewFileShouldBeCreated()
    {
        self::assertInstanceOf(CsvWriter::class, new CsvWriter(__DIR__ . '/data/non-existent-file.csv'));
    }

    public function testAccessors()
    {
        $csvFile = new CsvWriter(sys_get_temp_dir() . '/test-write.csv');
        self::assertEquals('test-write.csv', $csvFile->getBasename());
        self::assertEquals("\"", $csvFile->getEnclosure());
        self::assertEquals(",", $csvFile->getDelimiter());
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
        self::expectExceptionMessage("Cannot write object into a column");
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
        self::expectExceptionMessage("Cannot write array into a column");
        $csvFile->writeRow($row);
    }
}
