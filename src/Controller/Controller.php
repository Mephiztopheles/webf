<?php


namespace Mephiztopheles\webf\Controller;


use Mephiztopheles\webf\Routing\Response;

class Controller {

    protected $modelClass;

    public function get ( Response $response, int $id ): Response {
        return $response->json( call_user_func( [ $this->modelClass, "get" ], $id ) );
    }
}