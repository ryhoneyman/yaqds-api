<?php

class DNSController extends DefaultController
{
   protected $dnsModel;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);
      $this->debug(5,'DNSController class instantiated');

      // The DNS model provides all the DNS data and methods to retrieve it; connect it and bring it online
      $this->dnsModel = new DNSModel($debug,$libs); 
   }

   public function query($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $query = $filterData['query'];
      $type  = strtoupper($parameters['type']);

      $dnsData = $this->dnsModel->query($query);

      if ($type) { $dnsData = $dnsData[$type]; }

      $this->content['query']   = $query;
      $this->content['results'] = $dnsData;

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }
}
?>
