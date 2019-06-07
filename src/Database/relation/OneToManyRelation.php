<?php


namespace Mephiztopheles\webf\Database\relation;


use Exception;
use Mephiztopheles\webf\App\App;

class OneToManyRelation extends Relation {

    public function update() {
        return "UPDATE $this->table SET $this->foreignKey = ? WHERE id = ?";
    }

    public function get() {

        try {

            $statement = App::getConnection()->createQuery( "SELECT * FROM $this->table WHERE $this->foreignKey = ?" );

            $statement->setParameter( 0, $this->entity->id );
            $data = $statement->list();
            $results = [];

            foreach ( $data as $values ) {

                $instace = new $this->class( $values );
                $results[] = $instace;
            }

            return $results;

        } catch ( Exception $e ) {
            App::error( $e->getMessage() );
        }

        return [];
    }
}