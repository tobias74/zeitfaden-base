<?php 

use SugarLoaf as SL;

class ZeitfadenApplication
{
	
	const STATUS_OK = true;
	const STATUS_ERROR_NOT_LOGGED_IN = -10; 
	const STATUS_GENERAL_ERROR = -100; 
	const STATUS_EMAIL_ALREADY_TAKEN = -15;
	const STATUS_ERROR_INVALID_ACTION = -1001;
	const STATUS_ERROR_WRONG_INPUT = -5;
	const STATUS_ERROR_SOLE_NOT_FOUND = -5001; 
	


	public function __construct($configData)
	{
	  $this->httpHost = $configData['httpHost'];
    $this->dependencyConfigurator = $configData['dependencyConfigurator'];
    $this->configLoader = $configData['configLoader'];
    $this->configurationServiceName = $configData['configurationServiceName'];
    $this->applicationIni = $configData['applicationIni'];

    
		switch ($this->httpHost)
    {
      case "test.zeitfaden.de":
      case "test.zeitfaden.com":
      case "test.db-shard-one.zeitfaden.com":
      case "test.db-shard-two.zeitfaden.com":
      case "test.db-shard-three.zeitfaden.com":
        $this->applicationId = $this->applicationIni['test']['application_id'];
        $this->facebookAppId = $this->applicationIni['test']['facebook_app_id'];
        $this->facebookAppSecret = $this->applicationIni['test']['facebook_app_secret'];
        break;
  
      case "www.zeitfaden.de":
      case "www.zeitfaden.com":
      case "livetest.zeitfaden.com":
      case "livetest.zeitfaden.de":
      case "live.db-shard-one.zeitfaden.com":
      case "live.db-shard-two.zeitfaden.com":
      case "live.db-shard-three.zeitfaden.com":
        $this->applicationId = $this->applicationIni['live']['application_id'];
        $this->facebookAppId = $this->applicationIni['live']['facebook_app_id'];
        $this->facebookAppSecret = $this->applicationIni['live']['facebook_app_secret'];
        break;
        
        
      default:
        throw new \ErrorException("no configuration for this domain: ".$this->httpHost);
        break;
        
    }
     
     	
		
		$this->dependencyManager = SL\DependencyManager::getInstance();
		$this->dependencyManager->setProfilerName('PhpProfiler');

    $this->config = $this->configLoader->getNewConfigInstance();


    $this->dependencyConfigurator->configureDependencies($this->dependencyManager,$this);


    if ($this->configurationServiceName !== false)
    {
      $this->configLoader->setConfigurationService( $this->dependencyManager->get($this->configurationServiceName) );
    }


		$this->configLoader->loadConfiguration($this->httpHost,$this->applicationId, $this->config);
    
    
		$this->mySqlProfiler = $this->dependencyManager->get('SqlProfiler');
		$this->phpProfiler = $this->dependencyManager->get('PhpProfiler');

		
	}

  public function getConfig()
  {
    return $this->config;
  }

	public function getApplicationId()
  {
    return $this->applicationId;
  }
	
  public function getFacebookAppId()
  {
      return $this->facebookAppId;
  }
  
  public function getFaceBookAppSecret()
  {
      return $this->facebookAppSecret;
  }
  
  public function getFacebookConfig()
  {
      return array(
            'appId' => $this->getFacebookAppId(),
            'secret' => $this->getFacebookAppSecret()
        );
  }

  

	
  public function getUserSession()
  {
    return $this->userSession;  
  }
	
	public function setUserSession($val)
  {
    $this->userSession = $val;
  }
  
  

  public function run($serverContext)
  {
        $appTimer = $this->phpProfiler->startTimer('#####XXXXXXX A1A1-COMPLETE_RUN XXXXXXXXXXXX################');
        
        $serverContext->startSession();
        
        $request = $serverContext->getRequest();
        
        $response = new \PivoleUndPavoli\Response();
        


        // check for options-reuqest
        if ($request->getRequestMethod() === 'OPTIONS')
        {
          $appTimer->stop();
          
          $profilerJson = json_encode(array(
              'phpLog' => $this->phpProfiler->getHash(),
              'dbLog' => $this->mySqlProfiler->getHash()
          ));
          
          return $response;
        }        

        
        $userSessionRecognizer = $this->dependencyManager->get('UserSessionRecognizer');
        $session = $_SESSION;

        $this->setUserSession($userSessionRecognizer->recognizeUserSession($_SESSION));
                
        
        $this->getRouteManager()->analyzeRequest($request);
        
        
        $frontController = new \PivoleUndPavoli\FrontController($this);
        $frontController->setDependencyManager($this->dependencyManager);
        
        try
        {
          $frontController->dispatch($request,$response);
          $response->appendValue('status',ZeitfadenApplication::STATUS_OK);
          $response->appendValue('requestCompletedSuccessfully',true);
        }
        catch (ZeitfadenException $e)
        {
          $response->enable();
          $response->appendValue('status',$e->getCode());
          $response->appendValue('errorMessage',$e->getMessage());
          $response->appendValue('stackTrace',$e->getTraceAsString());
        }
        catch (ZeitfadenNoMatchException $e)
        {
          $response->appendValue('error', ZeitfadenApplication::STATUS_ERROR_SOLE_NOT_FOUND);
          $response->appendValue('errorMessage',$e->getMessage());
          $response->appendValue('stackTrace',$e->getTraceAsString());
        }
        
        $response->appendValue('profilerData',array(
          'phpProfiler'   => $this->phpProfiler->getHash(),
          'mysqlProfiler' => $this->mySqlProfiler->getHash()  
        )); 
        

        
        if ($this->getUserSession()->getFacebookUserId() != false) 
        {
            $response->appendValue('isFacebookUser', true);
        } 
        else 
        {
            $response->appendValue('isFacebookUser', false);
        }        



        
        $appTimer->stop();
        
        $profilerJson = json_encode(array(
            'phpLog' => $this->phpProfiler->getHash(),
            'dbLog' => $this->mySqlProfiler->getHash()
        ));
        
        $response->addHeader("ZeitfadenProfiler: ".$profilerJson);
        
        
        $response->appendValue('loginId', $loggedInUser->getId());
        $response->appendValue('loginEmail', $loggedInUser->getEmail());
        $response->appendValue('loginUserId', $loggedInUser->getId());
        $response->appendValue('loginUserEmail', $loggedInUser->getEmail());
        $response->appendValue('loginFacebookUserId', $loggedInUser->getFacebookUserId());
        
        
        return $response;
  }




	
    public function runRestful($serverContext)
    {
      
        $appTimer = $this->phpProfiler->startTimer('#####XXXXXXX A1A1-COMPLETE_RUN XXXXXXXXXXXX################');
        
        $serverContext->startSession();
        
        $request = $serverContext->getRequest();
        
        $response = new \PivoleUndPavoli\Response();
        


        // check for options-reuqest
        if ($request->getRequestMethod() === 'OPTIONS')
        {
          $appTimer->stop();
          
          $profilerJson = json_encode(array(
              'phpLog' => $this->phpProfiler->getHash(),
              'dbLog' => $this->mySqlProfiler->getHash()
          ));
          
          return $response;
        }        

        
        $userSessionRecognizer = $this->dependencyManager->get('UserSessionRecognizer');
        $session = $_SESSION;

        $this->setUserSession($userSessionRecognizer->recognizeUserSession($_SESSION));
                
        
        $this->getRouteManager()->analyzeRequest($request);
        
        
        $frontController = new \PivoleUndPavoli\FrontController($this);
        $frontController->setDependencyManager($this->dependencyManager);

        $frontController->dispatch($request,$response);
        

        
        $appTimer->stop();
        
        $profilerJson = json_encode(array(
            'phpLog' => $this->phpProfiler->getHash(),
            'dbLog' => $this->mySqlProfiler->getHash()
        ));
        
        $response->addHeader("ZeitfadenProfiler: ".$profilerJson);
        
        return $response;
    }
		
	
	
	public function getRouteManager()
  {
    
    $routeManager = new \PivoleUndPavoli\RouteManager();
    

    $routeManager->addRoute(new \PivoleUndPavoli\Route(
      '/:controller/:action/*',
      array()
    ));
    
    
    $routeManager->addRoute(new \PivoleUndPavoli\Route(
      'getUserById/:userId',
      array(
        'controller' => 'user',
        'action' => 'getById'
      )
    ));

    $routeManager->addRoute(new \PivoleUndPavoli\Route(
      'getStationById/:stationId',
      array(
        'controller' => 'station',
        'action' => 'getById'
      )
    ));

    $routeManager->addRoute(new \PivoleUndPavoli\Route(
        'getStationsByQuery/:query',
        array(
            'controller' => 'station',
            'action' => 'getByQuery'
        )
    ));

    $routeManager->addRoute(new \PivoleUndPavoli\Route(
        'getUsersByQuery/:query',
        array(
            'controller' => 'user',
            'action' => 'getByQuery'
        )
    ));
        
    $routeManager->addRoute(new \PivoleUndPavoli\Route(
        'oauth/:action/*',
        array(
            'controller' => 'OAuth2'
        )
    ));
                    
    return $routeManager;
  }
  
	
	
}




