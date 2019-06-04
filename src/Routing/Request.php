<?php

namespace Mephiztopheles\webf\Routing;

/**
 *
 * Holds request information
 * Class Request
 * @package Routing
 */
class Request {

    public $serverProtocol;
    public $requestMethod;
    public $requestUri;
    public $documentRoot;
    public $body;
    public $query;

    public function __construct () {

        $this->serverProtocol = $_SERVER[ "SERVER_PROTOCOL" ];
        $this->documentRoot   = $_SERVER[ "DOCUMENT_ROOT" ];
        $this->requestUri     = $_SERVER[ "REQUEST_URI" ];
        $this->requestMethod  = $_SERVER[ "REQUEST_METHOD" ];

        $this->body  = $this->getBody();
        $this->query = $this->getQuery();
    }

    /**
     * retrieves POST input decoded from JSON
     * @return mixed|null
     */
    private function getBody () {

        if ( $this->requestMethod === "GET" )
            return null;

        return json_decode( file_get_contents( 'php://input' ) );
    }

    /**
     * retrieves GET input as stdClass and automatically parses numeric parameters
     * @return mixed
     */
    private function getQuery () {

        $output = json_decode( json_encode( $_GET ) );

        foreach ( $output as $k => $v )
            if ( is_numeric( $v ) )
                $output->$k = intval( $v );

        return $output;
    }
}
