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

   public function getSpellById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $spellId   = $filterData['id'];
      $spell     = $this->spellModel->getSpellById($spellId);
      $spellData = $spell->data;


      if ($spellData === false) { return $this->standardError($this->spellModel->error); }
      if (!$spellData)          { return $this->standardError("Spell does not exist"); }

      $spellData['_is_bard_song'] = $spell->isBardSong();
      $spellData['_description']  = $this->spellModel->createSpellDescription($spell);

      return $this->standardOk($spellData);
   }

   public function getSpellEffectById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $spellId = $filterData['id'];

      $spellEffectInfo = $this->spellModel->getSpellEffectById($spellId);

      return $this->standardOk($spellEffectInfo);
   }
   
   public function searchSpells($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $searchName = $parameters['name'];
      $searchLike = $parameters['like'];
      $searchMax  = $parameters['max'];

      $spellData = $this->spellModel->searchByName($searchName,$searchLike,$searchMax);

      if ($spellData === false) { return $this->standardError($this->spellModel->error); }
      if (!$spellData)          { return $this->standardNotFound("No matches"); }

      return $this->standardOk($spellData);
   }

}
