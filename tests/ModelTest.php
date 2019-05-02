<?php
declare( strict_types = 1 );

use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Database\Connection;
use Mephiztopheles\webf\Database\Statement;
use Mephiztopheles\webf\Model\Model;
use PHPUnit\Framework\TestCase;

class Person extends Model {

    public $firstName;
    public $name;
}

class CamelCase extends Model {

}

final class ModelTest extends TestCase {

    public function testTableIsCorrect (): void {

        $this->assertEquals( 'person', Person::getTable() );
        $this->assertEquals( 'camel_case', ( new CamelCase() )->getTable() );
    }

    public function testQueryIsCorrect (): void {

        $p            = new Person();
        $p->firstName = "Peter";

        $this->assertEquals( "INSERT INTO person( firstName, name ) VALUES ( ?, ? )", $p->createQueryAndParameters()[ "query" ] );

        $p->id = 1;

        $this->assertEquals( "UPDATE person SET firstName = ?, name = ? WHERE id = ?", $p->createQueryAndParameters()[ "query" ] );

        $p->name = "Griffin";
        unset( $p->id );
        $this->assertEquals( "INSERT INTO person( firstName, name ) VALUES ( ?, ? )", $p->createQueryAndParameters()[ "query" ] );

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

        $stub  = $this->createMock( Connection::class );
        $query = $this->createMock( Statement::class );

        $personData            = new stdClass();
        $personData->id        = 1;
        $personData->firstName = null;
        $personData->name      = null;

        $camelCaseData     = new stdClass();
        $camelCaseData->id = 1;

        $stub->method( 'createQuery' )->willReturn( $query );

        $query->expects( $this->at( 1 ) )->method( 'get' )->willReturn( $personData );
        $query->expects( $this->at( 3 ) )->method( 'get' )->willReturn( $camelCaseData );
        $query->expects( $this->at( 5 ) )->method( 'get' )->willReturn( $camelCaseData );
        $query->expects( $this->at( 7 ) )->method( 'get' )->willReturn( null );

        App::setConnection( $stub );
        $this->assertEquals( $person, Person::get( 1 ) );

        $this->assertNotEquals( $person, CamelCase::get( 1 ) );
        $this->assertEquals( $camelCase, CamelCase::get( 1 ) );

        $this->assertNull( CamelCase::get( 1 ) );
    }
}