<?php

namespace Routing;

use Exception;
use stdClass;

/**
 * @param $url string
 * @return string
 */
function cleanUri ( $url ) {


    $phpSelf = dirname( $_SERVER[ 'PHP_SELF' ] );

    // prevent removing fist slash
    if ( $phpSelf != "/" )
        $url = str_replace( $phpSelf, '', $url );

    // Query-string entfernen
    $query_string = strpos( $url, '?' );
    if ( $query_string !== false )
        $url = substr( $url, 0, $query_string );

    // Das ermöglicht es http://localhost/index.php/uri zu nutzen
    $basename = basename( $_SERVER[ 'PHP_SELF' ] );
    if ( substr( $url, 1, strlen( $basename ) ) == $basename )
        $url = substr( $url, strlen( $basename ) + 1 );

    // Es soll auf jeden Fall mit einem / enden
    $url = rtrim( $url, '/' ) . '/';
    // doppelte slashes werden behoben
    $url = preg_replace( '/\/+/', '/', $url );

    return $url;
}

function hatRecht ( $name ) {

    if ( !isset( $_SESSION[ "user_id" ] ) )
        return false;

    foreach ( $_SESSION[ "rechte" ] as $recht )
        if ( $recht == $name )
            return true;
    return false;
}

/**
 * Used to handle routes  to eliminate the need to create a php file for every endpoint
 * @method post( $uri, $callback, ...$rechte )
 * @method get( $uri, $callback, ...$rechte )
 * @method delete( $uri, $callback, ...$rechte )
 */
class Router {

    private $root;
    private $supportedHttpMethods = array(
        "GET",
        "POST",
        "DELETE"
    );

    function __construct () {

        $this->root = str_replace( $_SERVER[ 'PHP_SELF' ], "", Request::$requestUri );
    }

    /**
     * magic method
     * @param $name string name der Methode
     * @param $args array arguments
     * @throws Exception
     */
    function __call ( $name, $args ) {

        list( $route, $method ) = $args;
        array_splice( $args, 0, 2 );

        if ( !in_array( strtoupper( $name ), $this->supportedHttpMethods ) )
            $this->invalidMethodHandler();

        $formatted = $this->formatRoute( $route );

        if ( isset( $this->{strtolower( $name )}[ $formatted ] ) )
            throw new Exception( 'Die URI "' . htmlspecialchars( $route ) . '" wurde schon registriert' );

        $obj = new stdClass();

        $obj->method = $method;
        $obj->rechte = $args;

        $this->{strtolower( $name )}[ $formatted ] = $obj;
    }

    /**
     * @param $route (string)
     * @return string
     */
    private function formatRoute ( $route ) {

        // Make sure the route ends in a / since all of the URLs will
        $route = rtrim( $route, '/' ) . '/';
        // Custom capture, format: <:var_name|regex>
        $route = preg_replace( '/\<\:(.*?)\|(.*?)\>/', '(?P<\1>\2)', $route );
        // Alphanumeric capture (0-9A-Za-z-_), format: <:var_name>
        $route = preg_replace( '/\<\:(.*?)\>/', '(?P<\1>[A-Za-z0-9\-\_]+)', $route );
        // Numeric capture (0-9), format: <#var_name>
        $route = preg_replace( '/\<\#(.*?)\>/', '(?P<\1>[0-9]+)', $route );
        // Numeric capture (0-9) (optional), format: <?#var_name>
        $route = preg_replace( '/\<\?\#(.*?)\>/', '(?P<\1>[0-9])?', $route );
        // Wildcard capture (Anything INCLUDING directory separators), format: <*var_name>
        $route = preg_replace( '/\<\*(.*?)\>/', '(?P<\1>.+)', $route );
        // Wildcard capture (Anything EXCLUDING directory separators), format: <!var_name>
        $route = preg_replace( '/\<\!(.*?)\>/', '(?P<\1>[^\/]+)', $route );
        // Add the regular expression syntax to make sure we do a full match or no match
        $route = '#^' . $route . '$#';

        return $route;
    }

    private function invalidMethodHandler () {
        Response::methodNotAllowed();
    }

    private function defaultRequestHandler () {
        Response::notFound();
    }

    /**
     * @param $descriptor
     * @return bool
     */
    private function checkRechte ( $descriptor ) {

        if ( empty( $descriptor->rechte ) )
            return true;

        foreach ( $descriptor->rechte as $recht )
            if ( hatRecht( $recht ) )
                return true;

        Response::notAllowed();
        return false;
    }

    /**
     * überprüft, ob die uri auf eine route zutrifft und ruft dann den Controller auf
     */
    function resolve () {

        $uri    = cleanUri( Request::$requestUri );
        $routes = $this->{strtolower( Request::$requestMethod )};
        header( "-x-uri:$uri - " . Request::$requestUri );

        foreach ( $routes as $route => $descriptor ) {

            if ( preg_match( $route, $uri, $matches ) ) {

                if ( !$this->checkRechte( $descriptor ) )
                    return;

                $params = array();

                // named Parameters
                foreach ( $matches as $key => $match )
                    if ( is_string( $key ) )
                        $params[] = $match;

                try {

                    if ( gettype( $descriptor->method ) == "string" ) {

                        $parts  = explode( "@", $descriptor->method );
                        $clazz  = "\Controller\\" . $parts[ 0 ];
                        $method = $parts[ 1 ];

                        $result = call_user_func_array( array( new $clazz(), $method ), $params );

                    } else {

                        $result = call_user_func_array( $descriptor->method, $params );
                    }

                    if ( !empty( $result ) )
                        echo $result;

                } catch ( Exception $e ) {

                    if ( $e instanceof APIException ) {

                        header( "x-own-message:true" );
                        header( Request::$serverProtocol . " {$e->getStatusCode()} {$e->getMessage()}" );

                    } else {

                        header( "{$_SERVER["SERVER_PROTOCOL"]} 500 {$e->getMessage()}" );
                    }
                }

                return;
            }
        }

        $this->defaultRequestHandler();
    }

    /**
     * Wenn jede Datei geladen wurde und jeder Code ausgeführt wurde, werden Klassen destructed und dann kann hier gehandelt werden
     */
    function __destruct () {

        $this->resolve();
    }
}