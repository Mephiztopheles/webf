<?php

namespace Routing;

/**
 * Includes shorthands for the response codes
 * Class Response
 * @package Routing
 */
class Response {

    public static function ok () {
        self::header( "200 OK" );
    }

    public static function created () {
        self::header( "201 Created" );
    }

    public static function methodNotAllowed () {
        self::header( "405 Method Not Allowed" );
    }

    public static function notAllowed () {
        self::header( "403 Not Allowed" );
    }

    public static function notFound () {
        self::header( "404 Not found" );
    }

    public static function json ( $content = null ) {

        header( "Content-Type: application/json; charset=UTF-8" );

        if ( isset( $content ) )
            return json_encode( $content, JSON_UNESCAPED_UNICODE );

        return "";
    }

    public static function header ( $header ) {
        header( Request::$serverProtocol . " $header" );
    }
}