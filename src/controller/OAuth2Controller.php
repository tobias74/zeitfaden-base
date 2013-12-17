<?php
//http://test.zeitfaden.com/OAuth2/authorize/?response_type=code&client_id=testclient&state=xyz

class OAuth2Controller extends AbstractZeitfadenController
{
  protected function declareActionsThatNeedLogin()
  {
    return array(
      'authorize'
      );
  }


  public function setOAuth2Service($val)
  {
    $this->oauth2Service = $val;
  }

  protected function getOAuth2Service()
  {
    return $this->oauth2Service;
  }

      
  public function meAction()
  {
    echo $_SESSION['loggedInUserId'];
    die();
  }


  public function tokenAction()
  {
    $response = $this->getOAuth2Service()->handleTokenRequest(OAuth2\Request::createFromGlobals());
    $response->send();
    die();
  }  
  
  
  public function resourceAction()
  {
    if ($this->getOAuth2Service()->getAccessTokenData(OAuth2\Request::createFromGlobals()))
    {
      // this is an oauth2-request.
      if (!$this->getOAuth2Service()->verifyResourceRequest(OAuth2\Request::createFromGlobals()))
      {
        $this->getOAuth2Service()->getResponse()->send();
        die();
      }
      else
      {
        error_log('good inside api access');
        echo "you access my APIs!!!";
        $token = $this->getOAuth2Service()->getAccessTokenData(\OAuth2\Request::createFromGlobals());
        echo "User ID associated with this token is {$token['user_id']}";      
        echo "###########################################";
        print_r($token);
        echo "###########################################";
        die();
      }
              
    }
    else 
    {
      // it is not an oauth2 request. carry on.
      
    }
  }
  
  
  public function authorizeAction()
  {
    $request = \OAuth2\Request::createFromGlobals();
    $response = new \OAuth2\Response();
    
    if (!$this->getOAuth2Service()->validateAuthorizeRequest($request, $response))
    {
      $response->send();
      die();      
    }
    
    if (empty($_POST)) 
    {
      exit('
    <form method="post">
      <label>Do You Authorize TestClient?</label><br />
      <input type="submit" name="authorized" value="yes">
      <input type="submit" name="authorized" value="no">
    </form>');
    }
    
    // print the authorization code if the user has authorized your client
    $is_authorized = ($_POST['authorized'] === 'yes');
    $this->getOAuth2Service()->handleAuthorizeRequest($request, $response, $is_authorized, $this->getLoggedInUserId());
    if ($is_authorized) 
    {
      // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
      $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
      exit("SUCCESS! Authorization Code: $code");
    }
    
    $response->send(); 
   
    
  }
  
  
  
  
  
}