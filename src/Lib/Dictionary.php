<?php 
namespace Opensymap\Lib;

class Dictionary 
{   
    public $repo;
    
    public function __construct($init = null)
    {
        $this->repo = is_array($init) ? $init : array();
    }
    
    public function __invoke($key)
    {
        return $this->get($key);
    }
    
    public function __call($method, $args)
    {
        echo 'ci sono'.$method;
    }
    
    public function  buildKey()
    {
        return implode('.', func_get_args());
    }
    
    public function &get($key) 
    {
        $ksearch = explode('.', $key);
        $target =& $this->repo;
        
        foreach ($ksearch as $k) { 
            if (array_key_exists($k, $target)) {
                $target =& $target[$k];
            } else {
                return null;
            }
        }
        return $target;
    }
     
    public function set($key, $value, $append = false)
    {
        $ksearch = explode('.',$key);
        $klast   = count($ksearch)-1;
        $target  =& $this->repo;
        
        foreach ($ksearch as $i => $k) {
            if ($klast == $i) {
                if (!$append) {
                    $target[$k] = $value;
                } elseif (is_array($target[$k])) {
                    $target[$k][] = $value;
                } else {
                    $target[$k] = array($value);
                }
            } elseif (array_key_exists($k, $target)) {
                $target = &$target[$k];
            } elseif(count($ksearch) != ($i+1)) {
                $target[$k] = array(); 
                $target =& $target[$k];
            } 
        }
    }

    public function keyExists($key)
    {
        $ksearch = explode('.',$key);
        $target = $this->repo;
        $nnode = count($ksearch);
        foreach($ksearch as $k) {
            if (!is_array($target)) { 
                break;
            } 
            if (array_key_exists($k, $target)){
                $target = $target[$k];
            } else {
                break;
            }
            $nnode--;
        }
        return $nnode ? false : true;
    }
    
    public function keyIsEmpty($key)
    {
        if ($this->keyExists($key)){
            return (count($this->get($key)) != 0);
        }
        return true;
    }
}
