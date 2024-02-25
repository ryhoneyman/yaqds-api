<?php
include_once 'ldap.class.php';

class AdModel extends DefaultModel
{
   protected $config        = null;
   protected $basedn        = null;
   protected $ldap          = null;
   public    $connectedLdap = false;

   public function __construct($debug = null, $libs = null)
   {
      parent::__construct($debug,$libs);

      $this->connectDatabase();

      if ($this->databaseConnected()) { 
         $this->connectedLdap = $this->connectLdap(); 
      }
      else { $this->error = 'Cannot connect to database for LDAP configuration'; }
   }

   public function connectLdap()
   {
      $this->config = $this->apicore->getConfig(array('ad.user','ad.pass','ad.forest','ad.server','ad.basedn'));
      $this->ldap   = new LDAP($debug);
 
      $ldaprc = $this->ldap->connect($this->config['ad.user'].'@'.$this->config['ad.forest'],
                                     $this->config['ad.pass'],$this->config['ad.server']);

      $this->basedn = $this->config['ad.basedn'];

      $connected = ($ldaprc) ? true : false;

      if (!$connected) { $this->error = 'Cannot connect to LDAP server'; }

      return $connected;
   }

   public function userDetails($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; }

      $return = $this->ldap->get_user_details($this->basedn,$user);

      // Strip all non-printable characters from the return, since the directory stores binary data sets we don't care about.
      //array_walk_recursive($return,function(&$value, $key) { $value = preg_replace('/[^\r\n\t\x20-\x7E\xA0-\xFF]/','',$value); });

      return $return;
   }

   public function userExists($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; } 

      $return = $this->ldap->user_exists($this->basedn,$user);
  
      return $return;
   } 

   public function getMailFromSamaccountname($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; } 

      $return = $this->ldap->get_mail_from_samaccountname($this->basedn,$user);

      return $return;
   }

   public function getSamaccountnameFromMail($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; }

      $return = $this->ldap->get_samaccountname_from_mail($this->basedn,$user);

      return $return;
   }

   public function getUserGroups($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; }

      if (preg_match('/\@/',$user)) { $user = $this->getSamaccountnameFromMail($user); }
      $return = array_keys($this->ldap->get_user_groups($this->basedn,$user));

      return $return;
   }

   public function getUserdnFromSamaccountname($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; }

      $return = $this->ldap->get_userdn_from_samaccountname($this->basedn,$user);

      return $return;
   }

/*
   public function function($user)
   {
      $return = null;

      if (!$this->connectedLdap) { return $return; }

      $return = $this->ldap->function($this->basedn,$user);

      return $return;
   }
*/

}
?>
