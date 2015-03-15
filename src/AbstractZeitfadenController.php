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

  public function getProfiler()
  {
    return $this->profiler; 
  }
  
  public function setProfiler($val)
  {
    $this->profiler = $val; 
  }


  public function setApplicationId($val)
  {
    $this->applicationId = $val;
  }

  public function getApplicationId()
  {
    return $this->applicationId;
  }
  
  
  
  
}