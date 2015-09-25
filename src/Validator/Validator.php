<?php
namespace Opensymap\Validator;

abstract class Validator
{    
    protected $field = array();
    
    public function __construct(&$f) 
    {
        $this->field = $f;
        if (!array_key_exists('label',$this->field)) {
            $this->field['label'] = $this->field['name'];
        }
    }
    
    abstract public function check();
}
