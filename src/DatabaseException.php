<?php

declare(strict_types=1);

namespace Simy\DB;

use Exception;
use Throwable;

class DatabaseException extends Exception
{
    private ?string $sql = null;
    private ?array $params = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?string $sql = null,
        ?array $params = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function __toString(): string
    {
        $message = parent::__toString();
        
        if ($this->sql) {
            $message .= "\nSQL: " . $this->sql;
        }
        
        if ($this->params) {
            $message .= "\nParams: " . json_encode($this->params, JSON_PRETTY_PRINT);
        }
        
        return $message;
    }
}