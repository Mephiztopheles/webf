<?php

namespace Mephiztopheles\webf\Routing;

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

    // remove query-string
    $queryString = strpos( $url, '?' );
    if ( $queryString !== false )
        $url = substr( $url, 0, $queryString );

    // allows to use http://localhost/index.php/uri
    $basename = basename( $_SERVER[ 'PHP_SELF' ] );
    if ( substr( $url, 1, strlen( $basename ) ) == $basename )
        $url = substr( $url, strlen( $basename ) + 1 );

    // ensure it ends with a /
    $url = rtrim( $url, '/' ) . '/';
    // remove double slashes
    $url = preg_replace( '/\/+/', '/', $url );

    return $url;
}

/**
 * Used to handle routes  to eliminate the need to create a php file for every endpoint
 * @method post( $uri, $callback, ...$rights )
 * @method get( $uri, $callback, ...$rights )
 * @method delete( $uri, $callback, ...$rights )
 */
class Router {

    /**
     * @var Response
     * @type Response
     */
    private $response;

    private $root;
    private $rights               = [];
    private $supportedHttpMethods = array(
        "GET",
        "POST",
        "DELETE"
    );


    function __construct ( $root, $rights ) {

        $this->root     = $root;
        $this->rights   = $rights;
        $this->response = new Response();
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
            throw new Exception( 'The URI "' . htmlspecialchars( $route ) . '" was already registered' );

        $descriptor = new stdClass();

        $descriptor->method = $method;
        $descriptor->rights = $args;

        $this->{strtolower( $name )}[ $formatted ] = $descriptor;
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
        $this->response->methodNotAllowed();
    }

    private function defaultRequestHandler () {
        $this->response->notFound();
    }

    /**
     * @param $descriptor
     * @return bool
     */
    private function checkAccess ( $descriptor ) {

        if ( empty( $descriptor->rights ) )
            return true;

        if ( empty( $this->rights ) )
            return false;

        foreach ( $descriptor->rights as $right )
            foreach ( $this->rights as $name )
                if ( $name == $right )
                    return true;

        $this->response->notAllowed();
        return false;
    }

    /**
     * checks the routes and calls the Controller
     */
    private function resolve () {

        $uri    = cleanUri( Request::$requestUri );
        $routes = $this->{strtolower( Request::$requestMethod )};
        header( "-x-uri:$uri" );

        foreach ( $routes as $route => $descriptor ) {

            if ( preg_match( $route, $uri, $matches ) ) {

                if ( !$this->checkAccess( $descriptor ) )
                    return;

                $params = array();

                // named Parameters
                foreach ( $matches as $key => $match )
                    if ( is_string( $key ) )
                        $params[] = $match;

                $params[] = $this->response;

                try {

                    if ( gettype( $descriptor->method ) == "string" ) {

                        $parts  = explode( "@", $descriptor->method );
                        $clazz  = $parts[ 0 ];
                        $method = $parts[ 1 ];

                        $result = call_user_func_array( array( new $clazz(), $method ), $params );

                    } else {

                        $result = call_user_func_array( $descriptor->method, $params );
                    }

                    if ( !empty( $result ) ) {

                        if ( $result instanceof Response )
                            $result->send();
                        else
                            $this->response->json( $result )->send();
                    }

                } catch ( Exception $e ) {

                    if ( $e instanceof APIException ) {

                        $this->response->header( "x-message:true" )->header( Request::$serverProtocol . " {$e->getStatusCode()} {$e->getMessage()}" );

                    } else {
                        header( "{$_SERVER["SERVER_PROTOCOL"]} 500 {$e->getMessage()}" );
                    }
                }

                return;
            }
        }

        $this->defaultRequestHandler();
    }

    function __destruct () {
        $this->resolve();
    }
}