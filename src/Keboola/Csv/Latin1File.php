<?php

namespace Keboola\Csv;

/**
* Csv with latin1 charset with utf8 translation
*/
class Latin1File extends CsvFile
{
    /**
     * Translate the values of the row to the utf8 encode
     */
    protected function _readLine()
    {
        $row = parent::_readLine();

        if ($row !== false) {
            return array_map(function ($value) {
                return utf8_encode($value);
            }, $row);
        }

        return false;
    }
}