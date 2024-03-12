<?php

/**
 * ApiModel
 */
class ApiModel extends DefaultModel
{   
   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);

      $main->loadDefinesFromDB('YAQDS_API_%');

      if (!$main->buildClass('api','MyAPI',null,'myapi.class.php')) { $this->notReady("API library error"); return; }

      if (!$main->obj('api')->baseUrl($main->getDefined('YAQDS_API_URL'))) { $this->notReady("API base URL not defined"); return; }

      if (!$main->obj('api')->v1Authenticate($main->getDefined('YAQDS_API_CLIENT_ID'),$main->getDefined('YAQDS_API_CLIENT_SECRET'))) { 
         $this->notReady("Could not authenticate to API");
         return;
      };
   }
}
