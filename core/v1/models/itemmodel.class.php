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

   public function decodeItemType($typeId) 
   {
      $typeList = [
         '0' => '1H Slashing',
         '1' => '2H Slashing',
         '2' => 'Piercing',
      ];

      return $typeList[$typeId] ?: null;
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

   public function createItemDescription($itemData)
   {
      $return = [];

      if (!is_array($itemData)) { return $return; }
      
      $format = [
         "{{PROPERTIES}}",
         "{{SLOTS}} {{TYPE}} {{DAMAGE}}",
         "{{AC}}",
         "{{STR}} {{STA}} {{DEX}} {{AGI}} {{WIS}} {{INT}} {{CHA}} {{HP}} {{MANA}}",
         "{{MR}} {{FR}} {{CR}} {{DR}} {{PR}}",
         "{{WEIGHT}} {{SIZE}}",
         "{{EFFECT}}",
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
         'mr'   => 'MR',
         'fr'   => 'FR',
         'cr'   => 'CR',
         'dr'   => 'DR',
         'pr'   => 'PR',
      ];

      $values = [];

      $itemId   = $itemData['id'];
      $itemName = $itemData['name'];

      if ($itemData['magic'] == 1) { $values['propertyList'][] = 'MAGIC ITEM'; }
      if (preg_match('/^(?:.)?\#/',$itemData['lore'])) { $values['propertyList'][] = 'ARTIFACT'; } 
      if (preg_match('/^(?:.)?\*/',$itemData['lore'])) { $values['propertyList'][] = 'LORE'; } 
      if ($itemData['nodrop'] == 0) { $values['propertyList'][] = 'NO DROP'; }
      if ($itemData['norent'] == 0) { $values['propertyList'][] = 'NORENT'; }

      foreach ($statList as $dbKey => $formatKey) {
         $statValue = $itemData[$dbKey];
         $values[$formatKey] = ($statValue != 0) ? sprintf("%s: %s%s",$formatKey,(($statValue > 0) ? (($dbKey == 'ac') ? '' : '+') : '-'),$statValue) : ''; 
      }

      $values['slotList']  = $this->decodeItemSlots($itemData['slots'],true);
      $values['classList'] = $this->decodeItemClasses($itemData['classes']);
      $values['raceList']  = $this->decodeItemRaces($itemData['races']);
      $values['sizeName']  = $this->decodeItemSize($itemData['size'] ?: 0);
      $values['weightVal'] = $this->decodeItemWeight($itemData['weight'] ?: 0);

      $values['PROPERTIES'] = implode(', ',$values['propertyList']); 
      $values['SLOTS']      = ($values['slotList']) ? 'Slot: '.implode(', ',$values['slotList']) : '';
      $values['TYPE']       = $this->decodeItemType($itemData['itemtype']);
      $values['DAMAGE']     = ($itemData['damage'] && $itemData['delay']) ? sprintf("%d/%d",$itemData['damage'],$itemData['delay']) : '';
      $values['CLASSES']    = 'Class: '.(($values['classList']) ? implode(', ',$values['classList']) : 'NONE');
      $values['RACES']      = 'Race: '.(($values['raceList']) ? implode(', ',$values['raceList']) : 'NONE');   
      $values['WEIGHT']     = 'WT: '.$values['weightVal'];
      $values['SIZE']       = 'Size: '.$values['sizeName'];
      
      // No valid items currently have worn + click effect, so we can use whichever one we find, and not check for both
      $values['effect'] = $itemData['worneffect'] ?: $itemData['clickeffect'] ?: null;

      if ($values['effect']) {
         $spellInfo = $this->spellModel->getSpellById($values['effect']);
         $spellName = $spellInfo['name'];
      }
      
      $values['EFFECT'] = ($values['effect'] && $spellName) ? sprintf("Effect: %s",$spellName) : ''; 

      $return = [];

      foreach ($format as $line) {
         $lineValue = trim(preg_replace('/\s+/',' ',$this->main->replaceValues($line,$values)));
         if ($lineValue) { $return[] = $lineValue; }
      }
   
      return $return;
   }
}
