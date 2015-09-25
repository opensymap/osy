<?php
namespace Opensymap\Validator;

class IsValidEmailAddress extends Validator
{
    public function check()
    {
        // Controlla la corretta formattazione dell'indirizzo email tramite eregi.
        if (!preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $this->field['value'])) {
            return "Il campo {$this->field['label']} non contiene un indirizzo email valido";
        }
        // Se il risultato e' positivo confermo poi passo alla verifica del DNS
        list($alias, $domain) = explode("@", $this->field['value']);
        if (function_exists('chkdnsrr') && !Chkdnsrr($domain, "MX")) {
            return "Il campo {$this->field['label']} non contiene un indirizzo email valido";
        }
        return false;
    }
}
