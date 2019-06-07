<?php

namespace Mephiztopheles\webf\Database\clause;

use Mephiztopheles\webf\Database\QueryBuilder;

abstract class Clause extends QueryBuilder {

    public abstract function convert( &$parts, &$parameters );
}