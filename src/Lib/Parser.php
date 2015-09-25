<?php
namespace Opensymap\Lib;

class Parser
{
    private $text;
    private $list;
    
    public function __construct($txt)
    {
        $this->text = $txt;
    }
    
    private function breakDown()
    {
        $res = array();
        $len = strlen($this->text);
        $txt = $this->text;
        $cur = 0;
        $end = $len;
        $del = array('{','}');
        while ($cur < $len) {
            foreach ($del as $k => $v) {
                if ($cur > $len) {
                    continue;
                }
                $pos = strpos($txt, $v, $cur);
                if ($pos === false) {
                    $pos = $len;
                }
                $cmd = substr($txt, $cur, $pos-$cur);
                if (!empty($cmd)) {
                    $res[] = $v == '}' ? '{'.$cmd.'}' : $cmd;
                }
                $cur = $pos+1;
            }
        }
        return $res;
    }

    private function exec(&$list)
    {
        $res = '';
        foreach ($list as $k => $raw) {
            if (empty($raw)) {
                continue;
            }
            if ($raw[0] != '{') {
                $res .= $raw;
                continue;
            }
            $raw = str_replace(array('{','}'), '', $raw);
            //This code is necessary for escape php notice.
            $araw = explode(' ', $raw, 2);
            $cmd  = $araw[0];
            $par  = (count($araw) == 2) ? $araw[1] : null;
            switch ($cmd) {
                case 'if':
                    $this->cmdIf($par, $k);
                    break;
                case 'var':
                    $res .= $this->cmdVar($k+1);
                    break;
            }
        }
        return $res;
    }
    
    private function cmdIf($par, $k)
    {
        eval('$res = ('.$par.');');
        
        if (!$res) {
            for ($i = $k; $i < count($this->list); $i++) {
                if ($this->list[$i] == '{/if}') {
                    $this->list[$i] = '';
                    break;
                } else {
                    $this->list[$i] = '';
                }
            }
        }
    }
    
    private function cmdVar($k)
    {
        $val = $_REQUEST[$this->list[$k]];
        if (substr_count($val, '[') > 0 and $val[0] == '[') {
            $val = str_replace(array('][','[',']'), array("','","'","'"), $val);
        }
        $this->list[$k-1] = '';
        $this->list[$k] = '';
        $this->list[$k+1] = '';
        return $val;
    }
    
    public function parse()
    {
        $rs_bd = array();
        $this->list = $this->breakDown($rs_bd);
        $res = $this->exec($this->list);
        return $res;
    }
}
