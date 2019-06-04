<?php /** @noinspection SqlNoDataSourceInspection */


namespace Mephiztopheles\webf\Database;


use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\clause\WhereClause;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Routing\APIException;
use PDOStatement;

class QueryBuilder {

    private $table;
    private $max;
    private $offset;
    private $where = [];

    public function table ( string $table ) {

        $this->table = $table;
        return $this;
    }

    public function where ( $name, $value, $operation = "=" ) {

        $this->where[] = new WhereClause( self::toSnakeCase( $name ), $value, $operation );
        return $this;
    }

    public function max ( $max ) {

        $this->max = $max;
        return $this;
    }

    public function offset ( $offset ) {

        $this->offset = $offset;
        return $this;
    }

    /**
     * @return array
     * @throws APIException
     * @throws IllegalStateException
     */
    public function list () {

        $query = "SELECT * FROM $this->table";

        $statement = new Statement( App::getConnection(), $query );
        $this->applyWhere( $statement, $query );

        return $statement->list();
    }

    /**
     * @return array
     * @throws APIException
     * @throws IllegalStateException
     */
    public function get () {

        $query = "SELECT * FROM $this->table";

        $parameters = $this->applyWhere( $query );
        $statement  = App::getConnection()->createQuery( $query );
        $statement->setParameters( $parameters );

        return $statement->get();
    }

    /**
     * @param $object
     * @return bool|PDOStatement
     * @throws APIException
     * @throws IllegalStateException
     */
    public function update ( $object ) {

        $query = "UPDATE $this->table SET";

        $values     = [];
        $parameters = [];

        foreach ( $object as $k => $v ) {

            $k        = self::toSnakeCase( $k );
            $values[] = " $k = ?";

            $parameters[] = $v;
        }

        $this->where( "id", $object->id );
        $query      .= join( ",", $values );
        $parameters = array_merge( $parameters, $this->applyWhere( $query ) );

        $statement = App::getConnection()->createQuery( $query );
        $statement->setParameters( $parameters );

        return $statement->execute();
    }

    /**
     * @param $object
     * @return Statement
     * @throws IllegalStateException
     * @throws APIException
     */
    public function insert ( $object ) {

        $query = "INSERT INTO $this->table";

        $statement = new Statement( App::getConnection(), $query );

        $fields = [];
        $values = [];

        $i = 0;
        foreach ( $object as $k => $v ) {

            $k        = self::toSnakeCase( $k );
            $fields[] = $k;
            $values[] = "?";

            $statement->setParameter( $i, $v );
            $i++;
        }

        $query .= "( " . join( ", ", $fields ) . " ) VALUES ( " . join( ", ", $values ) . " )";

        $statement->execute();

        return $statement;
    }

    private static function toSnakeCase ( $input ) {

        preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]*)!', $input, $matches );
        $ret = $matches[ 0 ];

        foreach ( $ret as &$match )
            $match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );

        return implode( '_', $ret );
    }

    private function applyWhere ( string &$query ) {

        $parameters = [];
        if ( !empty( $this->where ) ) {

            $query .= " WHERE";
            foreach ( $this->where as $clause ) {

                $query        .= " " . $clause->get();
                $parameters[] = $clause->getValue();
            }
        }

        return $parameters;
    }
}

