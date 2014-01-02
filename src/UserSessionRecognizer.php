<?php


class UserSessionRecognizer
{

  public function setFacebook($val)
  {
    $this->facebook = $val;
  }


  protected function getFacebook()
  {
    return $this->facebook;
  }


 	public function setOAuth2Service($val)
  {
    $this->oAuth2Service = $val;
  }


  protected function getOAuth2Service()
  {
    return $this->oAuth2Service;
  }
  
  
  public function isOAuth2Request()
  {
    return $this->getOAuth2Service()->getAccessTokenData(OAuth2\Request::createFromGlobals());
  }         
  
  public function recognizeLoginIdentityByOAuth2()
  {
    if (!$this->getOAuth2Service()->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
    {
      $this->getOAuth2Service()->getResponse()->send();
      die();
    }
    else
    {
      $oAuth2Token = $this->getOAuth2Service()->getAccessTokenData(\OAuth2\Request::createFromGlobals());

      $oAuthApplicationId = $oAuth2Token['client_id'];
      $loggedInUserId = $oAuth2Token['user_id'];
      //$isOAuthSession=true;
    
      $userSession = new OAuth2UserSession();
      $userSession->setOAuthApplicationId($oAuthApplicationId);
      $userSession->setLoggedInUserId($loggedInUserId);
      
      return $userSession; 
      
        
    }
     
  }

  protected function recognizeLoginIdentityBySession($session)
  {
    $loggedInUserId = isset($session['loggedInUserId']) ? $session['loggedInUserId'] : 0;
    $facebookUserId = isset($session['facebookUserId']) ? $session['facebookUserId'] : '';
    if ($loggedInUserId != false)
    {
      $userSession = new NativeUserSession();
      $userSession->setLoggedInUserId($loggedInUserId);
      $userSession->setFacebookUserId($facebookUserId);
    }
    else
    {
      $userSession = new AnonymousUserSession();      
    }
    
    return $userSession;
  }

  public function recognizeUserSession($session)
  {
      if ($this->isOAuth2Request())
      {
        // this is an oauth2-request.
        $userSession = $this->recognizeLoginIdentityByOAuth2();
      }
      else 
      {
        // it is not an oauth2 request. carry on.
        $userSession = $this->recognizeLoginIdentityBySession($session);
      }
    
      return $userSession;    
  }
 
  
  
}
