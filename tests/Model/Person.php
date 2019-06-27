<?php


namespace Mephiztopheles\webf\test\Model;


use Mephiztopheles\webf\Model\Model;

class Person extends Model {

    public $firstName;
    public $name;

    public $birthday;

    static $dates = [ "birthday" ];
}