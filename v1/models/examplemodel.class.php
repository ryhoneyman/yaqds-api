<?php
//include_once 'local/something.class.php';

class ExampleModel extends DefaultModel
{
   public $something = null;

   public function __construct($debug = null, $main = null)
   {
      $this->needsDatabase = '';

      parent::__construct($debug,$main);
   }

   public function exampleMethod()
   {
      $this->something = new Something($this->debug);
 
      $result = $this->something->method();

      return $result;
   }

   public function exampleDatabaseQuery($lookup)
   {
      $query = sprintf("select * from something where lookupId = %d",$lookup);

      $result = $this->main->db()->query($query);

      return $result;
   }
}
?>
