<?php

class SpellController extends DefaultController
{
   protected $apiModel   = null;
   protected $spellModel = null;
   
   /**
    * __construct
    *
    * @param  LWPLib\Debug|null $debug
    * @param  Main|null $main
    * @return void
    */
   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);
      $this->debug(5,get_class($this).' class instantiated');

      $this->apiModel = new ApiModel($debug,$main);

      if (!$this->apiModel->ready) { $this->notReady($this->apiModel->error); return; }

      // The model provides all the data and methods to retrieve it; connect it and bring it online
      $this->spellModel = new SpellModel($debug,$main); 

      // If the model isn't ready we need to flag the controller as not ready and set status
      if (!$this->spellModel->ready) { $this->notReady($this->spellModel->error); return; }
   }

   public function getSpellList($request)
   {
      $this->debug(7,'method called');

      $filterData = $request->filterData;

      $object = $filterData['object'];
      $attrib = $filterData['attrib'];

      $spellList = $this->spellModel->getAll();

      $content = array('data' => $spellList);

      return $this->standardOk($content);
   }

   public function getSpellById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $spellId = $filterData['id'];

      $spellData = $this->spellModel->getSpellById($spellId);

      $content = is_array($spellData) ? array_change_key_case($spellData) : $spellData;

      return $this->standardOk($content);
   }
}
