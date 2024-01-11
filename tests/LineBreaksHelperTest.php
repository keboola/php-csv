<?php

declare(strict_types=1);

namespace Keboola\Csv\Tests;

use Keboola\Csv\LineBreaksHelper;
use PHPUnit\Framework\TestCase;

class LineBreaksHelperTest extends TestCase
{
    /**
     * @dataProvider getDataSet
     * @param string $enclosure
     * @param string $escapedBy
     * @param string $input
     * @param string $expectedOutput
     * @param string $expectedLineBreak
     */
    public function testLineBreaksDetection($enclosure, $escapedBy, $input, $expectedOutput, $expectedLineBreak)
    {
        // Test clear CSV values
        $output = LineBreaksHelper::clearCsvValues($input, $enclosure, $escapedBy);
        self::assertSame($expectedOutput, $output);

        // The same result must be returned when used multiple times
        $output2 = LineBreaksHelper::clearCsvValues($output, $enclosure, $escapedBy);
        self::assertSame($expectedOutput, $output2);

        // Test line breaks detection
        self::assertSame(
            json_encode($expectedLineBreak),
            json_encode(LineBreaksHelper::detectLineBreaks($input, $enclosure, $escapedBy)),
        );
    }

    public function getDataSet()
    {
        $lineEnds = [
          'n' => "\n",
          'r' => "\r",
          'r-n' => "\r\n",
        ];

        yield 'empty' => [
            '"',
            '',
            '',
            '',
            "\n",
        ];

        yield 'empty-enclosure' => [
            '',
            '',
            'col1|col2',
            'col1|col2',
            "\n",
        ];

        yield 'empty-escaped-by' => [
            '"',
            '\\',
            '',
            '',
            "\n",
        ];

        foreach ($lineEnds as $prefix => $lineEnd) {
            yield "$prefix-empty-enclosure" => [
                '',
                '',
                implode($lineEnd, [
                    'col1,col2',
                    'abc,def',
                ]),
                implode($lineEnd, [
                    'col1,col2',
                    'abc,def',
                ]),
                $lineEnd,
            ];

            yield "$prefix-simple" => [
                '"',
                '',
                implode($lineEnd, [
                    'col1,col2',
                    'line without enclosure,second column',
                    '"enclosure "" in column","hello \"',
                    '"line with enclosure","second column"',
                    '"column with enclosure "", and comma inside text","second column enclosure in text """',
                ]),
                implode($lineEnd, [
                    'col1,col2',
                    'line without enclosure,second column',
                    '"",""',
                    '"",""',
                    '"",""',
                ]),
                $lineEnd,
            ];

            yield "$prefix-simple-escaped-by" => [
                '"',
                '\\',
                implode($lineEnd, [
                    'col1,col2',
                    'line without enclosure,second column',
                    '"enclosure \" in column","hello \\\\"',
                    '"line with enclosure","second column"',
                    '"column with enclosure \", and comma inside text","second column enclosure in text \""',
                ]),
                implode($lineEnd, [
                    'col1,col2',
                    'line without enclosure,second column',
                    '"",""',
                    '"",""',
                    '"",""',
                ]),
                $lineEnd,
            ];

            yield "$prefix-multiline-n" => [
                '"',
                '',
                implode($lineEnd, [
                    "\"xyz\",\"\n\n\nabc\n\n\n\"\"\n\n\nxyz\n\n\n\"",
                    '"abc","def"',
                ]),
                implode($lineEnd, [
                    '"",""',
                    '"",""',
                ]),
                $lineEnd,
            ];

            yield "$prefix-multiline-r" => [
                '"',
                '',
                implode($lineEnd, [
                    "\"xyz\",\"\r\r\rabc\r\r\r\"\"\r\r\rxyz\r\r\r\"",
                    '"abc","def"',
                ]),
                implode($lineEnd, [
                    '"",""',
                    '"",""',
                ]),
                $lineEnd,
            ];

            yield "$prefix-multiline-r-n" => [
                '"',
                '',
                implode($lineEnd, [
                    "\"xyz\",\"\r\n\r\n\r\nabc\r\n\r\n\r\n\"\"\r\n\r\n\r\nxyz\r\n\r\n\r\n\"",
                    '"abc","def"',
                ]),
                implode($lineEnd, [
                    '"",""',
                    '"",""',
                ]),
                $lineEnd,
            ];
        }
    }
}
