<?php

class DefaultModel extends Base
{
   public $error   = null;
   public $libs    = array();
   public $apicore = null;
   public $dbh     = null;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug);

      $this->libs = $libs;

      if ($libs['apicore']) { $this->apicore = $libs['apicore']; }
      if ($libs['dbh'])     { $this->dbh    = $libs['dbh']; }
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
