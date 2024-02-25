<?php

class AuthController extends DefaultController
{
   private $defaultTokenLifetime = 3600;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'AuthController class instantiated');

      // This controller needs the database, so bring it online if it's not connected
      $this->connectDatabase();
   }

   public function authOAuth($request)
   {
      $this->debug(7,'method called');

      if (!preg_match('/^client_credentials$/i',$request->parameters['grant_type'])) {
         $this->statusCode       = 400;
         $this->statusMessage    = 'Bad Request';
         $this->content['error'] = 'unsupported_grant_type';
         return true;
      }

      // Decode the BASE64 credentials from the Authorization Basic header
      list($clientId,$clientSecret) = $this->apiauth->decodeBasicAuth($request->auth);

      if (!$this->databaseConnected()) {
         $this->statusCode       = 500;
         $this->statusMessage    = 'Server Error';
         $this->content['error'] = 'internal_error';
         return true;
      }

      $keydata = $this->apicore->getKeyData($clientId,$clientSecret);
 
      if (!$keydata['api_key_id']) {
         $this->statusCode       = 401;
         $this->statusMessage    = 'Unauthorized';
         $this->content['error'] = 'invalid_client';
         return true;
      }

      $keydata['apitoken'] = $this->apiauth->generateToken($keydata['api_key_id']);

      $request->keyId = $keydata['api_key_id'];
      $request->token = $keydata['apitoken'];

      $this->debug(9,"new token: ".$keydata['apitoken']);

      $success = $this->apicore->updateToken($keydata,$this->defaultTokenLifetime);

      if (!$success) {
         $this->statusCode       = 500;
         $this->statusMessage    = 'Server Error';
         $this->content['error'] = 'internal_error';
         return true;
      }

      $this->statusCode              = 200;
      $this->statusMessage           = 'OK';
      $this->headers                 = array('Cache-Control: no-store','Pragma: no-cache');
      $this->content['access_token'] = $keydata['apitoken'];
      $this->content['token_type']   = 'bearer';
      $this->content['expires_in']   = $this->defaultTokenLifetime;

      return true;
   }

   public function authToken($request)
   {
      $this->debug(7,'method called');

      if (!$this->databaseConnected()) {
         $this->statusCode       = 500;
         $this->statusMessage    = 'Database Error';
         $this->content['error'] = 'Database Connection Error';
         return true;
      }

      $keydata = $this->apicore->getKeyData('',$request->parameters['key']);

      if (!$keydata['api_key_id']) { 
         $this->statusCode       = 401;
         $this->statusMessage    = 'Unauthorized';
         $this->content['error'] = 'User Not Authorized';
         return true;
      }

      $keydata['apitoken'] = $this->apiauth->generateToken($keydata['api_key_id']);

      $request->keyId = $keydata['api_key_id']; 
      $request->token = $keydata['apitoken'];

      $this->debug(9,"new token: ".$keydata['apitoken']);

      $success = $this->apicore->updateToken($keydata,$this->defaultTokenLifetime);

      if (!$success) {
         $this->statusCode       = 500;
         $this->statusMessage    = 'Server Error';
         $this->content['error'] = 'Token Update Error';
         return true;
      }
      
      $this->statusCode       = 200;
      $this->statusMessage    = 'OK';
      $this->content['token'] = $keydata['apitoken'];

      return true;
   }

   public function authBasic($request)
   {
      $this->debug(7,'method called');

      if (!$this->databaseConnected()) {
         $this->statusCode       = 500;
         $this->statusMessage    = 'Database Error';
         $this->content['error'] = 'Database Connection Error';
         return true;
      }

      // Decode the BASE64 credentials from the Authorization Basic header
      list($clientUser,$clientPass) = $this->apiauth->decodeBasicAuth($request->auth);

      $keydata = $this->apicore->getKeyData($clientUser,$clientPass);

      if (!$keydata['api_key_id']) {
         $this->statusCode       = 401;
         $this->statusMessage    = 'Unauthorized';
         $this->content['error'] = 'User Not Authorized';
         return true;
      }

      $keydata['apitoken'] = $this->apiauth->generateToken($keydata['api_key_id']);

      $request->keyId = $keydata['api_key_id'];
      $request->token = $keydata['apitoken'];

      $this->debug(9,"new token: ".$keydata['apitoken']);

      $success = $this->apicore->updateToken($keydata,$this->defaultTokenLifetime);

      if (!$success) {
         $this->statusCode       = 500;
         $this->statusMessage    = 'Server Error';
         $this->content['error'] = 'Token Update Error';
         return true;
      }

      $this->statusCode       = 200;
      $this->statusMessage    = 'OK';
      $this->content['token'] = $keydata['apitoken'];

      return true;
   }
}
?>
