<?php

namespace Keboola\Csv;

class CsvReader extends AbstractCsvFile implements \Iterator
{
    /**
     * @deprecated use Keboola\Csv\CsvOptions::DEFAULT_ENCLOSURE
     */
    const DEFAULT_ESCAPED_BY = CsvOptions::DEFAULT_ESCAPED_BY;

    /**
     * @var int
     */
    private $skipLines;

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
    private $lineBreak;

    /**
     * CsvFile constructor.
     * @param string|resource $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escapedBy
     * @param int $skipLines
     * @throws Exception
     */
    public function __construct(
        $file,
        $delimiter = CsvOptions::DEFAULT_DELIMITER,
        $enclosure = CsvOptions::DEFAULT_ENCLOSURE,
        $escapedBy = CsvOptions::DEFAULT_ESCAPED_BY,
        $skipLines = 0
    ) {
        $this->options = new CsvOptions($delimiter, $enclosure, $escapedBy);
        $this->setSkipLines($skipLines);
        $this->setFile($file);

        $this->lineBreak = $this->detectLineBreak();
        $this->validateLineBreak();

        rewind($this->filePointer);
        $this->header = UTF8BOMHelper::detectAndRemoveBOM($this->readLine());
        $this->rewind();
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
                Exception::INVALID_PARAM
            );
        }
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
                Exception::FILE_NOT_EXISTS
            );
        }
        $this->filePointer = @fopen($fileName, "r");
        if (!$this->filePointer) {
            throw new Exception(
                "Cannot open file {$fileName} " . error_get_last()['message'],
                Exception::FILE_NOT_EXISTS
            );
        }
    }

    /**
     * @return string
     */
    protected function detectLineBreak()
    {
        @rewind($this->getFilePointer());
        $sample = @fread($this->getFilePointer(), 10000);

        return LineBreaksHelper::detectLineBreaks($sample, $this->getEnclosure(), $this->getEscapedBy());
    }

    /**
     * @return array|false|null
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function readLine()
    {
        // allow empty enclosure hack
        $enclosure = !$this->getEnclosure() ? chr(0) : $this->getEnclosure();
        $escapedBy = !$this->getEscapedBy() ? chr(0) : $this->getEscapedBy();
        return @fgetcsv($this->getFilePointer(), null, $this->getDelimiter(), $enclosure, $escapedBy);
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    protected function validateLineBreak()
    {
        $lineBreak = $this->getLineBreak();
        if (in_array($lineBreak, ["\r\n", "\n"])) {
            return $lineBreak;
        }

        throw new InvalidArgumentException(
            "Invalid line break. Please use unix \\n or win \\r\\n line breaks.",
            Exception::INVALID_PARAM
        );
    }

    /**
     * @return string
     */
    public function getLineBreak()
    {
        return $this->lineBreak;
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

    /**
     * @return string
     */
    public function getEscapedBy()
    {
        return $this->options->getEscapedBy();
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
    public function getLineBreakAsText()
    {
        return trim(json_encode($this->getLineBreak()), '"');
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
}
