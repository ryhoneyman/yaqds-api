<?php

class SpellController extends DefaultController
{
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

   public function getItemById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $itemId = $filterData['id'];

      $itemData = $this->itemModel->getItemById($itemId);

      $this->main->debug->writeFile('itemcontroller.getitembyid.debug.log',json_encode([$itemId,$itemData]),false);

      if ($itemData === false) { return $this->standardError($this->itemModel->error); }
      if (!$itemData)          { return $this->standardError("Item does not exist"); }

      $itemData['_description'] = $this->itemModel->createItemDescription($itemData);

      $this->main->debug->writeFile('itemcontroller.getitembyid.debug.log',json_encode(["OK!",$itemData]),false);

      return $this->standardOk($itemData);
   }

   public function getSpellById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $spellId = $filterData['id'];

      $spellData = $this->spellModel->getSpellById($spellId);

      if ($spellData === false) { return $this->standardError($this->spellModel->error); }
      if (!$spellData)          { return $this->standardError("Item does not exist"); }

      $spellData['_description'] = $this->spellModel->createSpellDescription($spellData);

      return $this->standardOk($spellData);
   }

   public function getSpellEffectById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $spellId = $filterData['id'];

      $spellData = $this->spellModel->getSpellEffectById($spellId);

      return $this->standardOk($spellData);
   }
}
