<?php

namespace Routing;

/**
 *
 * Holds request information
 * Class Request
 * @package Routing
 */
class Request {

    public static $serverProtocol;
    public static $requestMethod;
    public static $requestUri;
    public static $documentRoot;

    /**
     * retrieves POST input decoded from JSON
     * @return mixed|null
     */
    public static function getBody () {

        if ( self::$requestMethod === "GET" )
            return null;

        return json_decode( file_get_contents( 'php://input' ) );
    }

    /**
     * retrieves GET input as stdClass and automatically parses numeric parameters
     * @return mixed
     */
    public static function getQuery () {

        $output = json_decode( json_encode( $_GET ) );

        foreach ( $output as $k => $v )
            if ( is_numeric( $v ) )
                $output->$k = intval( $v );

        return $output;
    }
}

Request::$documentRoot = $_SERVER[ "DOCUMENT_ROOT" ];
Request::$requestUri = $_SERVER[ "REQUEST_URI" ];
Request::$requestMethod = $_SERVER[ "REQUEST_METHOD" ];
Request::$serverProtocol = $_SERVER[ "SERVER_PROTOCOL" ];