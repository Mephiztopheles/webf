<?php

namespace Mephiztopheles\webf\Database\clause;

abstract class Clause {

    public abstract function get (): string;

    public abstract function getValue ();
}