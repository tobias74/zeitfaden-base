<?php


class ZeitfadenOAuth2
{
  protected $server = false;
  
  public function __construct($databaseProvider)
  {
    $this->databaseProvider = $databaseProvider;
    
    //$this->initServer();
    
  } 
  
  protected function initServer()
  {
    $storage = new \OAuth2\Storage\Mongo($this->databaseProvider->getMongoDbService(),array());
    $this->server = new \OAuth2\Server($storage);
    $this->server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage));
    $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));    
    $this->server->addGrantType(new OAuth2\GrantType\RefreshToken($storage));    
  }
  
  public function __call($name, $parameters)
  {
    if (!$this->server)
    {
      $this->initServer();
    }
    return call_user_func_array(array($this->server, $name), $parameters);
  }
  
}


