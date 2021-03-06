<?php


namespace Mephiztopheles\webf\Database;


use Exception;
use PDO;

class Connection {

    /**
     * @type PDO
     */
    private $pdo;

    /**
     * Connection constructor.
     *
     * @param        $host     string
     * @param        $port     integer
     * @param        $database string
     * @param        $user     string
     * @param        $password string
     * @param string $type
     */
    public function __construct( $host, $port, $database, $user, $password, $type = "MYSQL" ) {

        switch ( $type ) {
            case "MYSQL":
                $this->pdo = new PDO( "mysql:host=" . $host . ( isset( $port ) ? ":" . $port : "" ) . ";dbname=" . $database, $user, $password, [ PDO::MYSQL_ATTR_FOUND_ROWS => true ] );
                break;
            case "SQLLITE":
                $this->pdo = new PDO( "sqlite:" . $database );
        }

        $this->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
        $this->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, false );
        $this->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true );
        $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }

    /**
     * @param $attribute int
     * @param $value     mixed
     */
    public function setAttribute( $attribute, $value ): void {
        $this->pdo->setAttribute( $attribute, $value );
    }

    public function transactional( $function ) {

        $this->beginTransaction();

        try {

            call_user_func( $function );
            $this->commit();

        } catch ( Exception $e ) {
            $this->rollBack();
        }
    }

    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollBack(): void {
        $this->pdo->rollBack();
    }

    /**
     * retrieves last insert id
     *
     * @return int
     */
    public function lastInsertId(): int {
        return intval( $this->pdo->lastInsertId() );
    }

    public function pdo(): PDO {
        return $this->pdo;
    }

    public function createQuery( string $query ): Statement {
        return new Statement( $this, $query );
    }
}