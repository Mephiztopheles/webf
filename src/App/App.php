<?php

namespace Mephiztopheles\webf\App;


use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Database\Connection;

class App {

    /**
     * @type Connection
     */
    public static $connection;

    /**
     * @return Connection
     * @throws IllegalStateException
     */
    public static function getConnection () {

        if ( !isset( self::$connection ) )
            throw new IllegalStateException( "Set connection before using it" );

        return self::$connection;
    }

    public static function setConnection ( $connection ) {
        self::$connection = $connection;
    }

    public static function warn ( string $message ) {
        trigger_error( $message, E_USER_WARNING );
    }

    public static function log ( string $message ) {
        trigger_error( $message, E_USER_NOTICE );
    }

    public static function error ( string $message ) {
        trigger_error( $message, E_USER_ERROR );
    }
}