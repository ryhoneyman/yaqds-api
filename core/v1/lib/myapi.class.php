<?php

include_once 'apibase.class.php';

class MyAPI extends LWPLib\APIBase
{
   protected $version  = 1.0;
   
   /**
    * __construct
    *
    * @param  LWPLib\Debug|null $debug
    * @param  array|null $options
    * @return void
    */
   public function __construct($debug = null, $options = null)
   {
      parent::__construct($debug,$options);

      $this->authType('auth.header.bearer');

      $this->loadUris([
         'v1-authenticate'              => '/v1/auth/token',
         'v1-data-provider-query'       => '/v1/data/provider/query/{{database}}',
         'v1-data-provider-query-table' => '/v1/data/provider/query/{{database}}/{{table}}',
         'v1-data-provider-modify'      => '/v1/data/provider/modify/{{database}}',
         'v1-spells'                    => '/v1/spell?fields={{fields}}',
      ]);
   }
   

   public function v1GetSpells($fields = null)
   {
      $request = [
         'params' => ['fields' => $fields],
         'options' => [
            'method' => 'GET',
         ]
      ];

      if (!$this->makeRequest('v1-spells','auth,json',$request)) { 
        $this->error($this->clientError());
        return false; 
      }

      return $this->clientResponse();
   }

   /**
    * v1Authenticate
    *
    * @param  string $clientId
    * @param  string $clientSecret
    * @return bool
    */
   public function v1Authenticate($clientId, $clientSecret)
   {
      $request = array(
         'data' => array('client_id' => $clientId, 'client_secret' => $clientSecret),
         'options' => array(
            'timeout' => 15,
         ),
      );

      $requestResult = $this->makeRequest('v1-authenticate','json',$request);

      if ($requestResult === false) { $this->error($this->clientError()); return false; }

      $token = $this->clientResponseValue('token');

      if (!$token) { $this->error('Could not authenticate'); return false; }

      $this->authToken = $token;

      return true;
   }
      
   /**
    * v1DataProviderBindQuery
    *
    * @param  string $database
    * @param  string $statement
    * @param  string|null $types
    * @param  array|null $data
    * @param  array|null $options
    * @return mixed
    */
   public function v1DataProviderBindQuery($database, $statement, $types = null, $data = null, $options = null)
   {
      $index  = $options['index'] ?: null;
      $single = $options['single'] ?: false;

      $request = array(
         'params' => array('database' => $database),
         'data' => array(
            'statement' => $statement, 
            'types'     => $types,
            'data'      => $data,
            'index'     => $index,
            'single'    => $single
         ),
         'options' => array(
            'method' => 'POST',
         )
      );

      if (!$this->makeRequest('v1-data-provider-query','auth,json',$request)) { 
        $this->error($this->clientError());
        return false; 
      }

      /*
      $this->debug->writeFile('debug.myapi.v1dataproviderbindquery.log',json_encode([
         'database'  => $database,
         'statement' => $statement,
         'types'     => $types,
         'data'      => $data,
         'options'   => $options,
         'return'    => $this->clientResponse(),
      ]),false);
      */

      return $this->clientResponse();
   }
   
   /**
    * v1DataProviderTableData
    *
    * @param  string $database
    * @param  string $table
    * @param  array|null $options
    * @return mixed
    */
   public function v1DataProviderTableData($database, $table, $options = null)
   {
      $options = (is_array($options)) ? $options : array();

      $request = array(
         'params' => array('database' => $database, 'table' => $table),
         'data' => $options,
         'options' => array(
            'method' => 'POST',
         )
      );

      if (!$this->makeRequest('v1-data-provider-query-table','auth,json',$request)) { 
        $this->error($this->clientError());
        return false; 
      }

      return $this->clientResponse();
   }
}