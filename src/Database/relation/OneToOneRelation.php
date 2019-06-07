<?php


namespace Mephiztopheles\webf\Database\relation;


use Exception;
use Mephiztopheles\webf\App\App;

class OneToOneRelation extends Relation {

    public function update() {

        try {

            $statement = App::getConnection()->createQuery( "UPDATE $this->table SET $this->foreignKey = ? WHERE id = ?" );

            $statement->setParameter( 0, $this->entity->id );
            $statement->execute();

        } catch ( Exception $e ) {
            App::error( $e->getMessage() );
        }
    }

    public function get() {

        try {

            $statement = App::getConnection()->createQuery( "SELECT * FROM $this->table WHERE $this->foreignKey = ?" );

            $statement->setParameter( 0, $this->entity->id );
            $data = $statement->get();

            if ( $data == null )
                return null;

            $key = preg_replace( "/_id$/", "", $this->foreignKey );

            $data->$key = $this->entity;

            return new $this->class( $data );

        } catch ( Exception $e ) {
            App::error( $e->getMessage() );
        }

        return null;
    }
}