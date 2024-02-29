<?php

class DefaultController extends Base
{
   public $statusCode    = 405;
   public $statusMessage = 'Method Not Defined';
   public $headers       = array();
   public $content       = array();
   public $main          = null;
   public $apicore       = null;
   public $ready         = true;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug);

      if (!$main || !$main->obj('apicore')) {
         $this->notReady(null,null,'Cannot locate main libraries');
      }

      if ($this->ready) {
         $this->main    = $main;
         $this->apicore = $main->obj('apicore');
      }
   }

   public function notReady($code = null, $message = null, $error = null)
   {
      $this->statusCode = $code ?: 500; 
      $this->statusMessage = $message ?: 'Server Error'; 
      $this->setError($error ?: 'An error occurred'); 

      $this->ready = false;

      $this->debug(5,sprintf("Controller signalling not ready: code(%s) status(%s) error(%s)",$this->statusCode,$this->statusMessage,$this->content['error']));

      return true;
   }

   public function setError($contentError)
   {
      $this->content = array('error' => $contentError);

      return true;
   }
}
?>
