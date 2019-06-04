<?php

namespace Mephiztopheles\webf\test;

use Mephiztopheles\webf\Model\Role;
use Mephiztopheles\webf\Model\User;
use Mephiztopheles\webf\Routing\APIException;


class UserTest extends DBTest {

    /**
     * @throws APIException
     */
    public function testInsert () {

        $user     = new User();
        $user->id = 1;
        $role     = new Role();

        $role->id = 2;

        $this->connection->createQuery( "CREATE TABLE role_user(role_id int(11) NOT NULL , user_id int(11) NOT NULL)" )->execute();

        $user->getRoles()->add( $role );
        $roles = $this->connection->createQuery( "SELECT * FROM role_user" )->list();

        $this->assertEquals( 1, count( $roles ) );
        $this->assertEquals( 1, $roles[ 0 ]->user_id );
        $this->assertEquals( 2, $roles[ 0 ]->role_id );
    }
}