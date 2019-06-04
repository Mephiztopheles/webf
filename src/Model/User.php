<?php


namespace Mephiztopheles\webf\Model;


class User extends Model {

    private $firstName;
    private $lastName;
    private $userName;
    private $password;

    private $roles = [];

    public function getDisplayName () {
        return isset( $this->userName ) ? $this->userName : "$this->firstName $this->lastName";
    }

    public function getRoles () {
        return $this->belongsToMany( Role::class );
    }
}