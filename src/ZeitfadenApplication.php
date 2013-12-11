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
    
		switch ($this->httpHost)
    {
      case "test.zeitfaden.de":
      case "test.zeitfaden.com":
      case "test.db-shard-one.zeitfaden.com":
      case "test.db-shard-two.zeitfaden.com":
      case "test.db-shard-three.zeitfaden.com":
        $this->applicationId = 'zeitfaden_test';
        $this->facebookAppId = '516646775013909';
        $this->facebookAppSecret = 'b565442b784941eec855edcf4228bf58';
        break;
  
      case "www.zeitfaden.de":
      case "www.zeitfaden.com":
        die('in application, no live yet.');
        $this->applicationId = 'zeitfaden_live';
        $this->facebookAppId = '516646775013909';
        $this->facebookAppSecret = 'b565442b784941eec855edcf4228bf58';
        break;
        
      case "livetest.zeitfaden.com":
      case "livetest.zeitfaden.de":
      case "livetest.db-shard-one.zeitfaden.com":
      case "livetest.db-shard-two.zeitfaden.com":
      case "livetest.db-shard-three.zeitfaden.com":
        $this->applicationId = 'zeitfaden_live';
        $this->facebookAppId = '516646775013909';
        $this->facebookAppSecret = 'b565442b784941eec855edcf4228bf58';
        break;
        
      default:
        throw new \ErrorException("no configuration for this domain: ".$this->httpHost);
        break;
        
    }
     
     	
		
		$this->dependencyManager = SL\DependencyManager::getInstance();
		$this->dependencyManager->setProfilerName('PhpProfiler');


    $this->dependencyConfigurator->configureDependencies($this->dependencyManager,$this);


    
    $this->config = $this->dependencyManager->get('ZeitfadenConfig');
    $this->config->performConfiguration($this->httpHost,$this->applicationId);
		
    
    
		$this->mySqlProfiler = $this->dependencyManager->get('SqlProfiler');
		$this->phpProfiler = $this->dependencyManager->get('PhpProfiler');

						
		
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
  
  

	
    public function runRestful($serverContext)
    {
        // the application should use a UserSessionRecognizer!
        // in that case the application would not need the oauth2-servcie anymore, becuae the recognizer would have it.
        
        
        // maybe we can refacrtor the Application furth down? 
      
      
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
        //print_r($session);

        $this->setUserSession($userSessionRecognizer->recognizeUserSession($_SESSION));
        //print_r($this->getUserSession());
        //die();
                
        
        $this->getRouteManager()->analyzeRequest($request);
        
        
        $frontController = new \PivoleUndPavoli\FrontController($this);
        $frontController->setDependencyManager($this->dependencyManager);
        
        try
        {
            $frontController->dispatch($request,$response);
        }
        catch (ZeitfadenException $e)
        {
            die($e->getMessage());
        }
        catch (ZeitfadenNoMatchException $e)
        {
            die($e->getMessage());
        }
        
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




