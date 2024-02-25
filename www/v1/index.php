<?php
include_once 'yaqds-api-init.php';
include_once 'local/main.class.php';

$main = new Main(array(
   'debugLevel'     => 0,
   'errorReporting' => false,
   'sessionStart'   => false,
   'memoryLimit'    => null,
   'sendHeaders'    => false,
   'database'       => false,
   'input'          => false,
   'html'           => false,
   'adminlte'       => false,
));

spl_autoload_register('autoLoader');

$starttime = microtime(true);

$main->buildClass('apicore','ApiCore',null,'local/apicore.class.php');
$main->buildClass('token','Token',null,'local/token.class.php');
$main->buildClass('request','Request',null,'local/request.class.php');
$main->buildClass('response','Response',null,'local/response.class.php');
$main->buildClass('router','Router',null,'local/router.class.php');

$debuglevel   = 0;                    // global debug level 0(none) - 9(full)
$debugbuffer  = true;                 // buffer debug information and display at the end (prevents header interference)
$ratelimit    = true;                 // rate limit resets to the API, reduces overall speed - offers overload protection
$basedir      = '/opt/epic/api/v2';   // base directory for the API
$durationLast = 0;                    // used to calculate debug event durations

$debug    = new Debug($debuglevel,$debugbuffer);
$cipher   = new Cipher($debug);
$dbh      = new MySQL($debug);
$apicore  = new APICore($debug,$dbh);
$apiauth  = new APIAuth($debug);
$request  = new Request($debug);
$response = new Response($debug);
$router   = new Router($debug);
$token    = new Token($debug);

showDuration("Start, classes built"); 

// Set directories for file access
$apicore->tokenDir   = $basedir.'/tokens';
$router->endpointDir = $basedir.'/etc/endpoints';

// Set database connect parameters that come from api.conf
// but defer connecting until we need the database to improve performance
$dbh->setParameters(array('host'     => $dbhost, 
                          'dbname'   => $dbname, 
                          'username' => $cipher->decode($dbuser), 
                          'password' => $cipher->decode($dbpass)));

// libraries to make available to controllers
$libs = array(
   'apicore' => $apicore,
   'apiauth' => $apiauth,
   'dbh'     => $dbh,
   'debug'   => $debug,
);

// Debug user request object
//$debug->trace(9,json_encode($request));

// If the request wasn't for authentication and we have a Authorization Basic header we have to process it
if (!$apiauth->isAuthRequest($router->categoryName) && preg_match('/^basic\s+/i',$request->auth)) {
   $authController = new AuthController($debug,$libs);
   $authController->authBasic($request);
}

// If we were supplied a token, prefetch and store token data for global use
if ($request->token) {
   $token->mapData($apicore->getTokenData($request->token));
   showDuration("token mapped"); 

   if ($ratelimit && $token->valid) {
      // Borrow a use unit from the pool for our token
      $result = $apicore->addTokenCounter($token);
      showDuration("token counter adjusted up"); 
   }
}

// Debug token object
//$debug->trace(0,json_encode($token));

// Check if this token is currently exceeds its limits
if (!$ratelimit || !$token->limitExceeds) {
   // Match the request path with known endpoints and determine if it exists
   if (!$router->processRequestPath($request)) { 
      $response->setStatus(404,'Resource Not Found');
   }
   else {
      // Check user token to see if we are authorized to use the API call
      $allowed = checkToken($token,$request,$response,$router,$libs);
      showDuration("token checked"); 

      if ($allowed) {
         // Execute the API call, a false return indicates a non-user error
         // At the moment the return isn't used, but it could be in the future
         $result = apiMain($request,$response,$router,$libs);
         showDuration("API endpoint finish"); 
      }
   }
}
else {
   $response->setStatus(429,'Too Many Requests');
}

// Add rate limit headers if limiting is on
if ($ratelimit && $token->rateLimit) {
   $rateRemaining = ($token->rateLimit > $token->rateCount) ? $token->rateLimit - $token->rateCount : 0;
   $response->addCustomHeader('X-RateLimit-Limit: '.$token->rateLimit);
   $response->addCustomHeader('X-RateLimit-Remaining: '.$rateRemaining);
   $response->addCustomHeader('X-RateLimit-Reset: '.date('r',$token->rateExpires));
   showDuration("ratelimit header set");
}

// set HTTP protocol response
$response->setProtocol($request->protocol);

// Output response to client
$response->sendHeader($token);
showDuration("header sent"); 
$response->sendBody();
showDuration("body sent"); 

// Return our use unit to the pool for our token
if ($ratelimit && $token->valid) {
   $result = $apicore->removeTokenCounter($token);
   showDuration("token counter adjusted down"); 
}

$finishtime  = microtime(true);
$elapsedtime = $finishtime - $starttime;

// Log user request and response to database
$apicore->logApiRequestFile($request,$response,$elapsedtime);
showDuration("request logged"); 

$debug->trace(9,sprintf("total duration: %1.6f",$elapsedtime));

// If debug is enabled, output debug information
if ($debuglevel > 0) { print $debug->logOutput(); }

// END MAIN /-----------------------------------------------------------------------

?>
<?php

function apiMain($request, $response, $router, $libs)
{
   $debug = $libs['debug'];

   // Get controller and function from router
   $controllerName = $router->controllerName;
   $functionName   = $router->functionName;

   // Controller doesn't exist
   if (!class_exists($controllerName)) {
      $response->setStatus(501,'Controller Not Implemented');
      return false;
   }

   $controller = new $controllerName($debug,$libs);

   // Controller could not be initialized
   if (!$controller) {
      $response->setStatus(500,'Controller Error');
      return false;
   }

   // Check to see if the class method (endpoint function) exists in the controller class
   if (!method_exists($controller,$functionName)) {
      $response->setStatus(405,'Function Not Implemented');
      return false;
   }

   // Make function call to the controller
   $cfResult = $controller->$functionName($request);

   // Something went wrong in the controller method
   if (!$cfResult) {
      $response->setStatus(500,'Controller Function Error');
      return false;
   }

   // set initial status from controller
   $response->setStatus($controller->statusCode,$controller->statusMessage);

   if (!empty($controller->headers)) {
      foreach ($controller->headers as $controllerHeader) {
         $response->addCustomHeader($controllerHeader);
      }
   }

   $viewName = ucfirst($request->format).'View';

   // View doesn't exist
   if (!class_exists($viewName)) {
      $response->setStatus(501,'View Not Implemented');
      return false;
   }

   $view = new $viewName($debug);

   // View could not be initialized
   if (!$view) {
      $response->setStatus(501,'View Error');
      return false;
   }

   // Render content in view
   $vResult = $view->render($controller->content);

   // Something went wrong in the view render
   if (!$vResult) {
      $response->setStatus(500,'View Render Error');
      return false;
   }

   $response->setContentType($view->contentType);
   $response->setContentBody($view->contentBody);

   return true;
}

function checkToken($token, $request, $response, $router, $libs)
{
   $apiauth = $libs['apiauth'];
   $debug   = $libs['debug'];

   // If the request was for authentication, there's no need to check the token
   if ($apiauth->isAuthRequest($router->categoryName)) { return true; }

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
      else if (!$apiauth->authorize($token->accessList,$router->controllerName,$router->functionName,$request->method)) {
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

   if ($debug->level < 9) { return false; }

   $now = microtime(true);

   if (!$durationLast) { $durationLast = $now; }
   
   $debug->trace(9,sprintf("%s: %1.6f secs",$label,($now - $durationLast)));

   $durationLast = $now;

   return true;
}
?>
