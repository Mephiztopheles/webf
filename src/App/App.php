<?php

namespace Mephiztopheles\webf\App;


use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Database\Connection;

class App {

    /**
     * @type Connection
     */
    public static $connection;
    public static $debugQueries = true;

    /**
     * @return Connection
     * @throws IllegalStateException
     */
    public static function getConnection() {

        if ( !isset( self::$connection ) )
            throw new IllegalStateException( "Set connection before using it" );

        return self::$connection;
    }

    public static function setConnection( $connection ) {
        self::$connection = $connection;
    }

    public static function warn( string $message ) {
        self::syslog( LOG_WARNING, $message );
    }

    public static function log( string $message ) {
        self::syslog( LOG_INFO, $message );
    }

    public static function error( string $message ) {
        self::syslog( LOG_ERR, $message );
    }

    private static function syslog( $priority, $message ) {
        syslog( $priority, $message );
    }
}