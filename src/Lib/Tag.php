<?php
namespace Opensymap\Lib;

class Tag
{
    private $att = array();
    private $cnt = array();
    public $ref = array();
    public $deep = 0;
    /*private $event = array();*/
    public $parent = null;

    public function __construct($tag = 'dummy', $id = null, $content = null)
    {
        $this->att(0, $tag);
        if (!empty($id)) {
            $this->att('id', $id);
        }
        if (!is_null($content)) {
            $this->add($content);
        }
    }

    public function __get($a)
    {
        if ($a == 'tag') {
            return $this->att[0];
        }
        return (is_array($this->att) && array_key_exists($a, $this->att)) ? $this->att[$a] : null;
    }

    public function __set($p, $v)
    {
        $this->att[$p] = $v;
    }

    public function add($a, $d = 'last')
    {
        if (is_object($a)) {
            if ($a instanceof tag) {
                $a->deep = abs($this->deep) + 1;
                $this->deep = abs($this->deep) * -1;
            }
            if ($a->id && array_key_exists($a->id, $this->ref)) {
                $a = $this->ref[$a->id];
                return $a;
            } elseif ($a->id) {
                $this->ref[$a->id] = $a;
            }
            $a->parent =& $this;
        }
        if ($d=='last') {
            if (is_array($this->cnt)) {
                array_push($this->cnt, $a);
            } else {
                //var_dump($a->id);
                //$this->cnt[] = $a;
            }
        } else {
            array_unshift($this->cnt, $a);
            ksort($this->cnt);
        }
        return $a;
    }

    public function add2($a)
    {
        if (!is_array($a)) {
            return $this->add($a);
        }
        foreach ($a as $t) {
            $this->add($t);
        }
        return $t;
    }

    public function att($p, $v = '', $concat = false)
    {
        if (is_array($p)) {
            foreach ($p as $k => $v) {
                $this->att[$k] = $v;
            }
            return $this;
        }
        if ($concat && !empty($this->att[$p])) {
            $concat_car = ($concat === true) ? ' ' : $concat;
            $this->att[$p] .= "{$concat_car}{$v}";
        } else {
            $this->att[$p] = $v;
        }
        return $this;
    }

    public function closest($query)
    {
        $app =& $this;
        $cls = $id = $tag = array();
        foreach (explode(',', $query) as $k => $qry) {
            switch ($qry[0]) {
                case '.':
                    $cls[] = $qry;
                    break;
                case '#':
                    $id[] = $qry;
                    break;
                default:
                    $tag[] = $qry;
                    break;
            }
        }
        while (!empty($app) &&
               !(in_array($app->tag, $tag) ||
               $this->inArray2(explode(' ', $app->class), $cls) ||
               in_array($app->id, $id))
        ) {
            $app =& $app->parent;
        }
        return $app;
    }

    protected function _build()
    {
        $str_tag = $str_cnt = null;
        if (is_array($this->cnt)) {
            foreach ($this->cnt as $kcnt => $cnt) {
                //$str_cnt .= ($this->trigger($cnt,'onbuild') ? "" : '' ). $cnt;
                $str_cnt .= $cnt;
            }
        }
        $tag = is_array($this->att) ? array_shift($this->att) : null;
        if ($tag == 'dummy') {
            return $str_cnt;
        } elseif (!empty($tag)) {
            $str_spc = $this->deep != 0 ? "\n".str_repeat("  ", abs($this->deep)) : '';
            $str_tag = $str_spc."<{$tag}";
            foreach ($this->att as $key => $val) {
				try {
					$str_tag .= " {$key}=\"{$val}\"";
				} catch (Exception $e){
					echo $this->id;
				}
                
            }
            $str_tag .= ">";
        }
        if (!in_array($tag, array('input','img','link'))) {
            $str_spc2 = $this->deep < 0 ? $str_spc : '';
            $str_tag .= $str_cnt . (!empty($tag) ? $str_spc2."</{$tag}>" : '');
        }
        unset($this->att); // = array();
        unset($this->cnt); // = array();
        return $str_tag;
    }
    
    public static function inArray2(&$a, &$b)
    {
        if (empty($a)) {
            return;
        }
        if (is_array($a)) {
            foreach ($a as $val) {
                if (in_array($val, $b)) {
                    return true;
                }
                return false;
            }
        } else {
            return in_array($a, $b);
        }
    }
    
    public function tagEvent($evt, $fnc = null)
    {
        if (!is_null($fnc)) {
            $this->event[$evt][] = $fnc;
        } elseif (array_key_exists($evt, $this->event)) {
            return $this->event[$evt];
        }
    }

    /*private function trigger($obj,$evt)
    {
        if ($obj instanceof tag)
        {
            $e = $obj->tagEvent($evt);
            if (is_array($e))
            {
                foreach($e as $f)  $f($this);
            }
            return true;
        }
        return false;
    }*/

    public static function create($tag)
    {
        return new tag($tag);
    }

    public function get()
    {
        return $this->_build();
    }

    public function getAtt()
    {
        return $this->att;
    }

    public function getCnt($i)
    {
        return $this->cnt[$i];
    }

    public function child($i = 0)
    {
        if (is_null($i)) {
            return $this->cnt;
        }
        if (array_key_exists($i, $this->cnt)) {
            return $this->cnt[$i];
        }
        return false;
    }

    public function isEmpty()
    {
        return count($this->cnt) > 0 ? false : true;
    }

    public function __toString()
    {
        try {
            $str = $this->get();
            return $str;
        } catch (\Exception $e) {
            //var_dump($str);
            //trigger_error($e->getMessage());
            return $e->getMessage();
            echo '<pre>';
            //var_dump(debug_backtrace(10));
            echo '</pre>';
            return $this->id;
        }
    }
}
