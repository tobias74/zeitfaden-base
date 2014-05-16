<?php

class DistanceToPinOrderer extends \VisitableSpecification\AbstractOrderer
{

  public function __construct($fieldName,$latitude,$longitude,$direction)
  {
    $this->field = $fieldName;
    $this->latitude = $latitude;
    $this->longitude = $longitude;
	$this->direction = $direction;
  }
  
  public function getField()
  {
    return $this->field;
  }
  
  public function getlatitude()
  {
    return $this->latitude;
  }
  
  public function getLongitude()
  {
    return $this->longitude;
  }

  public function getDirection()
  {
  	return $this->direction;
  }

  public function acceptVisitor($visitor)
  {
    $visitor->visitDistanceToPinOrderer($this);
  }
  
  
}


