<?php

/**
 * DecodeModel
 */
class DecodeModel extends DefaultModel
{   
   public function __construct($debug = null, $main = null, $options = null)
   {
      parent::__construct($debug,$main,$options);
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

   public function decodeSplurtValues($effectFormula)
   {
      $splurtVals = [
         '107' => 1,
         '108' => 2,
         '120' => 5,
         '122' => 12,
      ];

      return (isset($splurtVals[$effectFormula])) ? $splurtVals[$effectFormula] : null;
   }

   public function decodeNpcSpecialAbilities($specialAbilities = '')
   {
      $abilityMap = [
         '1' => [
            'name' => 'Summon',
            'params' => [
               ['name' => 'enabled', 'default' => 1],
               ['name' => 'cooldown_ms', 'default' => 6000],
               ['name' => 'hp_ratio', 'default' => 97],
            ],
         ],
         '2' => [
            'name' => 'Enrage',
            'params' => [
               ['name' => 'hp_percent', 'default' => RULE_NPC_STARTENRAGEVALUE],  // 10
               ['name' => 'duration_ms', 'default' => 10000],
               ['name' => 'cooldown_ms', 'default' => 360000],
            ],
         ],
         '3' => [
            'name' => 'Rampage',
            'params' => [
               ['name' => 'percent_chance', 'default' => 20],
               ['name' => 'target_count', 'default' => RULE_COMBAT_MAXRAMPAGETARGET],  // 3
               ['name' => 'percent_damage', 'default' => 100],
               ['name' => 'flat_added_damage', 'default' => 0],
               ['name' => 'percent_ac_ignore', 'default' => 0],
               ['name' => 'flat_ac_ignore', 'default' => 0],
               ['name' => 'percent_natural_crit', 'default' => 100],
               ['name' => 'flat_added_crit', 'default' => 0],
            ],
         ],
         '4' => [
            'name' => 'Area Rampage',
         ],
         '5' => [
            'name' => 'Flurry',
         ],
         '6' => [
            'name' => 'Triple Attack',
         ],
         '7' => [
            'name' => 'Dual Wield',
         ],
         '8' => [
            'name' => 'Do Not Equip',
         ],
         '9' => [
            'name' => 'Bane Attack',
         ],
         '10' => [
            'name' => 'Magical Attack',
         ],
         '11' => [
            'name' => 'Ranged Attack',
         ],
         '12' => [
            'name' => 'Unslowable',
         ],
         '13' => [
            'name' => 'Unmezable',
         ],
         '14' => [
            'name' => 'Uncharmable',
         ],
         '15' => [
            'name' => 'Unstunable',
         ],
         '16' => [
            'name' => 'Unsnareable',
         ],
         '17' => [
            'name' => 'Unfearable',
         ],
         '18' => [
            'name' => 'Immune to Dispell',
         ],
         '19' => [
            'name' => 'Immune to Melee',
         ],
         '20' => [
            'name' => 'Immune to Magic',
         ],
         '21' => [
            'name' => 'Immune to Fleeing',
         ],
         '22' => [
            'name' => 'Immune to Non-Bane Damage',
         ],
         '23' => [
            'name' => 'Immune to Non-Magical Damage',
         ],
         '24' => [
            'name' => 'Will Not Aggro',
         ],
         '25' => [
            'name' => 'Immune to Aggro',
         ],
         '26' => [
            'name' => 'Resist Ranged Spells',
         ],
         '27' => [
            'name' => 'See Through Feign Death',
         ],
         '28' => [
            'name' => 'Immune to Taunt',
         ],
         '29' => [
            'name' => 'Tunnel Vision',
         ],
         '30' => [
            'name' => 'Does Not Buff/Heal Friends',
         ],
         '31' => [
            'name' => 'Unpacifiable',
         ],
         '32' => [
            'name' => 'Leashed',
         ],
         '33' => [
            'name' => 'Tethered',
         ],
         '34' => [
            'name' => 'Permaroot Flee',
         ],
         '35' => [
            'name' => 'No Harm from Players',
         ],
         '36' => [
            'name' => 'Always Flee',
         ],
         '37' => [
            'name' => 'Flee Percentage',
         ],
         '38' => [
            'name' => 'Allow Beneficial',
         ],
         '39' => [
            'name' => 'Disable Melee',
         ],      
         '40' => [
            'name' => 'Chase Distance',
         ],
         '41' => [
            'name' => 'Allow Tank',
         ],
         '42' => [
            'name' => 'Proximity Aggro',
         ],
         '43' => [
            'name' => 'Always Call for Help',
         ],
         '44' => [
            'name' => 'Use Warrior Skills',
         ],
         '45' => [
            'name' => 'Always Flee Low Con',
         ],
         '46' => [
            'name' => 'No Loitering',
         ],
         '47' => [
            'name' => 'Bad Faction Block Hand In',
         ],
         '48' => [
            'name' => 'PC Deathblow Corpse',
         ],
         '49' => [
            'name' => 'Corpse Camper',
         ],
         '50' => [
            'name' => 'Reverse Slow',
         ],
         '51' => [
            'name' => 'No Haste',
         ],
         '52' => [
            'name' => 'Immune to Disarm',
         ],
         '53' => [
            'name' => 'Immune to Riposte',
         ],
         '54' => [
            'name' => 'Proximity Aggro',
         ],
         '55' => [
            'name' => 'Max Special Attack',
         ],
      ];
   
      $abilityList = explode('^',$specialAbilities);
      $decodedList = [];
   
      foreach ($abilityList as $ability) {
         $abilityProperties = explode(',',$ability);
         $abilityType       = $abilityProperties[0];
         $abilityName       = $abilityMap[$abilityType]['name'] ?: 'Unknown';
         $abilityEnabled    = ($abilityProperties[0] == '0') ? false : true;
   
         if ($abilityEnabled) { $decodedList[] = $abilityName; }
      }
   
      if (!$decodedList) { return "None"; }
   
      return implode(', ',array_unique($decodedList));
   }
   
   public function decodeExpansionName($expansionNumber)
   {
      $expansionList = [
         '-1' => 'Any',
         '0'  => 'Classic',
         '1'  => 'Kunark',
         '2'  => 'Velious',
         '3'  => 'Luclin',
         '4'  => 'Planes',
         '5'  => 'PostPlanes',
         '6'  => 'Disabled',
         '7'  => 'Disabled',
         '99' => 'Any',
      ];
   
      $minorList = [
         ''  => '',
         '0' => '',
         '3' => 'II',
         '6' => 'III',
         '9' => 'IV',
      ];
   
      list($major,$minor) = explode('.',$expansionNumber);

      $majorExpansion = (isset($expansionList[$major])) ? $expansionList[$major] : 'Unknown';
      $minorExpansion = (isset($minorList[$minor])) ? $minorList[$minor] : '';
   
      return sprintf("%s%s",$majorExpansion,$minorExpansion);
   }
   
   public function decodeGridType($value, $type)
   {
      $type = strtolower($type);
   
      $expansionList = [
         'wander' => [
            '0' => 'Circular',
            '1' => 'Random10',
            '2' => 'Random',
            '3' => 'Patrol',
            '4' => 'OneWayRepop',
            '5' => 'Random5LoS',
            '6' => 'OneWayDepop',
            '7' => 'CenterPoint',
            '8' => 'RandomCenterPoint',
            '9' => 'RandomPath',
         ],
         'pause' => [
            '0' => 'RandomHalf',
            '1' => 'Full',
            '2' => 'Random',
         ],
      ];
   
      return ((isset($expansionList[$type][$value])) ? $expansionList[$type][$value] : null);
   }

   public function decodeSpellEffectList($spellData)
   {
      $spellEffectList = [];

      for ($effectPos = 1; $effectPos <= 12; $effectPos++) {
         $effectId      = $spellData['effectid'.$effectPos];
         $effectBase    = $spellData['effect_base_value'.$effectPos];
         $effectLimit   = $spellData['effect_limit_value'.$effectPos];
         $effectMax     = $spellData['max'.$effectPos];
         $effectFormula = $spellData['formula'.$effectPos];

         // Effect ID 10 (SE_CHA) when set to all zeroes is used as a placeholder/spacer
         if ($effectId == 254 || ($effectId == 10 && $effectBase == 0 && $effectMax == 0)) { continue; }

         $spellEffectList[$effectPos] = [
            'id'               => $effectId,
            'base'             => $effectBase,
            'limit'            => $effectLimit,
            'max'              => $effectMax,
            'formula'          => $effectFormula,
            'effectName'       => $this->decodeSpellEffect($effectId,'name'),
            'effectDisplay'    => $this->decodeSpellEffect($effectId,'display'),
            'effectExceptions' => $this->decodeSpellEffect($effectId,'exceptions'),
         ];
      }

      return $spellEffectList;
   }

   public function decodeSpellClasses($spellData)
   {
      $classList = [];

      if (!isset($spellData['classes1'])) { return $classList; }

      for ($classId = 1; $classId <= CLASS_MAX_COUNT; $classId++) {
         $classLevel = $spellData['classes'.$classId];
         if ($classLevel < SPELL_LEVEL_CANNOT_USE) { $classList[$this->decodeClass($classId,true)] = $classLevel; }
      }

      return $classList;
   }

   public function decodeSpellEffect($spellEffectId, $return = null)
   {
      $spellEffectList = [
         '0' => [
            'name'    => 'SE_CurrentHP',
            'label'   => 'Current HP',
            'display' => ['format' => 1, 'label' => 'Hitpoints', 'allowDuration' => true],
         ],
         '1' => [
            'name'    => 'SE_ArmorClass',
            'label'   => 'Armor Class',
            'display' => ['format' => 2, 'label' => 'AC'],
         ],
         '2' => [
            'name'    => 'SE_ATK',
            'label'   => 'ATK',
            'display' => ['format' => 1, 'label' => 'Attack'],
         ],
         '3' => [
            'name'    => 'SE_MovementSpeed',
            'label'   => 'Movement Speed',
            'display' => ['format' => 1, 'label' => 'Movement Speed', 'values' => ['effect:units' => 'raw^%']],
         ],
         '4' => [
            'name'    => 'SE_STR',
            'label'   => 'STR',
            'display' => ['format' => 1, 'label' => 'Strength'],
         ],
         '5' => [
            'name'    => 'SE_DEX',
            'label'   => 'DEX',
            'display' => ['format' => 1, 'label' => 'Dexerity'],
         ],
         '6' => [
            'name'    => 'SE_AGI',
            'label'   => 'AGI',
            'display' => ['format' => 1, 'label' => 'Agility'],
         ],
         '7' => [
            'name'    => 'SE_STA',
            'label'   => 'STA',
            'display' => ['format' => 1, 'label' => 'Stamina'],
         ],
         '8' => [
            'name'    => 'SE_INT',
            'label'   => 'INT',
            'display' => ['format' => 1, 'label' => 'Intelligence'],
         ],
         '9' => [
            'name'    => 'SE_WIS',
            'label'   => 'WIS',
            'display' => ['format' => 1, 'label' => 'Wisdom'],
         ],
         '10' => [
            'name'    => 'SE_CHA',
            'label'   => 'CHA',
            'display' => ['format' => 1, 'label' => 'Charisma'],
         ],
         '11' => [
            'name'    => 'SE_AttackSpeed',
            'label'   => 'Attack Speed',
            'display' => ['format' => 13, 'label' => 'Attack Speed'],
         ],
         '12' => [
            'name'    => 'SE_Invisibility',
            'label'   => 'Invisibility',
            'display' => ['format' => 0, 'label' => 'Invisibility'],
         ],
         '13' => [
            'name'    => 'SE_SeeInvis',
            'label'   => 'See Invis',
            'display' => ['format' => 0, 'label' => 'See Invisible'],
         ],
         '14' => [
            'name'    => 'SE_WaterBreathing',
            'label'   => 'Water Breathing',
            'display' => ['format' => 0, 'label' => 'Water Breathing'],
         ],
         '15' => [
            'name'    => 'SE_CurrentMana',
            'label'   => 'Current Mana',
            'display' => ['format' => 1, 'label' => 'Mana', 'allowDuration' => true],
         ],
         '18' => [
            'name'    => 'SE_Lull',
            'label'   => 'Lull',
            'display' => ['format' => 0, 'label' => 'Pacify'],
         ],
         '19' => [
            'name'    => 'SE_AddFaction',
            'label'   => 'Add Faction',
            'display' => ['format' => 1, 'label' => 'Faction'],
         ],
         '20' => [
            'name'    => 'SE_Blind',
            'label'   => 'Blind',
            'display' => [
               'format' => 8,
               'label' => 'Blindness',
               'values' => [
                  'strength' => 'decode^decodeBlindnessStackingValue^{{effect:base}}'
               ]
            ],
         ],
         '21' => [
            'name'    => 'SE_Stun',
            'label'   => 'Stun',
            'display' => ['format' => 9, 'label' => 'Stun'],
         ],
         '22' => [
            'name'    => 'SE_Charm',
            'label'   => 'Charm',
            'display' => ['format' => 10, 'label' => 'Charm'],
         ],
         '23' => [
            'name'    => 'SE_Fear',
            'label'   => 'Fear',
            'display' => ['format' => 10, 'label' => 'Fear'],
         ],
         '24' => [
            'name'    => 'SE_Stamina',
            'label'   => 'Stamina',
            'display' => ['format' => 1, 'label' => 'Endurance', 'allowDuration' => true, 'reverseAdjust' => true],
         ],
         '25' => [
            'name'    => 'SE_BindAffinity',
            'label'   => 'Bind Affinity',
            'display' => [
               'format' => 'Bind Location: {{bindType}}',
               'values' => [
                  'bindType' => 'decode^decodeBindTypeById^{{effect:base}}',
               ],
            ],
         ],
         '26' => [
            'name'    => 'SE_Gate',
            'label'   => 'Gate',
            'display' => [
               'format' => 'Teleport to Bound Location: {{bindType}}',
               'values' => [
                  'bindType' => 'decode^decodeBindTypeById^{{effect:base}}',
               ],
            ],
         ],
         '27' => [
            'name'    => 'SE_CancelMagic',
            'label'   => 'Cancel Magic',
            'display' => [
               'format' => 'Dispel Magic: (+{{effect:base}} to caster level effectiveness)',
            ],
         ],
         '28' => [
            'name'    => 'SE_InvisVsUndead',
            'label'   => 'Invis vs Undead',
            'display' => ['format' => 0, 'label' => 'Invisibility versus Undead'],
         ],
         '29' => [
            'name'    => 'SE_InvisVersusAnimals',
            'label'   => 'Invis vs Animals',
            'display' => ['format' => 0, 'label' => 'Invisibility versus Animals'],
         ],
         '30' => [
            'name'    => 'SE_ChangeFrenzyRadius',
            'label'   => 'Change Frenzy Radius',
            'display' => ['format' => 6, 'label' => 'Aggro Radius'],
         ],
         '31' => [
            'name'    => 'SE_Mez',
            'label'   => 'Mez',
            'display' => ['format' => 3, 'label' => 'Mezmerize'],
         ],
         '32' => [
            'name'    => 'SE_SummonItem',
            'label'   => 'Summon Item',
            'display' => [
               'format' => 'Summon: {{itemName}}',
               'values' => [
                  'itemName' => 'data^getItemInfoById^{{effect:base}}^Name',
               ],
            ],
         ],
         '33' => [
            'name'    => 'SE_SummonPet',
            'label'   => 'Summon Pet',
            'display' => ['format' => 11, 'label' => 'Summon Pet'],
         ],
         '35' => [
            'name'    => 'SE_DiseaseCounter',
            'label'   => 'Disease Counter',
            'display' => ['format' => 1, 'label' => 'Disease Counter'],
         ],
         '36' => [
            'name'    => 'SE_PoisonCounter',
            'label'   => 'Poison Counter',
            'display' => ['format' => 1, 'label' => 'Poison Counter'],
         ],
         '40' => [
            'name'    => 'SE_DivineAura',
            'label'   => 'Divine Aura',
            'display' => ['format' => 0, 'label' => 'Invulnerability'],
         ],
         '41' => [
            'name'    => 'SE_Destroy',
            'label'   => 'Destroy',
            'display' => ['format' => 'Destroy {{spell:targetTypeName}} up to L51'],
         ],
         '42' => [
            'name'    => 'SE_ShadowStep',
            'label'   => 'ShadowStep',
            'display' => ['format' => 0, 'label' => 'Shadow Step'],
         ],
         '43' => [ 
            'name'    => 'SE_Berserk',
            'label'   => 'Berserk',
            'display' => ['format' => 0, 'label' => 'Berserk'], 
         ],
         '44' => [ 
            'name'    => 'SE_Lycanthropy',
            'label'   => 'Lycanthropy',
            'display' => ['format' => 0, 'label' => 'Lycanthropy'], 
         ],
         '45' => [
            'name'    => 'SE_Vampirism',
            'label'   => 'Vampirism',
            'display' => ['format' => 0, 'label' => 'Vampirism'], 
         ],
         '46' => [
            'name'    => 'SE_ResistFire',
            'label'   => 'Resist Fire',
            'display' => ['format' => 1, 'label' => 'Fire Resistance'],
         ],
         '47' => [
            'name'    => 'SE_ResistCold',
            'label'   => 'Resist Cold',
            'display' => ['format' => 1, 'label' => 'Cold Resistance'],
         ],
         '48' => [
            'name'    => 'SE_ResistPoison',
            'label'   => 'Resist Poison',
            'display' => ['format' => 1, 'label' => 'Poison Resistance'],
         ],
         '49' => [
            'name'    => 'SE_ResistDisease',
            'label'   => 'Resist Disease',
            'display' => ['format' => 1, 'label' => 'Disease Resistance'],
         ],
         '50' => [
            'name'    => 'SE_ResistMagic',
            'label'   => 'Resist Magic',
            'display' => ['format' => 1, 'label' => 'Magic Resistance'],
         ],
         '52' => [
            'name'    => 'SE_SenseDead',
            'label'   => 'Sense Undead',
            'display' => ['format' => 0, 'label' => 'Sense Undead'],
         ],
         '53' => [
            'name'    => 'SE_SenseSummoned',
            'label'   => 'Sense Summoned',
            'display' => ['format' => 0, 'label' => 'Sense Summoned'],
         ],
         '54' => [
            'name'    => 'SE_SenseAnimals',
            'label'   => 'Sense Animals',
            'display' => ['format' => 0, 'label' => 'Sense Animals'],
         ],
         '55' => [
            'name'    => 'SE_Rune',
            'label'   => 'Rune',
            'display' => ['format' => 1, 'label' => 'Melee Damage Absorbance'],
         ],
         '56' => [
            'name'    => 'SE_TrueNorth',
            'label'   => 'True North',
            'display' => ['format' => 0, 'label' => 'True North'],
         ],
         '57' => [
            'name'    => 'SE_Levitate',
            'label'   => 'Levitate',
            'display' => ['format' => 0, 'label' => 'Levitate'],
         ],
         '58' => [
            'name'    => 'SE_Illusion',
            'label'   => 'Illusion',
            'display' => [
               'format' => 'Illusion: {{raceName}}',
               'values' => [
                  'raceName' => 'decode^decodeRace^{{effect:base}}',
               ],
            ],
         ],
         '59' => [
            'name'    => 'SE_DamageShield',
            'label'   => 'Damage Shield',
            'display' => ['format' => 1, 'label' => 'Damage Shield', 'reverseAdjust' => true],
         ],
         '61' => [
            'name'    => 'SE_Identify',
            'label'   => 'Identify',
            'display' => ['format' => 0, 'label' => 'Identify'],
         ],
         '63' => [
            'name'    => 'SE_WipeHateList',
            'label'   => 'Wipe Hate List',
            'display' => ['format' => 4, 'label' => 'Memblur'],
         ],
         '64' => [
            'name'    => 'SE_SpinTarget',
            'label'   => 'SpinStun',
            'display' => ['format' => 0, 'label' => 'Spin Stun'],
         ],
         '65' => [
            'name'    => 'SE_Infravision',
            'label'   => 'Infravision',
            'display' => ['format' => 0, 'label' => 'Infravision'],
         ],
         '66' => [
            'name'    => 'SE_Ultravision',
            'label'   => 'Ultravision',
            'display' => ['format' => 0, 'label' => 'Ultravision'],
         ],
         '67' => [
            'name' => 'SE_EyeOfZomm',
            'label'   => 'Eye of Zomm',
            'display' => ['format' => 11, 'label' => 'Summon'],
         ],
         '68' => [
            'name'    => 'SE_ReclaimPet',
            'label'   => 'Reclaim Pet',
            'display' => ['format' => 0, 'label' => 'Reclaim Pet'],
         ],
         '69' => [
            'name'    => 'SE_TotalHP',
            'label'   => 'Total HP',
            'display' => ['format' => 1, 'label' => 'Maximum Hitpoints'],
         ],
         '71' => [
            'name'    => 'SE_NecPet',
            'label'   => 'Summon Undead Pet',
            'display' => ['format' => 11, 'label' => 'Summon Undead'],
         ],
         '73' => [
            'name'    => 'SE_Bindsight',
            'label'   => 'Bind Sight',
            'display' => ['format' => 0, 'label' => 'Bind Sight'],
         ],
         '74' => [
            'name'    => 'SE_FeignDeath',
            'label'   => 'Feign Death',
            'display' => ['format' => 0, 'label' => 'Feign Death'],
         ],
         '75' => [
            'name'    => 'SE_VoiceGraft',
            'label'   => 'Voice Graft',
            'display' => ['format' => 0, 'label' => 'Voice Graft'],
         ],
         '76' => [
            'name'    => 'SE_Sentinel',
            'label'   => 'Sentinel',
            'display' => ['format' => 0, 'label' => 'Sentinel'],
         ],
         '77' => [
            'name'    => 'SE_LocateCorpse',
            'label'   => 'Locate Corpse',
            'display' => ['format' => 0, 'label' => 'Locate Corpse'],
         ],
         '78' => [
            'name'    => 'SE_AbsorbMagicAttack',
            'label'   => 'Absorb Spell Damage',
            'display' => ['format' => 1, 'label' => 'Absorb Magic Damage'],
         ],
         '79' => [
            'name'    => 'SE_CurrentHPOnce',
            'label'   => 'Instantaneous HP',
            'display' => ['format' => 1, 'label' => 'Hitpoints initially'],
         ],
         '81' => [
            'name'    => 'SE_Revive',
            'label'   => 'Revive',
            'display' => ['format' => 'Resurrect and restore {{effect:base}}% experience'],
         ],
         '82' => [
            'name'    => 'SE_SummonPC',
            'label'   => 'Summon PC',
            'display' => ['format' => 0, 'label' => 'Summon Player'],
         ],
         '83' => [
            'name'    => 'SE_Teleport',
            'label'   => 'Teleport',
            'display' => ['format' => 5, 'label' => 'Teleport'],
         ],
         '84' => [
            'name'    => 'SE_TossUp',
            'label'   => 'Gravity Flux',
            'display' => ['format' => 15, 'label' => 'Toss Into Air', 'qualifier' => 'upward', 'values' => ['effect:value' => 'raw^abs:{{effect:base}}', 'effect:units' => 'raw^ units']],
         ],
         '85' => [
            'name'    => 'SE_WeaponProc',
            'label'   => 'Weapon Proc',
            'display' => ['format' => 17, 'label' => 'Add Melee Proc'],
         ],
         '86' => [
            'name'    => 'SE_Harmony',
            'label'   => 'Harmony',
            'display' => ['format' => 6, 'label' => 'Assist Radius'],
         ],
         '87' => [
            'name'    => 'SE_MagnifyVision',
            'label'   => 'Magnify Vision',
            'display' => ['format' => 1, 'label' => 'Magnification', 'values' => ['effect:units' => 'raw^%']],
         ],
         '88' => [
            'name'    => 'SE_Succor',
            'label'   => 'Evacuate',
            'display' => ['format' => 5, 'label' => 'Evacuate'],
         ],
         '89' => [
            'name'    => 'SE_ModelSize',
            'label'   => 'Model Size',
            'display' => ['format' => 16, 'label' => 'Target Size'],
         ],
         '90' => [
            'name'    => 'SE_Cloak',
            'label'   => 'Ignore Pet',
            'display' => ['format' => 0, 'label' => 'Ignore Pet'],
         ],
         '91' => [
            'name'    => 'SE_SummonCorpse',
            'label'   => 'Summon Corpse',
            'display' => ['format' => 'Summon Corpse up to L{{effect:base}}'],
         ],
         '92' => [
            'name'    => 'SE_InstantHate',
            'label'   => 'Add Hate',
            'display' => ['format' => 1, 'label' => 'Hate']
         ],
         '93' => [
            'name'    => 'SE_StopRain',
            'label'   => 'Stop Rain',
            'display' => ['format' => 0, 'label' => 'Stop Rain'],
         ],
         '94' => [
            'name'    => 'SE_NegateIfCombat',
            'label'   => 'Negate If Combat', 
            'display' => ['format' => 0, 'label' => 'Removed if Player Attacks or Casts']
         ],
         '95' => [
            'name'    => 'SE_Sacrifice',
            'label'   => 'Sacrifice',
            'display' => [
               'format' => 'Sacrifice Player between L{{sacrificeMinLevel}} and L{{sacrificeMaxLevel}}',
               'values' => [
                  'sacrificeMinLevel' => 'data^getRuleInfoByName^Spells:SacrificeMinLevel^rule_value',
                  'sacrificeMaxLevel' => 'data^getRuleInfoByName^Spells:SacrificeMaxLevel^rule_value',
               ],
            ],
         ],
         '96' => [
            'name'    => 'SE_Silence',
            'label'   => 'Silence',
            'display' => ['format' => 0, 'label' => 'Silence']
         ],
         '97' => [
            'name'    => 'SE_ManaPool',
            'label'   => 'Mana Pool',
            'display' => ['format' => 1, 'label' => 'Maximum Mana'],
         ],
         '98' => [
            'name'    => 'SE_AttackSpeed2',
            'label'   => 'Haste v2',
            'display' => ['format' => 13, 'label' => 'Haste v2'],
         ],
         '99' => [
            'name'    => 'SE_Root',
            'label'   => 'Root',
            'display' => ['format' => 0, 'label' => 'Root'],
         ],
         '100' => [
            'name'    => 'SE_HealOverTime',
            'label'   => 'Heal Over Time',
            'display' => ['format' => 1, 'label' => 'Hitpoints', 'forceDuration' => true],
         ],
         '101' => [
            'name'    => 'SE_CompleteHeal',
            'label'   => 'Complete Heal',
            'display' => ['format' => 0, 'label' => 'Complete Heal With Recast Blocker'],
         ],
         '102' => [
            'name'    => 'SE_Fearless',
            'label'   => 'Fear Immunity',
            'display' => ['format' => 0, 'label' => 'Fear Immunity'],
         ],
         '103' => [
            'name'    => 'SE_CallPet',
            'label'   => 'Call Pet',
            'display' => ['format' => 0, 'label' => 'Summon Pet to Caster'],
         ],
         '104' => [
            'name'    => 'SE_Translocate',
            'label'   => 'Translocate',
            'display' => ['format' => 5, 'label' => 'Translocate'],
         ],
         '105' => [
            'name'    => 'SE_AntiGate',
            'label'   => 'Gate Blocker',
            'display' => ['format' => 0, 'label' => 'Inhibit Gate'],
         ],
         '106' => [
            'name'    => 'SE_SummonBSTPet',
            'label'   => 'Summon Warder',
            'display' => ['format' => 11, 'label' => 'Summon Warder'],
         ],
         '107' => [
            'name'    => 'SE_AlterNPCLevel',
            'label'   => 'Scale Level',
            'text' => 'missing'
         ],
         '108' => [
            'name'    => 'SE_Familiar',
            'label'   => 'Familiar',
            'display' => ['format' => 11, 'label' => 'Summon Familiar'],
         ],
         '109' => [
            'name'    => 'SE_SummonItemIntoBag',
            'label'   => 'Summon Item Into Bag',
            'display' => [
               'format' => 'Summon Item in Bag: {{itemName}}',
               'values' => [
                  'itemName' => 'data^getItemInfoById^{{effect:base}}^Name',
               ],
            ],
         ],
         '110' => [
            'name'    => 'SE_IncreaseArchery',
            'label'   => 'Increase Archery', 
            'display' => ['format' => 1, 'label' => 'Archery'],
         ],
         '111' => [
            'name'    => 'SE_ResistAll',
            'label'   => 'Resist All',
            'display' => ['format' => 1, 'label' => 'All Resists'],
         ],
         '112' => [
            'name'    => 'SE_CastingLevel',
            'label'   => 'Effective Casting Level',
            'display' => ['format' => 1, 'label' => 'Effective Casting Level'],
         ],
         '113' => [
            'name'    => 'SE_SummonHorse',
            'label'   => 'Summon Horse',
            'display' => ['format' => 18, 'label' => 'Summon Mount'],
         ],
         '114' => [
            'name'    => 'SE_ChangeAggro',
            'label'   => 'Add Hate Over Time',
            'display' => ['format' => 15, 'label' => 'Hate Multiplier', 'adjust' => true, 'qualifier' => 'by', 'values' => ['effect:value' => 'raw^{{effect:base}}', 'effect:units' => 'raw^%']],
         ],
         '115' => [
            'name'    => 'SE_Hunger',
            'label'   => 'Hunger',
            'display' => ['format' => 0, 'label' => 'Reset Hunger/Thirst'],
         ],
         '116' => [
            'name'    => 'SE_CurseCounter',
            'label'   => 'Curse Counter',
            'display' => ['format' => 1, 'label' => 'Curse Counter'],
         ],
         '117' => [
            'name'    => 'SE_MagicWeapon',
            'label'   => 'Magic Weapon', 
            'display' => ['format' => 0, 'label' => 'Make Weapons Magical'],
         ],
         '118' => [
            'name'    => 'SE_Amplification',
            'label'   => 'Increase Singing Skill',
            'display' => ['format' => 26, 'label' => 'Singing Skill'],
         ],
         '119' => [
            'name'    => 'SE_AttackSpeed3',
            'label'   => 'Haste v3',
            'display' => ['format' => 19, 'label' => 'Attack Speed Overhaste'],
         ],
         '120' => [
            'name'    => 'SE_HealRate',
            'label'   => 'Increase Regen Cap',
            'display' => ['format' => 19, 'label' => 'Healing Effectiveness'],
         ],
         '121' => [
            'name'    => 'SE_ReverseDS',
            'label'   => 'Reverse Damage Shield',
            'display' => ['format' => 15, 'label' => 'Reverse Damage Shield', 'qualifier' => 'heals for', 'values' => ['effect:value' => 'raw^abs:{{effect:base}}']],
         ],
         '123' => [
            'name'    => 'SE_Screech',
            'label'   => 'Screech Stacking',
            'display' => ['format' => 20],
         ],
         '124' => [
            'name'    => 'SE_ImprovedDamage',
            'label'   => 'Improved Spell Damage',
            'display' => ['format' => 1, 'label' => 'Spell Damage', 'values' => ['effect:units' => 'raw^%']],
         ],
         '125' => [
            'name'    => 'SE_ImprovedHeal',
            'label'   => 'Improved Healing',
            'display' => ['format' => 15, 'label' => 'Healing', 'adjust' => true, 'qualifier' => 'by', 'values' => ['effect:value' => 'raw^{{effect:base}}', 'effect:units' => 'raw^%']],
         ],
         '126' => [
            'name' => 'SE_SpellResistReduction', 
            'label'   => 'Spell Resist Reduction', 
            'text' => 'missing'
         ],
         '127' => [
            'name' => 'SE_IncreaseSpellHaste',
            'label'   => 'Spell Haste',
            'display' => ['format' => 1, 'label' => 'Spell Haste', 'values' => ['effect:units' => 'raw^%']],
         ],
         '128' => [
            'name' => 'SE_IncreaseSpellDuration',
            'label'   => 'Spell Duration', 
            'display' => ['format' => 1, 'label' => 'Spell Duration', 'values' => ['effect:units' => 'raw^%']],
         ],
         '129' => [
            'name' => 'SE_IncreaseRange',
            'label'   => 'Spell Range', 
            'display' => ['format' => 1, 'label' => 'Spell Range', 'values' => ['effect:units' => 'raw^%']],
         ],
         '130' => [
            'name' => 'SE_SpellHateMod', 
            'label'   => 'Spell Hate', 
            'display' => ['format' => 15, 'label' => 'Spell Hate Multiplier', 'adjust' => true, 'qualifier' => 'by', 'values' => ['effect:value' => 'raw^abs:{{effect:base}}', 'effect:units' => 'raw^%']],
         ],
         '131' => [
            'name' => 'SE_ReduceReagentCost', 
            'label'   => 'Reagent Cost', 
            'display' => ['format' => 15, 'label' => 'Change of Using Reagent', 'adjust' => true, 'reverseAdjust' => true, 'qualifier' => 'by', 'values' => ['effect:value' => 'raw^{{effect:base}}', 'effect:units' => 'raw^%']],
         ],
         '132' => [
            'name' => 'SE_ReduceManaCost', 
            'label'   => 'Reduce Mana Cost', 
            'display' => ['format' => 1, 'label' => 'Mana Cost', 'values' => ['effect:units' => 'raw^%'], 'reverseAdjust' => true],
         ],
         '133' => [
            'name' => 'SE_RFcStunTimeMod',
            'label'   => 'Reduce Fizzle Time',
            'text' => 'missing'
         ],
         '134' => [
            'name' => 'SE_LimitMaxLevel',
            'label'   => 'Limit: Max Spell Level',
            'display' => ['format' => 14, 'label' => 'Max Spell Level', 'values' => ['limit' => 'raw^{{effect:base}}']],
         ],
         '135' => [
            'name' => 'SE_LimitResist',
            'label'   => 'Limit: Resist Type',
            'display' => ['format' => 14, 'label' => 'Spell Target', 'values' => ['inclusion' => 'raw^effect:base', 'limit' => 'decode^decodeResistType^abs:{{effect:base}}']],
         ],
         '136' => [
            'name' => 'SE_LimitTarget',
            'label'   => 'Limit: Target Type',
            'display' => ['format' => 14, 'label' => 'Spell Target', 'values' => ['inclusion' => 'raw^effect:base', 'limit' => 'decode^decodeSpellTargetType^abs:{{effect:base}}']],
         ],
         '137' => [
            'name'    => 'SE_LimitEffect',
            'label'   => 'Limit: Effect Type',
            'display' => ['format' => 14, 'label' => 'Spell Effect', 'values' => ['inclusion' => 'raw^effect:base', 'limit' => 'decode^decodeSpellEffect^abs:{{effect:base}}^label']],
         ],
         '138' => [
            'name'    => 'SE_LimitSpellType',
            'label'   => 'Limit: Spell Type',
            'display' => ['format' => 14, 'label' => 'Spell Type', 'map' => ['0' => 'Detrimental', '1' => 'Beneficial'], 'values' => ['limit' => 'map^{{effect:base}}']],
         ],
         '139' => [
            'name'    => 'SE_LimitSpell',
            'label'   => 'Limit: Spell',
            'display' => ['format' => 14, 'label' => 'Spell', 'values' => ['inclusion' => 'raw^effect:base', 'limit' => 'spell^getSpellDataById^abs:{{effect:base}}^name']],
         ],
         '140' => [
            'name' => 'SE_LimitMinDur',
            'label'   => 'Limit: Min Spell Duration',
            'display' => ['format' => 14, 'label' => 'Minimum Duration', 'values' => ['limit' => 'raw^{{effect:base}}', 'units' => 'raw^ ticks']],
         ],
         '141' => [
            'name'    => 'SE_LimitInstant',
            'label'   => 'Limit: Instant Spells',
            'display' => ['format' => 14, 'label' => 'Instant Spells', 'map' => ['0' => 'Exclude ', '1' => 'Include '], 'values' => ['mapValue' => 'map^{{effect:base}}', 'effect:limitadjust' => 'values^mapValue']],
         ],
         '142' => [
            'name' => 'SE_LimitMinLevel',
            'label'   => 'Limit: Min Spell Level',
            'display' => ['format' => 14, 'label' => 'Min Spell Level', 'values' => ['limit' => 'raw^{{effect:base}}']],
         ],
         '143' => [
            'name' => 'SE_LimitCastTimeMin',
            'label'   => 'Limit: Min Cast Time',
            'display' => ['format' => 14, 'label' => 'Minimum Cast Time', 'values' => ['limit' => 'raw^mstos:{{effect:base}}', 'units' => 'raw^s']],
         ],
         '145' => [
            'name'    => 'SE_Teleport2',
            'label'   => 'Teleport To Zone Coords',
            'display' => ['format' => 5, 'label' => 'Banish'],
         ],
         '147' => [
            'name'    => 'SE_PercentalHeal',
            'label'   => 'Percent Heal',
            'display' => ['format' => 19, 'label' => 'Hitpoints', 'values' => ['effect:units' => 'raw^hitpoints']],
         ],
         '148' => [
            'name' => 'SE_StackingCommandBlock',
            'label'   => 'Stacking: Block',
            'display' => ['format' => 12, 'label' => 'Block New Spell'],
         ],
         '149' => [
            'name' => 'SE_StackingCommandOverwrite',
            'label'   => 'Stacking: Overwrite',
            'display' => ['format' => 12, 'label' => 'Overwrite Existing Spell'],
         ],
         '150' => [
            'name' => 'SE_DeathSave',
            'label'   => 'Death Save',
            'display' => ['format' => 21, 'values' => ['triggerPercent' => 'raw^16']],
         ],
         '151' => [
            'name'    => 'SE_SuspendPet',
            'label'   => 'Suspend Pet',
            'display' => ['format' => 0, 'label' => 'Suspend Pet'],
         ],
         '152' => [
            'name'    => 'SE_TemporaryPets',
            'label'   => 'Swarm Pet',
            'display' => ['format' => 11, 'temporary' => true, 'label' => 'Summon Swarm Pet'],
         ],
         '153' => [
            'name'    => 'SE_BalanceHP',
            'label'   => 'Balance HP',
            'display' => ['format' => 22],
         ],
         '154' => [
            'name'    => 'SE_DispelDetrimental',
            'label'   => 'Dispel Detrimental',
            'display' => [
               'format' => 'Dispel Detrimental Buffs ({{chance}}% Chance)',
               'values' => [
                  'chance' => 'raw^percent10:{{effect:base}}',
               ],
            ],
         ],
         '155' => [
            'name'    => 'SE_SpellCritDmgIncrease',
            'label'   => 'Increase Spell Crit Direct Damage',
            'text' => 'missing'
         ],
         '156' => [
            'name'    => 'SE_IllusionCopy',
            'label'   => 'Target\'s Target Illusion',
            'display' => ['format' => 0, 'label' => 'Illusion: Target'],
         ],
         '157' => [
            'name'    => 'SE_SpellDamageShield',
            'label'   => 'Spell DS',
            'display' => [
               'format' => 'Inflict {{effect:base}} Damage on Caster when Hit by Spell',
            ],
         ],
         '158' => [
            'name'    => 'SE_Reflect',
            'label'   => 'Reflect Spell',
            'display' => ['format' => 23],
         ],
         '159' => [
            'name'    => 'SE_AllStats',
            'label'   => 'Increase All Stats Cap',
            'display' => ['format' => 1, 'label' => 'All Base Stats'],
         ],
         '160' => [
            'name'    => 'SE_MakeDrunk',
            'label'   => 'Drunk',
            'display' => [
               'format' => 'Intoxicate if Alcohol Tolerance is under {{effect:base}}',
            ],
         ],
         '161' => [
            'name'    => 'SE_MitigateSpellDamage',
            'label'   => 'Mitigate Spell Damage by %',
            'display' => ['format' => 24, 'label' => 'Spell'],
         ],
         '162' => [
            'name'    => 'SE_MitigateMeleeDamage',
            'label'   => 'Mitigate Melee Damage by %',
            'display' => ['format' => 24, 'label' => 'Melee'],
         ],
         '163' => [
            'name'    => 'SE_NegateAttacks',
            'label'   => 'Block Next Spell',
            'display' => ['format' => 25],
         ],
         '164' => [
            'name'    => 'SE_AppraiseLDonChest',
            'label'   => 'Sense LDoN Chest',
            'display' => ['format' => 0, 'label' => 'Sense LDoN Chest', 'inUse' => false],
         ],
         '165' => [
            'name'    => 'SE_DisarmLDoNTrap',
            'label'   => 'Disarm LDoN Trap',
            'display' => ['format' => 0, 'label' => 'Disarm LDoN Trap', 'inUse' => false],
         ],
         '167' => [
            'name' => 'SE_PetPowerIncrease',
            'label'   => 'Increase Pet Power',
            'text' => 'missing'
         ],
         '168' => [
            'name'    => 'SE_MeleeMitigation',
            'label'   => 'Mitigate Incoming Damage',
            'display' => ['format' => 7, 'label' => 'Incoming Melee Damage'],
         ],
         '169' => [
            'name'    => 'SE_CriticalHitChance',
            'label'   => 'Increase Melee Crit Rate',
            'display' => ['format' => 7, 'label' => 'Melee Critical Hit Chance'],
            'exceptions' => [
               '4499' => ['label' => 'Set Melee Critical Hit Chance to {{chance}}%', 'values' => ['chance' => 'raw^0']]
            ],
         ],
         '170' => [
            'name' => 'SE_SpellCritChance',
            'label'   => 'Increase Spell Crit Rate',
            'text' => 'missing'
         ],
         '171' => [
            'name' => 'SE_CripplingBlowChance',
            'label'   => 'Increase Crippling Blow Rate',
            'text' => 'missing'
         ],
         '172' => [
            'name' => 'SE_AvoidMeleeChance',
            'label'   => 'Increase Chance to Avoid Melee',
            'text' => 'missing'
         ],
         '173' => [
            'name' => 'SE_RiposteChance',
            'label'   => 'Increase Riposte Rate',
            'text' => 'missing'
         ],
         '174' => [
            'name' => 'SE_DodgeChance',
            'label'   => 'Increase Dodge Rate',
            'text' => 'missing'
         ],
         '175' => [
            'name' => 'SE_ParryChance',
            'label'   => 'Increase Parry Rate',
            'text' => 'missing'
         ],
         '176' => [
            'name' => 'SE_DualWieldChance',
            'label'   => 'Increase Dual Wield Rate',
            'text' => 'missing'
         ],
         '177' => [
            'name' => 'SE_DoubleAttackChance',
            'label'   => 'Increase Double Attack Rate',
            'text' => 'missing'
         ],
         '178' => [
            'name' => 'SE_MeleeLifetap',
            'label'   => 'Melee Lifetap',
            'text' => 'missing'
         ],
         '179' => [
            'name' => 'SE_AllInstrumentMod',
            'label'   => 'Increase All Instrument Mod',
            'text' => 'missing'
         ],
         '180' => [
            'name' => 'SE_ResistSpellChance',
            'label'   => 'Increase Chance to Resist Spells',
            'text' => 'missing'
         ],
         '181' => [
            'name' => 'SE_ResistFearChance',
            'label'   => 'Increase Chance to Resist Fear',
            'text' => 'missing'
         ],
         '182' => [
            'name' => 'SE_HundredHands',
            'label'   => 'Hundred Hands',
            'text' => 'missing'
         ],
         '183' => [
            'name' => 'SE_MeleeSkillCheck',
            'label'   => 'Increase Chance to Hit with Skill',
            'text' => 'missing'
         ],
         '184' => [
            'name' => 'SE_HitChance',
            'label'   => 'Increase Chance to Hit',
            'text' => 'missing'
         ],
         '185' => [
            'name' => 'SE_DamageModifier',
            'label'   => 'Increase All Skill Damage',
            'display' => ['format' => 7, 'label' => 'Melee Damage'],
         ],
         '186' => [
            'name' => 'SE_MinDamageModifier',
            'label'   => 'Increase Min Damage with Skill',
            'text' => 'missing'
         ],
         '201' => [
            'name'    => 'SE_RangedProc',
            'label'   => 'Ranged Proc',
            'display' => ['format' => 17, 'label' => 'Add Ranged Proc'],
         ],
         '254' => [
            'name' => 'SE_Blank',
            'label'   => 'Blank',
            'text' => 'missing'
         ],
         '323' => [
            'name'    => 'SE_DefensiveProc',
            'label'   => 'Defensive Proc',
            'display' => ['format' => 17, 'label' => 'Add Defensive Proc'],
         ],
         '419' => [
            'name'    => 'SE_AddMeleeProc',
            'label'   => 'Melee Proc v2',
            'display' => ['format' => 17, 'label' => 'Add Melee Proc v2'],
         ],
         '427' => [
            'name'    => 'SE_SkillProc',
            'label'   => 'Skill Attempt Proc',
            'display' => ['format' => 17, 'label' => 'Add Skill Attempt Proc'],
         ],
         '429' => [
            'name'    => 'SE_SkillProcSuccess',
            'label'   => 'Skill Success Proc',
            'display' => ['format' => 17, 'label' => 'Add Skill Success Proc'],
         ],
         '500' => [
            'name'    => 'SE_Fc_CastTimeMod2',
            'label'   => 'Focus: Spell Haste',
            'display' => ['format' => 0, 'label' => 'Focus: Spell Haste', 'inUse' => false],
         ],
         '501' => [
            'name'    => 'SE_Fc_CastTimeAmt',
            'label'   => 'Focus: Spell Cast Time',
            'display' => ['format' => 0, 'label' => 'Focus: Spell Cast Time', 'inUse' => false],
         ],
         '503' => [
            'name'    => 'SE_Melee_Damage_Position_Mod',
            'label'   => 'Rear Arc Melee Damage Mod',
            'display' => ['format' => 0, 'label' => 'Melee Damage Position Percent Modifier', 'inUse' => false],
         ],
         '504' => [
            'name'    => 'SE_Melee_Damage_Position_Amt',
            'label'   => 'Rear Arc Melee Damage Amt',
            'display' => ['format' => 0, 'label' => 'Melee Damage Position Modifier', 'inUse' => false],
         ],
      ];

      return (($return) ? $spellEffectList[$spellEffectId][$return] : $spellEffectList[$spellEffectId]) ?: null;
   }
   
   public function decodeRace($raceId)
   {
      $raceList = [
         '1'   => 'Human',
         '2'   => 'Barbarian',
         '3'   => 'Erudite',
         '4'   => 'Wood Elf',
         '5'   => 'High Elf',
         '6'   => 'Dark Elf',
         '7'   => 'Half Elf',
         '8'   => 'Dwarf',
         '9'   => 'Troll',
         '10'  => 'Ogre',
         '11'  => 'Halfling',
         '12'  => 'Gnome',
         '14'  => 'Werewolf',
         '15'  => 'Brownie',
         '25'  => 'Fairy',
         '28'  => 'Fungusman',
         '42'  => 'Wolf',
         '43'  => 'Bear',
         '44'  => 'Freeport Guard',
         '48'  => 'Kobold',
         '49'  => 'Lava Dragon',
         '50'  => 'Lion',
         '52'  => 'Mimic',
         '55'  => 'Human Begger',
         '56'  => 'Pixie',
         '57'  => 'Dracnid',
         '60'  => 'Skeleton',
         '63'  => 'Tiger',
         '65'  => 'Vampire',
         '67'  => 'Highpass Citizen',
         '69'  => 'Wisp',
         '70'  => 'Zombie',
         '72'  => 'Ship',
         '73'  => 'Launch',
         '74'  => 'Froglok',
         '75'  => 'Elemental',
         '77'  => 'Neriak Citizen',
         '78'  => 'Erudite Citizen',
         '79'  => 'Bixie',
         '81'  => 'Rivervale Citizen',
         '88'  => 'Clockwork Gnome',
         '90'  => 'Halas Citizen',
         '91'  => 'Alligator',
         '92'  => 'Grobb Citizen',
         '93'  => 'Oggok Citizen',
         '94'  => 'Kaladim Citizen',
         '98'  => 'Elf Vampire',
         '106' => 'Felguard',
         '108' => 'Eye Of Zomm',
         '112' => 'Fayguard',
         '114' => 'Ghost Ship',
         '117' => 'Dwarf Ghost',
         '118' => 'Erudite Ghost',
         '120' => 'Wolf Elemental',
         '127' => 'Invisible Man',
         '128' => 'Iksar',
         '130' => 'Vahshir',
         '141' => 'Controlled Boat',
         '142' => 'Minor Illusion',
         '143' => 'Treeform',
         '145' => 'Goo',
         '158' => 'Wurm',
         '161' => 'Iksar Skeleton',
         '184' => 'Velious Dragon',
         '196' => 'Ghost Dragon',
         '198' => 'Prismatic Dragon',
         '209' => 'Earth Elemental',
         '210' => 'Air Elemental',
         '211' => 'Water Elemental',
         '212' => 'Fire Elemental',
         '216' => 'Horse',
         '240' => 'Teleport Man',
         '296' => 'Mithaniel Marr',
      ];

      return $raceList[$raceId] ?: null;
   }

   public function decodeClass($classId, $short = false)
   {
      $classList = [
         '1' => ['Warrior','WAR'],
         '2' => ['Cleric','CLR'],
         '3' => ['Paladin','PAL'],
         '4' => ['Ranger','RNG'],
         '5' => ['Shadowknight','SHD'],
         '6' => ['Druid','DRU'],
         '7' => ['Monk','MNK'],
         '8' => ['Bard','BRD'],
         '9' => ['Rogue','ROG'],
         '10' => ['Shaman','SHM'],
         '11' => ['Necromancer','NEC'],
         '12' => ['Wizard','WIZ'],
         '13' => ['Magician','MAG'],
         '14' => ['Enchanter','ENC'],
         '15' => ['Beastlord','BST'], 
      ];

      $return = ($short) ? $classList[$classId][1] : $classList[$classId][0];
   
      return $return ?: null;
   }

   public function decodeDeity($deityId)
   {
      $deityList = [
         '0'   => 'Unknown',
         '396' => 'Agnostic',
         '201' => 'Bertoxxulous',
         '202' => 'Brell Serilis',
         '203' => 'Cazic-Thule',
         '204' => 'Erollisi Marr',
         '205' => 'Fizzlethorpe Bristlebane',
         '206' => 'Innoruuk',
         '207' => 'Karana',
         '208' => 'Mithaniel Marr',
         '209' => 'Prexus',
         '210' => 'Quellious',
         '211' => 'Rallos Zek',
         '212' => 'Rodcet Nife',
         '213' => 'Solusek Ro', 
         '214' => 'The Tribunal', 
         '215' => 'Tunare', 
         '216' => 'Veeshan', 
      ];
   
      return $deityList[$deityId] ?: null;
   }
   
   public function decodeSkill($skillId)
   {
      $skillList = [
         '0'  => '1HBlunt',
         '1'  => '1HSlashing',
         '2'  => '2HBlunt',
         '3'  => '2HSlashing',
         '4'  => 'Abjuration',
         '5'  => 'Alteration',
         '6'  => 'ApplyPoison',
         '7'  => 'Archery',
         '8'  => 'Backstab',
         '9'  => 'BindWound',
         '10' => 'Bash',
         '11' => 'Block',
         '12' => 'BrassInstruments',
         '13' => 'Channeling',
         '14' => 'Conjuration',
         '15' => 'Defense',
         '16' => 'Disarm',
         '17' => 'DisarmTraps',
         '18' => 'Divination',
         '19' => 'Dodge',
         '20' => 'DoubleAttack',
         '21' => 'DragonPunch/TailRake',
         '22' => 'DualWield',
         '23' => 'EagleStrike',
         '24' => 'Evocation',
         '25' => 'FeignDeath',
         '26' => 'FlyingKick',
         '27' => 'Forage',
         '28' => 'HandtoHand',
         '29' => 'Hide',
         '30' => 'Kick',
         '31' => 'Meditate',
         '32' => 'Mend',
         '33' => 'Offense',
         '34' => 'Parry',
         '35' => 'PickLock',
         '36' => '_1HPiercing',
         '37' => 'Riposte',
         '38' => 'RoundKick',
         '39' => 'SafeFall',
         '40' => 'SenseHeading',
         '41' => 'Singing',
         '42' => 'Sneak',
         '43' => 'SpecializeAbjure',
         '44' => 'SpecializeAlteration',
         '45' => 'SpecializeConjuration',
         '46' => 'SpecializeDivination',
         '47' => 'SpecializeEvocation',
         '48' => 'PickPockets',
         '49' => 'StringedInstruments',
         '50' => 'Swimming',
         '51' => 'Throwing',
         '52' => 'TigerClaw',
         '53' => 'Tracking',
         '54' => 'WindInstruments',
         '55' => 'Fishing',
         '56' => 'MakePoison',
         '57' => 'Tinkering',
         '58' => 'Research',
         '59' => 'Alchemy',
         '60' => 'Baking',
         '61' => 'Tailoring',
         '62' => 'SenseTraps',
         '63' => 'Blacksmithing',
         '64' => 'Fletching',
         '65' => 'Brewing',
         '66' => 'AlcoholTolerance',
         '67' => 'Begging',
         '68' => 'JewelryMaking',
         '69' => 'Pottery',
         '70' => 'PercussionInstruments',
         '71' => 'Intimidation',
         '72' => 'Berserking',
         '73' => 'Taunt',
      ];
   
      return $skillList[$skillId] ?: null;
   }
   
   public function decodeSpellTargetType($spellTargetTypeId)
   {
      $spellTargetTypeList = [
         '1'  => 'TargetOptional',
         '2'  => 'GroupV1',
         '3'  => 'GroupTeleport',
         '4'  => 'PBAE',
         '5'  => 'Single',
         '6'  => 'Self',
         '8'  => 'TargetAE',
         '9'  => 'Animal',
         '10' => 'Undead',
         '11' => 'Summoned',
         '13' => 'Tap',
         '14' => 'Pet',
         '15' => 'Corpse',
         '16' => 'Plant',
         '17' => 'UberGiant',
         '18' => 'UberDragon',
         '20' => 'TargetAETap',
         '24' => 'UndeadAE',
         '25' => 'SummonedAE',
         '40' => 'BardAE',
         '41' => 'GroupV2',
         '43' => 'ProjectIllusion',
      ];
   
      return $spellTargetTypeList[$spellTargetTypeId] ?: null;
   }

   public function decodeSpellType($spellTypeMask)
   {
      $types = [];

      $spellTypeList = [
         '0'  => 'Nuke',
         '1'  => 'Heal',
         '2'  => 'Root',
         '3'  => 'Buff',
         '4'  => 'Escape',
         '5'  => 'Pet',
         '6'  => 'Lifetap',
         '7'  => 'Snare',
         '8'  => 'DOT',
         '9'  => 'Dispel',
         '10' => 'InCombatBuff',
         '11' => 'Mez',
         '12' => 'Charm',
         '13' => 'Slow',
         '14' => 'Debuff',
         '15' => 'Cure',
         '16' => 'Resurrect',
      ];

      $bitArray = array_reverse(str_split(base_convert($spellTypeMask,10,2)));

      foreach ($bitArray as $pos => $value) {
         if ($value) { $types[] = $spellTypeList[$pos]; }
      }
   
      return (($types) ? implode('/',$types) : 'None');
   }

   public function decodeBardType($bardTypeId)
   {
      $valueList = [
         '23' => 'Wind Instruments',
         '24' => 'Stringed Instruments',
         '25' => 'Brass Instruments',
         '26' => 'Percussion Instruments',
         '50' => 'Singing',
         '51' => 'All Instruments'
      ];

      return $valueList[$bardTypeId] ?: null;
   }

   public function decodeBlindnessStackingValue($effectValue)
   {
      $valueList = [
         '-1' => 'Low',
         '1'  => 'Normal',
         '2'  => 'Medium',
         '3'  => 'High',
      ];

      return $valueList[$effectValue] ?: null;
   }
   
   public function decodeBuffDuration($casterLevel, $formula, $duration) 
   {
      switch ($formula) {
         case 0: return 0;
         case 1:  $uDuration = $casterLevel / 2;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 2:  $uDuration = ($casterLevel <= 1) ? 6 : ($casterLevel / 2) + 5;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 3:  $uDuration = $casterLevel * 30;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 4:  $uDuration = 50;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 5:  $uDuration = 2;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 6:  $uDuration = ($casterLevel / 2) + 2;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 7:  $uDuration = $casterLevel;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 8:  $uDuration = $casterLevel + 10;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 9:  $uDuration = ($casterLevel * 2) + 10;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 10: $uDuration = ($casterLevel * 3) + 10;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 11: $uDuration = ($casterLevel * 30) + 90;
                  $return = ($uDuration < $duration) ? (($uDuration < 1) ? 1 : $uDuration) : $duration;
                  break;
         case 12: $uDuration = $casterLevel / 4; 
                  $uDuration = ($uDuration) ? $uDuration : 1;
                  $return = ($duration) ? (($uDuration < $duration) ? $uDuration : $duration) : $uDuration;
                  break;
         case 50: $return = hexdec('0xFFFE');
                  break;
         default: $return = 0;
      }
   
      return $return;
   }
   
   public function decodeResistType($resistTypeId)
   {
      $resistTypeList = [
         '0' => 'Unresistable',
         '1' => 'Magic',
         '2' => 'Fire',
         '3' => 'Cold',
         '4' => 'Poison',
         '5' => 'Disease',
      ];
   
      return $resistTypeList[$resistTypeId] ?: null;
   }
   
   public function decodeEmoteType($emoteTypeId)
   {
      $emoteTypeList = [
         '0' => 'say',
         '1' => 'emote',
         '2' => 'shout',
         '3' => 'message',
      ];
   
      return $emoteTypeList[$emoteTypeId] ?: 'say';
   }

   public function decodeBindTypeById($bindId)
   {
      $bindTypeList = [
         '1' => 'Primary',
         '2' => 'Secondary',
         '3' => 'Tertiary',
      ];
   
      return $bindTypeList[$bindId] ?: $bindTypeList['1'];
   }
   
   public function decodeNpcEvent($npcEventTypeId)
   {
      $npcEventTypeList = [
         '0' => 'LeaveCombat',
         '1' => 'EnterCombat',
         '2' => 'OnDeath',
         '3' => 'AfterDeath',
         '4' => 'Hailed',
         '5' => 'KilledPC',
         '6' => 'KilledNPC',
         '7' => 'OnSpawn',
         '8' => 'OnDespawn',
         '9' => 'Killed',
      ];
 
      return $npcEventTypeList[$npcEventTypeId] ?: null;
   }  
}