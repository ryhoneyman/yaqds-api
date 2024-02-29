<?php

class SpellModel extends DefaultModel
{
   public $something = null;

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);
   }

   public function getAll()
   {
      $query = "SELECT id, name FROM spells_new";

      $result = $this->main->db('yaqds')->query($query);

      return $result;
   }

   public function getSpellById($spellId)
   {
      $statement = "SELECT * FROM spells_new where id = ?";
      $types     = 'i';
      $data      = array($spellId);

      $result = $this->main->db('yaqds')->bindQuery($statement,$types,$data,array('multi' => false));

      return $result;
   }
}
?>
