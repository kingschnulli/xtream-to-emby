<?php

namespace App\Service\Xtream\Exception;

class XtreamException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}