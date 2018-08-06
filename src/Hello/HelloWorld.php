<?php

namespace Hello;

class HelloWorld {
  
    
  function __construct() {
  }

  
  public function content() {


    $connectionInfo = array(
    'db'     => getenv('DB_UIEXTENSION_DBNAME'),
    'server' =>  getenv('DB_UIEXTENSION_HOST'),
    'dbUser' =>  getenv('DB_UIEXTENSION_USERNAME'),
    'dbPass' => getenv('DB_UIEXTENSION_PASSWORD'),
    'dbType'     => getenv('DB_UIEXTENSION_DBTYPE'),
  );



    #$connObj = ConnectionFactory::build($connectionInfo);

    print $connectionInfo;

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
      #'#markup' => $this->t($connObj),
    ];
  }
}
