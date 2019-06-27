<?php

namespace Mephiztopheles\webf\Model;

use Exception;
use JsonSerializable;
use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\QueryBuilder;
use Mephiztopheles\webf\Database\relation\ManyToManyRelation;
use Mephiztopheles\webf\Database\relation\OneToManyRelation;
use Mephiztopheles\webf\Database\relation\OneToOneRelation;
use Mephiztopheles\webf\Database\relation\Relation;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Routing\APIException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use stdClass;

const lazyLoad = [];

abstract class Model implements JsonSerializable {

    public $id;

    protected static $transients = [];
    protected static $dates = [];

    public function __construct( $data = null ) {

        if ( isset( $data ) ) {

            try {

                $this->fill( $data );

            } catch ( Exception $e ) {
                App::error( $e->getMessage() );
            }
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function __get( string $name ) {

        $method = "get" . ucfirst( $name );
        if ( method_exists( $this, $method ) ) {

            $reflection = new ReflectionMethod( $this, $method );
            if ( !$reflection->isPublic() )
                throw new RuntimeException( "The $method method is not public." );

            if ( !isset( $this->$name ) ) {

                $this->$name = $reflection->invoke( $this );
                if ( $this->$name instanceof Relation )
                    $this->$name = $this->$name->get();
            }
        }

        if ( property_exists( $this, $name ) ) {

            $reflectedProperty = new ReflectionProperty( $this, $name );
            $reflectedProperty->setAccessible( true );

            $value = $reflectedProperty->getValue( $this );
        } else {

            $value = null;
            $this->$name = $value;
        }

        return $value;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws ReflectionException
     */
    public function __set( string $name, $value ): void {

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

    public static function getTable() {
        return self::toSnakeCase( self::className() );
    }

    public static function className() {

        $path = explode( '\\', get_called_class() );
        return array_pop( $path );
    }

    /**
     * @throws APIException
     * @throws IllegalStateException
     */
    public function save() {

        $class = get_called_class();

        $qb = new QueryBuilder();

        $qb->table( $class::getTable() );
        if ( isset( $this->id ) )
            $qb->update( $this );
        else
            $qb->insert( $this );

        if ( !isset( $this->id ) )
            $this->id = App::getConnection()->lastInsertId();
    }

    /**
     * @param int $id
     *
     * @return null
     * @throws APIException
     * @throws IllegalStateException
     */
    public static function get( int $id ) {

        $class = get_called_class();
        $parent = get_parent_class( $class );

        $table = $class::getTable();

        $qb = new QueryBuilder();
        $qb->table( $table )->where( "id", $id );

        if ( $parent != self::class && $table != self::getTable() )
            $qb->where( "class", $class );

        $data = $qb->get();

        if ( !isset( $data ) )
            return null;

        $instance = new $class( $data );
        $instance->id = $id;

        return $instance;
    }

    public static function find() {

        $class = get_called_class();
        $parent = get_parent_class( $class );

        $table = $class::getTable();

        $qb = new QueryBuilder();
        $qb->table( $table );

        if ( $parent != self::class && $table != self::getTable() )
            $qb->where( "class", $class );

        $qb->class( $class );

        return $qb;
    }

    /**
     * @param string $class
     * @param string $foreignKey
     * @param string $privateKey
     *
     * @return mixed
     */
    protected final function hasOne( string $class, string $foreignKey = null, string $privateKey = "id" ) {

        if ( $foreignKey == null )
            $foreignKey = self::className() . "_id";

        $foreignKey = self::toSnakeCase( $foreignKey );

        return new OneToOneRelation( $this, $class, call_user_func( [ $class, "getTable" ] ), $foreignKey, $privateKey );
    }

    /**
     * @param string      $class
     * @param string|null $foreignKey
     * @param string      $privateKey
     *
     * @return mixed
     */
    protected final function hasMany( string $class, string $foreignKey = null, string $privateKey = "id" ) {

        if ( $foreignKey == null )
            $foreignKey = self::className() . "Id";

        $foreignKey = self::toSnakeCase( $foreignKey );
        $privateKey = self::toSnakeCase( $privateKey );
        return new OneToManyRelation( $this, $class, call_user_func( [ $class, "getTable" ] ), $foreignKey, $privateKey );
    }

    protected final function belongsToMany( string $class, string $table = null, string $foreignKey = null, string $privateKey = null ) {

        $foreignTable = call_user_func( [ $class, "getTable" ] );
        $privateTable = $this->getTable();

        if ( $foreignKey == null )
            $foreignKey = $foreignTable . "Id";

        $foreignKey = self::toSnakeCase( $foreignKey );

        if ( $privateKey == null )
            $privateKey = $privateTable . "Id";

        $privateKey = self::toSnakeCase( $privateKey );

        if ( $table == null ) {

            $names = [ $foreignTable, $privateTable ];
            sort( $names );
            $table = $names[ 0 ] . "_" . $names[ 1 ];
        }

        return new ManyToManyRelation( $this, $class, $table, $foreignKey, $privateKey );
    }

    /**
     * @param array $attributes
     *
     * @throws Exception
     */
    protected function fill( $attributes ) {

        foreach ( $this->getProperties() as $k ) {

            $snakeCase = self::toSnakeCase( $k );

            $value = null;
            if ( isset( $attributes->$snakeCase ) )
                $value = $attributes->$snakeCase;

            $class = get_called_class();

            $dates = $class::$dates;
            if ( !empty( $dates ) )
                if ( in_array( $k, $dates ) )
                    $value = App::toDateTime( $value );

            $this->$k = $value;
        }
    }

    protected function serialize() {

        $data = new stdClass();

        $values = $this->getProperties( true );

        foreach ( $values as $k => $v ) {

            $data->$k = $v;

            $class = get_called_class();

            $dates = $class::$dates;
            if ( !empty( $dates ) )
                if ( in_array( $k, $dates ) )
                    $data->$k = App::toTimestamp( $v );
        }

        return $data;
    }

    private function getProperties( bool $values = false ): array {

        $properties = [];

        try {

            $reflection = new ReflectionClass( $this );

            $classProperties = $reflection->getProperties();
            $static = $reflection->getProperties( ReflectionProperty::IS_STATIC );
            $classProperties = array_diff( $classProperties, $static );

            foreach ( $classProperties as $property ) {

                $key = $property->getName();

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

        return $properties;
    }

    private static function toSnakeCase( $input ) {

        preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]*)!', $input, $matches );
        $ret = $matches[ 0 ];

        foreach ( $ret as &$match )
            $match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );

        return implode( '_', $ret );
    }


    public function jsonSerialize() {

        $data = $this->serialize();

        return get_object_vars( $data );
    }
}