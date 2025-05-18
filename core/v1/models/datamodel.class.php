<?php

/**
 * DataModel
 */
class DataModel extends DefaultModel
{ 
   public function __construct($debug = null, $main = null, $options = null)
   {
      parent::__construct($debug,$main,$options);
   }

   public function getHorseInfoBySpellId($spellId)
   {
      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      // None of the horse data in the database is consistent, so we need to do some work to get the correct name
      $mountDescriptions = [
         'Chimera'        => 'Chimera',
         'Cragslither1'   => 'Crimson Cragslither',
         'Cragslither2'   => 'Viridian Cragslither',
         'Cragslither3'   => 'Plagued Cragslither',
         'HorseBl'        => 'Black Horse',
         'HorseBr'        => 'Brown Horse',
         'HorseTa'        => 'Tan Horse',
         'HorseWh'        => 'White Horse',
         'Kiriin0'        => 'Mystical Kirin',
         'Kiriin2'        => 'Cursed Kirin',
         'LizardBlk'      => 'Black Lizard',
         'LizardGrn'      => 'Green Lizard',
         'LizardRed'      => 'Red Lizard',
         'LizardWht'      => 'White Lizard',
         'Nightmare'      => 'Nightmare',
         'Puma1'          => 'Shadow Panther',
         'Puma3'          => 'Snow Leopard',
         'Roboboar'       => 'Roboboar',
         'Unicorn'        => 'Unicorn',
         'WarHorseBl'     => 'Black War Horse',
         'WarHorseBr'     => 'Brown War Horse',
         'WarHorseTa'     => 'Tan War Horse',
         'WarHorseWh'     => 'White War Horse',
         'Worg'           => 'Worg',
         'Sokokar1'       => 'Emerald Sokokar',
         'Sokokar2'       => 'Bloodied Sokokar',
         'Sokokar3'       => 'Corrupted Sokokar',
         'Sokokar4'       => 'Sokokar',
         'Sokokar5'       => 'Flying Imperial Sokokar',
         'Feran1'         => 'Commanded Feran',
         'Feran2'         => 'Ferocious Feran',
         'HydraBlk'       => 'Onyx Hydra',
         'HydraGrn'       => 'Jade Hydra',
         'Spider1'        => 'Skittering',
         'Spider2'        => 'Wiring',
         'Bear1'          => 'Bear',
         'Bear2'          => 'War Bear',
         'Wrulon1'        => 'Wrulon Guardian',
         'Wrulon2'        => 'Wrulon Protector',
         'Wrulon3'        => 'Wrulon Warder',
         'LionBrown'      => 'Highland Lion',
         'LionWhite'      => 'King Kalakor',
         'ShinyRoboboar'  => 'Shiny New Class V Collapsable Roboboar',
         'CliknarBlack'   => 'Prime Cliknar',
         'CliknarRaid'    => 'Queens Prime Minion',
         'CliknarRed'     => 'Strong Captured Cliknar',
         'HobbyHorse'     => 'Hobby Horse',
         'TigerWhite'     => 'White Tiger of the Alabaster Jungle',
         'MBXm6'          => 'Festive Braxi',
         'MBLm0'          => 'Ognits Mini Dirigible Device',
         'MBLm1'          => 'Veilbreaker Escape Module',
         'MGLm0'          => 'Goral Stalker',
         'MKGm1'          => 'Forest Kangon',
         'MKGm2'          => 'Desert Kangon',
         'MPGm0'          => 'Pegasus',
         'MPGm2'          => 'Battle Armored Pegasus',
         'MPGm3'          => 'Onyx Skystrider',
         'MPGm4'          => 'Parade Armored Onyx Skystrider',
         'MPGm6'          => 'Dreadmare',
         'MPGm7'          => 'Blazing Skystrider',
         'MPGm9'          => 'Mechanical Skystrider',
         'MPGm10'         => 'Celestial Skystrider',
         'MPGm11'         => 'Dragonscale Skystrider',
         'MPUm4'          => 'Armored Snow Puma',
         'MPUm5'          => 'Plains Puma',
         'MPUm6'          => 'Forest Jaguar',
         'MPUm7'          => 'Grassland Tiger',
         'MRDm0'          => 'Charred Rotdog',
         'MRDm1'          => 'Fleshless Rotdog',
         'MRDm2'          => 'Albino Rotdog',
         'MRKm0'          => 'Anizoks Steam Escalator',
         'MRPm0'          => 'Venemous Raptor',
         'MRPm1'          => 'Tiger Raptor',
         'MSDm0'          => 'Sessiloid',
         'MSDm3'          => 'Armored Sessiloid',
         'MSLm0'          => 'Verdant Selyrah Whistle',
         'MSLm5'          => 'Prismatic Selyrah',
         'MTAm2'          => 'Desert Tarantula',
         'MTLm0'          => 'Verdant Hedgerow Leaf',
         'MTLm1'          => 'Dessicated Hedgerow Leaf',
         'MWMm0'          => 'Shadow Wurm',
         'MWMm1'          => 'Ember Wurm',
         'MWMm2'          => 'Golden Wurm',
         'MWMm3'          => 'Nature Touched Wurm',
         'MWMm4'          => 'Frost Wurm',
         'MWRm1'          => 'Firescale Wrulon',
         'UNMm5'          => 'Armored Planar Ardennes',
         'UNMm6'          => 'Armored Royal Ardennes',
         'UNMm7'          => 'Armored Royal Shire',
         'UNMm8'          => 'Armored Battle Shire',
         'UNMm9'          => 'Balebris Horse',
         'UNMm10'         => 'Dallyns Horse',
         'UNMm11'         => 'Meleens Horse',
         'UNMm13'         => 'Tahkas Horse',
      ];

      $horseInfo = $this->main->db($this->dbName)->bindQuery("SELECT h.filename as name,r.name as racename,h.mountspeed,h.notes FROM spells_new sn LEFT JOIN horses h ON sn.teleport_zone = h.filename ". 
                                      "LEFT JOIN races r ON r.id = h.race WHERE sn.id = ?",'i',[$spellId],['single' => true]);                        

      if (preg_match('/^invalid$/i',$horseInfo['racename'])) { $horseInfo['racename'] = ''; }

      $baseName = preg_replace('/^sum(\S+?)(fast|run1|run2|slow1|slow2.*)?$/i','$1',$horseInfo['name']);

      $horseInfo['type'] = $mountDescriptions[$baseName] ?? $horseInfo['racename'];

      if ($horseInfo['mountspeed']) { $horseInfo['mountspeed'] *= 100; }
      if ($horseInfo['notes'] && preg_match('/^none$/i',$horseInfo['notes'])) { $horseInfo['notes'] = null; }

      return $horseInfo;
   }

   public function getPetInfoBySpellId($spellId)
   {
      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      $magicianPetTypeList = [
         'Fire'  => [626, 630, 634, 316, 399, 403, 395, 498, 571, 575, 622, 1673, 1677, 3322],
         'Air'   => [627, 631, 635, 317, 396, 400, 404, 499, 572, 576, 623, 1674, 1678, 3371],
         'Earth' => [624, 628, 632, 58, 397, 401, 335, 496, 569, 573, 620, 1675, 1671, 3324],
         'Water' => [625, 629, 633, 315, 398, 402, 336, 497, 570, 574, 621, 1676, 1672, 3320],
      ];

      $petInfo = $this->main->db($this->dbName)->bindQuery("SELECT r.name,nt.level,nt.race,nt.bodytype FROM spells_new sn LEFT JOIN pets p ON sn.teleport_zone = p.type ". 
                                      "LEFT JOIN npc_types nt ON nt.id = p.npcID LEFT JOIN races r ON r.id = nt.race WHERE sn.id = ?",'i',[$spellId],['single' => true]);                        

      foreach ($magicianPetTypeList as $petType => $spellIdList) {
         if (in_array($spellId,$spellIdList)) { $petInfo['name'] = sprintf("%s %s",$petType,$petInfo['name']); }
      }

      if (preg_match('/^invalid$/i',$petInfo['name'])) { $petInfo['name'] = ''; }

      return $petInfo;
   }

   public function getLootTableEntriesById($lootTableId)
   {
      $this->debug(8,"called");

      $return = array();

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      $entryList = $this->main->db($this->dbName)->bindQuery("SELECT *, concat(loottable_id,'^',lootdrop_id) as id FROM loottable_entries WHERE loottable_id = ?",'i',array($lootTableId),array('index' => 'id'));

      return $entryList;
   }

   public function getLootDropEntriesById($lootDropId)  
   {
      $this->debug(8,"called");

      $return = array();

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available'); return false; }

      $entryList = $this->main->db($this->dbName)->bindQuery("SELECT lde.item_id, i.name as item_name, lde.chance, lde.multiplier, ".
                                        "lde.min_expansion as drop_min_expansion, lde.max_expansion as drop_max_expansion, ".
                                        "i.min_expansion as item_min_expansion, i.max_expansion as item_max_expansion, ". 
                                        "concat(lde.lootdrop_id,'^',lde.item_id) as id ". 
                                        "FROM lootdrop_entries lde LEFT JOIN items i on lde.item_id = i.id ". 
                                        "WHERE lootdrop_id = ?",'i',array($lootDropId),array('index' => 'id'));

      return $entryList;
   }

   public function getNpcLootTableList($options = null)
   {
      $this->debug(8,"called");

      $return = array();

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      //$npcLootTables = $this->main->db($this->dbName)->query("SELECT distinct(concat(nt.name,'^',nt.loottable_id,'^',s2.zone,'^',s2.min_expansion,'-',s2.max_expansion,'^',se.min_expansion,'-',se.max_expansion)) as entry FROM npc_types nt LEFT JOIN spawnentry se ON nt.id = se.npcID LEFT JOIN spawn2 s2 ON se.spawngroupID = s2.spawngroupID WHERE nt.loottable_id > 0 and nt.level >= 10");
      
      $npcLootTables = $this->main->db($this->dbName)->query("SELECT id, nt_name, nt_loottable_id, s2_zone, s2_min_expansion, s2_max_expansion, se_min_expansion, se_max_expansion FROM yaqds_npc_loottable WHERE nt_level >= 10");

      foreach ($npcLootTables as $entry => $entryInfo) {
         $name        = $entryInfo['nt_name'];
         $lootTableId = $entryInfo['nt_loottable_id'];
         $zone        = $entryInfo['s2_zone'];
         $s2MinExp    = $entryInfo['s2_min_expansion'];
         $s2MaxExp    = $entryInfo['s2_max_expansion'];
         $seMinExp    = $entryInfo['se_min_expansion'];
         $seMaxExp    = $entryInfo['se_max_expansion'];
         
         list($minExp,$maxExp) = explode('-',$this->calculateExpansion($s2MinExp,$s2MaxExp,$seMinExp,$seMaxExp));

         $cleanName = $this->cleanName($name);
         $cleanName = preg_replace('/^(a|an|the)\s+(.*)$/i','$2, $1',$cleanName);

         $index = sprintf("%s.%d",strtolower($cleanName),$lootTableId);
         $hash  = hash("crc32",$index);

         if (!isset($return['data'][$index])) {
            $return['data'][$index] = array(
               'hash'          => $hash,
               'raw_name'      => $name,
               'name'          => $cleanName,
               'min_expansion' => $minExp,
               'max_expansion' => $maxExp,
               'loottable_id'  => $lootTableId,
            );
         }

         $return['data'][$index]['zone'][$zone] = true;

         $return['lookup'][$hash] = $index;
      }

      if (array_key_exists('sort',$options) && $options['sort']) { ksort($return['data']); }

      return $return;
   }

   public function getRuleInfoByName($ruleName)
   {
      $this->debug(8,"called");

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      if (!preg_match('/^[\w\:]+$/',$ruleName)) { $this->error('invalid ruleName provided'); return false; }

      return $this->main->db($this->dbName)->query("SELECT * FROM rule_values WHERE rule_name = '$ruleName'",array('single' => true));
   }

   public function getItemInfoById($itemId)
   {
      $this->debug(8,"called");

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      if (!preg_match('/^\d+$/',$itemId)) { $this->error('invalid itemId provided'); return false; }

      return $this->main->db($this->dbName)->query("SELECT * FROM items WHERE id = $itemId",array('single' => true));
   }

   public function getZoneInfoByName($zoneName)
   {
      $this->debug(8,"called");

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneName provided'); return false; }

      return $this->main->db($this->dbName)->query("SELECT * FROM zone WHERE short_name = '$zoneName'",array('single' => true));
   }

   public function getZones($keyId = null, $columns = null, $expansion = null)
   {
      $this->debug(8,"called");

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      if (!is_null($expansion) && !preg_match('/^[\d\.]+$/',$expansion)) { $this->error('invalid expansion provided'); return false; }

      if (is_null($keyId)) { $keyId = 'zoneidnumber'; }
      if (is_null($columns)) { $columns = '*'; }

      if (!is_array($columns)) { $columns = array($columns); }
 
      $columnList = implode(', ',$columns);

      $query = "SELECT $columnList \n".
               "FROM zone \n".
               ((is_null($expansion)) ? '' : "WHERE expansion <= $expansion").
               '';

      return $this->main->db($this->dbName)->query($query,array('index' => $keyId));
   }

   public function getSpawnGridsByZoneName($zoneName)
   {
      $this->debug(8,"called");

      $return = array();

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneId provided'); return false; }
   
      $query = "SELECT ge.*, concat(ge.gridid,'.',ge.zoneid,'.',ge.number) as keyid \n".
               "FROM grid_entries ge \n".
               "LEFT JOIN zone z ON z.zoneidnumber = ge.zoneid \n".
               "WHERE z.short_name = '$zoneName'";

      $gridList = $this->main->db($this->dbName)->query($query,array('index' => 'keyid'));
   
      if (!$gridList) { return $return; }
   
      foreach ($gridList as $keyId => $gridInfo) {
         $return[$gridInfo['gridid']][$gridInfo['number']] = $gridInfo;
      }
   
      return $return;
   }

   public function getMapSpawnInfoByZoneName($zoneName, $zoneFloor = null, $zoneCeil = null, $expansion = null)
   {
      $this->debug(8,"called");

      if ($this->main->connectDatabase($this->dbName) === false) { $this->error('database not available: '.$this->main->error()); return false; }

      // Make sure these values are sanitized
      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneName provided'); return false; }
   
      if (!is_null($zoneFloor) && !preg_match('/^[\d\-]+$/',$zoneFloor)) { $this->error('invalid zoneFloor provided'); return false; }
      if (!is_null($zoneCeil) && !preg_match('/^[\d\-]+$/',$zoneCeil)) { $this->error('invalid zoneCeil provided'); return false; }
      if (!is_null($expansion) && !preg_match('/^[\d\.]+$/',$expansion)) { $this->error('invalid expansion provided'); return false; }

      $anyExpansion = $this->forceExpansion('any');

      $query = "SELECT concat(se.spawngroupID,'.',se.npcID,'.',s2.x,'.',s2.y) as keyid, z.short_name, z.zoneidnumber as zoneID, nt.name, nt.level, nt.maxlevel, sg.id as sgID, \n".
               "       nt.id as npcID, s2.min_expansion as spawnMinEx, s2.max_expansion as spawnMaxEx, se.min_expansion as entryMinEx, se.max_expansion as entryMaxEx, \n".
               "       se.chance, s2.x, s2.y, s2.z, s2.heading, s2.pathgrid as gridID, sg.name as sgName, sg.min_x as sgMinX, sg.min_y as sgMinY, ". 
               "       sg.max_x as sgMaxX, sg.max_y as sgMaxY \n".
               "FROM spawn2 s2 \n".
               "LEFT JOIN spawngroup sg ON s2.spawngroupID = sg.id \n".
               "LEFT JOIN spawnentry se ON se.spawngroupID = sg.id \n".
               "LEFT JOIN npc_types nt  ON nt.id = se.npcID \n".
               "LEFT JOIN zone z        ON z.short_name = s2.zone \n".
               "WHERE z.short_name = '$zoneName' \n".
               ((is_null($expansion)) ? '' :
               "AND   (((se.min_expansion <= $expansion or se.min_expansion = $anyExpansion) and (se.max_expansion >= $expansion or se.max_expansion = $anyExpansion)) \n".
               "       AND ((s2.min_expansion <= $expansion or s2.min_expansion = $anyExpansion) and (s2.max_expansion >= $expansion or s2.max_expansion = $anyExpansion))) \n".
               "AND z.expansion <= $expansion \n").
               "AND nt.bodytype < 64 \n".
               ((is_null($zoneFloor)) ? '' : "AND s2.z >= $zoneFloor \n").
               ((is_null($zoneCeil)) ? '' : "AND s2.z <= $zoneCeil \n").
               "ORDER BY sg.id, s2.x, s2.y, s2.z";
      
      return $this->main->db($this->dbName)->query($query);
   }

   public function cleanName($name)
   {
      return preg_replace('/_/',' ',preg_replace("/[^a-z0-9\'_]+/i",'',$name));
   }

   public function calculateExpansion($s2Min = -1, $s2Max = -1, $seMin = -1, $seMax = -1)
   {
      $format = '%1.1f-%1.1f';

      if ($s2Min == -1 && $s2Max == -1) { return sprintf($format,$seMin,$seMax); }

      return sprintf($format,$s2Min,$s2Max);
   }
}