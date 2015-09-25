<?php
namespace Opensymap\Validator;

class IsValidNumber extends Validator
{
    public function check()
    {
        if (!is_numeric($this->field['value'])) {
            return "Il campo ".$this->field['label']." non &egrave; numerico.";
        }
        return false;
    }
}
