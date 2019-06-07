<?php


namespace Mephiztopheles\webf\Database\relation;


use Mephiztopheles\webf\Model\Model;

abstract class Relation {

    protected $entity;
    protected $class;
    protected $table;

    protected $foreignKey;
    protected $privateKey;

    public function __construct( Model $entity, string $class, string $table, string $foreignKey, string $privateKey ) {

        $this->entity = $entity;
        $this->class = $class;
        $this->table = $table;
        $this->foreignKey = $foreignKey;
        $this->privateKey = $privateKey;
    }

    public abstract function update();

    public abstract function get();
}