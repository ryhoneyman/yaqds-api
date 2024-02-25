<?php

define('APP_BASEDIR','/opt/yaqds-api');
define('V1_BASEDIR',APP_BASEDIR.'/v1');
define('V1_LIBDIR',V1_BASEDIR.'/lib');
define('V1_WEBDIR',V1_BASEDIR.'/www');
define('V1_CONFIGDIR',V1_BASEDIR.'/etc');    // static configurations
define('V1_VARDIR',V1_BASEDIR.'/var');       // dynamic file data
define('V1_LOGDIR',V1_BASEDIR.'/log');       // logs

set_include_path(get_include_path().PATH_SEPARATOR.V1_LIBDIR);

?>
