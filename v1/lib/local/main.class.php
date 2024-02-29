<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'common/mainbase.class.php';

class Main extends MainBase
{
   public $constants      = null;
   public $data           = null;

   public function __construct($options = null)
   {
      parent::__construct($options);
   }

   public function initialize($options)
   {
      parent::initialize($options);

      if ($options['constants']) {
         if (!$this->buildClass('constants','Constants',null,'local/constants.class.php')) { exit; }
         $this->constants = $this->obj('constants');
      }
   }
}
?>
