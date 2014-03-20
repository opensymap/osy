<?
class parser
{
    private $__text;
    private $__list;
    
    public function __construct($txt)
    {
        $this->__text = $txt;
    }
    
    private function __break_down()
    {
        $res = array();
        $len = strlen($this->__text);
        $txt = $this->__text;
        $cur = 0;
        $end = $len;
        $del = array('{','}');
        while($cur < $len)
        {
            foreach($del as $k => $v)
            {
                if ($cur > $len) continue;
                $pos = strpos($txt,$v,$cur);
                if ($pos === false) $pos = $len;
                $cmd = substr($txt,$cur,$pos-$cur);
                if (!empty($cmd))
                { 
                    $res[] = $v == '}' ? '{'.$cmd.'}' : $cmd;
                }
                $cur = $pos+1;
            }
        }
        return $res;
    }

    private function __exec(&$list)
    {
        $res = '';
        foreach($list as $k => $raw)
        {
            if (empty($raw))
            {
                continue;
            }
            if ($raw[0] != '{')
            {
                $res .= $raw;
                continue;
            }
            $raw = str_replace(array('{','}'),'',$raw);
            //This code is necessary for escape php notice.
            $araw = explode(' ',$raw,2);
            $cmd  = $araw[0];
            $par  = (count($araw) == 2) ? $araw[1] : null;
            switch($cmd)
            {
                case 'if':
                           $this->__cmd_if($par,$k);
                           break;
                case 'var':
                           $res .= $this->__cmd_var($k+1);
                           break;
            }
        }
        return $res;
    }
    
    private function __cmd_if($par,$k)
    {
         eval('$res = ('.$par.');');
        
         if (!$res)
         {
            for ($i = $k; $i < count($this->__list); $i++)
            {
                if ($this->__list[$i] == '{/if}')
                {
                    $this->__list[$i] = '';
                    break;
                }
                 else 
                {
                    $this->__list[$i] = '';
                }
            }
         }
    }
    
    private function __cmd_var($k)
    {
        $val = $_REQUEST[$this->__list[$k]];
        if (substr_count($val,'[') > 0 and $val[0] == '[')
        {
            $val = str_replace(array('][','[',']'),array("','","'","'"),$val);
        }
        $this->__list[$k-1] = '';
        $this->__list[$k] = '';
        $this->__list[$k+1] = '';
        return $val;
    }
    
    public function parse()
    {
        $rs_bd = array();
        $this->__list = $this->__break_down($rs_bd);
        $res = $this->__exec($this->__list);
        return $res;
    }
}
?>
