<?php

class SpellController extends DefaultController
{
   protected $spellModel = null;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);
      $this->debug(5,get_class($this).' class instantiated');

      $main->attachDatabase('yaqds');

      // The model provides all the data and methods to retrieve it; connect it and bring it online
      $this->spellModel = new SpellModel($debug,$main); 

      // If the model isn't ready we need to flag the controller as not ready and set status
      if (!$this->spellModel->ready) { $this->notReady($this->spellModel->error); }
   }

   public function getSpellList($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
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
