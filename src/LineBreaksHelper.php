<?php

namespace Keboola\Csv;

class LineBreaksHelper
{
    const REGEXP_DELIMITER = '~';

    /**
     * Detect line-breaks style in CSV file
     * @param string $sample
     * @param string $enclosure
     * @param string $escapedBy
     * @return string
     */
    public static function detectLineBreaks($sample, $enclosure, $escapedBy)
    {
        $cleared = self::clearCsvValues($sample, $enclosure, $escapedBy);

        $possibleLineBreaks = [
            "\r\n", // win
            "\r", // mac
            "\n", // unix
        ];

        $lineBreaksPositions = [];
        foreach ($possibleLineBreaks as $lineBreak) {
            $position = strpos($cleared, $lineBreak);
            if ($position === false) {
                continue;
            }
            $lineBreaksPositions[$lineBreak] = $position;
        }


        asort($lineBreaksPositions);
        reset($lineBreaksPositions);

        return empty($lineBreaksPositions) ? "\n" : key($lineBreaksPositions);
    }

    /**
     * Clear enclosured values in CSV eg. "abc" to "",
     * because these values can contain line breaks eg, "abc\n\r\n\r\r\r\r",
     * and this makes it difficult to detect line breaks style in CSV,
     * if are another line breaks present in first line.
     * @internal Should be used only in detectLineBreaks, public for easier testing.
     * @param string $sample
     * @param string $enclosure
     * @param string $escapedBy eg. empty string or \
     * @return string
     */
    public static function clearCsvValues($sample, $enclosure, $escapedBy)
    {
        // Usually an enclosure character is escaped by doubling it, however, the escapeBy can be used
        $doubleEnclosure = $enclosure . $enclosure;
        $escapedEnclosure = empty($escapedBy) ? $doubleEnclosure : $escapedBy . $enclosure;
        $escapedEscape = empty($escapedBy) ? null : $escapedBy . $escapedBy;

        /*
         * Regexp examples:
         * enclosure: |"|, escapedBy: none, regexp: ~"(?>(?>"")|[^"])*"~
         * enclosure: |"|, escapedBy: |\|,  regexp: ~"(?>(?>\\"|\\\\)|[^"])*"~
        */
        // @formatter:off
        $regexp =
            // regexp start
            self::REGEXP_DELIMITER .
                // enclosure start
                preg_quote($enclosure, self::REGEXP_DELIMITER) .
                    /*
                     * Once-only group => if there is a match, do not try other alternatives
                     * See: https://www.php.net/manual/en/regexp.reference.onlyonce.php
                     * Without once-only group will be |"abc\"| false positive,
                     * because |\| is matched by group and |"| at the end of regexp.
                     */
                    // repeated once-only group start
                    '(?>' .
                            // once-only group start
                            '(?>' .
                                // escaped enclosure
                                preg_quote($escapedEnclosure, self::REGEXP_DELIMITER) .
                                // OR escaped escape char
                                ($escapedEscape ? '|' . preg_quote($escapedEscape, self::REGEXP_DELIMITER) : '') .
                            // group end
                            ')' .
                            // OR not enclosure
                            '|[^' . preg_quote($enclosure, self::REGEXP_DELIMITER) . ']' .
                    // group end
                    ')*' .
                // enclosure end
                preg_quote($enclosure, self::REGEXP_DELIMITER) .
            // regexp end
            self::REGEXP_DELIMITER;
        // @formatter:on

        return preg_replace($regexp, $doubleEnclosure, $sample);
    }
}
