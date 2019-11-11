<?php

namespace Keboola\Csv;

abstract class AbstractCsvFile
{
    /**
     * @deprecated use Keboola\Csv\CsvOptions::DEFAULT_DELIMITER
     */
    const DEFAULT_DELIMITER = CsvOptions::DEFAULT_DELIMITER;
    /**
     * @deprecated use Keboola\Csv\CsvOptions::DEFAULT_ENCLOSURE
     */
    const DEFAULT_ENCLOSURE = CsvOptions::DEFAULT_ENCLOSURE;

    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var resource
     */
    protected $filePointer;
    /**
     * @var CsvOptions
     */
    protected $options;

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->options->getDelimiter();
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->options->getEnclosure();
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    protected function closeFile()
    {
        if ($this->fileName && is_resource($this->filePointer)) {
            fclose($this->filePointer);
        }
    }

    /**
     * @param string|resource $file
     */
    protected function setFile($file)
    {
        if (is_string($file)) {
            $this->openCsvFile($file);
            $this->fileName = $file;
        } elseif (is_resource($file)) {
            $this->filePointer = $file;
        } else {
            throw new InvalidArgumentException("Invalid file: " . var_export($file, true));
        }
    }

    abstract protected function openCsvFile($fileName);

    /**
     * @return resource
     */
    protected function getFilePointer()
    {
        return $this->filePointer;
    }
}
