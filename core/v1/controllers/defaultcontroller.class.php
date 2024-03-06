<?php

class DefaultController extends LWPLib\Base
{
   public $statusCode    = 405;
   public $statusMessage = 'Method Not Defined';
   public $headers       = array();
   public $content       = array();
   public $main          = null;
   public $apicore       = null;
   public $ready         = true;
   
   /**
    * __construct
    *
    * @param  LWPLib\Debug|null $debug
    * @param  Main|null $main
    * @return void
    */
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

   public function notReady($contentError = null, $code = null, $message = null)
   {
      $this->standardError($contentError,$code,$message);

      $this->ready = false;

      $this->debug(5,sprintf("Controller signalling not ready: code(%s) status(%s) error(%s)",$this->statusCode,$this->statusMessage,$this->content['error']));

      return true;
   }

   public function standardError($contentError = null, $code = null, $message = null)
   {
      $this->statusCode    = $code ?: 500; 
      $this->statusMessage = $message ?: 'Server Error';
      $this->content       = array('error' => $contentError ?: 'An error occurred');

      return true;
   }

   public function standardOk($content = null, $code = null, $message = null)
   {
      $this->statusCode    = $code ?: 200; 
      $this->statusMessage = $message ?: 'OK';
      $this->content       = $content;

      return true;
   }
}
