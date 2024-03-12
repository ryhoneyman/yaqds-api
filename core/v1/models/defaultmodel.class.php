<?php

class DefaultModel extends LWPLib\Base
{
   public    $ready         = true;
   public    $error         = null;
   public    $main          = null;
   public    $apicore       = null;
   protected $needsDatabase = '';

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug);

      if (!$main || !$main->obj('apicore')) { $this->notReady('Cannot locate main libraries'); return; }

      if ($this->ready) {
         $this->main    = $main;
         $this->apicore = $main->obj('apicore');

         if ($this->needsDatabase) {
            $this->main->attachDatabase($this->needsDatabase);

            if ($this->main->isDatabaseConnected($this->needsDatabase)) { $this->notReady('Model cannot connect to database'); return; }
         }
      }
   }

   public function notReady($error)
   {
      $this->debug(9,'Model signaliing not ready');

      $this->ready = false;
      $this->error = $error;

      return true;
   }
}
