<?php

class OAuth2UserSession extends AbstractUserSession
{
 
 
  public function isOAuthSession()
  {
    return true;
  }
  
  
  
  public function setOAuthApplicationId($val)
  {
    $this->oAuthApplicationId = $val;
  }

  public function getOAuthApplicationId()
  {
    return $this->oAuthApplicationId;
  }

}





