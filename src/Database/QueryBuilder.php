<?php /** @noinspection SqlNoDataSourceInspection */


namespace Mephiztopheles\webf\Database;


use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\clause\WhereClause;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Routing\APIException;

class QueryBuilder {

    private $table;
    private $where = [];
    private $max;
    private $offset;

    public function table ( string $table ) {

        $this->table = $table;
        return $this;
    }

    public function where ( $name, $value, $operation = null ) {

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
     * @return object|\stdClass
     * @throws APIException
     * @throws IllegalStateException
     */
    public function get () {

        $query = "SELECT * FROM $this->table";

        $statement = new Statement( App::getConnection(), $query );
        $this->applyWhere( $statement, $query );

        return $statement->get();
    }

    /**
     * @param $object
     * @return Statement
     * @throws IllegalStateException
     * @throws APIException
     */
    public function update ( $object ) {

        $query     = "UPDATE $this->table SET";
        $statement = new Statement( App::getConnection(), $query );

        $values = [];

        foreach ( $object as $k => $v ) {

            $k        = self::toSnakeCase( $k );
            $values[] = " $k = ?";

            $parameters[] = $v;
            $statement->addParameter( $v );
        }

        $query .= join( ",", $values );
        $this->applyWhere( $statement, $query );

        $statement->run();

        return $statement;
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

        $statement->run();

        return $statement;
    }

    private static function toSnakeCase ( $input ) {

        preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]*)!', $input, $matches );
        $ret = $matches[ 0 ];

        foreach ( $ret as &$match )
            $match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );

        return implode( '_', $ret );
    }

    private function applyWhere ( Statement $statement, string &$query ) {

        if ( !empty( $this->where ) ) {

            $query .= " WHERE";
            foreach ( $this->where as $clause ) {

                $query .= $clause->get();
                $statement->addParameter( $clause->getValue() );
            }
        }
    }
}

