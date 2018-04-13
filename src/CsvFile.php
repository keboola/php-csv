<?php

namespace Keboola\Csv;

class CsvFile extends \SplFileInfo implements \Iterator
{
    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSURE = '"';
    const DEFAULT_ESCAPED_BY = "";

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var string
     */
    protected $enclosure;

    /**
     * @var string
     */
    protected $escapedBy;

    /**
     * @var int
     */
    protected $skipLines;

    /**
     * @var resource
     */
    protected $filePointer;

    /**
     * @var int
     */
    protected $rowCounter = 0;

    /**
     * @var array|null|false
     */
    protected $currentRow;

    /**
     * @var string
     */
    protected $lineBreak;

    /**
     * CsvFile constructor.
     * @param string $fileName
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escapedBy
     * @param int $skipLines
     * @throws InvalidArgumentException
     */
    public function __construct(
        $fileName,
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $escapedBy = self::DEFAULT_ESCAPED_BY,
        $skipLines = 0
    ) {
        parent::__construct($fileName);

        $this->escapedBy = $escapedBy;
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setSkipLines($skipLines);
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * @param integer $skipLines
     * @return CsvFile
     * @throws InvalidArgumentException
     */
    protected function setSkipLines($skipLines)
    {
        $this->validateSkipLines($skipLines);
        $this->skipLines = $skipLines;
        return $this;
    }

    /**
     * @param integer $skipLines
     * @throws InvalidArgumentException
     */
    protected function validateSkipLines($skipLines)
    {
        if (!is_int($skipLines) || $skipLines < 0) {
            throw new InvalidArgumentException(
                "Number of lines to skip must be a positive integer. \"$skipLines\" received.",
                Exception::INVALID_PARAM,
                null,
                Exception::INVALID_PARAM_STR
            );
        }
    }

    /**
     * @param string $delimiter
     * @return CsvFile
     * @throws InvalidArgumentException
     */
    protected function setDelimiter($delimiter)
    {
        $this->validateDelimiter($delimiter);
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @param string $delimiter
     * @throws InvalidArgumentException
     */
    protected function validateDelimiter($delimiter)
    {
        if (strlen($delimiter) > 1) {
            throw new InvalidArgumentException(
                "Delimiter must be a single character. \"$delimiter\" received",
                Exception::INVALID_PARAM,
                null,
                Exception::INVALID_PARAM_STR
            );
        }

        if (strlen($delimiter) == 0) {
            throw new InvalidArgumentException(
                "Delimiter cannot be empty.",
                Exception::INVALID_PARAM,
                null,
                Exception::INVALID_PARAM_STR
            );
        }
    }

    /**
     * @param string $enclosure
     * @return $this
     * @throws InvalidArgumentException
     */
    protected function setEnclosure($enclosure)
    {
        $this->validateEnclosure($enclosure);
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * @param string $enclosure
     * @throws InvalidArgumentException
     */
    protected function validateEnclosure($enclosure)
    {
        if (strlen($enclosure) > 1) {
            throw new InvalidArgumentException(
                "Enclosure must be a single character. \"$enclosure\" received",
                Exception::INVALID_PARAM,
                null,
                Exception::INVALID_PARAM_STR
            );
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function detectLineBreak()
    {
        rewind($this->getFilePointer());
        $sample = fread($this->getFilePointer(), 10000);
        rewind($this->getFilePointer());

        $possibleLineBreaks = [
            "\r\n", // win
            "\r", // mac
            "\n", // unix
        ];

        $lineBreaksPositions = [];
        foreach ($possibleLineBreaks as $lineBreak) {
            $position = strpos($sample, $lineBreak);
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
     * @return array|false|null
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function readLine()
    {
        $this->validateLineBreak();

        // allow empty enclosure hack
        $enclosure = !$this->getEnclosure() ? chr(0) : $this->getEnclosure();
        $escapedBy = !$this->escapedBy ? chr(0) : $this->escapedBy;
        return fgetcsv($this->getFilePointer(), null, $this->getDelimiter(), $enclosure, $escapedBy);
    }

    /**
     * @param string $mode
     * @return resource
     * @throws Exception
     */
    protected function getFilePointer($mode = 'r')
    {
        if (!is_resource($this->filePointer)) {
            $this->openCsvFile($mode);
        }
        return $this->filePointer;
    }

    /**
     * @param string $mode
     * @throws Exception
     */
    protected function openCsvFile($mode)
    {
        if ($mode == 'r' && !is_file($this->getPathname())) {
            throw new Exception(
                "Cannot open file {$this->getPathname()}",
                Exception::FILE_NOT_EXISTS,
                null,
                Exception::FILE_NOT_EXISTS_STR
            );
        }
        $this->filePointer = @fopen($this->getPathname(), $mode);
        if (!$this->filePointer) {
            throw new Exception(
                "Cannot open file {$this->getPathname()} " . error_get_last()['message'],
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

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * @return string
     */
    public function getEscapedBy()
    {
        return $this->escapedBy;
    }

    /**
     * @return int
     */
    public function getColumnsCount()
    {
        return count($this->getHeader());
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        $this->rewind();
        $current = $this->current();
        if (is_array($current)) {
            return $current;
        }

        return [];
    }

    /**
     * @param array $row
     * @throws Exception
     */
    public function writeRow(array $row)
    {
        $str = $this->rowToStr($row);
        $ret = fwrite($this->getFilePointer('w+'), $str);

        /* According to http://php.net/fwrite the fwrite() function
         should return false on error. However not writing the full
         string (which may occur e.g. when disk is full) is not considered
         as an error. Therefore both conditions are necessary. */
        if (($ret === false) || (($ret === 0) && (strlen($str) > 0))) {
            throw new Exception(
                "Cannot write to file {$this->getPathname()}",
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
                $type = gettype($column);
                throw new Exception(
                    "Cannot write {$type} into a column",
                    Exception::WRITE_ERROR,
                    null,
                    Exception::WRITE_ERROR_STR,
                    ['column' => $column]
                );
            }

            $return[] = $this->getEnclosure() .
                str_replace($this->getEnclosure(), str_repeat($this->getEnclosure(), 2), $column) .
                $this->getEnclosure();
        }
        return implode($this->getDelimiter(), $return) . "\n";
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getLineBreak()
    {
        if (!$this->lineBreak) {
            $this->lineBreak = $this->detectLineBreak();
        }
        return $this->lineBreak;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getLineBreakAsText()
    {
        return trim(json_encode($this->getLineBreak()), '"');
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function validateLineBreak()
    {
        try {
            $lineBreak = $this->getLineBreak();
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                "Failed to detect line break: " . $e->getMessage(),
                Exception::INVALID_PARAM,
                $e,
                Exception::INVALID_PARAM_STR
            );
        }
        if (in_array($lineBreak, ["\r\n", "\n"])) {
            return $lineBreak;
        }

        throw new InvalidArgumentException(
            "Invalid line break. Please use unix \\n or win \\r\\n line breaks.",
            Exception::INVALID_PARAM,
            null,
            Exception::INVALID_PARAM_STR
        );
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->currentRow;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->currentRow = $this->readLine();
        $this->rowCounter++;
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->rowCounter;
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->currentRow !== false;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        rewind($this->getFilePointer());
        for ($i = 0; $i < $this->skipLines; $i++) {
            $this->readLine();
        }
        $this->currentRow = $this->readLine();
        $this->rowCounter = 0;
    }
}
