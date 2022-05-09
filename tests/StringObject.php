<?php

declare(strict_types=1);

namespace Keboola\Csv\Tests;

class StringObject
{
    public function __toString()
    {
        return 'me string';
    }
}
