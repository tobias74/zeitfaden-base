<?php
namespace Zeitfaden\ElasticSearch;


class ElasticSearchQueryArray 
{
  //
  protected $whereClause;
  protected $clauseParts = array();
  
  
  public function __construct($context = null)
  {
    $this->context = $context;

    $mainDataMap = new DataMap();
    $mainDataMap->addColumn('id', 'id');
    $mainDataMap->addColumn('user_id', 'userId');
    $mainDataMap->addColumn('description', 'description');
    $mainDataMap->addColumn('publish_status', 'publishStatus');
    $mainDataMap->addColumn('zulu_start_date_string', 'startDate');
    $mainDataMap->addColumn('start_location.lat', 'startLatitude');
    $mainDataMap->addColumn('start_location.lon', 'startLongitude');
    $mainDataMap->addColumn('start_timezone', 'startTimezone');
    $mainDataMap->addColumn('zulu_end_date_string', 'endDate');
    $mainDataMap->addColumn('end_location.lat', 'endLatitude');
    $mainDataMap->addColumn('end_location.lon', 'endLongitude');
    $mainDataMap->addColumn('end_timezone', 'endTimezone');
    $mainDataMap->addColumn('start_location', 'startLocation');
    $mainDataMap->addColumn('end_location', 'endLocation');


    $this->context = $mainDataMap;    
  }
  

  protected function getTranslatedValue($column,$value)
  {
    switch ($column)
    {
      case 'zulu_start_date_string':
      case 'zulu_end_date_string':
        $date = \DateTime::createFromFormat('Y-m-d H:i:s',$value);
        return $date->format('Y-m-d').'T'.$date->format('H:i:s');
        break;

      default:
        return $value;         

    }
  }

  public function visitAndCriteria($andCriteria)
  {
    $firstArray = $this->getArrayForCriteria($andCriteria->getFirstCriteria());
    $secondArray = $this->getArrayForCriteria($andCriteria->getSecondCriteria());
   
    $whereArray = array('and' => array(
      $firstArray,
      $secondArray
    ));
      
                  
    $this->setArrayForCriteria($andCriteria, $whereArray);
        
  }
  
  public function visitOrCriteria($orCriteria)
  {
    //
    $firstArray = $this->getArrayForCriteria($orCriteria->getFirstCriteria());
    $secondArray = $this->getArrayForCriteria($orCriteria->getSecondCriteria());
   
    $whereArray = array('or' => array(
      $firstArray,
      $secondArray
    ));
      
                  
    $this->setArrayForCriteria($orCriteria, $whereArray);
              
  }

  
  public function visitEqualCriteria($criteria)
  {
    throw new \ErrorException('not yet implemented');
    return;

    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array($column => $criteria->getValue());
    
    $this->setArrayForCriteria($criteria, $comp);
  }
  
      
  public function visitGreaterThanCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'gt' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }


  public function visitGreaterOrEqualCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'gte' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }

  
  public function visitLessThanCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'lt' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
    
    
  public function visitLessOrEqualCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'lte' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
        
    
  public function visitNotEqualCriteria($criteria)
  {
    throw new \ErrorException('not yet implemented');
    return;
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array($column => array(
      '$ne' => $this->getTranslatedValue($column, $criteria->getValue())
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
    
        
    
  public function visitCriteriaBetween($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'gt' => $this->getTranslatedValue($column, $criteria->getStartValue()),
          'lt' => $this->getTranslatedValue($column, $criteria->getEndValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
        
  }
  
  public function visitNotCriteria($criteria)
  {
    $comp = array('not' => array(
      $this->getArrayForCriteria($criteria->getNestedCriteria()),
    ));
        
    $this->setArrayForCriteria($criteria, $comp);
  }
  
  
  public function visitWithinDistanceCriteria($criteria)
  {
    
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getGeometryField());

    $comp = array(
      'geo_distance' => array(
        'distance' => floatval($criteria->getMaximumDistance()),
        $column => array(
          'lat' => floatval($criteria->getLatitude()),
          'lon' => floatval($criteria->getLongitude())
        )
      )
    );


    
    $this->setArrayForCriteria($criteria, $comp);
          
  }
  
  
  public function getArrayForCriteria($criteria)
  {
    return $this->clauseParts[$criteria->getKey()];
  }

  protected function setArrayForCriteria($criteria,$clause)
  {
    $this->clauseParts[$criteria->getKey()] = $clause;
  }
    
} 






