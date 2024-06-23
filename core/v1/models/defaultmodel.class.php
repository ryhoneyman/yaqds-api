<?php

include_once 'request.class.php';

abstract class DefaultModel extends LWPLib\Base
{
   public    bool            $ready         = true;
   public    ?string         $error         = null;
   public    ?Main           $main          = null;
   public    ?APICore        $apicore       = null;
   public    ?DataController $data          = null;
   protected bool            $needsDatabase = false;
   protected mixed           $dbName        = null;

   public function __construct(?LWPLib\Debug $debug = null, ?Main $main = null)
   {
      parent::__construct($debug);

      if (!$main || !$main->obj('apicore')) { $this->notReady('Cannot locate main libraries'); return; }

      if ($this->ready) {
         $this->main    = $main;
         $this->apicore = $main->obj('apicore');

         if ($this->needsDatabase) {
            $this->main->attachDatabase($this->dbName);

            if (!$this->main->isDatabaseConnected($this->dbName)) { $this->notReady('Model cannot connect to database'); return; }    
         }

         if (!$this->main->buildClass('data','DataController',$main)) { $this->notReady("Data source library error"); return; }

         $this->data = $this->main->obj('data');
      }
   }

   public function dataRequest(string $database, string $statement, ?string $types = null, ?array $data = null): mixed 
   {
      $this->debug(7,'method called');

      $request = new LWPLib\Request($this->debug,['initBypass' => true]);

      $request->create(
         ['statement' => $statement, 'types' => $types, 'data' => $data],
         ['database'  => $database]
      );

      $result = $this->data->bindQueryDatabase($request);

      if ($result === false) { return false; }

      return $this->data->content;
   }

   public function notReady($error)
   {
      $this->debug(9,'Model signaling not ready');

      $this->ready = false;
      $this->error = $error;

      return true;
   }
}
