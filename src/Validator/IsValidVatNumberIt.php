<?php 
namespace Opensymap\Validator;

class IsValidVatNumberIt extends Validator
{
    public function check()
    {
        $pi = $this->field['value'];        
        if( strlen($pi) != 11 ) {
            return "La lunghezza della partita IVA non &egrave; corretta:\n"
                    ."la partita IVA dovrebbe essere lunga esattamente 11 caratteri.\n";
        }
        if( preg_match("/^[0-9]+\$/", $pi) != 1 ) {
            return "La partita IVA contiene dei caratteri non ammessi:\n"
                    ."la partita IVA dovrebbe contenere solo cifre.\n";
        }
        $s = 0;
        for( $i = 0; $i <= 9; $i += 2 ) {
            $s += ord($pi[$i]) - ord('0');
        }
        for( $i = 1; $i <= 9; $i += 2 ) {
            $c = 2*( ord($pi[$i]) - ord('0') );
            if( $c > 9 )  $c = $c - 9;
            $s += $c;
        }
        if( ( 10 - $s%10 )%10 != ord($pi[10]) - ord('0') ) {
            return "La partita IVA non &egrave; valida:\n"
            ."il codice di controllo non corrisponde.";
        }
        return false;
    }
}
