<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/opt/epic/conf'.PATH_SEPARATOR.'/opt/epic/api/v2/lib'.PATH_SEPARATOR.'/opt/epic/include');

spl_autoload_register('autoLoader');

$starttime = microtime(true);

include_once 'epicapi.conf';
include_once 'base.class.php';
include_once 'debug.class.php';
include_once 'cipher.class.php';
include_once 'database.class.php';
include_once 'auth.class.php';
include_once 'token.class.php';
include_once 'request.class.php';
include_once 'response.class.php';

$debuglevel  = 0;      // global debug level 0(none) - 9(full)
$debugbuffer = true;   // buffer debug information and display at the end (prevents header interference)
$ratelimit   = true;   // rate limit resets to the API, reduces overall speed - offers overload protection

$debug    = new Debug($debuglevel,$debugbuffer);
$cipher   = new Cipher($debug);
$db       = new Database($debug,'MySQL','mysql.class.php');
$request  = new Request($debug);
$response = new Response($debug);
$auth     = new Auth($debug);
$token    = new Token($debug);
$dbh      = $db->resource;  // The direct database handle from database wrapper

showDuration("start, classes built");

// We couldn't load the database driver
if (!is_null($dbh)) {
   // Bring the database online, values come from epicapi.conf and are deciphered
   $dbuser = $cipher->decode($dbuser);
   $dbpass = $cipher->decode($dbpass);
   $dbh->connect($dbhost,$dbuser,$dbpass,$dbname);
}

showDuration("database up");

// libraries to make available to controllers
$libs = array(
   'db'    => $db,
   'auth'  => $auth,
   'debug' => $debug,
);

$tmplfile = 'template';
$tmpl     = file_get_contents($tmplfile);

// If the database is connection, we can proceed
if ($dbh->connected) {
   $findcount = 1000;

   $findsql = "select *, unix_timestamp(expires) as ut_expires, unix_timestamp(expire_rate) as ut_expire_rate, unix_timestamp(expire_concurrent) as ut_expire_concurrent from api_token where expires > now() + interval 1 month limit $findcount";

   $results = $dbh->query($findsql);

   foreach ($results as $apikey => $info) {
      $dir = '/tmp/token/'.$info['apitoken'];
      $info['jsonaccess'] = stripslashes($info['jsonaccess']);
      @mkdir($dir,0777);
      foreach ($info as $key => $value) {
         file_put_contents($dir.'/'.$key,$value);
      }
      //break;
   }
}

// END MAIN /-----------------------------------------------------------------------

?>
<?php

function apiMain($request, $response, $libs)
{
   $debug = $libs['debug'];

   // controller as defined by the user request
   $controllerName = ucfirst($request->controller).'Controller';

   // Controller doesn't exist
   if (!class_exists($controllerName)) {
      $response->setStatus(501,'Controller Not Implemented');
      return false;
   }

   $controller = new $controllerName($debug,$libs);
   $methodName = strtolower($request->method).'Method';

   if (!method_exists($controller,$methodName)) {
      $response->setStatus(501,'Method Not Implemented');
      return false;
   }

   $cmResult = $controller->$methodName($request);

   // Something went wrong in the controller method
   if (!$cmResult) {
      $response->setStatus(500,'Controller Error');
      return false;
   }

   // set initial status from controller
   $response->setStatus($controller->statusCode,$controller->statusMessage);

   $viewName = ucfirst($request->format).'View';

   // View doesn't exist
   if (!class_exists($viewName)) {
      $response->setStatus(501,'Resource Not Implemented');
      return false;
   }

   $view    = new $viewName($debug);
   $vResult = $view->render($controller->content);

   // Soemthing went wrong in the view render
   if (!$vResult) {
      $response->setStatus(500,'View Render Error');
      return false;
   }

   $response->setContentType($view->contentType);
   $response->setContentBody($view->contentBody);

   return true;
}

function checkToken($token, $request, $response, $libs)
{
   $db    = $libs['db'];
   $auth  = $libs['auth'];
   $debug = $libs['debug'];

   // If the request was for authentication, there's no need to check the token
   if (preg_match('/^auth$/i',$request->controller)) { return true; }

   // If user supplied a token, validate it's properites
   if ($request->token) {
      if (!$token->exists) {
         $response->setStatus(401,'Invalid Token');
         return false;
      }
      else if ($token->expired) {
         $response->setStatus(401,'Token Expired');
         return false;
      }
      //else if ($tokenResult['requests'])

      if (!$auth->authorize($token->accesslist,$request->controller,$request->method)) {
         $response->setStatus(403,'Insufficent Privilege');
         return false;
      }
   }
   else {
      $response->setStatus(401,'Unauthorized');
      return false;
   }

   return true;
}

function autoLoader($classname)
{
   global $debug;

   $lcname = strtolower($classname);

   $debug->trace(9,'Looking for class '.$classname);

   if (preg_match('/[a-z0-9]+(model|view|controller)$/i',$classname,$match)) {
      $type = strtolower($match[1]);
      $file .= __DIR__."/{$type}s/$lcname.class.php";

      $debug->trace(9,'Trying to load '.$type.' file: '.$file);

      if (file_exists($file)) {
         $return = (!@include_once($file)) ? false : true;
         $debug->trace(9,'File found: '.$file.' [success:'.$return.']');
         return $return;
      }
      else {
         $debug->trace(9,'File not found: '.$file);
      }
   }

   $debug->trace(9,'Class '.$classname.' not valid for autoload.');

   return false;
}

function showDuration($label)
{
   global $debug, $durationLast;

   $now = microtime(true);

   if (!$durationLast) { $durationLast = $now; }
   
   $debug->trace(0,sprintf("%s: %1.6f secs",$label,($now - $durationLast)));

   $durationLast = $now;
}
?>
