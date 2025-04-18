<?php

class ItemController extends DefaultController
{
   protected $itemModel  = null;
   
   /**
    * __construct
    *
    * @param  LWPLib\Debug|null $debug
    * @param  Main|null $main
    * @return void
    */
   public function __construct($debug = null, $main = null)
   {
      parent::__construct($debug,$main);

      $this->debug(5,get_class($this).' class instantiated');

      // The model provides all the data and methods to retrieve it; connect it and bring it online
      $this->itemModel  = new ItemModel($debug,$main); 

      // If the model isn't ready we need to flag the controller as not ready and set status
      if (!$this->itemModel->ready)  { $this->notReady($this->itemModel->error); return; }
   }

   public function getItemList($request)
   {
      $this->debug(7,'method called');

      $filterData = $request->filterData;

      //$object = isset($filterData['object']) ? $filterData['object'] : null;
      //$attrib = isset($filterData['attrib']) ? $filterData['attrib'] : null;

      $itemList = $this->itemModel->getAll();

      $content = array('data' => $itemList);

      return $this->standardOk($content);
   }

   public function getItemById($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $itemId = $filterData['id'];

      $itemData = $this->itemModel->getItemById($itemId);

      $this->main->debug->writeFile('itemcontroller.getitembyid.debug.log',json_encode([$itemId,$itemData]));

      if ($itemData === false) { return $this->standardError($this->itemModel->error); }
      if (!$itemData)          { return $this->standardError("Item does not exist"); }

      $itemData['_description'] = $this->itemModel->createItemDescription($itemData);

      $this->main->debug->writeFile('itemcontroller.getitembyid.debug.log',json_encode(["OK!",$itemData]));

      return $this->standardOk($itemData);
   }

   public function searchItems($request)
   {
      $this->debug(7,'method called');

      $parameters = $request->parameters;
      $filterData = $request->filterData;

      $searchName = $parameters['name'];
      $searchLike = $parameters['like'];
      $searchMax  = $parameters['max'];

      $itemData = $this->itemModel->searchByName($searchName,$searchLike,$searchMax);

      if ($itemData === false) { return $this->standardError($this->itemModel->error); }
      if (!$itemData)          { return $this->standardNotFound("No matches"); }

      return $this->standardOk($itemData);
   }
}
