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
     * @var array
     */
    protected $contextParams;

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
        $this->setContextParams($params);
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

    /**
     * @return array
     */
    public function getContextParams()
    {
        return $this->contextParams;
    }

    /**
     * @param array|null $contextParams
     * @return Exception
     */
    public function setContextParams($contextParams)
    {
        $this->contextParams = (array)$contextParams;
        return $this;
    }
}
