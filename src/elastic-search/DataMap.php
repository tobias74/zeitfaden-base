<?php 
namespace Zeitfaden\ElasticSearch;

class DataMap
{
    private $map = array();
    
    public function __construct()
    {
    }

    public function addColumn($columnName, $fieldName)
    {
        $this->map[$columnName] = $fieldName;
    }
    
    function getColumns()
    {
        $cols = array_keys($this->map);
        return $cols;
    }

    function getFields()
    {
        $fields = array_values($this->map);
        return $fields;
    }

    public function getFieldForColumn($column)
    {
      if (!isset($this->map[$column]))
      {
        throw new \ErrorException('column not found? '.$column.' in here: '.print_r($this->map,true));
      }
        return $this->map[$column];     
    }
    
    public function getColumnForField($field)
    {
        $columns = $this->getColumns();
        $pos = array_search($field, $this->getFields());
        
        if ($pos === false)
        {
            throw new \Exception('coding error. field not found.'.$field.' in here: '.print_r($this->map,true));
        }
        return $columns[$pos];      
    }
    
    public function existsColumn($column)
    {
        if (array_search($column, $this->getColumns()) !== false)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function existsField($field)
    {
        if (array_search($field, $this->getFields()) !== false)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
}

