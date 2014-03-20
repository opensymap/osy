<?
function in_array2(&$a,&$b)
{
    if (empty($a)) return;
    if (is_array($a))
    {
        foreach($a as $val)
        {
            if (in_array($val,$b))
            {
                return true;
            }
            return false;
        }
    }
     else
    {
        return in_array($a,$b);
    }
}

class tag
{
    private $att = Array();
    private $cnt = Array();
    public $ref = Array(); 
    /*private $event = array();*/
    public $parent = null;
    
    public function __construct($tag='dummy',$id=Null,$content=null)
	{
        $this->att(0,$tag);
        if (!empty($id)) $this->att('id',$id);
        if (!is_null($content)) $this->add($content);
    }
    
    public function __get($a)
	{
        if ($a == 'tag') return $this->att[0];
        return (is_array($this->att) && key_exists($a,$this->att)) ? $this->att[$a] : null;
    }
    
    public function __set($p,$v)
	{
       $this->att[$p] = $v;
    }
    
    public function add($a,$d='last')
	{
        //echo 'sto aggiungendo '.$a->id."<br>\n";
        //$this->trigger($a,'onadd');
        if (is_object($a))
        {
          if ($a->id && key_exists($a->id,$this->ref))
          { 
             $a = $this->ref[$a->id];
             return $a;
          }
           elseif ($a->id)
          {
            $this->ref[$a->id] = $a;
          }
          $a->parent =& $this;
        }
        if ($d=='last')
        {
            if (is_array($this->cnt))
            {
                array_push($this->cnt,$a);
            }
             else
            {
                var_dump($a->id);
            }
        }
         else
        {
            array_unshift($this->cnt,$a);
            ksort($this->cnt);
        }
        return $a;
    }
    
    public function add2($a)
	{
        if (!is_array($a))  return $this->add($a);
		foreach($a as $t) $this->add($t);
        return $t;
    }
    
    public function att($p,$v='',$concat=false)
	{
        if (is_array($p))
		{
            foreach ($p as $k => $v) $this->att[$k] = $v;
            return $this;
        } 
        if ($concat && !empty($this->att[$p]))
        {
            $concat_car = ($concat===true) ? ' ' : $concat;
            $this->att[$p] .= "{$concat_car}{$v}";
        } 
         else 
        {
            $this->att[$p] = $v;
        }
        return $this;
    }
    
    public function closest($query)
    {
        $app =& $this;
        $cls = $id = $tag = array();
        foreach(explode(',',$query) as $k => $qry)
        {
            switch($qry[0])
            {
                case '.' : 
                           $cls[] = $qry;
                           break;
                case '#' : 
                           $id[] = $qry;
                           break;
                default  :
                           $tag[] = $qry;
                           break;
            }
        }
        while (!empty($app) && !(in_array($app->tag,$tag) || in_array2(explode(' ',$app->class),$cls) || in_array($app->id,$id)))
        {
            $app =& $app->parent;
        }
        return $app;
    }
    
    protected function build($depth=0)
	{
        $str_tag = $str_cnt = null;
        if (is_array($this->cnt))
        {
            foreach($this->cnt as $kcnt => $cnt)
		    {
                //$str_cnt .= ($this->trigger($cnt,'onbuild') ? "" : '' ). $cnt;
                $str_cnt .= $cnt;
            }
        }
        $tag = is_array($this->att) ? array_shift($this->att) : null;
        if ($tag == 'dummy')
        {
            return $str_cnt;
        }
         elseif (!empty($tag))
		{
            $str_tag = "<{$tag}";
            foreach($this->att as $key => $val)
			{
                $str_tag .= " {$key}=\"{$val}\"";
            }
            $str_tag .= ">";
        }
        if (!in_array($tag,array('input','img','link')))
        {
            $str_tag .= $str_cnt . (!empty($tag) ? "</{$tag}>\n" : '');
        }
        unset($this->att); // = array();
        unset($this->cnt); // = array();
        return $str_tag;
    }
    
    public function tag_event($evt,$fnc=null)
    {
        if (!is_null($fnc))
        {
            $this->event[$evt][] = $fnc;
        }
         elseif(array_key_exists($evt,$this->event))
        {
           return $this->event[$evt];
        }
    }
    
    /*private function trigger($obj,$evt)
    {
		if ($obj instanceof tag)
        {
            $e = $obj->tag_event($evt);
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
    
    public function get($depth=0)
	{
        return $this->build($depth);
    }
    
    public function get_cnt($i)
	{
        return $this->cnt[$i];
    }
    
	public function child($i=0)
	{
		if (is_null($i))               return $this->cnt;
		if (key_exists($i,$this->cnt)) return $this->cnt[$i];
		return false;
	}
	
    public function is_empty()
	{
        return count($this->cnt) > 0 ? false : true;
    }

    public function __toString()
	{
        try
        {
            $str = $this->get();
            return $str;
        }
        catch (Exception $e)
        {
            //var_dump($str);
            trigger_error($e->getMessage());
            return $this->id;
        }

    }
}

class page extends tag
{

     private $__part = Array();
     private $__script = Array();
     public function __construct()
     {
        $this->__part['doctype'] = 'html';
        $this->add_part('html','html');
        $this->add_part('head','head','html');
        $this->add_part('body','body','html');
     }
     
     public function __get($p)
     {
        return $this->__part[$p];
     }
     
     public function add_body($o)
     {
        return $this->__part['body']->add($o);
     }

     public function add_css($path)
     {
        $this->__part['head']->add(tag::create('link'))
                             ->att('rel','stylesheet')
                             ->att('href',$path);
     }
     
     public function add_meta()
     {
        return $this->__part['head']->add(tag::create('meta'));
     }
     
     public function add_part($p,$t,$par=null)
     {
          $t = is_object($t) ? $t : tag::create($t);
          $this->__part[$p] = !empty($par) ? $this->__part[$par]->add($t) : $this->add($t);
     }
     
     public function add_script($src,$cod='')
     {
        if (in_array($src,$this->__script)) return false;
        $this->__script[] = $src;
        $s = $this->__part['head']->Add(tag::create('script'));
        if ($src) $s->att('src',$src);
        $s->add($cod);
        return true;
     }

     public function get()
     {
        return trim("<!DOCTYPE {$this->doctype}>\n".parent::get());
     }
     
     public function part($p)
     {
        return $this->__part[strtolower($p)];
     }

     public function set_doc_type($d)
     {
        $this->__part['doctype'] = $d;
     }
     
     public function set_title($t)
     {
        $this->__part['head']->Add(new tag('title'))->add($t);
     }
  }
?>
