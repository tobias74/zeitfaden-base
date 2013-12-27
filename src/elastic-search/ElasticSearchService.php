<?php
namespace Zeitfaden\ElasticSearch;

class ElasticSearchService
{


    public function setApplication($val)
    {
        $this->application = $val;
    }

    protected function getApplication()
    {
        return $this->application;
    }

    protected function getApplicationId()
    {
        return $this->getApplication()->getApplicationId();
    }


  protected function getElasticSearchConfiguration()
  {
    $values = $this->getApplication()->getApplicationIni();
    
    return array(
      'host' => $values['elastic_search_host']
    );
  }

  protected function getElasticaClient()
  {
      $client = new \Elastica\Client($this->getElasticSearchConfiguration());
      return $client;

  }

  protected function getElasticaIndex()
  {
    $client = $this->getElasticaClient();
    $index = $client->getIndex($this->getApplicationId());

    if (!$index->exists())
    {
        $index->create(array());
        $index->refresh();    
    }

    return $index;
  }


  protected function createStationTypeMapping($type)
  {
    $typeMapping = new \Elastica\Type\Mapping();
    $typeMapping->setType($type);
    $typeMapping->setProperties(array(
      'start_location' => array(
        'type'=>'geo_point'
      ),
      'end_location' => array(
        'type'=>'geo_point'
      )
    ));
    
    $typeMapping->send();

  }

  public function getStationType()
  {
    $index = $this->getElasticaIndex();
      $type = $index->getType('station');
      $this->createStationTypeMapping($type);
      return $type;

  }


  public function addStationDocument($station)
  {
    $this->getStationType()->addDocument(new \Elastica\Document($station->getId(), array(
      'user_id' => $station->getUserId(),
      'start_location' => array('lat'=> doubleval($station->getStartLatitude()), 'lon' => doubleval($station->getStartLongitude())),
      'end_location' => array('lat'=> doubleval($station->getEndLatitude()), 'lon' => doubleval($station->getEndLatitude())),
      'description' => $station->getDescription(),
      'zulu_start_date_string' =>$station->getZuluStartDateString(),
      'zulu_end_date_string' =>$station->getZuluEndDateString()
    )));

  }

  public function deleteStationDocument($station)
  {
    $this->getStationType()->deleteIds(array($station->getId()));

  }


  public function performQuery($filter)
  {
      $query = array(
          'query' => array(
            'matchAll' => new \stdClass()
          ),

          'filter' => $filter,
          'sort' => array(
            '_geo_distance' => array(
              'start_location' => array(-1,-1),
              'order' => 'asc',
              'unit' => 'km'
            )
          )

      );
      
      $path = $this->getElasticaIndex()->getName() . '/' . $this->getStationType()->getName() . '/_search';
      $response = $this->getElasticaClient()->request($path, \Elastica\Request::GET, $query);
      $responseArray = $response->getData();
      
      return $responseArray; ;
        

  }


  public function demo()
  {
      $query = array(
          'query' => array(
//              'query_string' => array(
//                  'query' => 'ruflin',
//              )
            'matchAll' => new stdClass()
          ),
/*          'filter' => array(
            'geo_bounding_box' => array(
              'start_location' => array(
                'top_left' => array(1,1),
                'bottom_right' => array(-1,-1)
              )
            )
          ),
*/          

          'filter' => array(
              'range' => array(
                'zulu_start_date_string' => array(
                  'gt' => '2011-01-01',
                  'lt' => '2014-01-01'
                )
              )
          ),
          'sort' => array(
            '_geo_distance' => array(
              'start_location' => array(-1,-1),
              'order' => 'asc',
              'unit' => 'km'
            )
          )

      );
      
      $path = $this->getElasticaIndex()->getName() . '/' . $this->getStationType()->getName() . '/_search';
      echo "here then";
      $response = $this->getElasticaClient()->request($path, \Elastica\Request::GET, $query);
      $responseArray = $response->getData();
      
      print_r($responseArray);
        
      die();
  }

}












