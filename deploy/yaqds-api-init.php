<?php

define('APP_BASEDIR','/opt/yaqds-api');
define('APP_CONFIGDIR',APP_BASEDIR.'/etc');      // global configurations
define('APP_LWPLIBDIR',APP_BASEDIR.'/lwplib/lib');
define('V1_BASEDIR',APP_BASEDIR.'/core/v1');
define('V1_LIBDIR',V1_BASEDIR.'/lib');
define('V1_WEBDIR',V1_BASEDIR.'/www');
define('V1_CONFIGDIR',V1_BASEDIR.'/etc');        // v1 static configurations
define('V1_TOKENDIR',APP_BASEDIR.'/tokens/v1');  // v1 cached tokens
define('V1_LOGDIR',APP_BASEDIR.'/log/v1');       // v1 logs

set_include_path(get_include_path().PATH_SEPARATOR.V1_LIBDIR.PATH_SEPARATOR.APP_LWPLIBDIR);

?>
