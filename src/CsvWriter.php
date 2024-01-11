<?php

namespace Keboola\Csv;

use TypeError;
use ValueError;

class CsvWriter extends AbstractCsvFile
{
    /**
     * @var string
     */
    private $lineBreak;

    /**
     * CsvFile constructor.
     * @param string|resource $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $lineBreak
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function __construct(
        $file,
        $delimiter = CsvOptions::DEFAULT_DELIMITER,
        $enclosure = CsvOptions::DEFAULT_ENCLOSURE,
        $lineBreak = "\n"
    ) {
        $this->options = new CsvOptions($delimiter, $enclosure);
        $this->setLineBreak($lineBreak);
        $this->setFile($file);
    }

    /**
     * @param string $lineBreak
     */
    private function setLineBreak($lineBreak)
    {
        $this->validateLineBreak($lineBreak);
        $this->lineBreak = $lineBreak;
    }

    /**
     * @param string $lineBreak
     */
    private function validateLineBreak($lineBreak)
    {
        $allowedLineBreaks = [
            "\r\n", // win
            "\r", // mac
            "\n", // unix
        ];
        if (!in_array($lineBreak, $allowedLineBreaks)) {
            throw new Exception(
                'Invalid line break: ' . json_encode($lineBreak) .
                ' allowed line breaks: ' . json_encode($allowedLineBreaks),
                Exception::INVALID_PARAM,
            );
        }
    }

    /**
     * @param string $fileName
     * @throws Exception
     */
    protected function openCsvFile($fileName)
    {
        try {
            $this->filePointer = @fopen($fileName, 'w');
        } catch (ValueError $e) {
            throw new Exception(
                "Cannot open file {$fileName} " . $e->getMessage(),
                Exception::FILE_NOT_EXISTS,
                $e,
            );
        }

        if (!$this->filePointer) {
            throw new Exception(
                "Cannot open file {$fileName} " . error_get_last()['message'],
                Exception::FILE_NOT_EXISTS,
            );
        }
    }

    /**
     * @param array $row
     * @throws Exception
     */
    public function writeRow(array $row)
    {
        $str = $this->rowToStr($row);
        try {
            $ret = @fwrite($this->getFilePointer(), $str);
        } catch (TypeError $e) {
            throw new Exception(
                'Cannot write to CSV file ' . $this->fileName .
                'Error: ' . $e->getMessage() .
                ' Return: false' .
                ' To write: ' . strlen($str) . ' Written: 0',
                Exception::WRITE_ERROR,
                $e,
            );
        }

        /* According to http://php.net/fwrite the fwrite() function
         should return false on error. However not writing the full
         string (which may occur e.g. when disk is full) is not considered
         as an error. Therefore both conditions are necessary. */
        if (($ret === false) || (($ret === 0) && (strlen($str) > 0))) {
            throw new Exception(
                'Cannot write to CSV file ' . $this->fileName .
                ($ret === false && error_get_last() ? 'Error: ' . error_get_last()['message'] : '') .
                ' Return: ' . json_encode($ret) .
                ' To write: ' . strlen($str) . ' Written: ' . (int) $ret,
                Exception::WRITE_ERROR,
            );
        }
    }

    /**
     * @param array $row
     * @return string
     * @throws Exception
     */
    public function rowToStr(array $row)
    {
        $return = [];
        foreach ($row as $column) {
            if (!(
                is_scalar($column)
                || is_null($column)
                || (
                    is_object($column)
                    && method_exists($column, '__toString')
                )
            )) {
                throw new Exception(
                    'Cannot write data into column: ' . var_export($column, true),
                    Exception::WRITE_ERROR,
                );
            }

            $enclosure = $this->getEnclosure();
            $escapedEnclosure = str_repeat($enclosure, 2);
            $columnValue = ($column === false) ? '0' : ($column ?? '');

            $escapedColumn = str_replace($enclosure, $escapedEnclosure, $columnValue);
            $return[] = sprintf('%s%s%s', $enclosure, $escapedColumn, $enclosure);
        }
        return implode($this->getDelimiter(), $return) . $this->lineBreak;
    }
}
