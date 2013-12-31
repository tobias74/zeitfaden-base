<?php

abstract class AbstractZeitfadenController
{

  public function __construct($request, $response, $application)
  {
	$this->_request = $request;
	$this->_response = $response;
    $this->_application = $application;
  }

  protected function getApplication()
  {
    return $this->_application;  
  }
	
	
  public function demoAction()
  {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    die();

  }

  protected function getUserSession()
  {
    return $this->getApplication()->getUserSession();  
  }

	public function getRequestParameter($name,$default)
	{
		return $this->_request->getParam($name,$default);
	}
	
	
  public function setElasticSearchService($val)
  {
    $this->elasticSearchService = $val;
  }

  protected function getElasticSearchService()
  {
    return $this->elasticSearchService;
  }


  public function getLoggedInUserId()
  {
    return $this->getUserSession()->getLoggedInUserId();
  }
  
	
	
	protected function isUserLoggedIn()
	{
    return $this->getUserSession()->isUserLoggedIn();
	}


  protected function sendGridFile($values)
  {
    
    if (!isset($values['done']) || $values['done'] != 1)
    {
      //print_r($values);
      echo "not ready yet.. transcoding....";
      throw new \ErrorException('not done yet, still transcoding.');
    }
    
    $mongoClient = new \MongoClient($values['mongoServerIp']);
    $mongoDb = $mongoClient->$values['collectionName'];
    $gridFS = $mongoDb->getGridFS();
    
    try
    {
      $gridFile = $gridFS->findOne(array('_id' => new MongoId($values['gridFileId'])));
      $fileTime = $gridFile->file['uploadDate']->sec;
      
      if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
      {
        error_log('we did get the http if modiefed...');
        if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $fileTime)
        {
          error_log('and we answered, not modified');
          header('HTTP/1.0 304 Not Modified');
          exit;
        }
        else
        {
          error_log('and we answered, yes modified, continue loading.');
        }
      }  
      
      
      $this->_response->addHeader('Content-Length: '.$gridFile->getSize());
      $this->_response->addHeader('Last-Modified: '.gmdate('D, d M Y H:i:s',$fileTime).' GMT',true,200);
      $this->_response->setStream($gridFile->getResource());
      $this->_response->addHeader('Cache-Control: maxage='.(60*60*24*31));
      $this->_response->addHeader('Expires: '.gmdate('D, d M Y H:i:s',time()+60*60*24*31).' GMT',true,200);
      if (!isset($gridFile->file['metadata']['type']))
      {
        //$this->_response->addHeader('Content-type: image/png');
        $this->_response->addHeader('Content-Disposition: attachment');
      }
      else
      {
        $this->_response->addHeader('Content-type: '.$gridFile->file['metadata']['type']);
      }
      
      //header('Content-Disposition: attachment; filename="video.ogg"');

      
    }
    catch (\Exception $e)
    {
      die('send back default video / (image) file with message to wait: '.$e->getMessage());
    }
    
  }


}
