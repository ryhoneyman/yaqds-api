<?php

include_once 'spell.class.php';
include_once 'format.class.php';

/**
 * SpellModel
 */
class SpellModel extends DefaultModel
{ 
   protected $decodeModel = null;
   protected $dataModel   = null;
   protected $format      = null;

   public function __construct($debug = null, $main = null, $options = null)
   {
      parent::__construct($debug,$main,$options);

      $this->decodeModel = new DecodeModel($debug,$main,$options);
      $this->dataModel   = new DataModel($debug,$main,$options);
      $this->format      = new LWPLib\Format($debug);
   }

   public function getAll($fields = null)
   {
      if (is_null($fields)) { $fields = 'id,name'; }

      $statement = "SELECT $fields FROM spells_new";

      $result = $this->api->v1DataProviderBindQuery($this->dbName,$statement);

      if ($result === false) { $this->error = $this->api->error(); return false; }

      return (isset($result['data']['results'])) ? $result['data']['results'] : null;
   }

   public function getSpellById($spellId)
   {
      $spellData = $this->getSpellDataById($spellId);

      if ($spellData === false) { return false; }
      if (!$spellData)          { return null; }

      $return = new Spell($this->debug,['data' => $spellData]);

      return $return;
   }

   public function getSpellDataById($spellId)
   {
      $statement = "SELECT * FROM spells_new where id = ?";
      $types     = 'i';
      $data      = [(int)$spellId];

      $result = $this->api->v1DataProviderBindQuery($this->dbName,$statement,$types,$data,['single' => true]);

      if ($result === false) { $this->error = $this->api->error(); return false; }
 
      if (!isset($result['data']['results'])) { return null; }
 
      $spellData = $result['data']['results'];
 
      if (is_array($spellData)) { $spellData = array_change_key_case($spellData); }  

      return $spellData;
   }

   public function getSpellEffectById($spellId)
   {
      $spell = $this->getSpellById($spellId);
   
      if (!$spell->data) { return null; }

      $result = [];

      $keyMap = [
         'effectid'           => 'id',
         'effect_base_value'  => 'base',
         'effect_limit_value' => 'limit',
         'max'                => 'max',
         'formula'            => 'formula'
      ];

      foreach ($spell->data as $spellKey => $spellValue) {
         if (preg_match('/^(effectid|effect_base_value|effect_limit_value|max|formula)(\d+)$/i',$spellKey,$match)) {
            $spellEffectKey = strtolower($match[1]);
            $spellEffectPos = $match[2];

            if ($spell->data['effectid'.$spellEffectPos] == 254) { continue; }

            $result['raw'][$spellEffectPos][$keyMap[$spellEffectKey] ?: $spellEffectKey] = $spellValue; 
         }
      }

      return $result;
   }

   /**
    * searchByName
    *
    * @return mixed
    */
    public function searchByName($name, $like = false, $limit = null): mixed
    {
       if (empty($name)) { return null; }
 
       if (is_null($limit)) { $limit = 50; }
 
       $statement = "SELECT id, name FROM spells_new where name ".(($like) ? "like ?" : " = ?")." LIMIT $limit";
  
       $result = $this->api->v1DataProviderBindQuery($this->dbName,$statement,'s',($like) ? ["%$name%"] : ["$name"]);
  
       if ($result === false) { $this->error = $this->api->error(); return false; }
  
       return (isset($result['data']['results'])) ? $result['data']['results'] : null;
    }

   public function createSpellDescription($spell)
   {
      $return = [];

      if (!$spell) { return $return; }

      $format = [
         "{{SKILL}}",
         "{{MANA COST}}",
         "{{CAST TIME}}",
         "{{RECAST TIME}}",    
         "{{TARGET}}",
         "{{DURATION}}",
         "{{RESIST}}",
         "{{RANGE}}",
         "{{AE RANGE}}",
         "{{CLASSES}}",
         "_EMPTY_",
         "_EFFECT_",
         "_EMPTY_",
         "{{CAST ON YOU}}",
         "{{CAST ON OTHER}}",
      ];

      $values = [];

      $spellData = $this->processSpellData($spell);

      $effectValues   = $spellData['_effectValues'];
      $classList      = $spellData['_classList'];
      $castTime       = $spellData['_castTime'];
      $recastTime     = $spellData['_recastTime'];
      $recoveryTime   = $spellData['_recoveryTime'];
      $duration       = $spellData['_duration'];
      $targetTypeName = $spellData['_targetTypeName'];
      $manaCost       = $spellData['mana'];
      $resistType     = $spellData['resisttype'];
      $resistDiff     = $spellData['ResistDiff'];
   
      $effectDisplayList = [];
      foreach ($effectValues as $effectPos => $effectInfo) { 
         $effectDisplayList[$effectPos] = sprintf(" %2d: %s",$effectPos,$this->createFormattedSpellEffectText($spellData,$effectInfo));
      }
   
      $values['MANA COST'] = $manaCost ? sprintf("Mana Cost: %s",$manaCost) : '';
      $values['CAST TIME'] = sprintf("Cast Time: %s",$castTime);
      $values['RECAST TIME'] = $recastTime ? sprintf("Recast Time: %s",$recastTime) : '';
      $values['RECOVERY TIME'] = $recoveryTime ? sprintf("Recovery Time: %s",$recoveryTime) : '';
      $values['DURATION'] = $duration ? sprintf("Duration: %s",$duration) : '';
      $values['TARGET'] = sprintf("Target: %s",$targetTypeName);
      $values['RESIST'] = sprintf("Resist: %s%s",$this->decodeModel->decodeResistType($resistType),$resistDiff ? sprintf(" (%d)",$resistDiff) : '');
      $values['RANGE'] = $spellData['range'] ? sprintf("Range: %s",$spellData['range']) : '';
      $values['AE RANGE'] = $spellData['ae_range'] ? sprintf("AE Range: %s",$spellData['ae_range']) : '';
      $values['CAST ON YOU'] = $spellData['cast_on_you'] ? sprintf("On you: %s",$spellData['cast_on_you']) : '';
      $values['CAST ON OTHER'] = $spellData['cast_on_other'] ? sprintf("On others: Target %s",$spellData['cast_on_other']) : '';
      $values['CLASSES'] = $classList ? sprintf("Classes: %s",implode(' ', array_map(function ($key, $value) { return (($value == 254) ? $key : sprintf('%s(%s)',$key,$value)); }, array_keys($classList), $classList))) : '';
      $values['SKILL'] = sprintf("Skill: %s",preg_replace('/([a-z])([A-Z])/','$1 $2',$this->decodeModel->decodeSkill($spellData['skill'])));

      $return = [];

      foreach ($format as $line) {
         if ($line == '_EMPTY_') { 
            $return[] = ''; 
         }
         else if ($line == '_EFFECT_') { 
            foreach ($effectDisplayList as $effectLine) {$return[] = $effectLine; }
         }    
         else {
            $lineValue = trim(preg_replace('/[\ \t]+/',' ',$this->main->replaceValues($line,$values,"\n")));
            if ($lineValue) { $return[] = $lineValue; }
         }  
      }
   
      return $return;
   }

   public function createFormattedSpellEffectText($spellData, $effectInfo) 
   {
      $availClasses = [
         'data'   => $this->dataModel,
         'decode' => $this->decodeModel,
         'spell'  => $this,
         'raw'    => true,    
         'map'    => true, 
         'values' => true,  
      ];

      $spellId     = $spellData['id'];
      $hasDuration = $spellData['hasDuration'];

      $effectId       = $effectInfo['id'];
      $minValue       = $effectInfo['minValue'];
      $maxValue       = $effectInfo['maxValue'];
      $minLevel       = $effectInfo['minLevel'];
      $maxLevel       = $effectInfo['maxLevel'];
      $splurtVal      = $effectInfo['splurtVal'];
      $effectDisplay  = $effectInfo['effectExceptions'][$spellId] ?: $effectInfo['effectDisplay'];
      $effectName     = $effectInfo['effectName'];
      $textFormat     = $effectDisplay['format'] ?: 0;
      $textLabel      = $effectDisplay['label'];
      $textValues     = $effectDisplay['values'] ?: [];
      $allowDuration  = $effectDisplay['allowDuration'] ? true : false;
      $reverseAdjust  = $effectDisplay['reverseAdjust'] ? true : false;
      $map            = $effectDisplay['map'] ?? [];

      foreach ($spellData as $spellDataKey => $spellDataValue) {
         $values[sprintf("spell:%s",$spellDataKey)] = $spellDataValue;
      }

      $values = array_merge($values,[
         'effect:id'        => $effectId,
         'effect:label'     => $textLabel,
         'effect:formula'   => $effectInfo['formula'],
         'effect:base'      => $effectInfo['base'],
         'effect:max'       => $effectInfo['max'],
         'effect:limit'     => $effectInfo['limit'],
         'effect:minLevel'  => $minLevel,
         'effect:maxLevel'  => $maxLevel,
         'effect:splurtVal' => $splurtVal,
         'effect:units'     => '',
      ]);

      $adjustPos = ($reverseAdjust) ? 'Decrease' : 'Increase';
      $adjustNeg = ($reverseAdjust) ? 'Increase' : 'Decrease';

      $values['effect:adjust']   = ($minValue < 0) ? $adjustNeg : $adjustPos; 
      $values['effect:minValue'] = abs($minValue); 
      $values['effect:maxValue'] = ($minValue > 0 && $maxValue <= 0) ? $maxValue : abs($maxValue); 

      if ($splurtVal) { $values['effect:splurtLabel'] = ($minValue > 0 && $minValue > $maxValue) ? 'subtract' : 'add'; }

      foreach ($textValues as $valueKey => $valueString) {
         //print "processing: $valueString\n";
         list($valueClass,$valueFunction,$valueParams,$valueIndex) = explode('^',$this->main->replaceValues($valueString,$values));
         //print "processed: $valueClass,$valueFunction,$valueParams,$valueIndex\n";

         if (!isset($availClasses[$valueClass])) { continue; }

         if ($valueClass == 'raw')         { $callValue = $this->processValue($valueFunction); }
         else if ($valueClass == 'map')    { $callValue = $this->processValue($map[$valueFunction]) ?: 'Unknown'; }
         else if ($valueClass == 'values') { $callValue = $this->processValue($values[$valueFunction]); }
         else {
            $valueParamList = explode(',',$valueParams);
            $valueArray     = [];

            foreach ($valueParamList as $valueParam) {
               $valueArray[] = $this->processValue($valueParam);
            }

            $callResult = call_user_func_array([$availClasses[$valueClass],$valueFunction],$valueArray);
            $callValue  = (($valueIndex) ? $callResult[$valueIndex] : $callResult) ?: 'Unknown';
         }

         $values[$valueKey] = $callValue;
      }

      $effectFormat = '';
      //$effectFormat = "i:{{effect:id}} f:{{effect:formula}} b:{{effect:base}} m:{{effect:max}} l:{{effect:limit}}: ";

      switch ($textFormat) {
         // Generic effect with min/max, optionally over time, optional qualifier (such as % or units) - accounts for splurt decay/cumulative
         case 1: {
            $effectFormat .= "{{effect:adjust}} {{effect:label}} by {{effect:minValue}}{{effect:units}}";

            if ($minValue != $maxValue) { 
               if ($splurtVal) { $effectFormat .= " and {{effect:splurtLabel}} {{effect:splurtVal}} per tick, ending at {{effect:maxValue}}"; }
               else            { $effectFormat .= " (L{{effect:minLevel}}) to {{effect:maxValue}}{{effect:units}} (L{{effect:maxLevel}})"; }
            }
            if ($hasDuration && $allowDuration) { $effectFormat .= ' per tick'; }

            break;
         }
         // ArmorClass
         case 2: {
            $acModifier             = (1000 / 847);
            $minClientClothValue    = floor(floor($minValue / 3) * $acModifier);
            $minClientNonClothValue = floor(floor($minValue / 4) * $acModifier);
            $maxClientClothValue    = floor(floor($maxValue / 3) * $acModifier);
            $maxClientNonClothValue = floor(floor($maxValue / 4) * $acModifier);

            $values['effect:minClientClothValue']    = $minClientClothValue;
            $values['effect:minClientNonClothValue'] = $minClientNonClothValue;
            $values['effect:maxClientClothValue']    = $maxClientClothValue;
            $values['effect:maxClientNonClothValue'] = $maxClientNonClothValue;
         
            $formats = [
               'silk'    => "Cloth Casters by {{effect:minClientClothValue}}",
               'nonsilk' => "Everyone Else by {{effect:minClientNonClothValue}}",
            ];

            if ($minClientClothValue != $maxClientClothValue)       { $formats['silk']    .= " (L{{effect:minLevel}}) to {{effect:maxClientClothValue}} (L{{effect:maxLevel}})"; }
            if ($minClientNonClothValue != $maxClientNonClothValue) { $formats['nonsilk'] .= " (L{{effect:minLevel}}) to {{effect:maxClientNonClothValue}} (L{{effect:maxLevel}})"; }

            if ($hasDuration && $allowDuration) { 
               foreach (array_keys($formats) as $formatType) { $formats[$formatType] .= ' per tick'; }
            }

            $effectFormat .= "{{effect:adjust}} {{effect:label}} for ".implode(', ',$formats);

            break;
         }
         // Generic up to max
         case 3: {
            $effectFormat .= "{{effect:label}} up to {{effect:max}}";
            break;
         }
         // Generic percentage
         case 4: {
            $effectFormat .= "{{effect:label}} ({{effect:maxValue}}%)";
            break;
         }
         // Teleportation
         case 5: {
            $effectFormat .= "{{spell:targetName}}{{effect:label}} to {{spell:zoneName}} ({{spell:effect_base_value1}},{{spell:effect_base_value2}},{{spell:effect_base_value3}})";
            $targetType   = $this->decodeModel->decodeSpellTargetType($spellData['targettype']);

            if (preg_match('/^same$/i',$spellData['teleport_zone'])) {
               $values['spell:zoneName'] = 'Current Zone';
            }
            else {
               $zoneInfo                   = $this->dataModel->getZoneInfoByName($spellData['teleport_zone']);
               $values['spell:zoneName']   = $zoneInfo['long_name'] ?: 'Unknown Zone';
            }
            $values['spell:targetName'] = ($targetType == 'GroupTeleport') ? 'Group ' : (($targetType == 'Self') ? 'Self ' : ''); 
            break;
         }
         // Aggro/Assist Radius
         case 6: {
            // Harmony uses a special flag to prevent level restrictions
            $ruleResults     = $this->dataModel->getRuleInfoByName('AlKabor:EnableLatePlanesHarmonyNerf');
            $isHarmonyNerfed = ($ruleResults['rule_value'] == 'false') ? false : true;
            $isHarmony       = ($spellData['name'] == 'Harmony') ? true : false;

            $effectFormat .= "Change {{effect:label}} to {{effect:base}}";

            if ($effectInfo['max'] > 0 && (!$isHarmony || ($isHarmony && $isHarmonyNerfed))) { $effectFormat .= " up to L{{effect:max}}"; }

            break;
         }
         // Adjust percent by skill
         case 7: {
            $values['effect:skill'] = ($values['effect:limit'] == -1) ? 'All Skills' : $this->decodeModel->decodeSkill($values['effect:limit']); 

            $effectFormat .= "{{effect:adjust}} {{effect:label}} by {{effect:minValue}}% for {{effect:skill}}";

            break;
         }
         // Effects that can cause or remove impact, such as Blindness
         case 8: {
            $beneficial = ($spellData['goodEffect'] || in_array($this->decodeModel->decodeSpellTargetType($spellData['targettype']),['GroupV1','GroupTeleport','GroupV2','BardAE'])) ? true : false;

            $values['effect:adjust'] = ($beneficial) ? 'Remove ' : '';

            $effectFormat .= "{{effect:adjust}}{{effect:label}} (Strength: {{strength}})";

            break;
         }
         // Effects with duration optionally up to a certian level
         case 9: {
            $maxLevel           = $values['effect:max'] ?: null;
            $values['duration'] = $this->format->formatDuration($values['effect:base']/1000);

            $effectFormat .= "{{effect:label}} ({{duration}})";

            if ($maxLevel) { $effectFormat .= " up to L$maxLevel"; }

            break;
         }
         // Generic up to max level
         case 10: {
            if ($effectName == 'SE_Fear' && $values['effect:max'] == 0) { $values['effect:max'] = 52; }

            $effectFormat .= "{{effect:label}} up to L{{effect:max}}";

            break;
         }
         // Summoned Pets
         case 11: {
            $petInfo = $this->dataModel->getPetInfoBySpellId($spellId);

            foreach ($petInfo as $petKey => $petValue) { $values["pet:$petKey"] = $petValue; }

            $effectFormat .= "{{effect:label}}: L{{pet:level}} {{pet:name}}";

            break;
         }
         // Slot conditional
         case 12: {
            $newEffect      = $values['effect:base'] ? $this->decodeModel->decodeSpellEffect($values['effect:base']) : null;
            $newEffectLabel = (!is_null($newEffect)) ? $newEffect['display']['label'] : '';
            $newSlot        = $values['effect:formula'] ? $values['effect:formula'] - 201 + 1 : 0;

            $effectFormat .= sprintf("{{effect:label}} if Slot %s is '%s' and < {{effect:max}}",$newSlot,$newEffectLabel);

            break;
         }
         // Percent of 100
         case 13: {
            // Assume min/max are either both over 100 or both under 100
            $offsetMinValue  = ($minValue - 100);
            $offsetMaxValue  = ($maxValue - 100);
            $adjustLabel     = ($maxValue > 100) ? 'Increase' : 'Decrease';
            $valueDesc       = ($offsetMinValue != $offsetMaxValue) ? sprintf("%s%% (L%s) to %s%% (L%s)",abs($offsetMinValue),$minLevel,abs($offsetMaxValue),$maxLevel) : sprintf("%s%%",abs($offsetMaxValue));

            $effectFormat .= sprintf("%s {{effect:label}} by %s",$adjustLabel,$valueDesc);

            break;
         }
         // Limits
         // Supports:  ADJUST LABEL | ADJUST LABEL (LIMIT) | LABEL (LIMIT)
         case 14: {
            if (!$values['effect:limitadjust']) {
               $values['effect:limitadjust'] = ($values['inclusion']) ? (($values[$values['inclusion']] >= 0) ? 'Include ' : 'Exclude ') : '';
            }
            $values['effect:limit'] = ($values['limit']) ? sprintf(" (%s%s)",$values['limit'],$values['units'] ?? '') : '';

            $effectFormat .= "Limit: {{effect:limitadjust}}{{effect:label}}{{effect:limit}}";
            break;
         }

         // Custom format or generic label only
         default: $effectFormat .= ($textFormat) ? $textFormat : "{{effect:label}}";
      }

      $effectFormat = $this->main->replaceValues($effectFormat,$values) ?: sprintf("Missing: %s/%s",$effectId,$effectName);

      return $effectFormat;
   }

   public function processValue($value)
   {
      if (preg_match('/^(\S+):(.*)$/',$value,$match)) { 
         $action   = $match[1];
         $rawValue = $match[2];

         if (preg_match('/^abs$/i',$action))        { $value = abs($rawValue); }
         else if (preg_match('/^mstos$/i',$action)) { $value = sprintf("%1.1f",$rawValue/1000); }
         else if (preg_match('/^neg$/i',$action))   { $value = -$rawValue; }
      }

      return $value;
   }

   public function processSpellData($spell)
   {
      $return = [];

      if (!$spell) { return $return; }

      $spellData = $spell->data;

      $maxServerLevel = 65;
      $tickInSecs     = 6;

      $effectList = $this->decodeModel->decodeSpellEffectList($spellData);
      $classList  = $this->decodeModel->decodeSpellClasses($spellData);
   
      if (!$classList) { $classList = ['None' => 0]; } 
   
      $minLevel = min($classList);
      $maxLevel = null;
   
      $buffFormula   = $spellData['buffdurationformula'];
      $buffDuration  = $spellData['buffduration'];
      $targetType    = $spellData['targettype'];
      $castTime      = $this->format->formatDurationShort($spellData['cast_time'] / 1000,['fractional' => true]);
      $recoveryTime  = $this->format->formatDurationShort($spellData['recovery_time'] / 1000,['fractional' => true]);
      $recastTime    = $this->format->formatDurationShort($spellData['recast_time'] / 1000,['fractional' => true]);
      
      $minDuration  = null;
      $maxDuration  = null;
      $hasDuration  = ($buffFormula == 0 && $buffDuration == 0) ? false : true;
      $effectValues = [];
   
      $spell->property('buffduration',$buffDuration);
      $spell->property('buffdurationformula',$buffFormula);
   
      // Process duration of spell
      if (!$hasDuration) { $duration = 'Instant'; }
      else {
         $minDuration      = $spell->calculateBuffDurationFormula($minLevel,$buffFormula,$buffDuration);
         $maxDuration      = null;
         $maxDurationLevel = $minLevel;
   
         for ($checkLevel = $maxServerLevel; $checkLevel >= $minLevel; $checkLevel--) {
            $checkDuration = $spell->calculateBuffDurationFormula($checkLevel,$buffFormula,$buffDuration);
            if ($maxDuration && $maxDuration != $checkDuration) { break; }
            $maxDuration      = $checkDuration;
            $maxDurationLevel = $checkLevel;
         }
         
         $duration = ($minDuration == $maxDuration) ? 
            (($minDuration == 0) ? 'Instant' :sprintf("%d ticks (%s)",$minDuration,$this->format->formatDurationShort($minDuration*$tickInSecs))) :
               sprintf("%s ticks [%s] (L%d) to %s ticks [%s] (L%d)",
                  $minDuration,$this->format->formatDurationShort($minDuration*$tickInSecs),$minLevel,
                  $maxDuration,$this->format->formatDurationShort($maxDuration*$tickInSecs),$maxDurationLevel,
               );
      }
   
      // Process levels for effect data
      foreach ($effectList as $effectPos => $effectInfo) {
         $effectId       = $effectInfo['id'];
         $effectFormula  = $effectInfo['formula'];
         $effectBase     = $effectInfo['base'];
         $effectMax      = $effectInfo['max'];
         $minValue       = $spell->calculateEffectValueFormula($effectFormula,$effectBase,$effectMax,$minLevel,$maxDuration);  
         $maxValue       = $spell->calculateEffectValueFormula($effectFormula,$effectBase,$effectMax,$maxServerLevel,1);
   
         $effectInfo['splurtVal'] = $this->decodeModel->decodeSplurtValues($effectFormula);
         $effectInfo['minValue']  = $minValue;
         $effectInfo['maxValue']  = $maxValue;
   
         //for ($checkLevel = $minLevel; $checkLevel <= $maxServerLevel; $checkLevel++) {
         //   $checkValue = $spell->calculateEffectValueFormula($effectFormula,$effectBase,$effectMax,$checkLevel);
         //   if (($checkValue > 0 && $checkValue >= $maxValue) || ($checkValue < 0 && $checkValue <= $maxValue)) { $maxLevel = $checkLevel; break; }
         //}
   
         for ($checkLevel = $maxServerLevel; $checkLevel >= $minLevel; $checkLevel--) {
            $checkValue = $spell->calculateEffectValueFormula($effectFormula,$effectBase,$effectMax,$checkLevel);
            if ($maxValue && $maxValue != $checkValue) { break; }
            $maxLevel = $checkLevel;
         }
   
         $effectInfo['minLevel'] = $minLevel;
         $effectInfo['maxLevel'] = $maxLevel;
   
         $effectValues[$effectPos] = $effectInfo;
      }

      $spellData['_effectList']     = $effectList;
      $spellData['_effectValues']   = $effectValues;
      $spellData['_classList']      = (isset($classList['None'])) ? [] : $classList;
      $spellData['_minLevel']       = $minLevel;
      $spellData['_maxLevel']       = $maxLevel;
      $spellData['_duration']       = $duration;
      $spellData['_castTime']       = $castTime;
      $spellData['_recastTime']     = $recastTime;
      $spellData['_recoveryTime']   = $recoveryTime;
      $spellData['_minDuration']    = $minDuration;
      $spellData['_maxDuration']    = $maxDuration;
      $spellData['_hasDuration']    = $hasDuration;
      $spellData['_maxServerLevel'] = $maxServerLevel;
      $spellData['_tickInSecs']     = $tickInSecs;
      $spellData['_targetTypeName'] = $this->decodeModel->decodeSpellTargetType($targetType);
      
      return $spellData;
   }
}
