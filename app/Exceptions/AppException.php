<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class AppException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly int $errorCode = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
