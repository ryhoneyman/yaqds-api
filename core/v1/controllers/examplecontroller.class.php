<?php

class ExampleController extends DefaultController
{
   protected $exampleModel = null;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);

      $this->debug(5,get_class($this).' class instantiated');

      // The model provides all the data and methods to retrieve it; connect it and bring it online
      $this->exampleModel = new ExampleModel($debug,$main); 

      $this->exampleModel->ready = false;
      $this->exampleModel->error = 'oops';

      // If the model isn't ready we need to flag the controller as not ready and set status
      if (!$this->exampleModel->ready) { $this->notReady(500,'Server Error',$this->exampleModel->error); }
   }

   public function exampleMethod1($request)
   {
      $this->debug(7,'method called');

      $this->content['response'] = 'test';
      $this->content['request']  = $request;

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;

   }

   public function exampleMethodLookupAttrib($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $object = $filterData['object'];
      $attrib = $filterData['attrib'];

      $objectData = $this->exampleModel->getObject($object);

      $this->content['object']  = $object;
      $this->content['details'] = $objectData[$attrib];

      $this->statusCode    = 200;
      $this->statusMessage = 'OK';

      return true;
   }
}
?>
