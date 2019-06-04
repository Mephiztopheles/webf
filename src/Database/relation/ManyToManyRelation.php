<?php


namespace Mephiztopheles\webf\Database\relation;


use Exception;
use Mephiztopheles\webf\App\App;
use Mephiztopheles\webf\Model\Model;

class ManyToManyRelation extends Relation {

    public function update () {
        // TODO: Implement update() method.
    }

    public function get () {

        try {

            $statement = App::getConnection()->createQuery( "SELECT * FROM $this->table WHERE $this->privateKey = ?" );

            $statement->setParameter( 0, $this->entity->id );
            $data    = $statement->list();
            $results = [];

            foreach ( $data as $values ) {

                $instace = new $this->class($values);
                $results[] = $instace;
            }

            return $results;

        } catch ( Exception $e ) {
            App::error( $e->getMessage() );
        }

        return [];
    }

    /**
     * @param Model $entity
     */
    public function add ( Model $entity ) {

        try {

            $statement = App::getConnection()->createQuery( "INSERT INTO $this->table ($this->foreignKey, $this->privateKey) VALUES(?, ?)" );

            $statement->setParameter( 0, $entity->id );
            $statement->setParameter( 1, $this->entity->id );
            $statement->execute();

        } catch ( Exception $e ) {
            App::error( $e->getMessage() );
        }
    }

    public function remove ( Model $entity ) {

    }
}