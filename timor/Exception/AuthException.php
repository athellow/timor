<?php
declare (strict_types = 1);

namespace timor\Exception;

use Exception;

class AuthException extends \RuntimeException
{
    private $statusCode;
    private $headers;

    public function __construct(string $message = '', $code = 0, Exception $previous = null, array $headers = [])
    {
        $this->headers    = $headers;

        if (is_numeric($message)) {
            $this->statusCode = $message;
            // $code = $message;
            $message = $message;    // TODO get lang;
        }

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}