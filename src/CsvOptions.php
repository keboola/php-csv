<?php

namespace Keboola\Csv;

class CsvOptions
{
    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSURE = '"';
    const DEFAULT_ESCAPED_BY = '';

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var string
     */
    private $escapedBy;

    /**
     * @param  string $delimiter
     * @param  string $enclosure
     * @param  string $escapedBy
     * @throws InvalidArgumentException
     */
    public function __construct(
        $delimiter = self::DEFAULT_DELIMITER,
        $enclosure = self::DEFAULT_ENCLOSURE,
        $escapedBy = self::DEFAULT_ESCAPED_BY
    ) {
        $this->escapedBy = $escapedBy;
        $this->validateDelimiter($delimiter);
        $this->delimiter = $delimiter;
        $this->validateEnclosure($enclosure);
        $this->enclosure = $enclosure;
    }

    /**
     * @param  string $enclosure
     * @throws InvalidArgumentException
     */
    protected function validateEnclosure($enclosure)
    {
        if (strlen($enclosure) > 1) {
            throw new InvalidArgumentException(
                'Enclosure must be a single character. ' . json_encode($enclosure) . ' received',
                Exception::INVALID_PARAM,
            );
        }
    }

    /**
     * @param  string $delimiter
     * @throws InvalidArgumentException
     */
    protected function validateDelimiter($delimiter)
    {
        if (strlen($delimiter) > 1) {
            throw new InvalidArgumentException(
                'Delimiter must be a single character. ' . json_encode($delimiter) . ' received',
                Exception::INVALID_PARAM,
            );
        }

        if (strlen($delimiter) === 0) {
            throw new InvalidArgumentException(
                'Delimiter cannot be empty.',
                Exception::INVALID_PARAM,
            );
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
