<?php /** @noinspection SqlNoDataSourceInspection */


namespace Mephiztopheles\webf\Database;


use Closure;
use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\clause\AndClause;
use Mephiztopheles\webf\Database\clause\Clause;
use Mephiztopheles\webf\Database\clause\OrClause;
use Mephiztopheles\webf\Database\clause\WhereClause;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Model\Model;
use Mephiztopheles\webf\Routing\APIException;
use PDOStatement;

class QueryBuilder {

    private $table;
    private $max;
    private $offset;
    protected $where = [];
    /**
     * @var string
     */
    private $class;

    public function table( string $table ) {

        $this->table = $table;
        return $this;
    }

    public function where( $name, $value, $operation = "=" ) {

        $this->where[] = new WhereClause( self::toSnakeCase( $name ), $value, $operation );
        return $this;
    }

    /**
     * @param Closure $callback
     *
     * @return $this
     */
    public function and( Closure $callback ) {

        $this->where[] = new AndClause( $callback );
        return $this;
    }

    /**
     * @param Closure $callback
     *
     * @return $this
     */
    public function or( Closure $callback ) {

        $this->where[] = new OrClause( $callback );
        return $this;
    }

    public function max( $max ) {

        $this->max = $max;
        return $this;
    }

    public function offset( $offset ) {

        $this->offset = $offset;
        return $this;
    }

    /**
     * @return array
     * @throws APIException
     * @throws IllegalStateException
     */
    public function list() {

        $query = "SELECT * FROM $this->table";

        $parameters = $this->applyWhere( $query );
        $statement = App::getConnection()->createQuery( $query );

        $statement->setParameters( $parameters );
        $statement->class( $this->class );

        return $statement->list();
    }

    /**
     * @return array
     * @throws APIException
     * @throws IllegalStateException
     */
    public function get() {

        $query = "SELECT * FROM $this->table";

        $parameters = $this->applyWhere( $query );
        $statement = App::getConnection()->createQuery( $query );
        $statement->setParameters( $parameters );

        $statement->class( $this->class );

        return $statement->get();
    }

    /**
     * @param $object
     *
     * @return bool|PDOStatement
     * @throws APIException
     * @throws IllegalStateException
     */
    public function update( $object ) {

        $query = "UPDATE $this->table SET";

        $values = [];
        $parameters = [];

        foreach ( $object as $k => $v ) {

            $k = self::toSnakeCase( $k );
            $values[] = " $k = ?";

            $parameters[] = $v;
        }

        $this->where( "id", $object->id );
        $query .= join( ",", $values );
        $parameters = array_merge( $parameters, $this->applyWhere( $query ) );

        $statement = App::getConnection()->createQuery( $query );
        $statement->setParameters( $parameters );

        return $statement->execute();
    }

    /**
     * @param $object
     *
     * @return Statement
     * @throws IllegalStateException
     * @throws APIException
     */
    public function insert( $object ) {

        $parent = get_parent_class( $object );
        $query = "INSERT INTO $this->table";

        $fields = [];
        $values = [];
        $parameters = [];

        foreach ( $object as $k => $v ) {

            $k = self::toSnakeCase( $k );
            $fields[] = "`$k`";
            $values[] = "?";

            $parameters[] = $v;
        }

        if ( $parent != Model::class && $this->table == $parent::getTable() ) {

            $fields[] = "`class`";
            $values[] = "?";
            $parameters[] = get_class( $object );
        }

        $query .= "( " . join( ", ", $fields ) . " ) VALUES ( " . join( ", ", $values ) . " )";
        $statement = new Statement( App::getConnection(), $query );
        $statement->setParameters( $parameters );

        $statement->execute();

        return $statement;
    }

    private static function toSnakeCase( $input ) {

        preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]*)!', $input, $matches );
        $ret = $matches[ 0 ];

        foreach ( $ret as &$match )
            $match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );

        return implode( '_', $ret );
    }

    protected function applyWhere( string &$query ) {

        $parameters = [];
        if ( !empty( $this->where ) ) {

            $parts = [];
            /**
             * @var Clause $clause
             */
            foreach ( $this->where as $clause )
                $clause->convert( $parts, $parameters );

            $query .= " WHERE " . join( " AND ", $parts );
        }

        return $parameters;
    }

    public function class( $class ) {
        $this->class = $class;
    }
}

