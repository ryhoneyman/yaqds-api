<?php

/**
 * ItemModel
 */
class ItemModel extends DefaultModel
{   
   protected $decodeModel = null;
   protected $spellModel  = null;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);

      $this->decodeModel = new DecodeModel($debug,$main);
      $this->spellModel  = new SpellModel($debug,$main);
   }
   
   /**
    * getAll
    *
    * @return mixed
    */
   public function getAll(): mixed
   {
      $database  = 'yaqds';
      $statement = "SELECT id, name FROM items";

      $result = $this->api->v1DataProviderBindQuery($database,$statement);

      if ($result === false) { $this->error = $this->api->error(); return false; }

      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }

   /**
    * getAll
    *
    * @return mixed
    */
   public function searchByName($name, $like = false, $limit = null): mixed
   {
      if (empty($name)) { return null; }

      if (is_null($limit)) { $limit = 50; }

      $database  = 'yaqds';
      $statement = "SELECT id, name FROM items where name ".(($like) ? "like ?" : " = ?")." LIMIT $limit";
 
      $result = $this->api->v1DataProviderBindQuery($database,$statement,'s',($like) ? ["%$name%"] : ["$name"]);
 
      if ($result === false) { $this->error = $this->api->error(); return false; }
 
      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }
   
   /**
    * getItemById
    *
    * @param  int $itemId
    * @return mixed
    */
   public function getItemById(int $itemId): mixed
   {
      $database  = 'yaqds';
      $statement = "SELECT * FROM items where id = ?";

      $result = $this->api->v1DataProviderBindQuery($database,$statement,'i',[$itemId],['single' => true]);

      $this->main->debug->writeFile('itemmodel.getitembyid.debug.log',json_encode([
         'statement' => $statement,
         'itemId'    => $itemId,
         'result'    => $result,
      ]),false);

      if ($result === false) { $this->error = $this->api->error(); return false; }

      if (!isset($result['data']['results'])) { return null; }

      $return = $result['data']['results'];

      if (is_array($return)) { $return = array_change_key_case($return); }
      
      return $return;
   }



   public function createItemDescription($itemData)
   {
      $return = [];

      if (!is_array($itemData)) { return $return; }
      
      $format = [
         "{{PROPERTIES}}",
         "{{SLOTS}}",
         "{{SKILL}} {{DELAY}}",
         "{{DAMAGE}} {{AC}}",
         "{{STR}} {{DEX}} {{STA}} {{CHA}} {{WIS}} {{INT}} {{AGI}} {{HP}} {{MANA}}",
         "{{HP OVERFLOW}} {{MANA OVERFLOW}}",
         "{{SV FIRE}} {{SV DISEASE}} {{SV COLD}} {{SV MAGIC}} {{SV POISON}}",
         "{{EFFECT}}",
         "{{WEIGHT}} {{SIZE}}",
         "{{CLASSES}}",
         "{{RACES}}",
      ];

      $statList = [
         'aagi' => 'AGI',
         'acha' => 'CHA',
         'adex' => 'DEX',
         'aint' => 'INT',
         'asta' => 'STA',
         'astr' => 'STR',
         'awis' => 'WIS',
         'hp'   => 'HP',
         'mana' => 'MANA',
         'ac'   => 'AC',
         'mr'   => 'SV MAGIC',
         'fr'   => 'SV FIRE',
         'cr'   => 'SV COLD',
         'dr'   => 'SV DISEASE',
         'pr'   => 'SV POISON',
      ];

      $values = [];

      $itemId   = $itemData['id'];
      $itemName = $itemData['name'];

      if ($itemData['magic'] == 1) { $values['propertyList'][] = 'MAGIC ITEM'; }
      if (preg_match('/^(?:.)?\#/',$itemData['lore'])) { $values['propertyList'][] = 'ARTIFACT'; } 
      if (preg_match('/^(?:.)?\*/',$itemData['lore'])) { $values['propertyList'][] = 'LORE ITEM'; } 
      if ($itemData['nodrop'] == 0) { $values['propertyList'][] = 'NO DROP'; }
      if ($itemData['norent'] == 0) { $values['propertyList'][] = 'NORENT'; }

      $values['attribCount'] = 0;
      
      foreach (['aagi','acha','adex','aint','asta','astr','awis'] as $attrib) {
         if ($itemData[$attrib] > 0) { $values['attribCount']++; }
      }
   
      foreach ($statList as $dbKey => $formatKey) {
         $statValue = $itemData[$dbKey];
         $values[$formatKey] = ($statValue != 0) ? sprintf("%s: %s%s",$formatKey,(($statValue > 0) ? (($dbKey == 'ac') ? '' : '+') : '-'),$statValue) : ''; 
      }

      if ($values['attribCount'] >= 6) {
         $values['HP OVERFLOW']   = $values['HP'];
         $values['MANA OVERFLOW'] = $values['MANA'];
         $values['HP'] = '';
         $values['MANA'] = '';
      }
      else {
         $values['HP OVERFLOW']   = '';
         $values['MANA OVERFLOW'] = '';
      }

      $values['effect']      = null;
      $values['slotList']    = $this->decodeModel->decodeItemSlots($itemData['slots'],true);
      $values['classList']   = $this->decodeModel->decodeItemClasses($itemData['classes']);
      $values['raceList']    = $this->decodeModel->decodeItemRaces($itemData['races']);
      $values['sizeName']    = $this->decodeModel->decodeItemSize($itemData['size'] ?: 0);
      $values['weightVal']   = $this->decodeModel->decodeItemWeight($itemData['weight'] ?: 0);
      $values['weaponSkill'] = $this->decodeModel->decodeWeaponSkill($itemData['itemtype']);

      $values['PROPERTIES'] = implode(' ',$values['propertyList'] ?? []); 
      $values['SLOTS']      = ($values['slotList']) ? 'Slot: '.implode(' ',$values['slotList'] ?? []) : '';
      $values['SKILL']      = ($values['weaponSkill']) ? sprintf("Skill: %s",$values['weaponSkill']) : '';
      $values['DAMAGE']     = ($itemData['damage']) ? sprintf("DMG: %d",$itemData['damage']) : '';
      $values['DELAY']     = ($itemData['delay']) ? sprintf("Atk Delay: %d",$itemData['delay']) : '';
      $values['CLASSES']    = 'Class: '.(($values['classList']) ? implode(' ',$values['classList'] ?? []) : 'NONE');
      $values['RACES']      = 'Race: '.(($values['raceList']) ? implode(' ',$values['raceList'] ?? []) : 'NONE');   
      $values['WEIGHT']     = 'WT: '.$values['weightVal'];
      $values['SIZE']       = 'Size: '.$values['sizeName'];
      
      // No valid items currently have worn + proc + click effect on PQ, so we search the list in this order and never use more than one for display
      if ($itemData['worneffect']) {
         $values['effect']      = $itemData['worneffect'];
         $values['effectLabel'] = '(Worn)';
      }
      else if ($itemData['proceffect']) {
         $values['effect']      = $itemData['proceffect'];
         $values['effectLabel'] = ($itemData['worntype'] == 2) ? '(Worn)' : '(Combat)';
      }
      else if ($itemData['clickeffect']) {
         $values['effect'] = $itemData['clickeffect'];

         $effectType = $this->decodeModel->decodeItemEffectType($itemData['clicktype']);
         $castTime   = $this->decodeModel->decodeCastTime($itemData['casttime']);

         if (preg_match('/^equipclick$/i',$effectType)) { 
            $values['effectLabel'] = sprintf("(Must Equip. Casting Time: %s)",($castTime > 0) ? $castTime : 'Instant');
         }
      }

      if ($values['effect'] > 0) {
         $spell     = $this->spellModel->getSpellById($values['effect']);
         $spellName = $spell->property('name');
      }

      $values['EFFECT'] = ($values['effect'] && $spellName) ? sprintf("Effect: %s %s",$spellName,$values['effectLabel']) : ''; 

      $return = [];

      foreach ($format as $line) {
         $lineValue = trim(preg_replace('/\s+/',' ',$this->main->replaceValues($line,$values)));
         if ($lineValue) { $return[] = $lineValue; }
      }
   
      return $return;
   }
}
