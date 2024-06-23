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

      if (is_array($result['data']['results'])) { $result['data']['results'] = array_change_key_case($result['data']['results']); }

      return $result;
   }

   public function getSpellEffectById($spellId)
   {
      $spellInfo    = $this->getSpellById($spellId);
      $spellResults = $spellInfo['data']['results'];

      if (!$spellResults) { return null; }

      $result = array();

      $keyMap = array(
         'effectid'           => 'id',
         'effect_base_value'  => 'base',
         'effect_limit_value' => 'limit',
         'max'                => 'max',
         'formula'            => 'formula'
      );

      foreach ($spellResults as $spellKey => $spellValue) {
         if (preg_match('/^(effectid|effect_base_value|effect_limit_value|max|formula)(\d+)$/i',$spellKey,$match)) {
            $spellEffectKey = strtolower($match[1]);
            $spellEffectPos = $match[2];

            if ($spellResults['effectid'.$spellEffectPos] == 254) { continue; }

            $result['raw'][$spellEffectPos][$keyMap[$spellEffectKey] ?: $spellEffectKey] = $spellValue; 
         }
      }

      return $result;
   }
}
