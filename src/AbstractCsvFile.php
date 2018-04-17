<?php

namespace Keboola\Csv;

use SplFileInfo;

class AbstractCsvFile extends SplFileInfo
{
    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSURE = '"';

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
    protected $lineBreak;

    /**
     * @param string $delimiter
     * @throws InvalidArgumentException
     */
    protected function setDelimiter($delimiter)
    {
        $this->validateDelimiter($delimiter);
        $this->delimiter = $delimiter;
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

}
