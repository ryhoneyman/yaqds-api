<?php
include_once 'yaqds-api-init.php';
include_once 'main.class.php';

$main = new Main(array(
   'fileDefines'    => null,
   'debugLevel'     => 9,
   'debugBuffer'    => true,
   'debugLogDir'    => V1_LOGDIR,
   'errorReporting' => false,
   'sessionStart'   => false,
   'memoryLimit'    => null,
   'sendHeaders'    => false,
   'autoLoad'       => 'autoLoader',  // we define our own autoLoader function to distinguish between MVC components when loading
   'database'       => 'prepare',     // we set prepare only because we don't want the database connected for unauthed/improper requests
   'dbConfigDir'    => APP_CONFIGDIR,
   'dbDefines'      => null,
   'input'          => false,
   'html'           => false,
   'adminlte'       => false,
));

$main->buildClass('apicore','ApiCore',$main->db(),'apicore.class.php');
$main->buildClass('token','Token',null,'token.class.php');
$main->buildClass('request','LWPLib\Request',null,'request.class.php');
$main->buildClass('response','LWPLib\Response',null,'response.class.php');
$main->buildClass('router','Router',null,'router.class.php');

$main->prepareDatabase('db.yaqds.conf','yaqds');

$apicore  = $main->obj('apicore');
$router   = $main->obj('router');
$request  = $main->obj('request');
$response = $main->obj('response');
$token    = $main->obj('token');

$apiSettings = json_decode(file_get_contents(V1_CONFIGDIR.'/api.settings.json'),true);

$main->debug->traceDuration("Start, classes built"); 

$rateLimit = $apiSettings['rateLimit'];

// Set directories for file access
$apicore->tokenDir   = V1_TOKENDIR;
$apicore->logDir     = V1_LOGDIR;
$router->endpointDir = V1_CONFIGDIR.'/endpoints';

// Debug user request object
//$debug->trace(9,json_encode($request));

// If the request wasn't for authentication and we have a Authorization Basic header we have to process it
if (!$apicore->isAuthRequest($router->categoryName) && preg_match('/^basic\s+/i',$request->auth)) {
   $authController = new AuthController($main->debug,$main);
   $authController->authBasic($request);
}

// If we were supplied a token, prefetch and store token data for global use
if ($request->token) {
   $token->mapData($apicore->getTokenData($request->token));
   $main->debug->traceDuration("token mapped"); 

   if ($token->superUser) { $main->debug->trace(9,"Super user token provided"); }

   if ($rateLimit && !$token->superUser && $token->valid) {
      // Borrow a use unit from the pool for our token
      $result = $apicore->addTokenCounter($token);
      $main->debug->traceDuration("token counter adjusted up"); 
   }
}

// Debug token object
//$debug->trace(0,json_encode($token));

// Check if this token is currently exceeds its limits
if (!$rateLimit || $token->superUser || !$token->limitExceeds) {
   // Match the request path with known endpoints and determine if it exists
   if (!$router->processRequestPath($request)) { 
      $response->setStatus(404,'Resource Not Found');
   }
   else {
      // Check user token to see if we are authorized to use the API call
      if (!$token->superUser) {
         $allowed = checkToken($main);
         $main->debug->traceDuration("token checked"); 
      }

      if ($token->superUser || $allowed) {
         // Execute the API call, a false return indicates a non-user error
         // At the moment the return isn't used, but it could be in the future
         $result = apiMain($main);
         $main->debug->traceDuration("API endpoint finish"); 
      }
   }
}
else {
   $response->setStatus(429,'Too Many Requests');
}

// Add rate limit headers if limiting is on
if ($rateLimit && !$token->superUser && $token->rateLimit) {
   $rateRemaining = ($token->rateLimit > $token->rateCount) ? $token->rateLimit - $token->rateCount : 0;
   $response->addCustomHeader('X-RateLimit-Limit: '.$token->rateLimit);
   $response->addCustomHeader('X-RateLimit-Remaining: '.$rateRemaining);
   $response->addCustomHeader('X-RateLimit-Reset: '.date('r',$token->rateExpires));
   $main->debug->traceDuration("rateLimit header set");
}

// set HTTP protocol response
$response->setProtocol($request->protocol);

// Output response to client
$response->sendHeader($token);
$main->debug->traceDuration("header sent"); 
$response->sendBody();
$main->debug->traceDuration("body sent"); 

// Return our use unit to the pool for our token
if ($rateLimit && !$token->superUser && $token->valid) {
   $result = $apicore->removeTokenCounter($token);
   $main->debug->traceDuration("token counter adjusted down"); 
}

// Log user request and response to database
$apicore->logApiRequestFile($request,$response,$main->elapsedRuntime());
$main->debug->traceDuration("request logged"); 

$main->debug->traceDuration("total time",$main->startMs);

// If debug is enabled, output debug information
if ($main->debug->level() > 0) {
   $logData = $main->debug->getLog(true);
   if (!$token->superUser) { print $logData; } 
   //@file_put_contents(V1_LOGDIR.'/debug.log',$logData);
}

// END MAIN /-----------------------------------------------------------------------

?>
<?php

function apiMain($main)
{
   $request  = $main->obj('request');
   $response = $main->obj('response');
   $router   = $main->obj('router');

   // Get controller and function from router
   $controllerName = $router->controllerName;
   $functionName   = $router->functionName;

   // Controller doesn't exist
   if (!class_exists($controllerName)) {
      $response->setStatus(501,'Controller Not Implemented');
      return false;
   }

   $controller = new $controllerName($main->debug,$main);

   // Controller could not be initialized
   if (!$controller) {
      $response->setStatus(500,'Controller Error');
      return false;
   }

   // Call controller function, only if it indicates it's ready
   if ($controller->ready) { 
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

   $view = new $viewName($main->debug);

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

   // Controller initialized, but marked itself not ready.  
   // We wait until here because we need the headers/content (which may contain error messages) to render
   if (!$controller->ready) { return false; }

   return true;
}

function checkToken($main)
{
   $apicore  = $main->obj('apicore');
   $token    = $main->obj('token');
   $request  = $main->obj('request');
   $response = $main->obj('response');
   $router   = $main->obj('router');

   // If the request was for authentication, there's no need to check the token
   if ($apicore->isAuthRequest($router->categoryName)) { return true; }

   //$main->debug->trace(9,"token:".json_encode($token,JSON_UNESCAPED_SLASHES));

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
      else if (!$apicore->authorize($token->accessList,$router->controllerName,$router->functionName,$request->method)) {
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
   $lcname = strtolower($classname);

   if (preg_match('/[a-z0-9]+(model|view|controller)$/i',$classname,$match)) {
      $type = strtolower($match[1]);
      $file = V1_BASEDIR."/{$type}s/$lcname.class.php";  
   }
   else { 
      $type = 'global';
      $file = "$lcname.class.php"; 
   }

   if (file_exists($file)) {
      $return = (!@include_once($file)) ? false : true;
      return $return;
   }
   
   return false;
}
?>
