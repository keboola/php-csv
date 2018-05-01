<?php

namespace Keboola\Csv;

class CsvReader extends AbstractCsvFile implements \Iterator
{
    const DEFAULT_ESCAPED_BY = "";

    /**
     * @var string
     */
    private $escapedBy;

    /**
     * @var int
     */
    private $skipLines;

    /**
     * @var resource
     */
    private $filePointer;

    /**
     * @var int
     */
    private $rowCounter = 0;

    /**
     * @var array|null|false
     */
    private $currentRow;

    /**
     * @var array
     */
    private $header;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $lineBreak;

    /**
     * CsvFile constructor.
     * @param mixed $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escapedBy
     * @param int $skipLines
     * @throws Exception
     */
    public function __construct(
        $file,
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $escapedBy = self::DEFAULT_ESCAPED_BY,
        $skipLines = 0
    ) {
        $this->escapedBy = $escapedBy;
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setSkipLines($skipLines);
        if (is_string($file)) {
            $this->openCsvFile($file);
            $this->fileName = $file;
        } elseif (is_resource($file)) {
            $this->filePointer = $file;
        } else {
            throw new InvalidArgumentException("Invalid file: " . var_export($file, true));
        }
        $this->lineBreak = $this->detectLineBreak();
        rewind($this->filePointer);
        $this->header = $this->readLine();
        $this->rewind();
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * @param integer $skipLines
     * @return CsvReader
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
     * @return string
     */
    protected function detectLineBreak()
    {
        rewind($this->getFilePointer());
        $sample = fread($this->getFilePointer(), 10000);

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
     * @return resource
     */
    protected function getFilePointer()
    {
        return $this->filePointer;
    }

    /**
     * @param $fileName
     * @throws Exception
     */
    protected function openCsvFile($fileName)
    {
        if (!is_file($fileName)) {
            throw new Exception(
                "Cannot open file " . $fileName,
                Exception::FILE_NOT_EXISTS,
                null,
                Exception::FILE_NOT_EXISTS_STR
            );
        }
        $this->filePointer = @fopen($fileName, "r");
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
        if ($this->header) {
            return $this->header;
        }
        return [];
    }

    /**
     * @return string
     */
    public function getLineBreak()
    {
        return $this->lineBreak;
    }

    /**
     * @return string
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
