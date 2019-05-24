<?php


use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Controller\Controller;
use Mephiztopheles\webf\Database\Connection;
use Mephiztopheles\webf\Database\Statement;
use Mephiztopheles\webf\Model\Model;
use Mephiztopheles\webf\Routing\Response;
use PHPUnit\Framework\TestCase;

class Person extends Model {

    public  $firstName;
    private $name;
}

class PersonController extends Controller {

    protected $modelClass = Person::class;
}

class ControllerTest extends TestCase {

    /**
     * @runInSeparateProcess
     * @throws ReflectionException
     */
    function testGet () {

        $controller = new PersonController();
        $stub       = $this->createMock( Connection::class );
        $query      = $this->createMock( Statement::class );

        $data = [ "id" => 1 ];

        $stub->method( 'createQuery' )->willReturn( $query );
        $query->expects( $this->at( 1 ) )->method( 'get' )->willReturn( $data );

        App::setConnection( $stub );
        $person     = new Person();
        $person->id = 1;
        $this->expectOutputString( "{\"firstName\":null,\"id\":1}" );
        $controller->get( new Response(), 1 )->send();
    }
}