<?php

class RouterModel extends DefaultModel
{
   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);

      $this->connectedDb = $this->connectDatabase();

      if (!$this->connectedDb) { 
         $this->error = 'Cannot connect to database'; 
      }
   }
   
/*
   public function getOneRouterByName
   public function getOneRouterByIP
   public function getMultipleRouter
   
   public function queryRouter($options = null);
*/

   public function getRouter($name)
   {
      $return = array();

      if (!$this->connectedDb) { return $return; }


/*
   $searchlist = array();
   if ($params['search']) {
      foreach (explode(';',$params['search']) as $keyvalue) {
         list($key,$value) = explode(':',$keyvalue);
         $searchlist[$key] = implode('|',explode(',',$value));
      }
   }

   $searchstring = "";
   if (!empty($searchlist)) {
      $regexplist = array();
      foreach ($searchlist as $key => $value) {
         $regexplist[] = "$key regexp '($value)'";
      }
      $searchstring = "and ".implode(' and ',$regexplist);
   }
*/

      $query  = "select ".
                "   r.*, sml.* ".
                "from router r ".
                "left join sm_location sml ".
                "on r.router_loc_code = sml.sm_location_code ".
                "where r.router_enabled = 1 ".
                (($name) ? "and r.router_hostname = '".$this->db->resource->escapeString($name)."'" : '');
      $return = $this->db->resource->query($query);

      return $return;
   }
}
?>
