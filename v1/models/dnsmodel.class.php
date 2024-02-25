<?php
include_once 'dns.class.php';

class DNSModel extends DefaultModel
{
   protected $dns = null;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
   
      $this->dns = new DNS($debug);
   }

   public function query($name)
   {
      $return = $this->dns->query($name);

      return $return;
   }

   public function forwardLookup($name)
   {
      $return = $this->dns->forwardLookup($name);
  
      return $return;
   } 

   public function reverseLookup($ip)
   {
      $return = $this->dns->reverseLookup($ip);

      return $return;
   }
}
?>
