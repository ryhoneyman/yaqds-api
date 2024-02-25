<?php

class RouterController extends DefaultController
{
   protected $routerModel;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'RouterController class instantiated');

      // The router model provides all the router data and methods to retrieve it
      $this->routerModel = new routerModel($debug,$libs); 
   }

   public function getMethod($request) 
   {
      $this->debug(7,'method called');

      // If the model couldn't connect to the database, we need to throw an error
      if (!$this->routerModel->connectedDb) { 
         $this->statusCode    = 500;
         $this->statusMessage = 'Server Error';
         $this->setError($this->routerModel->error);
         return true; 
      }

      $routes   = $request->routes;
      $data     = $request->parameters;
      $router   = $routes[0];
      $sublevel = $routes[1];

      $this->content['router'] = $router;
      $this->content['data']   = $this->routerModel->getRouter($router);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }
}
?>
