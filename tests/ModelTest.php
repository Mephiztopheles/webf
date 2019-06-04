<?php
declare( strict_types = 1 );

use Mephiztopheles\webf\Database\relation\OneToManyRelation;
use Mephiztopheles\webf\Database\relation\OneToOneRelation;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Model\Model;
use Mephiztopheles\webf\Routing\APIException;
use Mephiztopheles\webf\test\DBTest;

class Customer extends Model {

    public $firstName;
    public $name;

    /**
     * @return OneToOneRelation
     */
    public function getAddress (): OneToOneRelation {
        return $this->hasOne( '\Address' );
    }

    /**
     * @return OneToManyRelation
     */
    public function getParents (): OneToManyRelation {
        return $this->hasMany( '\Customer' );
    }
}

class Address extends Model {

    public $customer;
}

class CamelCase extends Model {

}

final class ModelTest extends DBTest {

    public function testTableIsCorrect (): void {

        $this->assertEquals( 'customer', Customer::getTable() );
        $this->assertEquals( 'camel_case', ( new CamelCase() )->getTable() );
    }

    protected function setUp (): void {

        parent::setUp();

        $this->connection->createQuery( "CREATE TABLE customer(id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, name varchar(255), first_name varchar(255))" )->execute();
        $this->connection->createQuery( "CREATE TABLE camel_case(id INTEGER PRIMARY KEY AUTOINCREMENT, a INTEGER , b INTEGER)" )->execute();
        $this->connection->createQuery( "CREATE TABLE address(id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER)" )->execute();
    }

    /**
     * @throws APIException
     * @throws IllegalStateException
     */
    public function testQueryIsCorrect (): void {

        $customer            = new Customer();
        $customer->firstName = "Peter";
        $this->assertEquals( "INSERT INTO customer( first_name, name ) VALUES ( ?, ? )", $customer->createQueryAndParameters()[ "query" ] );

        $customer->save();

        $data = $this->connection->createQuery( "SELECT * FROM customer WHERE id = 1" )->get();
        $this->assertEquals( $customer->firstName, $data->first_name );

        $customer->save();

        $this->assertEquals( "UPDATE customer SET first_name = ?, name = ? WHERE id = ?", $customer->createQueryAndParameters()[ "query" ] );

        $customer->name = "Griffin";
        $customer->save();

        $data = $this->connection->createQuery( "SELECT * FROM customer WHERE id = 1" )->get();
        $this->assertEquals( $customer->name, $data->name );

        $camelCase = new CamelCase();

        $camelCase->b = "b";
        $camelCase->a = "a";
        $camelCase->save();
        $data = $this->connection->createQuery( "SELECT * FROM camel_case WHERE id = 1" )->get();
        $this->assertEquals( $camelCase->a, $data->a );
    }

    /**
     * @throws Exception
     */
    public function testGet (): void {

        $customer     = new Customer();
        $customer->id = 1;

        $camelCase     = new CamelCase();
        $camelCase->id = 1;

        $this->connection->createQuery( "INSERT INTO customer(id) VALUES(1)" )->execute();
        $this->connection->createQuery( "INSERT INTO camel_case(id) VALUES(1)" )->execute();

        $this->assertEquals( $customer, Customer::get( 1 ) );

        $this->assertNotEquals( $customer, CamelCase::get( 1 ) );
        $this->assertEquals( $camelCase, CamelCase::get( 1 ) );

        $this->connection->createQuery( "DELETE FROM camel_case" )->execute();
        $this->assertNull( CamelCase::get( 1 ) );
    }

    public function testHasOne () {

        $customer     = new Customer;
        $customer->id = 1;

        $this->connection->createQuery( "INSERT INTO address(id, customer_id) VALUES(1,1)" )->execute();

        $this->assertEquals( $customer, $customer->address->customer );

        $customer          = new Customer();
        $customer->address = null;

        $this->assertNull( $customer->address );
        unset( $customer->address );
        $this->assertNull( $customer->address );
    }

    function testHasMany () {

        $customer     = new Customer;
        $customer->id = 1;

        $this->connection->createQuery( "INSERT INTO customer(id) VALUES(1)" )->execute();
        $this->connection->createQuery( "INSERT INTO customer(id, customer_id) VALUES(2,1)" )->execute();
        $this->connection->createQuery( "INSERT INTO customer(id, customer_id) VALUES(3,1)" )->execute();

        $parent1     = new Customer();
        $parent1->id = 2;
        $parent2     = new Customer();
        $parent2->id = 3;
        $parents     = [ $parent1, $parent2 ];


        $this->assertEquals( $parents, $customer->parents );
    }
}

