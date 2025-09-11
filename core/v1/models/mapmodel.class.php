<?php

/**
 * MapModel
 */
class MapModel extends DefaultModel
{  
   public function __construct($debug = null, $main = null, $options = null)
   {
      parent::__construct($debug,$main,$options);
   }
   
   public function getZones($keyId = null, $columns = null, $maxExpansion = null)
   {
      if (!is_null($maxExpansion) && !preg_match('/^[\d\.]+$/',$maxExpansion)) { $this->error('invalid maximum expansion provided'); return false; }

      if (is_null($keyId))   { $keyId = 'zoneidnumber'; }
      if (is_null($columns)) { $columns = '*'; }

      if (!is_array($columns)) { $columns = array($columns); }

      $columnList = implode(', ',$columns);

      $zoneQuery = "SELECT $columnList ".
                   "FROM zone ".
                   ((is_null($maxExpansion)) ? '' : "WHERE expansion <= $maxExpansion").
                   '';

      $result = $this->api->v1DataProviderBindQuery($this->dbName,$zoneQuery,null,null,['index' => $keyId]);

      if ($result === false) { $this->error = $this->api->error(); return false; }

      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }

   /**
    * getMapLineDataByZoneName
    *
    * @return array The map line data
    */
   public function getMapLineDataByZoneName(string $zoneName): array
   {
      $mapRawData  = file_get_contents(APP_CONFIGDIR."/maps/$zoneName.txt");
      $mapLineData = array_filter(explode("\r\n",$mapRawData));

      return $mapLineData;
   }

   /**
    * getMapLineDataOptimizedByZoneName
    *
    * @return array The map line data optimized
    */
   public function getMapLineDataOptimizedByZoneName(string $zoneName): array
   {
      $mapLineData       = $this->getMapLineDataByZoneName($zoneName);
      $processedLineData = [];

      foreach ($mapLineData as $lineData) {
         $parts = preg_split("/,\s*/",preg_replace('/^L\s+/','',$lineData));

         $processedLineData[] = vsprintf("L %d, %d, %d, %d, %d, %d, %d, %d, %d",$parts);
      }

      return $processedLineData;
   }

   public function getMapSpawnInfoByZoneName(string $zoneName): mixed
   {
      $spawnQuery = "SELECT concat(se.spawngroupID,'.',se.npcID,'.',s2.x,'.',s2.y) as keyid, z.short_name, z.zoneidnumber as zoneID, nt.name, nt.level, nt.maxlevel, sg.id as sgID, ".
                    "       nt.id as npcID, s2.min_expansion as spawnMinEx, s2.max_expansion as spawnMaxEx, se.min_expansion as entryMinEx, se.max_expansion as entryMaxEx, ".
                    "       se.chance, s2.x, s2.y, s2.z, s2.heading, s2.pathgrid as gridID, sg.name as sgName, sg.min_x as sgMinX, sg.min_y as sgMinY, ". 
                    "       sg.max_x as sgMaxX, sg.max_y as sgMaxY, 'spawn' as source ".
                    "FROM spawn2 s2 ".
                    "LEFT JOIN spawngroup sg ON s2.spawngroupID = sg.id ".
                    "LEFT JOIN spawnentry se ON se.spawngroupID = sg.id ".
                    "LEFT JOIN npc_types nt  ON nt.id = se.npcID ".
                    "LEFT JOIN zone z        ON z.short_name = s2.zone ".
                    "WHERE z.short_name = ? ".
                    "AND nt.bodytype < 64 ".
                    "ORDER BY sg.id, s2.x, s2.y, s2.z";

      $result = $this->api->v1DataProviderBindQuery($this->dbName,$spawnQuery,'s',[$zoneName]);

      if ($result === false) { $this->error = $this->api->error(); return false; }

      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }

   public function getSpawnGridsByZoneName(string $zoneName): mixed
   {
      $spawnQuery = "SELECT concat(ge.gridid,'.',ge.zoneid,'.',ge.number) as keyid , ge.* ".
                    "FROM grid_entries ge ".
                    "LEFT JOIN zone z ON z.zoneidnumber = ge.zoneid ".
                    "WHERE z.short_name = ?";

      $result = $this->api->v1DataProviderBindQuery($this->dbName,$spawnQuery,'s',[$zoneName]);

      if ($result === false) { $this->error = $this->api->error(); return false; }

      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }
}