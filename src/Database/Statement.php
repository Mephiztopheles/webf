<?php


namespace Mephiztopheles\webf\Database;


use Mephiztopheles\webf\Routing\APIException;
use PDO;
use PDOStatement;
use stdClass;

class Statement {

    /**
     * @type Connection
     */
    private $connection;

    /**
     * @type string
     */
    private $query;

    private $parameters = [];

    public function __construct ( Connection $connection, string &$query ) {

        $this->query      = &$query;
        $this->connection = $connection;
    }

    public function __destruct () {
        $this->connection = null;
    }

    public function setParameter ( $name, $value ): Statement {

        $this->parameters[ $name ] = $value;
        return $this;
    }

    public function setParameters ( array $parameters ): Statement {

        $this->parameters = array_merge( [], $parameters );
        return $this;
    }

    /**
     * Helper function to return the Database statements result as array
     * @return array
     * @throws APIException
     */
    public function list (): array {

        $query = $this->execute();

        $results = [];

        while ( $row = $query->fetch( PDO::FETCH_OBJ, PDO::FETCH_ORI_NEXT ) )
            $results[] = $row;

        return $results;
    }

    /**
     * Helper function to return the Database statements first result as object
     * @return array|bool
     * @throws APIException
     */
    public function get () {

        $query = $this->execute();

        $result = $query->fetch( PDO::FETCH_OBJ );
        $query->closeCursor();
        if ( $result == false )
            return null;

        return $result;
    }

    /**
     * Helper function which allows to execute a statement without caring about a return value or decide to fetch an array or a single object
     * @return bool|PDOStatement
     * @throws APIException
     */
    public function execute (): PDOStatement {

        if ( empty( $this->parameters ) ) {

            $query = $this->connection->pdo()->query( $this->query );

        } else {

            $query = $this->prepare( $this->query );

            if ( $query->execute( $this->parameters ) == false )
                $this->error();
        }

        return $query;
    }


    /**
     * prepares the statement and checks if any error appeared.
     * @param $sql
     * @return bool|PDOStatement
     * @throws APIException
     */
    private function prepare ( $sql ): PDOStatement {

        $query = $this->connection->pdo()->prepare( $sql );
        if ( !$query )
            $this->error();

        return $query;
    }

    /**
     * prints the last errorinfo provided by PDO
     * @throws APIException
     */
    private function error () {

        print_r( $this->connection->pdo()->errorInfo() );
        throw new APIException( "Error while executing" );
    }

    public function addParameter ( $v ) {
        $this->parameters[] = $v;
    }

    public function toString (): string {
        return $this->query;
    }
}