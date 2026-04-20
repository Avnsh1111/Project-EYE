<?php

namespace App\Exceptions;

use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly int $used,
        public readonly int $total,
    ) {
        parent::__construct("Storage quota exceeded: {$used} / {$total} bytes used.");
    }
}
