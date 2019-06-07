<?php


namespace Mephiztopheles\webf\test\Model;


use Mephiztopheles\webf\Database\relation\OneToManyRelation;
use Mephiztopheles\webf\Database\relation\OneToOneRelation;
use Mephiztopheles\webf\Model\Model;

class Customer extends Model {

    public $firstName;
    public $name;

    /**
     * @return OneToOneRelation
     */
    public function getAddress(): OneToOneRelation {
        return $this->hasOne( 'Mephiztopheles\webf\test\Model\Address' );
    }

    /**
     * @return OneToManyRelation
     */
    public function getParents(): OneToManyRelation {
        return $this->hasMany( 'Mephiztopheles\webf\test\Model\Customer' );
    }
}