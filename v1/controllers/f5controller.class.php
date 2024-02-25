<?php
class F5Controller extends DefaultController
{
   protected $f5Model;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'F5Controller class instantiated');

      // The F5 model provides all the F5 data and methods to retrieve it; connect it and bring it online
      $this->f5Model = new F5Model($debug,$libs); 
   }

   public function getLTMDataGroup($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $deviceIp   = $filterData['deviceip'];
     
      if (!$this->f5Model->addDevice($deviceIp)) { return true; }

      $LTMDataGroup = $this->f5Model->getLTMDataGroup($deviceIp, 'internal', '~Common~dg_epic-test');

      $this->content['deviceip']  = $deviceIp;
      $this->content['datagroup'] = $LTMDataGroup;

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }
   
   public function getLTMPool($request)
   {
      $this->debug(7,'method called');
      
      $parameters = $request->parameters;
      $filterData = $request->filterData;
      
      $deviceIp   = $filterData['deviceip'];
      
      if (!$this->f5Model->addDevice($deviceIp)) { return true; }
      
      $LTMPool = $this->f5Model->getLTMPool($deviceIp);
      
      $this->content['deviceip']  = $deviceIp;
      $this->content['pool']      = $LTMPool;
      
      $this->statusCode    = 200;
      $this->statusMessage = 'OK';
      
      return true;
   }  

   public function getLTMVirtualServer($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $deviceIp   = $filterData['deviceip'];
      
      if (!$this->f5Model->addDevice($deviceIp)) { return true; }

      $LTMVirtualServer = $this->f5Model->getLTMVirtualServer($deviceIp);

      $this->content['deviceip']      = $deviceIp;
      $this->content['virtualserver'] = $LTMVirtualServer;

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   } 
   
   public function updateSYSFileDataGroup($request) 
   {

      $this->debug(7,'method called');
 
      $filterData = $request->filterData;
      $body       = $request->parameters;

      $deviceIp   = $filterData['deviceip'];
       
      if (!$this->f5Model->addDevice($deviceIp)) { return true; }

      // dg file name and type are hardcoded as it will always be the same and to avoid errors
      // is this file used only by emerson.com??? what if others would like to use this?
      $data['deviceip'] = $deviceIp;
      $data['sourcePath'] = $body['sourcepath'];
      // Maybe we can put these values in the json and put reguired as hidden?
      $data['name']     = 'dgext_epic-test.txt';
      $data['type']     = 'string';
     
      $result = $this->f5Model->updateSYSFileDataGroup($deviceIp, $data);

     
      $this->content['data']     = $data; 
      $this->content['code']     = $result['code']; 
      $this->content['result']   = $result['result'];
      
      $this->statusCode    = 200;
      $this->statusMessage = 'OK';
  
      return true;
   }
}
?>
