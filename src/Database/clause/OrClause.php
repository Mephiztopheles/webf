<?php


namespace Mephiztopheles\webf\Database\clause;


use Closure;

class OrClause extends Clause {

    public function __construct( Closure $callback ) {
        call_user_func( $callback, $this );
    }

    public function convert( &$parts, &$parameters ) {

        if ( !empty( $this->where ) ) {

            $subParts = [];
            $subParameters = [];

            /**
             * @var Clause $clause
             */
            foreach ( $this->where as $clause )
                $clause->convert( $subParts, $subParameters );

            $parts[] = "(" . join( " OR ", $subParts ) . " )";

            array_splice ($parameters, count($parameters)-1, 0, $subParameters);

        }
    }
}