<?php

namespace Keboola\Csv;

class Exception extends \Exception
{
    const FILE_NOT_EXISTS = 1;
    const INVALID_PARAM = 2;
    const WRITE_ERROR = 3;

    const INVALID_PARAM_STR = 'invalidParam';
    const WRITE_ERROR_STR = 'writeError';
    const FILE_NOT_EXISTS_STR = 'fileNotExists';

    /**
     * @var string
     */
    protected $stringCode;

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     * @param string $stringCode
     * @param array|null $params
     */
    public function __construct($message = "", $code = 0, $previous = null, $stringCode = null, $params = null)
    {
        $this->setStringCode($stringCode);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getStringCode()
    {
        return $this->stringCode;
    }

    /**
     * @param string $stringCode
     * @return Exception
     */
    public function setStringCode($stringCode)
    {
        $this->stringCode = (string)$stringCode;
        return $this;
    }
}
