<?php


namespace Mephiztopheles\webf\Database;


abstract class StatementBuilder {

    protected $table;

    public function table ( string $table ) {
        $this->table = $table;
    }

    public abstract function build (): Statement;
}