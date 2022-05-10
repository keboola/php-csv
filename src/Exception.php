<?php

declare(strict_types=1);

namespace Keboola\Csv;

use Exception as CoreException;

class Exception extends CoreException
{
    const FILE_NOT_EXISTS = 1;
    const INVALID_PARAM = 2;
    const WRITE_ERROR = 3;
}
