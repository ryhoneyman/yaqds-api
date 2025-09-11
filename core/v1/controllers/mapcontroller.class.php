<?php

class MapController extends DefaultController
{
   protected $mapModel  = null;
   protected $dbName    = 'yaqds';
   
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
      $this->mapModel = new MapModel($debug,$main,['dbName' => $this->dbName]); 

      // If the model isn't ready we need to flag the controller as not ready and set status
      if (!$this->mapModel->ready)  { $this->notReady($this->mapModel->error); return; }
   }

   public function getMapList($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $zones = $this->mapModel->getZones('short_name','short_name,long_name,zoneidnumber');

      return $this->standardOk($zones);
   }

   public function renderMapv1($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $zoneName = $filterData['name'];

      $mapLineData = $this->mapModel->getMapLineDataOptimizedByZoneName($zoneName);
      $spawnInfo   = $this->mapModel->getMapSpawnInfoByZoneName($zoneName);
      $gridInfo    = $this->mapModel->getSpawnGridsByZoneName($zoneName);

      $mapData = $mapLineData;

      $spawnData = [];

      foreach ($spawnInfo as $keyId => $entryInfo) {
         $entityX      = $entryInfo['x'];
         $entityY      = $entryInfo['y'];
         $entityZ      = $entryInfo['z'];
         $spawnXYZ     = sprintf("%d_%d_%d",$entityY,$entityX,$entityZ);
         $entityLvl    = $entryInfo['level'];
         $entityMaxLvl = $entryInfo['maxlevel'] ?: $entityLvl;
         $groupId      = $entryInfo['sgID'];
         $groupName    = $entryInfo['sgName'];
         $gridId       = $entryInfo['gridID'];
         $entityName   = str_replace(['#','_'],['',' '],$entryInfo['name']);
         $entityId     = $entryInfo['npcID'];

         $spawnData[$spawnXYZ][$groupId]['chance.total'] += $entryInfo['chance'];

         $spawnData[$spawnXYZ][$groupId]['pos'] = [
            'x' => -$entityX, // EQ maps are inverted axis
            'y' => -$entityY, // EQ maps are inverted axis
            'z' => $entityZ,
         ];

         $spawnData[$spawnXYZ][$groupId]['info']['sgID']   = $groupId;
         $spawnData[$spawnXYZ][$groupId]['info']['sgName'] = $groupName;

         $spawnData[$spawnXYZ][$groupId]['spawn'][] = [
            'chance'   => $entryInfo['chance'],
            'id'       => $entityId,
            'name'     => $entityName,
            'level'    => $entityLvl,
            'maxlevel' => $entityMaxLvl,
         ];

         if ($gridId) { $spawnData[$spawnXYZ][$groupId]['info']['gridID'] = $gridId; }
         else if ($entryInfo['sgMinX'] || $entryInfo['sgMinY'] || $entryInfo['sgMaxX'] || $entryInfo['sgMaxY']) { 
            $topLeftX = -$entryInfo['sgMaxX'];  // EQ waypoints are inverted axis
            $topLeftY = -$entryInfo['sgMaxY'];  // EQ waypoints are inverted axis
            $botLeftX = -$entryInfo['sgMinX'];  // EQ waypoints are inverted axis
            $botLeftY = -$entryInfo['sgMinY'];  // EQ waypoints are inverted axis

            $spawnData[$spawnXYZ][$groupId]['info']['gridID'] = sprintf('%d^%d^%d^%d',$topLeftX,$topLeftY,$botLeftX,$botLeftY);
         }
      }

      foreach ($spawnData as $spawnXYZ => $groupData) {
         foreach ($groupData as $groupId => $groupInfo) {
            $chanceTotal = $groupInfo['chance.total'];
            $pos         = $groupInfo['pos'];
            $info        = $groupInfo['info'];

            $spawnList = [];
            foreach ($groupInfo['spawn'] as $idx => $spawnEntry) {
               $chanceMult = ($chanceTotal) ? (100/$chanceTotal) : 1;
               $spawnLevel = ($spawnEntry['level'] == $spawnEntry['maxlevel']) ? sprintf("L%s",$spawnEntry['level']) : sprintf("L%s-%s",$spawnEntry['level'],$spawnEntry['maxlevel']);
               
               $spawnList[] = sprintf("%d%% %s (%s)",$spawnEntry['chance'] * $chanceMult,$spawnEntry['name'],$spawnLevel);
            }

            $mapData[] = sprintf("N %d, %d, %d, 255, 0, 0, %s-%s, %s, %s",
               $pos['x'],$pos['y'],$pos['z'],$info['sgID'],$info['sgName'],
               implode("|", $spawnList),$info['gridID'] ?? '0'
            );
         }
      }

      if ($gridInfo) {
         $gridList = [];
         foreach ($gridInfo as $keyId => $gridData) {
            $gridList[$gridData['gridid']][$gridData['number']] = $gridData;
         }

         foreach ($gridList as $gridId => $gridEntries) {
            ksort($gridEntries,SORT_NUMERIC);
               
            $waypoints = array_values($gridEntries);
            
            // Create line segments between consecutive waypoints
            for ($i = 0; $i < count($waypoints) - 1; $i++) {
               $startPoint = $waypoints[$i];
               $endPoint   = $waypoints[$i + 1];
               
               // Add to map data as line segments
               $mapData[] = sprintf("P %d, %d, %d, %d, %d, %d, %d",
                  $gridId, -$startPoint['x'], -$startPoint['y'], $startPoint['z'],
                  -$endPoint['x'], -$endPoint['y'], $endPoint['z']
               );
            }
         }
      }

      return $this->standardOk(['zoneName' => $zoneName, 'map' => $mapData]);
   }
}