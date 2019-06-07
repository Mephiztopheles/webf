<?php

namespace Mephiztopheles\webf\Database\clause;

class WhereClause extends Clause {

    private $name;
    private $operation;
    private $value;

    public function __construct( $name, $value, $operation = "=" ) {

        $this->name = $name;
        $this->operation = $operation;
        $this->value = $value;
    }

    public function convert( &$parts, &$parameters ) {

        switch ( $this->operation ) {
            case "not in":
            case "in":
                $parts[] = "`$this->name` $this->operation ( ? )";
                break;

            default:
                $parts[] = "`$this->name` $this->operation ?";
                break;
        }

        if ( is_array( $this->value ) )
            $parameters[] = join( ", ", $this->value );
        else
            $parameters[] = $this->value;
    }
}