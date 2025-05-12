<?php
include_once 'yaqds-api-init.php';

include_once 'main.class.php';
include_once 'spell.class.php';
include_once V1_BASEDIR.'/models/defaultmodel.class.php';
include_once V1_BASEDIR.'/models/decodemodel.class.php';
include_once V1_BASEDIR.'/models/datamodel.class.php';
include_once V1_BASEDIR.'/models/spellmodel.class.php';

$main = new Main([
   'fileDefines'    => null,
   'debugLevel'     => 0,
   'debugBuffer'    => true,
   'debugLogDir'    => V1_LOGDIR,
   'errorReporting' => false,
   'sessionStart'   => false,
   'memoryLimit'    => '256M',
   'sendHeaders'    => false,
   'database'       => false,     
   'dbConfigDir'    => APP_CONFIGDIR,
   'dbConfigFile'   => APP_CONFIGDIR.'/db.conf',
   'fileDefine'     => APP_CONFIGDIR.'/defines.json',
   'dbDefine'       => 'MY_%',
   'input'          => false,
   'html'           => false,
   'adminlte'       => false,
]);

$apiOptions = ['baseUrl' => MY_API_URL, 'authToken' => MY_API_AUTH_TOKEN];

if (!$main->buildClass('api','MyAPI',$apiOptions,'myapi.class.php')) { $main->debug(0,"API not available"); exit; }
if (!$main->buildClass('apicore','ApiCore',$main->db(),'apicore.class.php')) { $main->debug(0,"API Core not available"); exit; }

// Our default database is the API database, so create another connection to the Data database
$main->connectDatabase('yaqds','db.yaqds.conf');

/** @var MyAPI $api */
$api = $main->obj('api');

$spellModel = new SpellModel($main->debug,$main,['dbName' => 'yaqds']);

$result    = $api->v1GetSpells('all');
$spellList = $result['data'] ?? [];

$return = [];

foreach ($spellList as $spellId => $spellData) {
   if (is_array($spellData)) { $spellData = array_change_key_case($spellData); }

   $spell = new Spell($main->debug,['data' => $spellData]);

   
   $return[$spellId] = ['name' => $spellData['name'], 'data' => $spellModel->createSpellDescription($spell)];
}

print json_encode($return,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

?>
<?php

?>