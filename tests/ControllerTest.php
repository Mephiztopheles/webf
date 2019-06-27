<?php


use Mephiztopheles\webf\Controller\Controller;
use Mephiztopheles\webf\Routing\Response;
use Mephiztopheles\webf\test\DBTest;
use Mephiztopheles\webf\test\Model\Person;

class PersonController extends Controller {

    protected $modelClass = Person::class;
}

class ControllerTest extends DBTest {

    protected function setUp(): void {

        parent::setUp();
        $this->connection->createQuery( "CREATE TABLE person(id INTEGER PRIMARY KEY AUTOINCREMENT, person_id INTEGER, name varchar(255), first_name varchar(255), birthday TIMESTAMP)" )->execute();
    }

    /**
     * @runInSeparateProcess
     */
    function testGet() {

        $controller = new PersonController();

        $date = new DateTime( "now" );
        $data = new stdClass();

        $data->birthday = $date->getTimestamp();
        $person = new Person( $data );

        $person->save();

        $this->expectOutputString( "{\"firstName\":null,\"name\":null,\"birthday\":" . $date->getTimestamp() . ",\"id\":1}" );
        $controller->get( new Response(), 1 )->send();
    }
}