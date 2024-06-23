<?php

/**
 * SpellModel
 */
class ItemModel extends DefaultModel
{   
   /**
    * @var MyAPI|null $api
    */

   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);
   }

   public function getAll()
   {
      $database  = 'yaqds';
      $statement = "SELECT id, name FROM items WHERE id > ?";

      $request = $this->dataRequest($database,$statement,'i',array(0));

      return (isset($request['data']['results'])) ? $request['data']['results'] : null;
   }

   public function getItemById($itemId)
   {
      $database  = 'yaqds';
      $statement = "SELECT * FROM items where id = ?";
      $types     = 'i';
      $data      = array($itemId);

      $request = new LWPLib\Request($this->debug);

      $request->create(
         ['statement' => $statement, 'types' => $types, 'data' => $data],
         ['database'  => $database]
      );

      // Returned data will be in $this->data->content['data']
      $result = $this->data->bindQueryDatabase($request);

      if ($result === false) { return false; }

      if (is_array($this->data->content['data']['results'])) { $this->data->content['data']['results'] = array_change_key_case($this->data->content['data']['results']); }

      return $this->data->content;
   }
}
