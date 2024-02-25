<?php
class EDCController extends DefaultController
{
   protected $f5Model;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'EDCController class instantiated');

      // The F5 model provides all the F5 data and methods to retrieve it; connect it and bring it online
      $this->f5Model = new F5Model($debug,$libs); 
   }
    
   public function updateSYSFileDataGroup($request) 
   {
      $this->debug(7,'method called');

      // Place here the list of the load balancer to restrict access to emerson.com to only the cloud lbs 
      $lbs = array(
          'CentralUS-A' => '192.168.38.87',
          'CentralUS-B' => '192.168.38.86',
          'EastUS2-A'   => '192.168.56.137',
          'EastUS2-B'   => '192.168.56.138',
          'lab' => '10.15.16.38');

      $filterData = $request->filterData;
      $body       = $request->parameters;

      $deviceIp   = $lbs[$filterData['environment']];
       
      if (!$this->f5Model->addDevice($deviceIp)) { return true; }

      // Dg file name and type are hardcoded as it will always be the same and to avoid errors
      $data['environment'] = $filterData['environment'];
      $data['deviceip']    = $deviceIp;
      $data['sourcePath']  = $body['sourcepath'];
      $data['name']        = 'dgext_epic-test.txt';
      $data['type']        = 'string';
     
      $result = $this->f5Model->updateSYSFileDataGroup($deviceIp, $data);
    
      $this->content['status']   = (preg_match('/^20\d$/',$result['code']))?"success":"error";
      $this->content['code']     = $result['code']; 
      $this->content['data']     = $result['result']; 
      
      if ($result['result']['message']) { $this->content['message'] = $result['result']['message']; } 
      
      $this->statusCode    = 200;
      $this->statusMessage = 'OK';
  
      return true;
   }
}
?>
