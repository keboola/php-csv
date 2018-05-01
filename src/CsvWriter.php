<?php

namespace Keboola\Csv;

class CsvWriter extends AbstractCsvFile
{
    /**
     * @var resource
     */
    private $filePointer;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var string
     */
    private $fileName = '';

    /**
     * CsvFile constructor.
     * @param mixed $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $mode
     * @throws Exception
     */
    public function __construct(
        $file,
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $mode = 'w'
    ) {
        $this->setMode($mode);
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        if (is_string($file)) {
            $this->openCsvFile($file);
            $this->fileName = $file;
        } elseif (is_resource($file)) {
            $this->filePointer = $file;
        } else {
            throw new InvalidArgumentException("Invalid file: " . var_export($file, true));
        }
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * @param array $row
     * @throws Exception
     */
    public function writeRow(array $row)
    {
        $str = $this->rowToStr($row);
        $ret = fwrite($this->getFilePointer(), $str);

        /* According to http://php.net/fwrite the fwrite() function
         should return false on error. However not writing the full
         string (which may occur e.g. when disk is full) is not considered
         as an error. Therefore both conditions are necessary. */
        if (($ret === false) || (($ret === 0) && (strlen($str) > 0))) {
            throw new Exception(
                "Cannot write to CSV file " . $this->fileName . " " . error_get_last()['message'],
                Exception::WRITE_ERROR,
                null,
                Exception::WRITE_ERROR_STR
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
                    "Cannot write data into column: " . var_export($column, true),
                    Exception::WRITE_ERROR,
                    null,
                    Exception::WRITE_ERROR_STR
                );
            }

            $return[] = $this->getEnclosure() .
                str_replace($this->getEnclosure(), str_repeat($this->getEnclosure(), 2), $column) .
                $this->getEnclosure();
        }
        return implode($this->getDelimiter(), $return) . "\n";
    }

    /**
     * @return resource
     */
    protected function getFilePointer()
    {
        return $this->filePointer;
    }

    /**
     * @param string $fileName
     * @throws Exception
     */
    protected function openCsvFile($fileName)
    {
        $this->filePointer = @fopen($fileName, $this->mode);
        if (!$this->filePointer) {
            throw new Exception(
                "Cannot open file {$fileName} " . error_get_last()['message'],
                Exception::FILE_NOT_EXISTS,
                null,
                Exception::FILE_NOT_EXISTS_STR
            );
        }
    }

    protected function closeFile()
    {
        if (is_resource($this->filePointer)) {
            fclose($this->filePointer);
        }
    }

    protected function setMode($mode)
    {
        $this->validateMode($mode);
        $this->mode = $mode;
    }

    protected function validateMode($mode)
    {
        $allowedModes = ['w', 'a', 'x'];
        if (!in_array($mode, $allowedModes)) {
            throw new Exception(
                "Invalid file mode: " . $mode . " allowed modes: " . implode(',', $allowedModes),
                Exception::INVALID_PARAM,
                null,
                Exception::INVALID_PARAM_STR
            );
        }
    }
}
