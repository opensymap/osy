<?php
namespace Opensymap\Helper;

class HelperOsy 
{
/**
     *  Parse the string with regex and replace dummy
     *
     *  @param string $res string to parse
     *  @param string $src source of values
     *  @param string $pat pattern
     *
     *  @return string
     */
    public static function replaceVariable($res, $src = null, $pat = '<\[([^ ,]*)\]>')
    {
        $ores = $res;
        $sources = is_null($src) ? array(&$_REQUEST, &$GLOBALS, &$_POST, &$_GET) : array($src);
        // old $Pattern = "/<\[(.*)?\]>/";
        $pattern = "/".$pat."/";
        /**
         * Il pattern prevede che nella risorsa i parametri siano
         * indicati nel formato <[...]> questo per evitare problemi
         * con il segno di > e < eventualmente presenti.
         * In caso di risultati della query errata controllare che i
         * parametri rispettino il formato sopra riportato.
         */
        preg_match_all($pattern, $res, $dummies, PREG_PATTERN_ORDER);
        if (!is_array($dummies)) {
            //Se non è un array il risultato del matching restituisco
            //la risorsa senza sostituzioni;
            return $res;
        }
        /*
         * Scorro la lista delle variabili trovate dall'espressione regolare;
         */
        foreach ($dummies[1] as $k => $vnam) {
            //Se la variabile non ha nome continuo l'esecuzione
            if ($vnam!='0' && empty($vnam)) {
                continue;
            }
            $val = 'NULL';
            //Controllo se il suo valore è presente in una delle sorgenti
            foreach ($sources as $source) {
                if (!is_array($source) || empty($source)) {
                    continue;
                }
                if (array_key_exists($vnam, $source) && !empty($source[$vnam])) {
                    $val = is_array($source[$vnam]) ?
                           "'".implode("','", $source[$vnam])."'" :
                           str_replace("'", "''", $source[$vnam]);
                }
            }
            $tag = $dummies[0][$k];
            if ($val == 'NULL' && strpos($ores, "'".$tag."'") !== false) {
                $tag = "'".$tag."'";
            }
            $res = str_replace($tag, $val, $res);
        }
        return $res;
    }
    
    /**
     *  Parse the string with opensymap parse
     *
     *  @param string $str string to parse
     *
     *  @return string
     */
    public static function parseString($str)
    {
        $p = new \Opensymap\Lib\Parser($str);
        $p = $p->parse();
        return $p;
    } 

 /**
     *  If parameter a is empty return parameter b
     *
     *  @param string $a main argument
     *  @param string $b secondary argument
     *
     *  @return mixed
     */
    public static function nvl($a, $b)
    {
        return ( $a !==0 && $a !=='0' && empty($a)) ? $b : $a;
    }
    
    /**
     *  Eval ed exec the code passed in arguments
     *
     *  @param string $par list of parameters comma delimited
     *  @param string $cod contains code php to evalute ed execute
     *
     *  @return bool
     */
    public static function execString($par, $cod)
    {
        $fnc = create_function($par, $cod);
        if (empty($fnc)) {
            die($cod);
        }
        return $fnc();
    }
    
    /**
     *  Exec test on php string and return the result
     *
     *  @param string $str Contains sesssionid token
     *
     *  @return bool
     */
    public static function test($str)
    {
        $cod = str_replace('TEST', '$res = ', env::replaceVariable($str).';');
        eval($cod);
        return $res;
    }
}