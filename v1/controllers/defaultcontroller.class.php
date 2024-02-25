<?php

class DefaultController extends Base
{
   public $statusCode    = 405;
   public $statusMessage = 'Method Not Defined';
   public $headers       = array();
   public $content       = array();
   public $libs          = array();
   public $apicore       = null;
   public $apiauth       = null;
   public $dbh           = null;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug);

      $this->libs = $libs; 

      if ($libs['apicore']) { $this->apicore = $libs['apicore']; }
      if ($libs['apiauth']) { $this->apiauth = $libs['apiauth']; }
      if ($libs['dbh'])     { $this->dbh     = $libs['dbh']; }
   }

   public function setError($contentError)
   {
      $this->content = array('error' => $contentError);

      return true;
   }

   public function connectDatabase($dbclass = null)
   {
      if (!is_null($dbclass)) { $this->dbh = $dbclass; }

      if (is_a($this->dbh,'MySQL')) {
         if ($this->databaseConnected()) { return true; }
         else { return $this->dbh->connect(); }
      }

      return false;
   }

   public function databaseConnected() { return $this->dbh->connected; }
}
?>
