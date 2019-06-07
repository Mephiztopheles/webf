<?php

namespace Mephiztopheles\webf\Routing;

use \Exception;
use \Throwable;

/**
 * Exception which is used to automatically send the client the information to use this message as alert
 * Class APIException
 *
 * @package Routing
 */
class APIException extends Exception {

    private $statusCode;

    public function __construct( string $message = "", int $code = 500, Throwable $previous = null ) {
        parent::__construct( $message, 0, $previous );

        $this->statusCode = $code;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }
}