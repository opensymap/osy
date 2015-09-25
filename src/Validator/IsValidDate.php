<?php 
namespace Opensymap\Validator;

class IsValidDate extends Validator
{
    public function check()
    {
        list($g,$m,$a) = explode('/', $this->field['value']);
        //Se la data è valida la formatto secondo il tipo di db.
        if (!checkdate($m,$g,$a)) {
            return "Il campo {$this->field['label']} contiene una data non valida ($d}/{$m}/{$a}).";
        } else {
            $this->field['value'] = "{$a}-{$m}-{$g}";
        }
        return false;
    }
}
