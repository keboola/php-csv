<?php

namespace Keboola\Csv;

class CsvWriter extends AbstractCsvFile
{
    use CsvRowUtil;

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
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $lineBreak = "\n"
    ) {
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
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
                "Invalid line break: " . json_encode($lineBreak) .
                " allowed line breaks: " . json_encode($allowedLineBreaks),
                Exception::INVALID_PARAM
            );
        }
    }

    /**
     * @param string $fileName
     * @throws Exception
     */
    protected function openCsvFile($fileName)
    {
        $this->filePointer = @fopen($fileName, 'w');
        if (!$this->filePointer) {
            throw new Exception(
                "Cannot open file {$fileName} " . error_get_last()['message'],
                Exception::FILE_NOT_EXISTS
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
        $ret = @fwrite($this->getFilePointer(), $str);

        /* According to http://php.net/fwrite the fwrite() function
         should return false on error. However not writing the full
         string (which may occur e.g. when disk is full) is not considered
         as an error. Therefore both conditions are necessary. */
        if (($ret === false) || (($ret === 0) && (strlen($str) > 0))) {
            throw new Exception(
                "Cannot write to CSV file " . $this->fileName .
                ($ret === false && error_get_last() ? 'Error: ' . error_get_last()['message'] : '') .
                ' Return: ' . json_encode($ret) .
                ' To write: ' . strlen($str) . ' Written: ' . $ret,
                Exception::WRITE_ERROR
            );
        }
    }


    /**
     * TODO:
     *
     * @return mixed
     */
    protected function getLineBreak()
    {
        return $this->lineBreak;
    }
}
