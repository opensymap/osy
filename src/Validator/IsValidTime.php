<?php
namespace Opensymap\Validator;

class IsValidTime extends Validator
{
    public function check()
    {
        $part = explode($f['value']);
        foreach ($part as $Key => $Val) {
            switch ($Key) {
                case 0:
                    if (!is_numeric($Val)) {
                        return "Il campo ora contiene valori non validi";
                    }
                    //No break;
                case 1:
                case 2:
                    if (!is_numeric($Val) or $Val > 59 or $Val < 0) {
                        return "Il campo minuti contiene valori non validi";
                    }                
                default:
                    return "Il campo di tipo ora contiene un numero di parti superiore a tre";
                    break;
            }
        }
        return false;
    }
}
