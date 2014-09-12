<?php 
class ZeitfadenSimpleShard
{
  
}

class ZeitfadenShardingService
{
  public function __construct()
  {
    $this->cachedByUserId = array();
    $this->cachedShards = array();
    $this->cachedById = array();    
  }

  public function setApplicationId($val)
  {
    $this->applicationId = $val;
  }

  public function getApplicationId()
  {
    return $this->applicationId;
  }

    public function getDebugName()
    {
      return "HttpBasedShardingService";
    }
    
                    
    public function setProfiler($profiler)
    {
      $this->profiler = $profiler;
    }
    
    public function getProfiler()
    {
      return $this->profiler;
    }
    
    public function setShardProvider($val)
    {
      $this->shardProvider = $val;
    }
    



  public function getShardDataByUserId($userId)
  {
    $url = 'http://shardmaster.zeitfaden.com/shard/getShardForUser/userId/'.$userId.'/applicationId/'.$this->getApplicationId();
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    return $values;

  }



  public function getShardByUserId($userId)
  {
    if (!isset($this->cachedByUserId[$userId]))
    {
      try
      {
        $url = 'http://shardmaster.zeitfaden.com/shard/getShardForUser/userId/'.$userId.'/applicationId/'.$this->applicationId;
        
        $r = new HttpRequest($url, HttpRequest::METH_GET);
        $r->send();
        
        $values = json_decode($r->getResponseBody(),true);
        
        $shard = $this->shardProvider->provide();
        $this->mapShard($shard,$values['shard']);

        $this->cachedByUserId[$userId] = $shard;
      }
      catch (NoMatchException $e)
      {
        throw new NoMatchException('Did not find Shard for UserId: ' . $userId); 
      }
    }

    return $this->cachedByUserId[$userId];
  }




  public function getShardById($shardId)
  {
    if (!isset($this->cachedById[$shardId]))
    {
      try
      {
        $url = 'http://shardmaster.zeitfaden.com/shard/getShardById/shardId/'.$shardId.'/applicationId/'.$this->applicationId;
        
        $r = new HttpRequest($url, HttpRequest::METH_GET);
        $r->send();
        
        $values = json_decode($r->getResponseBody(),true);
        
        $shard = $this->shardProvider->provide();
        $this->mapShard($shard,$values['shard']);

        $this->cachedById[$shardId] = $shard;
      }
      catch (NoMatchException $e)
      {
        throw new NoMatchException('Did not find Shard for ShardId: ' . $shardId); 
      }
    }
    
    return $this->cachedById[$shardId];
  }
  
  public function getAllShards()
  {
      $url = 'http://shardmaster.zeitfaden.com/shard/getAllShards/applicationId/'.$this->applicationId;
      
      $r = new HttpRequest($url, HttpRequest::METH_GET);
      $r->send();
      
      $values = json_decode($r->getResponseBody(),true);
      
      $shardsData = $values['shards'];
      
      $shards = array();
      foreach ($shardsData as $shardData)
      {
        $shard = $this->shardProvider->provide();
        $this->mapShard($shard,$shardData);
        $shards[] = $shard;
      }
      
      return $shards;
      //print_r($shards);
      //die();
  }
  
  protected function mapShard($shard,$data)
  {
      $shard->injectValue('id',$data['shardId']);
      
      $shard->applicationId = $data['applicationId'];
      $shard->dbTablePrefix = $data['dbTablePrefix'];
      $shard->url = $data['url'];
      $shard->dbHost = $data['mySqlHost'];
      $shard->dbUser = $data['mySqlUser'];
      $shard->dbName = $data['mySqlDbName'];
      $shard->dbPassword = $data['mySqlPassword'];
      $shard->dbSocket = $data['mySqlSocket'];
      $shard->dbPort = $data['mySqlPort'];

      $shard->postgreSqlHost = $data['postgreSqlHost'];
      $shard->postgreSqlUser = $data['postgreSqlUser'];
      $shard->postgreSqlDbName = $data['postgreSqlDbName'];
      $shard->postgreSqlPassword = $data['postgreSqlPassword'];
      $shard->postgreSqlSocket = $data['postgreSqlSocket'];
      $shard->postgreSqlPort = $data['postgreSqlPort'];
      
      $shard->pathForFiles = $data['pathForFiles'];
    
  }
  
  
  public function getAvailableShards()
  {
    return $this->getAllShards();
  }
  
    
  public function asdasddoesUserHaveShard($userId)
  {
      $shard = $this->getShardByUserId($userId);
      if (count($shards) === 1)
      {
          return true;
      }
      elseif (count($shards) > 1)
      {
          throw new \ErrorException('too many shards for this user');
      }
      else
      {
          return false;    
      }
  }
    
  
  public function getLeastUsedShard()
  {
    $url = 'http://shardmaster.zeitfaden.com/shard/getLeastUsedShard/applicationId/'.$this->getApplicationId();
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    //$shardUrl = $values['shard']['url'];
    //$shardId = $values['shard']['shardId'];
    return $values['shard'];

  }


  public function assignUserToShard($userId,$shardId)
  {
      $url = 'http://shardmaster.zeitfaden.com/shard/assignUserToShard/shardId/'.$shardId.'/userId/'.$userId.'/applicationId/'.$this->getApplicationId();
      $r = new HttpRequest($url, HttpRequest::METH_POST);
      $r->send();
      $values = json_decode($r->getResponseBody(),true);
      return $values;
  }



  public function introduceUser($user)
  {
    $userId = $user->getId();
    
    $url = 'http://shardmaster.zeitfaden.com/shard/introduceUser/userId/'.$userId.'/applicationId/'.$this->applicationId;
    
    $r = new HttpRequest($url, HttpRequest::METH_PUT);
    $r->send();
    
    $values = json_decode($r->getResponseBody(),true);
    
    $shard = $this->shardProvider->provide();
    $this->mapShard($shard,$values['shard']);
  }
  
}