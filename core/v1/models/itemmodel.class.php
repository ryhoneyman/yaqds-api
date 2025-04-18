<?php

/**
 * ItemModel
 */
class ItemModel extends DefaultModel
{   
   protected $spellModel = null;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);

      $this->spellModel = new SpellModel($debug,$main);
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

      $result = $this->api->v1DataProviderBindQuery($database,$statement,'i',[$itemId],array('single' => true));

      $this->main->debug->writeFile('itemmodel.getitembyid.debug.log',json_encode([
         'statement' => $statement,
         'result'    => $result,
      ]));

      if ($result === false) { $this->error = $this->api->error(); return false; }

      if (is_array($result['data']['results'])) { $result['data']['results'] = array_change_key_case($result['data']['results']); }
      
      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }

   public function decodeItemClasses($classesBits)
   {
      $return = [];

      if      ($classesBits == 0)     { $return[] = 'NONE'; }
      else if ($classesBits == 32767) { $return[] = 'ALL';  }
      else {
         $classesList = [
            'WAR' => 1,
            'CLR' => 2,
            'PAL' => 4,
            'RNG' => 8,
            'SHD' => 16,
            'DRU' => 32,
            'MNK' => 64,
            'BRD' => 128,
            'ROG' => 256,
            'SHM' => 512,
            'NEC' => 1024,
            'WIZ' => 2048,
            'MAG' => 4096,
            'ENC' => 8192,
            'BST' => 16384,
         ];

         foreach ($classesList as $classAbbr => $classMask) {
            if ($classesBits & $classMask) { $return[] = $classAbbr; }
         } 
      }

      return $return;
   }

   public function decodeItemRaces($racesBits)
   {
      $return = [];

      if      ($racesBits == 0)     { $return[] = 'NONE'; }
      else if ($racesBits >= 16383) { $return[] = 'ALL';  }
      else {
         $racesList = [
            'HUM' => 1,
            'BAR' => 2,
            'ERU' => 4,
            'ELF' => 8,
            'HIE' => 16,
            'DEF' => 32,
            'HEF' => 64,
            'DWF' => 128,
            'TRL' => 256,
            'OGR' => 512,
            'HFL' => 1024,
            'GNM' => 2048,
            'IKS' => 4096,
            'VAH' => 8192,
            //'FRG' => 16384,
            //'DRK' => 32768,
         ];

         foreach ($racesList as $raceAbbr => $raceMask) {
            if ($racesBits & $raceMask) { $return[] = $raceAbbr; }
         } 
      }

      return $return;
   }

   public function decodeItemSlots($slotBits, $collapseSlots = false)
   {
      $return = [];

      $slotList = [
         'CHARM'       => 1,
         'EAR-L'       => 2,
         'HEAD'        => 4,
         'FACE'        => 8,
         'EAR-R'       => 16,
         'NECK'        => 32,
         'SHOULDER'    => 64,
         'ARMS'        => 128,
         'BACK'        => 256,
         'WRIST-L'     => 512,
         'WRIST-R'     => 1024,
         'RANGE'       => 2048,
         'HANDS'       => 4096,
         'PRIMARY'     => 8192,
         'SECONDARY'   => 16384,
         'RING-L'      => 32768,
         'RING-R'      => 65536,
         'CHEST'       => 131072,
         'LEGS'        => 262144,
         'FEET'        => 524288,
         'WAIST'       => 1048576,
         'POWERSOURCE' => 2097152,
         'AMMO'        => 4194304,
      ];

      foreach ($slotList as $slotName => $slotMask) {
         if ($slotBits & $slotMask) { $return[] = ($collapseSlots) ? preg_replace('/-.*$/','',$slotName): $slotName; }
      } 

      return array_unique($return);
   }

   public function decodeItemEffectType($effectTypeId) 
   {
      $effectTypeList = [
         0 => 'CombatProc',
         1 => 'Click',
         2 => 'Worn',
         3 => 'Expendable',
         4 => 'EquipClick',
         5 => 'Click2',
         6 => 'Focus',
         7 => 'Scroll',
         8 => 'Count',
      ];

      return $effectTypeList[$effectTypeId] ?: null;
   }

   public function decodeItemType($typeId, $useSkill = false) 
   {
      $typeList = [
         0  => ['1H Slashing','1H Slashing'],
         1  => ['2H Slashing','2H Slashing'],
         2  => ['Piercing','Piercing'],
         3  => ['1H Blunt','1H Blunt'],
         4  => ['2H Blunt','2H Blunt'],
         5  => ['Bow','Archery'],
         7  => ['Large Throwing','Throwing'],
         18 => ['Small Throwing','Throwing'],
         45 => ['Martial','Hand to Hand']
      ];

      $element = ($useSkill) ? 1 : 0;

      return $typeList[$typeId][$element] ?: null;
   }

   public function decodeWeaponSkill($typeId) 
   {
      // We only look at weapon skills
      if (!in_array($typeId,[1,2,3,4,5,7,18,34,45])) { return null; }

      return $this->decodeItemType($typeId,true);
   }

   public function decodeItemSize($sizeVal = 0) 
   {
      $sizeList = [
         0 => 'TINY',
         1 => 'SMALL',
         2 => 'MEDIUM',
         3 => 'LARGE',
         4 => 'GIANT',
         5 => 'GIGANTIC',
      ];

      return $sizeList[$sizeVal] ?: null;
   }

   public function decodeItemWeight($weightVal = 0) 
   {
      return sprintf("%1.1f",$weightVal / 10);
   }

   public function decodeCastTime($castTime = 0) 
   {
      return sprintf("%1.1f",$castTime / 1000);
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
      $values['slotList']    = $this->decodeItemSlots($itemData['slots'],true);
      $values['classList']   = $this->decodeItemClasses($itemData['classes']);
      $values['raceList']    = $this->decodeItemRaces($itemData['races']);
      $values['sizeName']    = $this->decodeItemSize($itemData['size'] ?: 0);
      $values['weightVal']   = $this->decodeItemWeight($itemData['weight'] ?: 0);
      $values['weaponSkill'] = $this->decodeWeaponSkill($itemData['itemtype']);

      $values['PROPERTIES'] = implode(' ',$values['propertyList']); 
      $values['SLOTS']      = ($values['slotList']) ? 'Slot: '.implode(' ',$values['slotList']) : '';
      $values['SKILL']      = ($values['weaponSkill']) ? sprintf("Skill: %s",$values['weaponSkill']) : '';
      $values['DAMAGE']     = ($itemData['damage']) ? sprintf("DMG: %d",$itemData['damage']) : '';
      $values['DELAY']     = ($itemData['delay']) ? sprintf("Atk Delay: %d",$itemData['delay']) : '';
      $values['CLASSES']    = 'Class: '.(($values['classList']) ? implode(' ',$values['classList']) : 'NONE');
      $values['RACES']      = 'Race: '.(($values['raceList']) ? implode(' ',$values['raceList']) : 'NONE');   
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

         $effectType = $this->decodeItemEffectType($itemData['clicktype']);
         $castTime   = $this->decodeCastTime($itemData['casttime']);

         if (preg_match('/^equipclick$/i',$effectType)) { 
            $values['effectLabel'] = sprintf("(Must Equip. Casting Time: %s)",($castTime > 0) ? $castTime : 'Instant');
         }
      }

      if ($values['effect']) {
         $spellInfo = $this->spellModel->getSpellById($values['effect']);
         $spellName = $spellInfo['name'];
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
