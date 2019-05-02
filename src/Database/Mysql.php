<?php

namespace Mephiztopheles\webf\Database;

use PDO;
use PDOStatement;
use Mephiztopheles\webf\Routing\APIException;
use stdClass;

/**
 * used as an Adapter for communicating with the database.
 * using prepared statements internally
 * Class Mysql
 * @package Database
 */
class Mysql {

    /**
     *
     * last statement is saved
     * @type PDOStatement
     */
    private static $query;

    /**
     * In some situations, it is necessary to open a new connection. this is the list of open connections
     * @type array<PDO>
     */
    private static $connections = [];
    private static $connection = -1;

    public static function beginTransaction () {
        self::getConnection()->beginTransaction();
    }

    public static function commit () {
        self::getConnection()->commit();
    }

    public static function rollBack(){
        self::getConnection()->rollBack();
    }

    public static function closeCursor () {
        self::$query->closeCursor();
    }

    /**
     * Helper function to return the Database statements result as array
     * @param $sql
     * @param array $args
     * @return array
     * @throws APIException
     */
    public static function list ( $sql, $args = null ) {

        $query = self::execute( $sql, $args );

        $results = [];

        do {

            $results = array_merge( $results, $query->fetchAll( PDO::FETCH_OBJ ) );

        } while ( $query->nextRowset() );

        return $results;
    }

    /**
     * Helper function to return the Database statements first result as object
     * @param $sql
     * @param array $args
     * @return object|stdClass
     * @throws APIException
     */
    public static function get ( $sql, $args = null ) {

        $query = self::execute( $sql, $args );

        $result = $query->fetch( PDO::FETCH_OBJ );
        $query->closeCursor();
        return $result;
    }

    /**
     * Helper function which allows to execute a statement without caring about a return value or decide to fetch an array or a single object
     * @param $sql
     * @param array $args
     * @return bool|PDOStatement
     * @throws APIException
     */
    public static function execute ( $sql, $args = null ) {

        if ( $args == null ) {

            $query = self::getConnection()->query( $sql );

        } else {

            $query = self::prepare( $sql );

            if ( $query->execute( $args ) == false )
                self::error();
        }

        return $query;
    }

    /**
     * Helper function to update n to m relations
     * @param $id
     * @param $list
     * @param $table
     * @param $pk
     * @param $fk
     * @throws APIException
     */
    public static function updateManyToMany ( $id, $list, $table, $pk, $fk ) {


        if ( count( $list ) == 0 ) {

            $query = self::prepare( "DELETE FROM $table where $pk = :id" );
            $query->execute( [ "id" => &$id ] );

        } else {

            $ids = [];
            foreach ( $list as $entry ) {

                if ( gettype( $entry->id ) === "integer" )
                    $ids[] = $entry->id;
                else
                    throw new APIException( "Primärschlüssel müssen Integer sein", 400 );
            }

            $idsString = implode( ",", $ids );

            $query = self::prepare( "DELETE FROM $table where $fk not in ($idsString) and $pk = :id" );
            $query->execute( [ "id" => &$id ] );
        }


        foreach ( $list as $entry ) {

            $query = self::prepare( "SELECT * FROM $table WHERE $pk = :id AND $fk = :fk" );
            $query->execute( [ "id" => $id, "fk" => $entry->id ] );

            if ( $query->rowCount() == 0 ) {

                $query = self::prepare( "INSERT INTO $table($pk, $fk) VALUES(:id,:fk)" );
                $query->execute( [ "id" => $id, "fk" => $entry->id ] );
            }
        }
    }

    /**
     * retrieves last insert id
     * @return string
     */
    public static function lastInsertId () {
        return intval( self::getConnection()->lastInsertId() );
    }

    /**
     * creates a new connection. Is called automatically on the end of this file.
     * Is using the configurations from config.ini
     * @return void
     */
    public static function connect () {
        global $config;

        self::$connection++;
        $dbConfig = $config->config[ "datenbank" ];
        self::$connections[ self::$connection ] = new PDO( "mysql:host=" . $dbConfig[ "host" ] . ":" . $dbConfig[ "port" ] . ";dbname=" . $dbConfig[ "datenbank" ], $dbConfig[ "benutzer" ], $dbConfig[ "passwort" ], [ PDO::MYSQL_ATTR_FOUND_ROWS => true ] );
        self::$connections[ self::$connection ]->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
        self::$connections[ self::$connection ]->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, false );
        self::$connections[ self::$connection ]->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true );
        self::$connections[ self::$connection ]->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }

    private static function getConnection () {
        return self::$connections[ self::$connection ];
    }

    /**
     * closes the current connection
     * PDO does not have a real function to close the connection, because the connection is closed on unload,
     * so this is only reducing the count of connections to return to the previous connection
     */
    public static function close () {
        self::$connection--;
    }

    /**
     * prepares the statement and checks if any error appeared.
     * @param $sql
     * @return bool|PDOStatement
     * @throws APIException
     */
    private static function prepare ( $sql ) {

        $query = self::getConnection()->prepare( $sql );
        if ( !$query )
            self::error();

        self::$query = $query;
        return $query;
    }

    /**
     * prints the last errorinfo provided by PDO
     * @throws APIException
     */
    private static function error () {

        print_r( self::getConnection()->errorInfo() );
        throw new APIException( "Fehler beim Statement" );
    }
}

Mysql::connect();