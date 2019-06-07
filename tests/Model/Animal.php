<?php

namespace Mephiztopheles\webf\test\Model;

use Mephiztopheles\webf\Model\Model;

class Animal extends Model {

    public static function getTable() {
        return "animal";
    }
}