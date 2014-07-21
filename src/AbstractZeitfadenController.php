<?php

// for better performance, searching can be done using a lastId to identify the last Station of the previous request.
// this eliminates offset and limit.
// lastId cannot be used when searching users or ordering stations by distance from a geopoint.

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

	
  protected function attachDistance($spec, $request)
  {
  	$latitude = $request->getParam('latitude',false);
  	$longitude = $request->getParam('longitude',false);
  	$maxDistance = $request->getParam('maxDistance',false);
	
  	if ($latitude && $longitude && $maxDistance)
  	{
        $criteria = new \VisitableSpecification\ST_WithinDistanceCriteria('startLocation', $longitude, $latitude, $maxDistance);
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
  	$sort = $request->getParam('sort',false);
    $direction = $request->getParam('direction',false);
    $lastId = $request->getParam('lastId',false);


  	if ($sort)
  	{
      if ($sort === 'byTime')
      {
        try
        {
          $datetime = $request->getParam('datetime',false);
          $timeObject = new DateTime($datetime);
        }
        catch (\Exception $e)
        {
          $timeObject = new DateTime();
        }
        
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
      else if ($sort === 'byDistanceToPin')
      {
        $datetimeHigh = $request->getParam('until',false);
        $datetimeLow = $request->getParam('from',false);
		
		if (!$datetimeHigh || !$datetimeLow)
		{
			$criteria = false;			
		}
		else 
		{
	        $timeObjectHigh = new DateTime($datetimeHigh);
	        $datetimeHigh = $timeObjectHigh->format('Y-m-d H:i:s');
	        $criteriaHigh = new \VisitableSpecification\LessOrEqualCriteria('startDate',$datetimeHigh);
	
	        $timeObjectLow = new DateTime($datetimeLow);
	        $datetimeLow = $timeObjectLow->format('Y-m-d H:i:s');
	        $criteriaLow = new \VisitableSpecification\GreaterOrEqualCriteria('startDate',$datetimeLow);
	        
	        $criteria = $criteriaHigh->logicalAnd($criteriaLow);
		}

        
		if ($direction == 'farFirst')
		{
			$dText = 'desc';
		}
		else 
		{
			$dText = 'asc';
		}
        $orderer = new \VisitableSpecification\DistanceToPinOrderer('startLocation', $request->getParam('latitude',0) , $request->getParam('longitude',0), $dText);          
      }
      else 
      {
        throw new \WrongRequestException(''); 
      }

      
  		$spec->setOrderer($orderer);
  		
		if ($criteria)
		{
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
  	else 
  	{
  		return $spec;	
  	}
  	
  }


  protected function attachStationAttachment($spec, $request)
  {
    $mustHaveAttachment = $request->getParam('stationMustHaveAttachment',false);
  
    if ($mustHaveAttachment)
    {
        //$criteria = new \VisitableSpecification\NotNullCriteria('fileId');
        //$criteria = new \VisitableSpecification\EqualCriteria('fileType','video/mpeg');
        $criteria = new \VisitableSpecification\EqualCriteria('fileType','image/jpeg','station');
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


  protected function attachUserAttachment($spec, $request)
  {
    $mustHaveAttachment = $request->getParam('userMustHaveAttachment',false);
  
    if ($mustHaveAttachment)
    {
      error_log('user must have attachment');
        //$criteria = new \VisitableSpecification\NotNullCriteria('fileId');
        //$criteria = new \VisitableSpecification\EqualCriteria('fileType','video/mpeg');
        $criteria = new \VisitableSpecification\EqualCriteria('fileType','image/jpeg','user');
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


  protected function attachUserId($spec, $request)
  {
    $userId = $request->getParam('userId',false);
  
    if ($userId)
    {
        //$criteria = new \VisitableSpecification\NotNullCriteria('fileId');
        //$criteria = new \VisitableSpecification\EqualCriteria('fileType','video/mpeg');
        $criteria = new \VisitableSpecification\EqualCriteria('userId',$userId);
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


  public function getUserSpecificationByRequest($request)
  {
  	$offset = $request->getParam('offset',0);
  	$limit = $request->getParam('limit',100);
    $limiter = new \VisitableSpecification\Limiter($offset,$limit);
  	$spec = new \VisitableSpecification\Specification();
    $spec->setLimiter($limiter);
    
    $spec = $this->attachUserAttachment($spec, $request);
  
  	return $spec;
  }


  public function getstationSpecificationByRequest($request)
  {
    $offset = $request->getParam('offset',0);
    $limit = $request->getParam('limit',100);
    $limiter = new \VisitableSpecification\Limiter($offset,$limit);
    $spec = new \VisitableSpecification\Specification();
    $spec->setLimiter($limiter);
    
    $spec = $this->attachDistance($spec, $request);
    $spec = $this->attachDateTime($spec, $request);
    $spec = $this->attachStationAttachment($spec, $request);
    
    $spec = $this->attachUserId($spec, $request);
  
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

  protected function getAttachmentUrlByRequest($request)
  {
    $entityId = $request->getParam($this->idName,0);
	return $this->getAttachmentUrlByEntityId($entityId);    
  }


	protected function clearAttachmentCacheForEntityId($entityId)
	{
	    $serveAttachmentUrl = $this->getAttachmentUrlByEntityId($entityId);
	    $flyUrl = 'http://flyservice.zeitfaden.com/image/clearAll/?imageUrl='.$serveAttachmentUrl;
	    $r = new HttpRequest($flyUrl, HttpRequest::METH_GET);
	    $r->send();
	}

  public function getImageAction()
  {
    $format = $this->_request->getParam('format','original');
    $imageSize = $this->_request->getParam('imageSize','medium');
	$width = $this->_request->getParam('width',100);		
	$height = $this->_request->getParam('height',100);		
    $serveAttachmentUrl = $this->getAttachmentUrlByRequest($this->_request);
    $flyUrl = 'http://flyservice.zeitfaden.com/image/getFlyImageId/format/'.$format.'/imageSize/'.$imageSize.'/width/'.$width.'/height/'.$height.'?imageUrl='.$serveAttachmentUrl;
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


