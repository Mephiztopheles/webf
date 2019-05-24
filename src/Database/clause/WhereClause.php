<?php

namespace Mephiztopheles\webf\Database\clause;

class WhereClause extends Clause {

    private $name;
    private $operation;
    private $value;

    public function __construct ( $name, $value, $operation = "=" ) {

        $this->name      = $name;
        $this->operation = $operation;
        $this->value     = $value;
    }

    public function get (): string {

        switch ( $this->operation ) {
            case "not in":
            case "in":
                return "$this->name $this->operation ( ? )";
        }

        return "$this->name $this->operation ?";
    }

    public function getValue () {

        if ( is_array( $this->value ) )
            return join( ", ", $this->value );

        return $this->value;
    }
}