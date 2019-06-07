<?php


namespace Mephiztopheles\webf\test;


use Mephiztopheles\webf\Database\QueryBuilder;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Routing\APIException;
use stdClass;

class QueryBuilderTest extends DBTest {


    /**
     * @throws APIException
     * @throws IllegalStateException
     */
    function testAND() {

        $this->connection->createQuery( "CREATE TABLE test(id INTEGER, name varchar, active tinyint)" )->execute();
        $this->connection->createQuery( "INSERT INTO test(id, name, active) VALUES(1,'hallo', 1)" )->execute();

        $qb = new QueryBuilder();

        $qb->table( "test" )->and( function ( QueryBuilder $query ) {
            return $query->where( "id", 1 )->where( "active", 1 );
        } );


        $result = new stdClass();
        $result->id = 1;
        $result->name = "hallo";
        $result->active = 1;
        $this->assertEquals( $result, $qb->get() );

        $qb = new QueryBuilder();

        $qb->table( "test" )->and( function ( QueryBuilder $query ) {
            return $query->where( "id", 1 )->where( "active", 1 )->where( "name", "test" );
        } );
        $this->assertNull( $qb->get() );
    }

    /**
     * @throws APIException
     * @throws IllegalStateException
     */
    function testOR() {

        $this->connection->createQuery( "CREATE TABLE test(id INTEGER, name varchar, active tinyint)" )->execute();
        $this->connection->createQuery( "INSERT INTO test(id, name, active) VALUES(1,'hallo', 1)" )->execute();

        $qb = new QueryBuilder();

        $qb->table( "test" )->or( function ( $query ) {
            /** @var QueryBuilder $query */
            return $query->where( "id", 1 )->where( "active", 0 );
        } );


        $result = new stdClass();
        $result->id = 1;
        $result->name = "hallo";
        $result->active = 1;
        $this->assertEquals( $result, $qb->get() );

        $qb = new QueryBuilder();

        $qb->table( "test" )->or( function ( QueryBuilder $query ) {
            return $query->where( "id", 1 )->where( "active", 1 )->where( "name", "test" );
        } );
        $this->assertEquals( $result, $qb->get() );
    }
}