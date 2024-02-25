<?php

class DefaultView extends Base {
   
   public $contentType = null;
   public $contentBody = '';

   public function __construct($debug = null)
   {
      parent::__construct($debug);
   }

   public function render($data = null) {
      return true;
   }
}
?>
