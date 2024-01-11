<?php

declare(strict_types=1);

namespace Keboola\Csv\Tests;

use Keboola\Csv\CsvOptions;
use Keboola\Csv\CsvWriter;
use Keboola\Csv\Exception;
use PHPUnit\Framework\Constraint\LogicalOr;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\TestCase;
use stdClass;

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
            ],
            [
                1, true,
            ],
            [
                2, false,
            ],
            [
                3, null,
            ],
            [
                'true', 1.123,
            ],
            [
                '1', 'null',
            ],
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
                    '"1","1"',
                    '"2","0"',
                    '"3",""',
                    '"true","1.123"',
                    '"1","null"',
                    '',
                ],
            ),
            $data,
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
                '1', new stdClass(),
            ],
        ];

        $csvFile->writeRow($rows[0]);

        try {
            $csvFile->writeRow($rows[1]);
            self::fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            // Exception message differs between PHP versions.
            $or = new LogicalOr();
            $or->setConstraints([
                new StringContains('Cannot write data into column: stdClass::'),
                new StringContains("Cannot write data into column: (object) array(\n)"),
            ]);
            self::assertThat($e->getMessage(), $or);
        }
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
                ],
            ),
            $data,
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
        if (PHP_VERSION_ID < 80000) {
            return [
                ['', 'Filename cannot be empty'],
                ["\0", 'fopen() expects parameter 1 to be a valid path, string given'],
            ];
        }

        return [
            ['', 'Path cannot be empty'],
            ["\0", 'Argument #1 ($filename) must not contain any null bytes'],
        ];
    }

    public function testNonStringWrite()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter($fileName);
        $row = [['nested']];
        self::expectException(Exception::class);
        self::expectExceptionMessage('Cannot write data into column: array');
        $csvFile->writeRow($row);
    }

    public function testWritePointer()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $file = fopen($fileName, 'w');
        $csvFile = new CsvWriter($file);
        $rows = [['col1', 'col2']];
        $csvFile->writeRow($rows[0]);

        // check that the file pointer remains valid
        unset($csvFile);
        fwrite($file, 'foo,bar');
        $data = file_get_contents($fileName);
        self::assertEquals(
            implode(
                "\n",
                [
                    '"col1","col2"' ,
                    'foo,bar',
                ],
            ),
            $data,
        );
    }

    public function testInvalidPointer()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        touch($fileName);
        $pointer = fopen($fileName, 'r');
        $csvFile = new CsvWriter($pointer);
        $rows = [['col1', 'col2']];

        try {
            $csvFile->writeRow($rows[0]);
            self::fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            // Exception message differs between PHP versions.
            $or = new LogicalOr();
            $or->setConstraints([
                new StringContains(
                    'Cannot write to CSV file  Return: 0 To write: 14 Written: 0',
                ),
                new StringContains(
                    'Cannot write to CSV file Error: fwrite(): ' .
                    'write of 14 bytes failed with errno=9 Bad file descriptor Return: false To write: 14 Written: 0',
                ),
                new StringContains(
                    'Cannot write to CSV file Error: fwrite(): ' .
                    'Write of 14 bytes failed with errno=9 Bad file descriptor Return: false To write: 14 Written: 0',
                ),
            ]);
            self::assertThat($e->getMessage(), $or);
        }
    }

    public function testInvalidPointer2()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        touch($fileName);
        $pointer = fopen($fileName, 'r');
        $csvFile = new CsvWriter($pointer);
        fclose($pointer);
        $rows = [['col1', 'col2']];
        self::expectException(Exception::class);
        self::expectExceptionMessage(
            'a valid stream resource Return: false To write: 14 Written: ',
        );
        $csvFile->writeRow($rows[0]);
    }

    public function testInvalidFile()
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('Invalid file: array');
        /** @noinspection PhpParamsInspection */
        new CsvWriter(['dummy']);
    }

    public function testWriteLineBreak()
    {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('csv-test');
        $csvFile = new CsvWriter(
            $fileName,
            CsvOptions::DEFAULT_DELIMITER,
            CsvOptions::DEFAULT_ENCLOSURE,
            "\r\n",
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
                ],
            ),
            $data,
        );
    }
}
