<?php


use Mephiztopheles\webf\Controller\Controller;
use Mephiztopheles\webf\Model\Model;
use Mephiztopheles\webf\Routing\Response;
use Mephiztopheles\webf\test\DBTest;

class Person extends Model {

    public $firstName;
    public $name;
}

class PersonController extends Controller {

    protected $modelClass = Person::class;
}

class ControllerTest extends DBTest {

    protected function setUp (): void {

        parent::setUp();
        $this->connection->createQuery( "CREATE TABLE person(id INTEGER PRIMARY KEY AUTOINCREMENT, person_id INTEGER, name varchar(255), first_name varchar(255))" )->execute();
    }

    /**
     * @runInSeparateProcess
     */
    function testGet () {

        $controller = new PersonController();

        $person     = new Person();
        $person->save();

        $this->expectOutputString( "{\"firstName\":null,\"name\":null,\"id\":1}" );
        $controller->get( new Response(), 1 )->send();
    }
}