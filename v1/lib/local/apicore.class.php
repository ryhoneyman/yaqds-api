<?php

class APICore extends Base
{
   protected $version = 1.0;

   public $tokenDir   = null;
   public $dbh        = null;
   public $cacheFiles = null;

   public function __construct($debug = null, $dbh = null)
   {
       parent::__construct($debug);

       $this->dbh = $dbh;

       $this->cacheFiles = array('api_key_id','apitoken','jsonaccess','limit_rate','limit_concurrent',
                                 'ut_expires','ut_expire_rate','ut_expire_concurrent');
   }

   public function getRbac($roles = null)
   {
      $rbacList = array();
      $return   = array();

      //if (!is_null($roles)) { return $return; }

      $roleList = $this->buildRoleList($this->dbh->query("select rolename,jsonaccess from api_rbac"),$roles);

      foreach ($roleList as $roleInfo) {
         if (!is_array($roleInfo)) { continue; }
         
         $privController = strtolower($roleInfo['controller']);                  // controller name specified in privilege
         $privFunction   = strtolower($roleInfo['function']);                    // function name specified in privilege
         $privMethod     = ($roleInfo['method']) ? $roleInfo['method'] : 'any'; // method name specified in privilege, if none then any

         $this->debug(9,"Found ACL($privController,$privFunction,$privMethod)");

         $anyController = (preg_match('/^any$/i',$privController)) ? true : false;
         $anyFunction   = (preg_match('/^any$/i',$privFunction)) ? true : false;

         // If we have a global wildcard allow, then other privileges won't matter
         if ($anyController && $anyFunction) { return array("any:any:any"); }

         // Full wildcard controllers get priority in the list, followed by wildcard methods
         $priority = ($anyController) ? 1 : (($anyFunction) ? 2 : 3);

         $rbacList[$priority]["$privController:$privFunction:$privMethod"]++; 
      }

      ksort($rbacList);

      foreach ($rbacList as $priority => $privileges) {
         $return = array_merge($return,array_keys($privileges));
      }
 
      return $return;
   }

   private function buildRoleList($roleData, $userRoles)
   {
      $return    = array();
      $groupList = array();

      foreach ($roleData as $roleName => $roleInfo) {
         $accessInfo = @json_decode($roleInfo['jsonaccess'],true); 

         if (!is_array($accessInfo)) { continue; }

         $groupList[$roleName] = $accessInfo;
      } 

      foreach ($userRoles as $userRole) {
         $allRoles = $this->resolveGroupDependencies($userRole,$groupList,'roles');
         foreach ($allRoles as $roleName) {
            $return = array_merge($return,$groupList[$roleName]);
         }
      }

      return $return;
   }

   private function resolveGroupDependencies($needle, $haystack, $groupkey = 'groups')
   {
      $needles = array();
   
      if (!$haystack[$needle]) { return ""; }
      else if (!$haystack[$needle][$groupkey]) { return array($needle); }
      else {
         foreach ($haystack[$needle][$groupkey] as $newneedle) {
            $result = $this->resolveGroupDependencies($newneedle,$haystack,$groupkey);
            if ($result) { $needles = array_merge($needles,$result); }
         }
      }
   
      return $needles;
   }

   public function updateToken($keydata, $tokenExpireSecs = 3600)
   {
      if (!is_array($keydata) || !$keydata['apitoken'] || !$keydata['api_key_id'] || !preg_match('/\s*$/',$this->tokenDir)) { return false; }

      $findsql = "select apitoken from api_token where api_key_id = %d";
      $current = $this->dbh->query(sprintf($findsql,$keydata['api_key_id']),array('multi' => 0));

      $sql = "insert into api_token (api_key_id,apitoken,jsonaccess,limit_rate,count_rate,expire_rate,".
                                    "limit_concurrent,count_concurrent,expire_concurrent,lastused,".
                                    "created,expires) ".
             "values (%d,'%s','%s',%d,0,now(),%d,0,now(),now(),now(),now() + interval %d second) ".
             "on duplicate key update apitoken = values(apitoken), jsonaccess = values(jsonaccess), ".
             "limit_rate = values(limit_rate), limit_concurrent = values(limit_concurrent), ".
             "lastused = values(lastused), created = values(created), expires = values(expires)";

      $keydata['jsonaccess']           = json_encode($keydata['access']);
      $keydata['ut_expires']           = time() + $tokenExpireSecs;
      $keydata['ut_expire_rate']       = 0;
      $keydata['ut_expire_concurrent'] = 0;

      $insert = sprintf($sql,$keydata['api_key_id'],$this->dbh->escapeString($keydata['apitoken']),
                             $this->dbh->escapeString($keydata['jsonaccess']),$keydata['limit_rate'],
                             $keydata['limit_concurrent'],$tokenExpireSecs);

      $dbrc = $this->dbh->execute($insert);

      if (!$dbrc) { return false; }

      // If we found a previous non-blank token, remove that token directory and it's files
      if (!preg_match('/^\s*$/',$current['apitoken'])) {
         $oldApiTokenDir = $this->tokenDir.'/'.preg_replace('/\W/','',$current['apitoken']);
         if (is_dir($oldApiTokenDir)) { 
            $this->debug(1,"removing $oldApiTokenDir");
            array_map('unlink', glob("$oldApiTokenDir/*"));
            rmdir($oldApiTokenDir);
         }
      }

      $newApiTokenDir = $this->tokenDir.'/'.preg_replace('/\W/','',$keydata['apitoken']);

      if (!mkdir($newApiTokenDir,0700)) { return false; }

      foreach ($this->cacheFiles as $file) { file_put_contents($newApiTokenDir.'/'.$file,$keydata[$file],LOCK_EX); }

      return true;
   }

   public function getKeyData($clientId, $clientSecret)
   {
      $sql    = "select * from api_key where client_id = '%s' and client_secret = '%s'";
      $query  = sprintf($sql,$this->dbh->escapeString($clientId),$this->dbh->escapeString($clientSecret));
      $result = $this->dbh->query($query,array('multi' => 0));

      if ($result['jsonrbac']) {
         $roleList = @json_decode($result['jsonrbac'],true);
         if (is_array($roleList)) { $result['access'] = $this->getRbac($roleList); } 
      }

      $result['api_key_id'] = $result['id'];

      return $result;
   }

   public function getTokenData($token)
   {
      $apiTokenDir = $this->tokenDir.'/'.$token;

      $this->debug(7,"check dir: $apiTokenDir");

      // If token is blank or the token directory doesn't exist, there's nothing to read
      if (preg_match('/^\s*$/',$token) || !is_dir($apiTokenDir)) { return array(); }

      $result = array('apitoken' => $token);
   
      foreach ($this->cacheFiles as $basefile) {
         $fp = fopen($apiTokenDir.'/'.$basefile,'r+');
         if (flock($fp,LOCK_SH)) {
            $result[$basefile] = trim(fgets($fp));
            flock($fp,LOCK_UN);
         }
         else {
            $this->debug(0,"cant lock");
         }
         fclose($fp);
      }

      $statfiles = array('ratestart','concurrentstart','concurrentfinish');
      $stats     = array();

      foreach ($statfiles as $file) {
         $stats[$file] = @stat($apiTokenDir.'/'.$file);
      }

      $result['count_rate'] = $stats['ratestart'][7];
      $result['count_concurrent'] = $stats['concurrentstart'][7] - $stats['concurrentfinish'][7];

      return $result;
   }

   public function getTokenDataDatabase($token)
   {
      $sql    = "select *, unix_timestamp(expires) as ut_expires, ".
                       "unix_timestamp(expire_rate) as ut_expire_rate, ".
                       "unix_timestamp(expire_concurrent) as ut_expire_concurrent ".
                "from api_token where apitoken = '%s'";
      $query  = sprintf($sql,$this->dbh->escapeString($token));
      $result = $this->dbh->query($query,array('multi' => 0));

      return $result;
   }

   public function getTokenFromKey($keyId, $validate = 1)
   {
      $sql    = "select apitoken from api_token where api_key_id = %d ".
                (($validate) ? "and expires >= now()" : '');
      $query  = sprintf($sql,$keyId);
      $result = $this->dbh->query($query,array('multi' => 0));

      return $result['apitoken'];
   }

   public function addTokenCounter($token)
   {
      $changes = array();

      if ($token->rateReset) {
         @file_put_contents($this->tokenDir.'/'.$token->value.'/ratestart','',LOCK_EX);
         @file_put_contents($this->tokenDir.'/'.$token->value.'/ut_expire_rate',$token->now + $token->rateInterval,LOCK_EX);
      }
      else if (!$token->rateExceeds) {
         @file_put_contents($this->tokenDir.'/'.$token->value.'/ratestart','.',FILE_APPEND|LOCK_EX);
      }

      if ($token->concurrentReset) {
         @file_put_contents($this->tokenDir.'/'.$token->value.'/concurrentstart','',LOCK_EX);
         @file_put_contents($this->tokenDir.'/'.$token->value.'/concurrentfinish','',LOCK_EX);
         @file_put_contents($this->tokenDir.'/'.$token->value.'/ut_expire_concurrent',$token->now + $token->concurrentInterval,LOCK_EX);
      }
      else if (!$token->rateExceeds) {
         @file_put_contents($this->tokenDir.'/'.$token->value.'/concurrentstart','.',FILE_APPEND|LOCK_EX);
      }

      return true;
   }

   public function addTokenCounterDatabase($token)
   {
      $changes = array();
  
      if ($token->rateReset) {
         $changes[] = sprintf("count_rate = 0, expire_rate = now() + interval %d second",$token->rateInterval);
      }
      else if (!$token->rateExceeds) {
         $changes[] = "count_rate = count_rate + 1";
      }

      if ($token->concurrentReset) {
         $changes[] = sprintf("count_concurrent = 0, expire_concurrent = now() + interval %d second",$token->concurrentInterval);
      }
      else if (!$token->rateExceeds) {
         $changes[] = "count_concurrent = count_concurrent + 1";
      }

      if (empty($changes)) { return true; } 

      $sql = "update api_token set ".implode(', ',$changes).", lastused = now() ".
             "where api_key_d = %d";

      $query   = sprintf($sql,$token->keyId);
      $success = $this->dbh->execute($query);

      $this->debug(9,"sql: [rc:$success] $query");

      return (($success) ? true : false);
   }

   public function removeTokenCounter($token)
   {
      $changes = array();

      if ($token->concurrentCount <= 0) { return true; }

      $result = @file_put_contents($this->tokenDir.'/'.$token->value.'/concurrentfinish','.',FILE_APPEND|LOCK_EX);

      return (($result !== false) ? true : false);
   }

   public function removeTokenCounterDatabase($token)
   {
      $changes = array();

      if ($token->concurrentCount <= 0) { return true; }

      $sql = "update api_token set count_concurrent = count_concurrent - 1, lastused = now() ".
             "where api_key_id = %d";

      $query   = sprintf($sql,$this->dbh->escapeString($token->keyId));
      $success = $this->dbh->execute($query);

      $this->debug(9,"sql: [rc:$success] $query");

      return (($success) ? true : false);
   }

   public function logApiRequestFile($request, $response, $elapsedtime)
   {
      $sql = "insert into api_log (accessed,apitoken,api_key_id,request,method,statuscode,statusmesg,elapsedsec,bytes) ".
             "values (now(),'%s',%d,'%s','%s',%d,'%s',%1.5f,%d)\n";
 
      $insert = sprintf($sql,$request->token,$request->keyId,$request->pathinfo,$request->method,$response->statusCode,$response->statusMessage,$elapsedtime,$response->contentLength);

      $result = file_put_contents('/opt/epic/log/api.v2.log',$insert,FILE_APPEND|LOCK_EX);

      return (($result !== false) ? true : false);
   }

   public function logApiRequest($request, $response, $elapsedtime)
   {
      $sql = "insert into api_log (accessed,apitoken,api_key_id,request,method,statuscode,statusmesg,elapsedsec,bytes) ".
             "values (now(),'%s',%d,'%s','%s',%d,'%s',%1.5f,%d)";

      $insert = sprintf($sql,$this->dbh->escapeString($request->token),$request->keyId,
                             $this->dbh->escapeString($request->pathinfo),$this->dbh->escapeString($request->method),
                             $response->statusCode,$this->dbh->escapeString($response->statusMessage),
                             $elapsedtime,$response->contentLength);

      $success = $this->dbh->execute($insert);

      return (($success) ? true : false);
   }

   public function getConfig($keys = null)
   {
      $return = array();

      $query  = "select * from config";
      $result = $this->dbh->query($query);

      foreach ($result as $name => $info) {
        if (is_array($keys) && !in_array($name,$keys)) { continue; }
  
        $return[$name] = $info['value']; 
      }

      return $return;
   }

   public function authorize($accessList, $controllerName, $functionName, $method)
   {
      if (!is_array($accessList)) { return false; }

      $this->debug(9,"loaded access controls: ".count($accessList)." found");

      // Linear match works faster than constructed hash lookup or multi-regex pattern
      // when element list is smaller, under hundreds of items.  Consider optimizing
      // this if element list is in the tens of thousands.
      foreach ($accessList as $accessItem) {
         list($accessController,$accessFunction,$accessMethod) = explode(':',$accessItem);

         $this->debug(9,"ACL($accessController,$accessFunction,$accessMethod) User($controllerName,$functionName,$method)");

         $matchController = (preg_match("~^($controllerName|any)$~i",$accessController)) ? true : false;
         $matchFunction   = (preg_match("~^($functionName|any)$~i",$accessFunction)) ? true : false;
         $matchMethod     = (preg_match("~^($method|any)$~i",$accessMethod)) ? true : false;

         $this->debug(9,"ACL match: controller($matchController) function($matchFunction) method($matchMethod)");

         if ($matchController && $matchFunction && $matchMethod) { return true; }
      }

      $this->debug(9,"no match for access");

      return false;
   }

   public function generateKey($unique)
   {
      $apikey = hash('sha1',microtime().$unique,false);

      $this->debug(9,"generated key: $apikey");

      return $apikey;
   }

   public function generateToken($keyId)
   {
      $apitoken = hash('sha256',microtime().$keyId,false);

      $this->debug(9,"generated token: $apitoken");

      return $apitoken;
   }

   public function decodeBasicAuth($requestAuth)
   {
      return explode(':',base64_decode(preg_replace('/^basic\s+/i','',$requestAuth)));
   }

   public function isAuthRequest($routerCategory)
   {
      return (preg_match('/^authentication$/i',$routerCategory)) ? true : false;
   }
}
