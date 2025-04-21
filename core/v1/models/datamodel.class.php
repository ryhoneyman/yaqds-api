<?php

/**
 * DataModel
 */
class DataModel extends DefaultModel
{ 
   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);
   }

   public function getPetInfoBySpellId($spellId)
   {
      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      $magicianPetTypeList = [
         'Fire'  => [626, 630, 634, 316, 399, 403, 395, 498, 571, 575, 622, 1673, 1677, 3322],
         'Air'   => [627, 631, 635, 317, 396, 400, 404, 499, 572, 576, 623, 1674, 1678, 3371],
         'Earth' => [624, 628, 632, 58, 397, 401, 335, 496, 569, 573, 620, 1675, 1671, 3324],
         'Water' => [625, 629, 633, 315, 398, 403, 336, 497, 570, 574, 621, 1676, 1672, 3320],
      ];

      $petInfo = $this->main->db()->bindQuery("SELECT r.name,nt.level,nt.race,nt.bodytype FROM spells_new sn LEFT JOIN pets p ON sn.teleport_zone = p.type ". 
                                      "LEFT JOIN npc_types nt ON nt.id = p.npcID LEFT JOIN races r ON r.id = nt.race WHERE sn.id = ?;",'i',array($spellId),array('single' => true));

      foreach ($magicianPetTypeList as $petType => $spellIdList) {
         if (in_array($spellId,$spellIdList)) { $petInfo['name'] = sprintf("%s %s",$petType,$petInfo['name']); }
      }

      return $petInfo;
   }

   public function getLootTableEntriesById($lootTableId)
   {
      $this->debug(8,"called");

      $return = array();

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      $entryList = $this->main->db()->bindQuery("SELECT *, concat(loottable_id,'^',lootdrop_id) as id FROM loottable_entries WHERE loottable_id = ?",'i',array($lootTableId),array('index' => 'id'));

      return $entryList;
   }

   public function getLootDropEntriesById($lootDropId)  
   {
      $this->debug(8,"called");

      $return = array();

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      $entryList = $this->main->db()->bindQuery("SELECT lde.item_id, i.name as item_name, lde.chance, lde.multiplier, ".
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

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      //$npcLootTables = $this->main->db()->query("SELECT distinct(concat(nt.name,'^',nt.loottable_id,'^',s2.zone,'^',s2.min_expansion,'-',s2.max_expansion,'^',se.min_expansion,'-',se.max_expansion)) as entry FROM npc_types nt LEFT JOIN spawnentry se ON nt.id = se.npcID LEFT JOIN spawn2 s2 ON se.spawngroupID = s2.spawngroupID WHERE nt.loottable_id > 0 and nt.level >= 10");
      
      $npcLootTables = $this->main->db()->query("SELECT id, nt_name, nt_loottable_id, s2_zone, s2_min_expansion, s2_max_expansion, se_min_expansion, se_max_expansion FROM yaqds_npc_loottable WHERE nt_level >= 10");

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

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      if (!preg_match('/^[\w\:]+$/',$ruleName)) { $this->error('invalid ruleName provided'); return false; }

      return $this->main->db()->query("SELECT * FROM rule_values WHERE rule_name = '$ruleName'",array('single' => true));
   }

   public function getItemInfoById($itemId)
   {
      $this->debug(8,"called");

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      if (!preg_match('/^\d+$/',$itemId)) { $this->error('invalid itemId provided'); return false; }

      return $this->main->db()->query("SELECT * FROM items WHERE id = $itemId",array('single' => true));
   }

   public function getZoneInfoByName($zoneName)
   {
      $this->debug(8,"called");

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneName provided'); return false; }

      return $this->main->db()->query("SELECT * FROM zone WHERE short_name = '$zoneName'",array('single' => true));
   }

   public function getZones($keyId = null, $columns = null, $expansion = null)
   {
      $this->debug(8,"called");

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      if (!is_null($expansion) && !preg_match('/^[\d\.]+$/',$expansion)) { $this->error('invalid expansion provided'); return false; }

      if (is_null($keyId)) { $keyId = 'zoneidnumber'; }
      if (is_null($columns)) { $columns = '*'; }

      if (!is_array($columns)) { $columns = array($columns); }
 
      $columnList = implode(', ',$columns);

      $query = "SELECT $columnList \n".
               "FROM zone \n".
               ((is_null($expansion)) ? '' : "WHERE expansion <= $expansion").
               '';

      return $this->main->db()->query($query,array('index' => $keyId));
   }

   public function getSpawnGridsByZoneName($zoneName)
   {
      $this->debug(8,"called");

      $return = array();

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

      if (!preg_match('/^\w+$/',$zoneName)) { $this->error('invalid zoneId provided'); return false; }
   
      $query = "SELECT ge.*, concat(ge.gridid,'.',ge.zoneid,'.',ge.number) as keyid \n".
               "FROM grid_entries ge \n".
               "LEFT JOIN zone z ON z.zoneidnumber = ge.zoneid \n".
               "WHERE z.short_name = '$zoneName'";

      $gridList = $this->main->db()->query($query,array('index' => 'keyid'));
   
      if (!$gridList) { return $return; }
   
      foreach ($gridList as $keyId => $gridInfo) {
         $return[$gridInfo['gridid']][$gridInfo['number']] = $gridInfo;
      }
   
      return $return;
   }

   public function getMapSpawnInfoByZoneName($zoneName, $zoneFloor = null, $zoneCeil = null, $expansion = null)
   {
      $this->debug(8,"called");

      if (!$this->main->connectDatabase() === false) { $this->error('database not available'); return false; }

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
      
      return $this->main->db()->query($query);
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