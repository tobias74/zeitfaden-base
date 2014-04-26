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

	
  protected function attachLocation($spec, $request)
  {
  	$latitude = $request->getParam('latitude',false);
  	$longitude = $request->getParam('longitude',false);
  	$distance = $request->getParam('distance',false);
	
  	if ($latitude && $longitude && $distance)
  	{
        $criteria = new \VisitableSpecification\ST_WithinDistanceCriteria('startLocation', $longitude, $latitude, $distance);
  	  $oldCriteria = $spec->getCriteria();
  	  if ($oldCriteria)
  	  {
  	  	$spec->setCriteria($oldCriteria->logicalAnd($criteria));
  	  }
  	  else
  	  {
  	  	$spec->setCriteria($criteria);
  	  }
  	}
  
  	return $spec;	
  	
  }
  
  protected function attachDateTime($spec, $request)
  {
  	$datetime = $request->getParam('datetime',false);
  	$sort = $request->getParam('sort',false);
    $direction = $request->getParam('direction',false);
    $lastId = $request->getParam('lastId',false);


  	if ($datetime && $sort && $direction)
  	{
  	  $timeObject = new DateTime($datetime);
      $datetime = $timeObject->format('Y-m-d H:i:s');
      
  	  if ($lastId)
      {
        $field = 'startDateWithId';  
        $value = $datetime.'_'.$lastId;
      }
      else
      {
        $field = 'startDate';
        $value = $datetime;
      }
      
      if ($sort === 'byTime')
      {
        if ($direction === 'intoThePast')
        {
          $criteria = new \VisitableSpecification\LessOrEqualCriteria($field, $value);
          $orderer = new \VisitableSpecification\SingleDescendingOrderer($field);
        }
        else if ($direction === 'intoTheFuture')
        {
          $criteria = new \VisitableSpecification\GreaterOrEqualCriteria($field,$value);
          $orderer = new \VisitableSpecification\SingleAscendingOrderer($field);
        }
        else 
        {
          throw new \WrongRequestException(''); 
        }
      }
      else 
      {
        throw new \WrongRequestException(''); 
      }

      
  
  		$spec->setOrderer($orderer);
  		
    	    $oldCriteria = $spec->getCriteria();
  	    if ($oldCriteria)
  	    {
  	  	  $spec->setCriteria($oldCriteria->logicalAnd($criteria));
  	    }
  	    else
  	    {
  	  	  $spec->setCriteria($criteria);
  	    }
  		
        return $spec;
  	}
  	else 
  	{
  		return $spec;	
  	}
  	
  }


  protected function attachAttachment($spec, $request)
  {
    $mustHaveAttachment = $request->getParam('mustHaveAttachment',false);
  
    if ($mustHaveAttachment)
    {
        //$criteria = new \VisitableSpecification\NotNullCriteria('fileId');
        //$criteria = new \VisitableSpecification\EqualCriteria('fileType','video/mpeg');
        $criteria = new \VisitableSpecification\EqualCriteria('fileType','image/jpeg');
        $oldCriteria = $spec->getCriteria();
        if ($oldCriteria)
        {
          $spec->setCriteria($oldCriteria->logicalAnd($criteria));
        }
        else
        {
          $spec->setCriteria($criteria);
        }
    }
  
    return $spec; 
    
  }


  protected function getSpecificationByRequest($request)
  {
    $limiter = new \VisitableSpecification\Limiter(0,100);
  	$spec = new \VisitableSpecification\Specification();
    $spec->setLimiter($limiter);
    
  	$spec = $this->attachLocation($spec, $request);
  	$spec = $this->attachDateTime($spec, $request);
    $spec = $this->attachAttachment($spec, $request);
  
  	return $spec;
  }

  protected function requiresLoggedInUser()
  {
    if (!$this->isUserLoggedIn())
    {
      throw new ErrorException('login required');
    }
  }

  protected function getUserSession()
  {
    return $this->getApplication()->getUserSession();  
  }

	public function getRequestParameter($name,$default)
	{
		return $this->_request->getParam($name,$default);
	}
	
  
	public function setDatabase($val)
  {
    $this->database = $val;
  }
  
    

  public function getLoggedInUserId()
  {
    return $this->getUserSession()->getLoggedInUserId();
  }
  
	
	
	protected function isUserLoggedIn()
	{
    return $this->getUserSession()->isUserLoggedIn();
	}



  public function getImageAction()
  {
    $format = $this->_request->getParam('format','original');
    $imageSize = $this->_request->getParam('imageSize','medium');
    $serveAttachmentUrl = $this->getAttachmentUrlByRequest($this->_request);
    $flyUrl = 'http://flyservice.zeitfaden.com/image/getFlyImageId/format/'.$format.'/imageSize/'.$imageSize.'?imageUrl='.$serveAttachmentUrl;
    $r = new HttpRequest($flyUrl, HttpRequest::METH_GET);
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    $this->sendGridFile($values);    
  }

  public function getVideoAction()
  {
    $format = $this->_request->getParam('format','webm');
    $serveAttachmentUrl = $this->getAttachmentUrlByRequest($this->_request);
    $flyUrl = 'http://flyservice.zeitfaden.com/video/getFlyVideoId/format/'.$format.'?videoUrl='.$serveAttachmentUrl;
    $r = new HttpRequest($flyUrl, HttpRequest::METH_GET);
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    $this->sendGridFile($values);    
    
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
