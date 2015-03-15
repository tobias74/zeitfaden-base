<?php

class OAuth2UserSession extends AbstractUserSession
{
 
 
  public function isOAuthSession()
  {
    return true;
  }
  
  
  
  public function setOAuthAppId($val)
  {
    $this->oAuthAppId = $val;
  }

  public function getOAuthAppId()
  {
    return $this->oAuthAppId;
  }

}





