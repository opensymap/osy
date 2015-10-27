<?php
namespace Opensymap\App\DataModel;

class FieldModel 
{
    private $properties = array();
    
    public function __construct($properties)
    {
        $this->properties = $properties;
    }
    
    public function __get($key)
    {
        return array_key_exists($key,$this->properties) ? $this->properties[$key] : null;
    }
    
    public function getHtmlName()
    {
        return $this->fieldViewAssoc;
    }
    
    public function getValue()
    {
        return empty($_REQUEST[$this->fieldViewAssoc]) ? null : $_REQUEST[$this->fieldViewAssoc];
    }
    
    public function isPrimaryKey()
    {
        return empty($this->properties['isPrimaryKey']) ? false : true;
    }
}
