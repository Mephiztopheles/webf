<?php

namespace Mephiztopheles\webf\Model;

use Mephiztopheles\webf\App\App;
use DateTime;
use Exception;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Routing\APIException;

abstract class Model {

    private static $protectedKeyWords = [ "id", "dates" ];

    public $id;

    protected $dates = [];

    public static function getTable () {
        return self::toSnakeCase( get_called_class() );
    }

    /**
     * @throws APIException
     * @throws IllegalStateException
     */
    public function save () {

        $qap = $this->createQueryAndParameters();

        $statement = App::getConnection()->createQuery( $qap[ "query" ] );
        $statement->setParameters( $qap[ "parameters" ] );
        $statement->run();
    }

    public function createQueryAndParameters () {

        $parameters = [];

        if ( isset( $this->id ) ) {

            $query  = "UPDATE " . $this->getTable() . " SET";
            $values = [];

            foreach ( $this as $k => $v ) {

                if ( self::parameterIsKeyWord( $k ) )
                    continue;

                $values[] = " $k = ?";

                $parameters[] = $v;
            }


            $query .= join( ",", $values );
            $query .= " WHERE id = ?";

            $parameters[] = $this->id;

        } else {

            $query = "INSERT INTO " . $this->getTable();

            $fields = [];
            $values = [];

            foreach ( $this as $k => $v ) {

                if ( self::parameterIsKeyWord( $k ) )
                    continue;

                $fields[] = $k;
                $values[] = "?";

                $parameters[] = $v;
            }

            $query .= "( " . join( ", ", $fields ) . " ) VALUES ( " . join( ", ", $values ) . " )";
        }

        return [ "query" => $query, "parameters" => $parameters ];
    }

    /**
     * @param int $id
     * @return mixed
     * @throws Exception
     */
    public static function get ( int $id ) {

        $class = get_called_class();

        $statement = App::getConnection()->createQuery( "SELECT * FROM " . self::getTable() . " WHERE id = ?" );
        $statement->setParameter( 0, $id );
        $data = $statement->get();

        if ( !isset( $data ) )
            return null;

        $instance     = new $class();
        $instance->id = $id;

        foreach ( $instance as $k => $v ) {

            if ( self::parameterIsKeyWord( $k ) )
                continue;

            $instance->$k = $data->$k;

            if ( !empty( $instance->dates ) ) {

                if ( in_array( $k, $instance->dates ) )
                    $instance->$k = new DateTime( $instance->$k );
            }
        }

        return $instance;
    }

    /**
     * @param $id
     * @param $list
     * @param $table
     * @param $pk
     * @param $fk
     * @throws Exception
     */
    private static function updateManyToMany ( $id, $list, $table, $pk, $fk ) {

        $connection = App::getConnection();
        $connection->beginTransaction();

        try {

            if ( count( $list ) == 0 ) {

                $query = $connection->createQuery( "DELETE FROM $table where $pk = :id" );

                $query->setParameter( "id", $id );
                $query->run();

            } else {

                $ids = [];
                foreach ( $list as $entry ) {

                    if ( gettype( $entry->id ) === "integer" )
                        $ids[] = $entry->id;
                    else
                        throw new APIException( "Primary keys has to be Integer", 400 );
                }

                $idsString = implode( ",", $ids );

                $query = $connection->createQuery( "DELETE FROM $table where $fk not in ($idsString) and $pk = :id" );
                $query->setParameter( "id", $id );
                $query->run();

                foreach ( $list as $entry ) {

                    $query = $connection->createQuery( "SELECT * FROM $table WHERE $pk = :id AND $fk = :fk" );
                    $query->setParameter( "id", $id )->setParameter( "fk", $entry->id );
                    $result = $query->run();

                    if ( $result->rowCount() == 0 ) {

                        $query = $connection->createQuery( "INSERT INTO $table($pk, $fk) VALUES(:id,:fk)" );
                        $query->setParameter( "id", $id )->setParameter( "fk", $entry->id );
                        $query->run();
                    }
                }
            }
        } catch ( Exception $e ) {

            $connection->rollBack();
            throw $e;
        }
    }

    private static function parameterIsKeyWord ( string $name ): bool {
        return in_array( $name, self::$protectedKeyWords );
    }

    private static function toSnakeCase ( $input ) {

        preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches );
        $ret = $matches[ 0 ];

        foreach ( $ret as &$match )
            $match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );

        return implode( '_', $ret );
    }
}