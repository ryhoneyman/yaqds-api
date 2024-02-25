<?php

class ADController extends DefaultController
{
   protected $adModel;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'ADController class instantiated');

      // The AD model provides all the AD data and methods to retrieve it; connect it and bring it online
      $this->adModel = new ADModel($debug,$libs); 

      // If the model couldn't connect to AD via LDAP, we need to set an error
      if (!$this->adModel->connectedLdap) { 
         $this->statusCode    = 500;
         $this->statusMessage = 'Server Error';
         $this->setError($this->adModel->error);
      }
   }

   public function userAttrib($request)
   {
      $this->debug(7,'method called');

      if (!$this->adModel->connectedLdap) { return true; }

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $user   = $filterData['user'];
      $attrib = $filterData['attrib'];

      $userdata = $this->adModel->userDetails($user);

      $this->content['user']    = $user;
      $this->content['details'] = $userdata[$attrib];

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }

   public function userDetails($request)
   {
      $this->debug(7,'method called');

      if (!$this->adModel->connectedLdap) { return true; }

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $user = $filterData['user'];

      $this->content['user']    = $user;
      $this->content['details'] = $this->adModel->userDetails($user);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }

   public function userExists($request) 
   {
      $this->debug(7,'method called');

      if (!$this->adModel->connectedLdap) { return true; }

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $user = $filterData['user'];

      $this->content['user']   = $user;
      $this->content['exists'] = $this->adModel->userExists($user);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }

   public function userEmail($request)
   {
      $this->debug(7,'method called');

      if (!$this->adModel->connectedLdap) { return true; }

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $user = $filterData['user'];

      $this->content['user'] = $user;
      $this->content['mail'] = $this->adModel->getMailFromSamaccountname($user);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }

   public function userGroups($request)
   {
      $this->debug(7,'method called');

      if (!$this->adModel->connectedLdap) { return true; }

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $user = $filterData['user'];

      $this->content['user']   = $user;
      $this->content['groups'] = $this->adModel->getUserGroups($user);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }

   public function userDN($request)
   {
      $this->debug(7,'method called');

      if (!$this->adModel->connectedLdap) { return true; }

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $user = $filterData['user'];

      $this->content['user']   = $user;
      $this->content['userdn'] = $this->adModel->getUserdnFromSamaccountname($user);

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }
}
?>
