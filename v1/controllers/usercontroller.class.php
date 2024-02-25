<?php

class EpicUserController extends DefaultController
{
   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'UserController class instantiated');

      $this->epicUserModel = new EpicUserModel($debug,$libs);
   }

   public function listUsers($request) 
   {
      $this->debug(7,'method called');

      $data = $request->parameters;

      $this->content['message'] = "here is a list of users";
      $this->content['data'] = $this->epicUserModel->getUsersLimit(5);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }

   public function getUser($request) 
   {
      $this->debug(7,'method called');

      $id   = $request->filterData['id'];
      $data = $request->parameters;

      $this->content['message'] = "here is the info for user $id";
      $this->content['data']    = array('detail1','detail2','detail3');

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }
 
   public function createUser($request) 
   {
      $data = $request->parameters;
       
      $this->statusCode    = 204;
      $this->statusMessage = 'OK';

      return true;
   }

   public function updateUser($request)
   {
      $data = $request->parameters;

      $this->statusCode    = 204;
      $this->statusMessage = 'OK';

      return true;
   }
}
?>
