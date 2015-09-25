<?
function nvl($a,$b)
{
    return ( $a !==0 && $a !=='0' && empty($a)) ? $b : $a;
}

function get_global($nam,$arr_val)
{
    $res = '';
    if (strpos($nam,'[') === false)
    {
        return key_exists($nam,$arr_val) ? $arr_val[$nam] : $res;
    }
    $arr_nam = explode('[',str_replace(']','',$nam));
    $res = false;
    foreach($arr_nam as $k => $nam)
    {
      if (array_key_exists($nam,$arr_val))
      {
          if (is_array($arr_val[$nam]))
          { 
            $arr_val = $arr_val[$nam]; 
          } 
            else 
          { 
            $res = $arr_val[$nam];
            break; 
          }
       }
    }
    return $res;
}
/*
 * Master class component
 */
class component extends tag
{
    protected $__par = array();
    protected $__evt = array();

    public function __construct($tag,$id=null)
    {
        parent::__construct($tag,$id);
    }
    
    protected function build()
    {
        $this->trigger('onbuild');
        if (method_exists($this,'__build_extra__'))
        {
            $this->__build_extra__();
        }
        //echo 'sto costruendo '.nvl($this->id,$this->name)."<br>\n";
        return parent::build(-1);
    }
    
    public function par($key,$val=null,$fnc=null)
    {
        $this->__par[$key] = $val;
        if (is_callable($fnc))
        {
            $fnc($key,$val,$this);
        }
        $this->trigger('oninsert',$key);
    }
    
    public function get_par($key)
    {
        return key_exists($key,$this->__par) ? $this->__par[$key] : null;
    }

    public function man($mom,$par,$fnc) //Parameter manager
    {
        $this->__evt[$mom][$par] = $fnc;
    }
    
    public function trigger($mom='onbuild',$par=null)
    {
        foreach(array_reverse($this->__par) as $k => $v)
        {
            if (!is_null($par) && $par != $k) continue;
            if (key_exists($mom,$this->__evt) && is_array($this->__evt[$mom]) && key_exists($k,$this->__evt[$mom]))
            {
                $this->__evt[$mom][$k]($v,$this);
            }
        }
    }
}

/*
 * Autocomplete component
 */
class autocomplete extends component
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('input',nvl($id,$nam));
        $this->att('type','text');
        $this->att('name',$nam);
        $this->att('class','autocomplete');
        osy_view::$page->add_script(OSY_WEB_ROOT.'/js/osy.autocomplete.js');
    }
    
    public function __build_extra__()
    {
        $this->att('ops',$_REQUEST['ajax']);
        if (!empty($_REQUEST['ajax']))
        {
            $sql = env::ReplaceVariable($this->get_par('datasource-sql'));
            $sql = env::parse_string($sql);
            $res = env::$dba->exec_query($sql,null,'ASSOC');
            die(json_encode($res)); 
        }
         else
        {
            $val = get_global($this->id,$_REQUEST);
            if (!empty($val)) $this->att('value',$val);
        }
    }
}

/*
 * Button component
 */
class button extends component
{
    public function __construct($nam,$id=null,$typ='button')
    {
        parent::__construct('button',nvl($id,$nam));
        $this->att('name',$nam);
        $this->att('type',$typ);
        $this->att('label','no-label');
        $this->par('no-label',true);
    }
    
    protected function __build_extra__()
    {
        $this->add($this->label);
        $frm_id = $this->get_par('form-related');
        if (!empty($frm_id))
        {
            $str_par = "obj_src={$this->id}";
            $frm_par = (key_exists('rel_fields',$this->__par)) ? explode(',',$this->__par['rel_fields']) : array();
            foreach($frm_par as $fld)
            { 
                $str_par .= '&'.$fld.'='.get_global($fld,$_REQUEST);
            }
            list($w,$h) = env::$dbo->exec_unique("SELECT coalesce(".env::$dba->cast('w.p_vl','integer').",640),
                                                         coalesce(".env::$dba->cast('h.p_vl','integer').",480)
                                                  FROM osy_obj f
                                                  LEFT JOIN osy_obj_prp h ON (f.o_id = h.o_id AND h.p_id = 'height')
                                                  LEFT JOIN osy_obj_prp w ON (f.o_id = w.o_id AND w.p_id = 'width')
                                                  WHERE f.o_id = ?",array($frm_id));
            $this->att('onclick',"osycommand.open_window('{$frm_id}','{$str_par}',$w,$h)");
        }
        //frm_rel_src
        /*
         *  $frm = self::get_parameter($nam,'REL_FRM_SRC');
         *  $fld_rel = self::get_parameter($nam,'REL_FIELDS');
         *  $fld_par = self::get_parameter($nam,'PAR_FIELDS');
         *  $obj->Att('onclick',"FieldSearch('{$nam}','{$frm}','{$fld_rel}','{$fld_par}')");
         */
    }
}

class submit extends button
{
     public function __construct($nam,$id=null)
     {
        parent::__construct($nam,nvl($id,$nam),'submit');
     }
}

//costruttore del combo box
class combo_box extends component
{
    public $__dat = array();
	public $__grp = array();

	public function __construct($nam,$id=null)
    {
        parent::__construct('select',nvl($id,$nam));
        $this->att('name',$nam);
    }

    protected function __build_extra__()
    {
        if ($dsr = $this->get_par('datasource'))
        {
            $this->__dat = $dsr;
        }
         elseif ($sql = $this->get_par('datasource-sql'))
        {
            $sql = env::ReplaceVariable($sql);
            $sql = env::parse_string($sql);
            try
            {
              $this->__dat = env::$dba->exec_query($sql,NULL,'BOTH');
            }
             catch(Exception $e)
            {
               $this->att(0,'dummy');
               $this->add('<div class="osy-error" id="'.$this->id.'">SQL ERROR - [LABEL]</div>');
               $this->add('<div class="osy-error-msg">'.($e->getMessage()).'<br>'.nl2br($sql).'</div>');
               return;
            }
        }
        if (!empty($this->__dat) && key_exists('_group',$this->__dat[0]))
        {
            if (!$this->get_par('option-select-disable')){  array_unshift($this->__dat,array('','- select -','_group'=>'')); }
            $this->__build_tree__($this->__dat);
        }
         else
        {
            if (!$this->get_par('option-select-disable')){ array_unshift($this->__dat,array('','- select -')); }
            $val = get_global($this->name,$_REQUEST);
            //echo $this->name.' - '.$val;
            foreach($this->__dat as $k => $itm)
            {
                $sel = ($val == $itm[0]) ? ' selected' : '';
                $this->add('<option value="'.$itm[0].'"'.$sel.'>'.nvl($itm[1],$itm[0])."</option>\n");
            }
        }
    }
    
     private function __build_tree__($res)
	 {
	 	$dat = array();
		foreach($res as $k => $rec)
		{
			if (empty($rec['_group']))
			{
				$dat[] = $rec;
			}
			 else
			{
				$this->__grp[$rec['_group']][] = $rec;
			}
		}
		$this->__build_branch__($dat);
	 }
	 
	 private function __build_branch__($dat,$lev=0)
	 {
	 	if (empty($dat)) return;
		$len = count($dat)-1;
        $cur_val = get_global($this->name,$_REQUEST);
		foreach($dat as $k => $rec)
		{
			$val = array();
			foreach($rec as $j => $v)
			{
				if (!is_numeric($j)) continue;
                if (count($val) == 2) continue;
				$sta = (empty($lev)) ? '' : '|';
				$end = $len == $k    ? "\\" : "|";
				$val[] = empty($val) ? $v : str_repeat('&nbsp;',$lev*5).$v;
			}
            $sel = ($cur_val == $val[0]) ? ' selected' : '';
			$this->add('<option value="'.$val[0].'"'.$sel.'>'.nvl($val[1],$val[0])."</option>\n");
			if (array_key_exists($val[0],$this->__grp))
			{
				$this->__build_branch__($this->__grp[$val[0]],$lev+1);
			}
		}
	 }
}

class check_box extends component
{
    private $__hdn = null;
    private $__chk = null;
    
    protected function __build_extra__()
    {
        if (array_key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id]))
        {
            $this->__chk->att('checked','checked');
        }
    }
    
    public function __construct($nam,$id=null)
    {
        parent::__construct('span',$nam);
        $this->__hdn = $this->add(new hidden_box($nam));
        $this->__chk = $this->add(new input_box('checkbox','chk_'.$nam,'chk_'.$nam));
        $this->__chk->att('class','osy-check')->att('value','1');
    }
}

class dummy extends component
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('div',$nam);
        $this->att('class','osy-dummy');
    }
    
    protected function __build_extra__()
    {
         if (!($txt = get_global($this->id,$_REQUEST)))
         {
            $txt = $this->get_par('text');
            $txt = env::ReplaceVariable($txt);
         }
         $this->add($txt);
    }
}

class input_box extends component
{
    public function __construct($typ,$nam,$id=null)
    {
        parent::__construct('input',$id);
        $this->att('type',$typ);
        $this->att('name',$nam);
        //gestore del parametro get-request-value
        $this->man('onbuild','get-request-value',function($fld)
        {
    
        });
    }
    protected function __build_extra__()
    {
           $val = get_global($this->id,$_REQUEST);
           if (!empty($val)) $this->att('value',$val);
    }
}

class date_box extends input_box
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('text',$nam,nvl($id,$nam));
        //$this->par('get-request-value',$nam);
        $this->att('readonly')
             ->att('size',8)
             ->att('maxlength',12)
             ->att('class','osy-datebox');
             /*->att('onclick','cal.ViewCalendar(event,this);');*/
    }
    
    protected function __build_extra__()
    {
        if (!empty($_REQUEST[$this->id]) && $this->get_par('date-format'))
        {
           $adat = explode('-',$_REQUEST[$this->id]);
           if (count($adat) == 3)
           {
               $_REQUEST[$this->id] = str_replace(array('yyyy','mm','dd'),$adat,$this->get_par('date-format'));
           }
        }
        $this->att('value',$_REQUEST[$this->id]);
    }
    
    public static function convert($d,$df='dd/mm/yyyy')
    {
        if (!empty($d) && !empty($df))
        {
           $adat = explode('-',$d);
           if (count($adat) == 3)
           {
               return str_replace(array('yyyy','mm','dd'),$adat,$df);
           }
        }
        return $d;
    }
}

class hidden_box extends input_box
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('hidden',$nam,nvl($id,$nam));
        $this->par('get-request-value',$nam);
    }
}

class iframe extends component
{
    public function __construct($name)
    {
        parent::__construct('iframe',$name);
        $this->att('name',$name);
        $this->att("style",'border: 1px solid gray; width: 99%;')->add('&nbsp;');
    }
    
    protected function __build_extra__()
    {
        $src = $this->get_par('src');
        if (!key_exists($this->id,$_REQUEST) && !empty($src))
		{
            $_REQUEST[$this->id] = $src;
        }
        if(key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id]))
        {
            $this->att('src',$_REQUEST[$this->id]);
        }
    }
}

class iframetab extends component
{
    private $iframe;
    
    public function __construct($name)
    {
       parent::__construct('div',$name);
       $this->att('class','osy-iframe-tab tabs');
       osy_view::$page->add_script(OSY_WEB_ROOT.'/js/osy.iframe.js');
       //osy_form::$page->add_script('../lib/jquery/jquery.scrollabletab.js');
    }
    
    protected function __build_extra__()
    {
        $this->add(tag::create('ul'));
        //$this->iframe = $this->add(tag::create('iframe'));
        //$this->iframe->att('name',$this->id)->att("style",'width: 100%;');
        $src = $this->get_par('src');
        if (!key_exists($this->id,$_REQUEST) && !empty($src))
		{
            $_REQUEST[$this->id] = $src;
        }
        if(key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id]))
        {
            $this->att('src',$_REQUEST[$this->id]);
        }
    }
}

class label extends component
{
    public function __construct($name)
    {
        parent::__construct('label',$name);
        $this->att('class','normal');
        $this->add(new hidden_box($name));
    }
    
    protected function __build_extra__()
    {
        $val = get_global($this->id,$_REQUEST);
        if ($sql = $this->get_par('datasource-sql'))
        {
            $sql = env::ReplaceVariable($sql);
            $sql = env::parse_string($sql);
            $val = $this->get_par('db-field-connected') ? $val : '[get-first-value]';
            $val = $this->get_from_datasource($val,$sql,env::$dba);
        }
        if ($pointer = $this->get_par('global-pointer'))
        {
            $ref = array(&$GLOBALS,&$_REQUEST,&$_POST);
            foreach ($ref as $global_arr)
            {
                if (key_exists($pointer,$global_arr))
                {
                    $val = $global_arr[$pointer];
                    break;
                }
            }
        }
        $this->add(nvl($val,'&nbsp;'));
    }
    
    public static function get_from_datasource($val,$lst,$db=null)
    {
        $lbl = $val;
        if (!is_array($lst) && !is_null($db))
        {
            try
            {
                $lst = $db->exec_query($lst,null,'NUM');
            }
             catch(Exception $e)
            {
               $this->att(0,'dummy');
               $this->add('<div class="osy-error" id="'.$this->id.'">SQL ERROR - [LABEL]</div>');
               $this->add('<div class="osy-error-msg">'.($e->getMessage()).'</div>');
               return;
            }
        }
        
        if ($val == '[get-first-value]')
        {
            return !empty($lst[0]) ? nvl($lst[0][1],$lst[0][0]) : null;
        }
         elseif (is_array($lst))
        {
            foreach($lst as $k => $rec)
            {
                if ($rec[0] == $val)
                {
                    return nvl($rec[1],$rec[0]);
                }
            }
        }
        return $lbl;
     }
}

//costruttore del text box
class text_box extends input_box
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('text',$nam,nvl($id,$nam));
        $this->par('get-request-value',$nam);
    }
}

//costruttore del multi box
class multi_box extends component
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('div',$nam,nvl($id,$nam));
        $this->att('class','osy-multibox');
    }
    
    protected function __build_extra__()
    {
        $sql = $this->get_par('datasource-sql');
        if (empty($sql))
        {
            die('[ERROR] - Multibox '.$this->id.' - query builder assente');
        }
        $sql = env::ReplaceVariable($sql);
        $sql = env::parse_string($sql);
        $res = env::$dba->exec_query($sql,null,'ASSOC');
        if (!empty($res))
		{
        	$mlt_tbl = $this->add(tag::create('table'));
            $val_from_db = (key_exists($this->id,$_REQUEST) && is_array($_REQUEST[$this->id])) ? false : true;
			foreach($res as $k => $cmp_raw)
			{
				if ($val_from_db)
			    {
                   $_REQUEST[$this->id][$cmp_raw['id']] = $cmp_raw['val'];
    		    }
                $mlt_row = $mlt_tbl->add(tag::create('tr'));
				$cmp = $lbl = null;
                if ($this->readonly)
                {
                    $cmp = tag::create('span');
                    if ($cmp_raw['typ'] == 'CMB')
                    {
                        $cmp_raw['val'] = label::get_from_datasource($cmp_raw['val'],$cmp_raw['sql_qry'],env::$dba);
                    }
                    $cmp->add($cmp_raw['val']);
                }
                 else
                {
    				$is_req = $cmp_raw['is_req'];
      				$cmp_nam = "{$this->id}[{$cmp_raw['id']}]";
                    switch($cmp_raw['typ'])
    				{	
    					case 'DAT' :
    								 $cmp = new date_box($cmp_nam);
    								 break;
    					case 'TXT' : 
                        case 'NUM' :
    								 $cmp = new text_box($cmp_nam);
                                     if ($cmp_raw['typ'] == 'NUM'){
                                         $cmp->att('class','numeric',true);
                                     } else {
                                         $cmp->att('class','text',true);
                                     }
    								 break;
    					case 'CMB' :
    								 $cmp = new combo_box($cmp_nam);
                                     //echo $cmp_raw['sql_qry'];
    								 $cmp->par('datasource-sql',Env::ReplaceVariable($cmp_raw['sql_qry']));
    								 break;
    				}
       				$cmp->att('label',$cmp_raw['nam']);
                    if (!empty($is_req))
                    {
                        $lbl = '(*) ';
                        $cmp->att('class','is-request',true);
                    }
                }
				if (!is_null($cmp))
				{
					$lbl = "<label class=\"multibox\">{$lbl}{$cmp_raw['nam']}</label>";
                    $mlt_row->add(tag::create('td'))->add($lbl);
					$mlt_row->add(tag::create('td'))->add($cmp);
				}
			}
		}
    }
}

//costruttore del text box
class password_box extends input_box
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('password',$nam,nvl($id,$nam));
        $this->par('get-request-value',$nam);
        $this->att('autocomplete','off');
    }
}

//Costruttore del pannello html
class panel extends component
{
    private $__cell = array();
    private $__crow = null;
	private $__tag = array('tr','td');
    
    public function __construct($id,$tag='table')
    {
        parent::__construct($tag,$id);
        $this->par('label-position','outside');
        if ($tag=='div') $this->__tag = array('div','div');
    }
    
    protected function __build_extra__()
    {
        ksort($this->__cell);
        
        foreach($this->__cell as $irow => $row)
        {
            ksort($row);
            $this->__row();
            foreach($row as $icol => $col)
            {
                //ksort($col);
                foreach($col as $icnt => $obj)
                {
                   $colspan=null;
                   if (is_object($obj['obj']) && $obj['obj']->tag == 'button')
                   {
                        unset($obj['lbl']);
                        $colspan=2;
                   }
                    elseif (!empty($obj['lbl']))
                   {
                       $obj['lbl'] = '<label'.(get_class($obj['obj']) == 'panel' ? ' class="osy-form-panel-label"' : '').'>'.$obj['lbl'].'</label>';
                   }
                   switch($this->__par['label-position'])
                   {
                        case 'outside':
                                        if (key_exists('lbl',$obj))
                                            $this->__cell($obj['lbl']);
                                        $this->__cell($obj['obj'],$colspan);
                                        break;
                        default :
                                        $this->__cell($obj,$colspan);
                                        break;
                   }
                }
            }
        }
    }
    
    private function __row()
    {
        return $this->__crow = $this->add(tag::create($this->__tag[0]));
    }
    
    private function __cell($content=null,$colspan=null)
    {
        if (is_null($content)) return;
        $cel = $this->__crow->add(tag::create($this->__tag[1]));
        if (!empty($colspan)) $cel->att('colspan',$colspan);
        $cel->add2($content);
   		return $cel;
    }
    
    public function put($lbl,$obj,$r=0,$c=0)
    {
        $this->__cell[$r][$c][] = array('lbl'=>$lbl,'obj'=>$obj);
    }
}

class file_box extends component
{
    public function __construct($name)
	{
		parent::__construct('input',$name);
        $this->att('name',$name);
        $this->att('type','file');
    }
    
    protected function __build_extra__()
    {
        //$form = $this->closest('form');
        //if (is_object($form)) $form->att('enctype','multipart/form-data');
    }
}

class form extends component
{
	private $__cmp = array();
	public  $corner = null;
	
    public function __construct($nam)
	{
		parent::__construct('form',$nam);
		$this->att('name',$nam);
        /*
         * Creo una div cornice che conterrà il panel principale in modo da avere
         * un componente a cui poter assegnare un altezza fissa e quindi far comparire
         * le barre di scorrimento
         */
        $this->corner = $this->add(tag::create('div'))->att('id',$nam.'-corner');
        /*
         * Aggiungere il panel nella posizione 0 serve ad assegnare un panel di default
         * alla form su cui verranno aggiunti tutti i componenti che non hanno un panel-parent 
         * settatto.
         */
		$this->__cmp[0] = $this->corner->add(new panel($nam.'-panel'));
        $this->__cmp[0]->par('label-position','inside');
	}
    
	public function put($obj,$lbl,$nam,$x=0,$y=0,$par=0)
	{
         //if (!class_exists($typ)) {echo $typ; return;}
         //$obj  = new $typ($nam);
         //$obj->label = $lbl;
         if ($x == -1) //Se l'oggetto non ha position lo aggiungo in testa
         {
             $this->add($obj,'first');
             $par = empty($par) ? -1 : $par; //$par = -1;
         }
		 // se il component ha dei childs nella sua posizione li aggiungo al componente
         if (array_key_exists($nam,$this->__cmp) && is_array($this->__cmp[$nam])) 
		 {
            foreach($this->__cmp[$nam] as $c)
			{
				$obj->put($c[0],$c[1],$c[2],$c[3]);
			}
		 }
         //Aggiungo il componente alla lista dei componenti.
         $this->__cmp[$nam] = $obj;
         //Se il parent del componente esiste lo associo direttamente al suo interno
		 if (array_key_exists($par,$this->__cmp) && is_object($this->__cmp[$par]))
		 {
		 	$this->__cmp[$par]->put($lbl,$this->__cmp[$nam],$x,$y);
		 }
		  else //Altrimenti lo metto nella posizione del parent in attesa che venga creato
		 {
		 	$this->__cmp[$par][] = array($lbl,&$this->__cmp[$nam],$x,$y);
		 }
		 return $this->__cmp[$nam];
	}
}

class check_list extends component
{
       private $table = null;
       public function __construct($name)
       {
            parent::__construct('div',$name);
            $this->table = $this->add(tag::create('table'));
            $this->att('class','osy-check-list');
       }
       
       protected function __build_extra__()
       {
          
           $a_val = array();
    	   if ($val = $this->get_par('values'))
           {
    	      $a_val_raw = explode(',',$val);
              foreach($a_val_raw as $k => $val)
              {
                $a_val[] = explode('=',$val);
              }
           }
           if ($sql = $this->get_par('datasource-sql'))
           {
              $sql = env::ReplaceVariable($sql);
              $sql = env::parse_string($sql);
              $a_val = env::$dba->exec_query($sql,null,'NUM');
           }
           $col = $this->cols ? $this->cols : 1;
    	   foreach($a_val as $k => $val)
    	   {
               if ($k == 0 or ($k % $col) == 0)  $tr = $this->table->add(tag::create('tr'));
               $tr->add(tag::create('td'))->add('<input type="checkbox" name="'.$this->id.'[]" value="'.$val[0].'"'.(!empty($val[2]) ? ' checked' : '').'>&nbsp;'.$val[1]);
    	   }
       }
}

class radio_list extends component
{

       public function __construct($name)
       {
            parent::__construct('table',$name);
            $this->att('class','osy-radio-list');
       }
       
       protected function __build_extra__()
       {
          
           $a_val = array();
    	   if ($val = $this->get_par('values'))
           {
    	      $a_val_raw = explode(',',$val);
              foreach($a_val_raw as $k => $val)
              {
                $a_val[] = explode('=',$val);
              }
           }
           if ($sql = $this->get_par('datasource-sql'))
           {
              $sql = env::ReplaceVariable($sql);
              $sql = env::parse_string($sql);
              $a_val = env::$dba->exec_query($sql,null,'NUM');
           }
    	   foreach($a_val as $k => $val)
    	   {
               $tr = $this->add(tag::create('tr'));
               $tr->add(tag::create('td'))->add('<input type="radio" name="'.$this->id.'" value="'.$val[0].'">');
               $tr->add(tag::create('td'))->add($val[1]);
    	   }
       }
}

class tab extends component
{
    private $__head = null;
    private $__body = null;
    private $__tabs = array();
    
    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->att('class','tabs');
        $this->add(new hidden_box($name))->att('class','req-reinit');
        //osy_form::$page->add_script('../lib/jquery/jquery.scrollabletab.js');
    }
    
    protected function __build_extra__()
    {
        $head = $this->add(tag::create('ul'));
        ksort($this->__tabs);
        $it = 0;
        foreach($this->__tabs as $row)
        {
            ksort($row);
            foreach($row as $cols)
            {
                foreach($cols as $obj)
                {
                    $head->add('<li><a href="#'.$this->id.'_'.$it.'" idx="'.$it.'"><p><span>'.$obj['lbl']."</span></p></a></li>\n");
                    $div = $this->add(tag::create('div'))->att('id',$this->id.'_'.$it);
                    if ($this->get_par('cell-height'))
                    {
                        $h = intval($this->get_par('cell-height'));
                        $obj['obj']->att('style','height : '.($h-30).'px');
                    }
                    $div->add($obj['obj']);
                    $it++;
                }
            }
        }
    }
    
    public function put($lbl,$obj,$r=0,$c=0)
    {
        //var_dump($lbl,$r,$c);
        $this->__tabs[$r][$c][] = array('lbl'=>$lbl,'obj'=>$obj);
    }
}

class text_area extends component
{
    public function __construct($name)
    {
        parent::__construct('textarea',$name);
        $this->name = $name;
    }
    
    public function __build_extra__()
    {
        $this->add($_REQUEST[$this->id]);
    }
}

class time_box extends input_box
{
    public function __construct($name)
    {
        osy_view::$page->add_script('/lib/jquery/jquery.timepicker.js');
        parent::__construct('text',$name);
        $this->att('autocomplete','off')
             ->att('size','8')
             ->att('style','text-align: right;')
             ->att('class','timebox');
    }
}

class variable_box extends component
{
    public function __construct($name)
    {
        parent::__construct('dummy',$name);
    }
    
    public function __build_extra__()
    {
        $sql = $this->get_par('datasource-sql');
        if (empty($sql)) die('[ERROR] - variable box '.$this->id.' - query builder assente');
        $sql = env::ReplaceVariable($sql);
        $sql = env::parse_string($sql);
    	list($typ,$sql) = env::$dba->exec_unique($sql);
		switch($typ)
        {
			case 'CMB':
                        $sql = env::ReplaceVariable($sql);
                        $this->add(new combo_box($this->id))
                             ->att('label',$this->label)
				             ->par('datasource-sql',$sql);//Setto la risorsa per popolare la combo e la connessione al DB necessaria ad effettuare le query.
						break;
            case 'TAR':
            		    $this->add(new text_area($this->id))->att('style','width: 95%;')->att('rows','20');
					    break;
			default :
					    $this->add(new text_box($this->id))->att('style','width: 95%');
					    break;
		}
    }
}
?>
