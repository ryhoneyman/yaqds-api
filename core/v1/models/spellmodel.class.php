<?php

/**
 * SpellModel
 */
class SpellModel extends DefaultModel
{   
   /**
    * @var MyAPI|null $api
    */
   protected $api = null;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);

      if (!$this->api = $main->obj('api')) { $this->notReady('API not available'); return; }
   }

   public function getAll()
   {
      $database  = 'yaqds';
      $statement = "SELECT id, name FROM spells_new";

      return $this->api->v1DataProviderBindQuery($database,$statement);
   }

   public function getSpellById($spellId)
   {
      $database  = 'yaqds';
      $statement = "SELECT * FROM spells_new where id = ?";
      $types     = 'i';
      $data      = array($spellId);

      $result = $this->api->v1DataProviderBindQuery($database,$statement,$types,$data,array('single' => true));

      return $result;
   }
}
