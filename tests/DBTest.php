<?php


namespace Mephiztopheles\webf\test;


use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\Connection;
use PHPUnit\Framework\TestCase;

abstract class DBTest extends TestCase {


    private $db = "datenbank.sqt";

    /**
     * @var Connection
     */
    protected $connection;

    protected function setUp (): void {

        $this->connection = new Connection( null, null, $this->db, null, null, "SQLLITE" );
        App::setConnection( $this->connection );
    }

    protected function tearDown (): void {

        unset( $this->connection );
        App::setConnection( null );

        unlink( $this->db );
    }
}