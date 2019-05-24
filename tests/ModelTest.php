<?php
declare( strict_types = 1 );

use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\Connection;
use Mephiztopheles\webf\Database\Statement;
use Mephiztopheles\webf\Exception\IllegalStateException;
use Mephiztopheles\webf\Model\Model;
use Mephiztopheles\webf\Routing\APIException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class Person extends Model {

    public  $firstName;
    private $name;

    /**
     * @return Address
     * @throws IllegalStateException
     * @throws APIException
     */
    public function getAddress (): Address {
        return $this->hasOne( '\Address' );
    }

    /**
     * @return array
     * @throws APIException
     * @throws IllegalStateException
     */
    public function getParents () {
        return $this->hasMany( '\Person' );
    }
}

class Address extends Model {

    public $person;
}

class CamelCase extends Model {

}

final class ModelTest extends TestCase {

    /**
     * @var MockObject
     */
    private $stub;

    /**
     * @var MockObject
     */
    private $query;

    /**
     * @throws ReflectionException
     */
    protected function setUp (): void {

        $this->stub  = $this->createMock( Connection::class );
        $this->query = $this->createMock( Statement::class );
        $this->stub->method( 'createQuery' )->willReturn( $this->query );
        App::setConnection( $this->stub );
    }

    public function testTableIsCorrect (): void {

        $this->assertEquals( 'person', Person::getTable() );
        $this->assertEquals( 'camel_case', ( new CamelCase() )->getTable() );
    }

    public function testQueryIsCorrect (): void {

        $p            = new Person();
        $p->firstName = "Peter";

        $this->assertEquals( "INSERT INTO person( first_name, name ) VALUES ( ?, ? )", $p->createQueryAndParameters()[ "query" ] );

        $p->id = 1;

        $this->assertEquals( "UPDATE person SET first_name = ?, name = ? WHERE id = ?", $p->createQueryAndParameters()[ "query" ] );

        $p->name = "Griffin";
        unset( $p->id );
        $this->assertEquals( "INSERT INTO person( first_name, name ) VALUES ( ?, ? )", $p->createQueryAndParameters()[ "query" ] );

        $c = new CamelCase();

        $c->b = "b";
        $c->a = "a";
        $this->assertEquals( "INSERT INTO camel_case( b, a ) VALUES ( ?, ? )", $c->createQueryAndParameters()[ "query" ] );
    }

    /**
     * @throws Exception
     */
    public function testGet (): void {

        $person     = new Person();
        $person->id = 1;

        $camelCase     = new CamelCase();
        $camelCase->id = 1;

        $personData    = [ "id" => 1, "first_name" => null, "name" => null ];
        $camelCaseData = [ "id" => 1 ];

        $this->query->expects( $this->at( 1 ) )->method( 'get' )->willReturn( $personData );
        $this->query->expects( $this->at( 3 ) )->method( 'get' )->willReturn( $camelCaseData );
        $this->query->expects( $this->at( 5 ) )->method( 'get' )->willReturn( $camelCaseData );
        $this->query->expects( $this->at( 7 ) )->method( 'get' )->willReturn( null );

        $this->assertEquals( $person, Person::get( 1 ) );

        $this->assertNotEquals( $person, CamelCase::get( 1 ) );
        $this->assertEquals( $camelCase, CamelCase::get( 1 ) );

        $this->assertNull( CamelCase::get( 1 ) );
    }

    public function testHasOne () {

        $person = new Person;


        $data = [ "id" => 1 ];

        $this->query->expects( $this->at( 1 ) )->method( 'get' )->willReturn( $data );

        $this->assertEquals( $person, $person->address->person );

        $person          = new Person();
        $person->address = null;

        $this->assertNull( $person->address );
        $this->query->expects( $this->at( 1 ) )->method( 'get' )->willReturn( $data );
        unset( $person->address );
        $this->assertEquals( $person, $person->address->person );
    }

    function testHasMany () {

        $person = new Person;

        $data = [ [ "id" => 3 ], [ "id" => 2 ] ];


        $parent1     = new Person();
        $parent1->id = 3;
        $parent2     = new Person();
        $parent2->id = 2;
        $parents     = [ $parent1, $parent2 ];

        $this->query->expects( $this->at( 1 ) )->method( 'list' )->willReturn( $data );

        $this->assertEquals( $parents, $person->parents );
    }
}

