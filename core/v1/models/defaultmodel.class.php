<?php

class DefaultModel extends Base
{
   public    $ready         = true;
   public    $error         = null;
   public    $main          = null;
   public    $apicore       = null;
   protected $needsDatabase = '';

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug);

      if (!$main || !$main->obj('apicore')) { 
         $this->error = 'Cannot locate main libraries';
         $this->ready = false; 
      }

      if ($this->ready) {
         $this->main    = $main;
         $this->apicore = $main->obj('apicore');

         if ($this->needsDatabase) {
            $this->main->attachDatabase($this->needsDatabase);

            if ($this->main->isDatabaseConnected($this->needsDatabase)) {
               $this->error = 'Model cannot connect to database';
               $this->ready = false;
            }
         }
      }
   }
}
