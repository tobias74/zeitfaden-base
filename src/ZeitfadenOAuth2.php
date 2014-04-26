<?php


class ZeitfadenOAuth2
{
  public function __construct($databaseProvider)
  {
    $storage = new \OAuth2\Storage\Mongo($databaseProvider->getMongoDbService(),array());
    $this->server = new \OAuth2\Server($storage);
    $this->server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage));
    $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));    
  } 
  
  public function __call($name, $parameters)
  {
    return call_user_func_array(array($this->server, $name), $parameters);
  }
  
}


