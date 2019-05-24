<?php

namespace Mephiztopheles\webf\Model;

use DateTime;
use Exception;
use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Routing\APIException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

const lazyLoad = [];

abstract class Model {

    private static $protectedKeyWords = [ "dates", "lazyLoad" ];

    public $id;

    protected $dates = [];

    /**
     * @param string $name
     * @return mixed
     * @throws ReflectionException
     */
    public function __get ( string $name ) {

        $method = "get" . ucfirst( $name );
        if ( method_exists( $this, $method ) ) {

            $reflection = new ReflectionMethod( $this, $method );
            if ( !$reflection->isPublic() )
                throw new RuntimeException( "The $method method is not public." );

            if ( !isset( $this->$name ) )
                $this->$name = $reflection->invoke( $this );
        }

        if ( property_exists( $this, $name ) ) {

            $reflectedProperty = new ReflectionProperty( $this, $name );
            $reflectedProperty->setAccessible( true );

            $value = $reflectedProperty->getValue( $this );
        } else {

            $value       = null;
            $this->$name = $value;
        }


        return $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws ReflectionException
     */
    public function __set ( string $name, $value ): void {

        if ( !property_exists( $this, $name ) )
            $this->$name = null;

        $reflectedProperty = new ReflectionProperty( $this, $name );
        $reflectedProperty->setAccessible( true );

        $method = "set" . ucfirst( $name );
        if ( method_exists( $this, $method ) ) {

            $reflectionMethod = new ReflectionMethod( $this, $method );
            if ( !$reflectionMethod->isPublic() )
                throw new RuntimeException( "The $method method is not public." );

            $reflectionMethod->invoke( $this, $value );

        } else {
            $reflectedProperty->setValue( $this, $value );
        }
    }

    public static function getTable () {
        return self::toSnakeCase( self::className() );
    }

    public static function className () {

        $path = explode( '\\', get_called_class() );
        return array_pop( $path );
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

    /**
     * @return array
     */
    public function createQueryAndParameters () {

        $parameters = [];

        if ( isset( $this->id ) ) {

            $query  = "UPDATE " . $this->getTable() . " SET";
            $values = [];

            foreach ( $this->getProperties( true ) as $k => $v ) {

                if ( $k == "id" )
                    continue;

                $k        = self::toSnakeCase( $k );
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

            foreach ( $this->getProperties( true ) as $k => $v ) {

                if ( $k == "id" )
                    continue;

                $k = self::toSnakeCase( $k );

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
     * @return null
     * @throws APIException
     * @throws IllegalStateException
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
        $instance->fill( $data );

        return $instance;
    }

    /**
     * @param string $class
     * @param string $foreignKey
     * @param string $privateKey
     * @return mixed
     * @throws IllegalStateException
     * @throws APIException
     */
    protected final function hasOne ( string $class, string $foreignKey = null, string $privateKey = "id" ) {

        if ( $foreignKey == null )
            $foreignKey = self::toSnakeCase( self::className() );

        $foreignKey = self::toSnakeCase( $foreignKey );

        $statement = App::getConnection()->createQuery( "SELECT * FROM " . call_user_func( [ $class, "getTable" ] ) . " WHERE $foreignKey = ?" );
        $statement->setParameter( 0, $this->$privateKey );

        $data = $statement->get();

        if ( !isset( $data ) )
            return null;

        $data[ $foreignKey ] = $this;

        $instance = new $class();

        /** @noinspection PhpUndefinedMethodInspection */
        $instance->fill( $data );

        return $instance;
    }

    /**
     * @param string $class
     * @param string|null $foreignKey
     * @param string $privateKey
     * @return array
     * @throws APIException
     * @throws IllegalStateException
     */
    protected final function hasMany ( string $class, string $foreignKey = null, string $privateKey = "id" ) {

        if ( $foreignKey == null )
            $foreignKey = self::toSnakeCase( self::className() );

        $foreignKey = self::toSnakeCase( $foreignKey );

        $statement = App::getConnection()->createQuery( "SELECT * FROM " . call_user_func( [ $class, "getTable" ] ) . " WHERE $foreignKey = ?" );
        $statement->setParameter( 0, $this->$privateKey );

        $data = $statement->list();
        if ( !isset( $data ) )
            return [];

        $instances = [];
        foreach ( $data as $datum ) {

            $instance = new $class();
            $instance->fill( $datum );

            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * @param array $attributes
     * @throws Exception
     */
    protected function fill ( array $attributes ) {

        foreach ( $this->getProperties() as $k ) {

            if ( self::parameterIsKeyWord( $k ) )
                continue;

            $snakeCase = self::toSnakeCase( $k );
            if ( isset( $attributes[ $snakeCase ] ) )
                $this->$k = $attributes[ $snakeCase ];
            else
                $this->$k = null;

            if ( !empty( $this->dates ) ) {

                if ( in_array( $k, $this->dates ) )
                    $this->$k = new DateTime( $this->$k );
            }
        }
    }

    /**
     * @param int $id
     * @param array $list
     * @param string $table
     * @param string $pk
     * @param string $fk
     * @throws IllegalStateException
     * @throws Exception
     */
    private static function updateManyToMany ( int $id, array $list, string $table, string $pk, string $fk ) {

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

    private function getProperties ( bool $values = false ): array {

        $properties = [];

        try {

            $reflection = new ReflectionClass( $this );

            foreach ( $reflection->getProperties() as $property ) {

                $key = $property->getName();

                if ( self::parameterIsKeyWord( $key ) )
                    continue;

                if ( $values ) {

                    $property->setAccessible( true );
                    if ( isset( $this->$key ) )
                        $properties[ $key ] = $property->getValue( $this );
                    else
                        $properties[ $key ] = null;
                } else {
                    $properties[] = $key;
                }
            }

        } catch ( ReflectionException $e ) {
        }

        foreach ( $this as $key => $value ) {

            if ( self::parameterIsKeyWord( $key ) )
                continue;

            if ( $values )
                $properties[ $key ] = $value;
            else
                $properties[] = $key;
        }

        return $properties;
    }

    private static function toSnakeCase ( $input ) {

        preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]*)!', $input, $matches );
        $ret = $matches[ 0 ];

        foreach ( $ret as &$match )
            $match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );

        return implode( '_', $ret );
    }
}