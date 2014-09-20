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

  public function setProfiler($val)
  {
    $this->profiler = $val;
  }

  protected function getProfiler()
  {
    return $this->profiler;
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
    $request = OAuth2\Request::createFromGlobals();
    // this is the old correct way:
    //$value = $this->getOAuth2Service()->getAccessTokenData($request);
    
    
    //this is the hacky way, but faster.    
    $headers = $request->headers('AUTHORIZATION');
    $headers_alt = $request->headers('Authorization');
    $value = !empty($headers_alt) || !empty($headers) || (bool) ($request->request('access_token')) || (bool) ($request->query('access_token')) || (bool) ($request->request('grant_type')) || (bool) ($request->query('grant_type'));
    
    return $value;
  }         
  
  public function recognizeLoginIdentityByOAuth2()
  {
    if (!$this->getOAuth2Service()->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
    {
      error_log('wrong oauth session? ########################-------------------------------------------------------');
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
        $timer = $this->getProfiler()->startTimer('doing oauth2');
        // this is an oauth2-request.
        $userSession = $this->recognizeLoginIdentityByOAuth2();
        $timer->stop();
      }
      else 
      {
        // it is not an oauth2 request. carry on.
        $userSession = $this->recognizeLoginIdentityBySession($session);
      }
    
      return $userSession;    
  }
 
  
  
}
