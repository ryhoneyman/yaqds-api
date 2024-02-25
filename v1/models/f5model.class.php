<?php
include_once 'f5.class.php';

class F5Model extends DefaultModel
{
   protected $config = null;
  
   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);

      $this->connectDatabase();

      if (!$this->databaseConnected()) { 
          $this->error = 'Cannot connect to database for configuration details'; 
      }
   }

   public function addDevice($deviceIp)
   {
      if (!$this->databaseConnected()) { return false; }

      // Creds for lab
      //$this->config = $this->apicore->getConfig(array('f5.user','f5.pass'));
      $this->config = $this->apicore->getConfig(array('tacacs.user','tacacs.pass'));

      $this->f5 = new F5($debug);
 
      $this->f5->addDevice($deviceIp,$deviceIp,'ltm',$this->config['f5.user'],$this->config['f5.pass']);
     
      return true;
   }

   public function getLTMDataGroup($deviceIp, $dgType, $dgName)
   {
      $return = null;

      $return = $this->f5->getLTMDataGroup($deviceIp, $dgType, $dgName);

      return $return;
   } 

   public function getLTMVirtualServer($deviceIp)
   {
      $return = null;

      $return = $this->f5->getLTMVirtualServer($deviceIp);
  
      return $return;
   } 
   
   public function getLTMPool($deviceIp)
   {
      $return = null;

      $return = $this->f5->getLTMPool($deviceIp);
  
      return $return;
   }   

   public function updateSYSFileDataGroup($deviceIp, $data)
   {
      $return = null;

      $return = $this->f5->updateSYSFileDataGroup($deviceIp, $data['name'], $data);

      return $return;
   }
    
}

?>
