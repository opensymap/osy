<?
$_GLOBALS['timestart'] = microtime(true);
ob_start("ob_gzhandler");
require_once("../lib/l.chk.acc.php");
require_once("../lib/c.view.php");

class toolbar extends component
{
    public function __construct($nam)
    {
        parent::__construct('div',$nam);
        $this->att('class','toolbar');
    }
}

class osy_view_grid extends osy_view
{
    protected static $__bod;
    protected static $__tol;

    protected static function __init_extra__()
    {
       self::$page->add_css(OSY_WEB_ROOT.'/css/smoothness/jquery-ui-1.9.1.custom.css');
       self::$page->add_css(OSY_WEB_ROOT.'/css/style.css');
       self::$page->add_script('/lib/jquery/jquery-1.10.2.min.js');
       self::$page->add_script('/lib/jquery/jquery-ui-1.10.3.custom.min.js');
       self::$page->add_script(OSY_WEB_ROOT.'/js/osy.view.grid.js');
       self::$page->add_script(OSY_WEB_ROOT.'/js/osy.datagrid.js'); 
       self::$form->add(new hidden_box('osy[rid]'));
       self::$__par['default_component_parent'] = 1;
       
       $sql = env::parse_string(self::$__par['sql-query']);
       self::$__tol = self::$form->add(new toolbar('toolbar'));
       self::__build_field_search__();
       //Costruisco il tab se necessario;
       self::__build_tab__();
       if (!self::$__bod)
       {
          self::$__bod = self::$form->add(new data_grid('data-view'));
       }
       self::$__bod->par('datasource-sql',$sql);
       self::$__bod->par('row-num',self::get_par('row-num'));
       self::$__bod->par('record-add',false);
       self::$__bod->par('form-related',self::get_par('form-related'));
       self::$__bod->par('form-related-ins',self::get_par('form-related-insert'));
       self::$__bod->att('class','dataview-2-body datagrid-2');
       
       if (!empty($_POST['filter']))
       {
            foreach($_POST['filter'] as $field => $value)
            {
                self::$__bod->add_filter($field,$value.'%','like');
            }
       }
       
       if(self::get_par('button-insert')=='1')
       {
          self::$__tol->add(new tag('img'))->att(Array('src'    => '../img/rec_new.gif',
		  				  						       'class'  => 'toolbar insert',
                                                       'alt'    => 'Aggiungi un nuovo record'));
       }
       //Aggiungo icona modifica record
       self::$__tol->add(new tag('img'))->att(Array('src'    => '../img/rec_upd.gif',
                                                    'pk'     => 'req',
												    'class'  => 'toolbar update',
                                                    'alt'    => 'Modifica il record selezionato'));
       //Aggiungo eventuali comandi
       if (self::get_par('FRMCMD'))
       {
            self::$__tol->add(self::$__par['FRMCMD']);
       }
       if (self::get_par('TYP') != 'DTS')
	   {
    	    self::$__tol->add(new tag('img'))->att(Array('src'    => '../img/rec_src.gif',
                                                         'alt'    => 'Ricerca dati',
													     'class'  => 'toolbar search',
                                                         'id'     =>  'button_show_search'));
       }
       if (self::get_par('FRMPDF'))
	   {
    	 	self::$__tol->Add(new tag('img'))->att(Array('src'    => '../img/ico.pdf.gif',
                                                         'alt'    => 'Modifica il record selezionato',
													     'class'  => 'toolbar',
                                                         'onclick'=> "OpenPage('".self::$Properties['FrmPdf']."',800,600)"));
       }
    }
    
    protected static function __build__()
    {
        if (key_exists('ajax',$_REQUEST) && $_REQUEST['ajax'] == 'yes')
        {
            die('<div>'.self::$__bod.'</div>');
        }
    }
    
    protected static function __build_field_search__()
    {
        if (!empty($_POST['ajax'])) return;
		$div = self::$form->add(tag::create('div'))->att('class',"osy-dataview-2-search hidden");
        $div->add("Cerca");
	    $div->add(new text_box('search_value'))->att('size','40');
	    $div->add(" in ");
		$select = $div->add(new combo_box('search_field'));
        //Tasto ricerca
    	$div->add(new button('btn_search'))->att('label','Avvia ricerca');
	   //Tasto pulisci filtro
    	$div->add(new button('btn_search_reset'))->att('label','Elimina filtro');
        //Contenitore dei filtri attivi
        $div->add(tag::create("div"))
            ->att('class','filter-active')
            ->add(tag::create("div"))
            ->att("style","clear: both");
        if (key_exists('filter',$_POST) && is_array($_POST['filter']) && 1==2)
        {
			$div_flt_cnt->add(tag::create('div'))->att("style","float: left; padding: 3px; margin:2px;")->add("Filtri attivi : ");
		    foreach($_POST['Filter'] as $k => $v)
            {
                    $div_flt = $div_flt_cnt->Add(tag::create('div'))->att("style","border: 1px solid silver; float:left; padding:3px; margin: 2px;");
            	  	$div_flt->add(new hidden("filter[$k]"))->Att('value',$v);
                    $div_flt->add(tag::create("img"))
   						   ->Att("src","/img/ico/del.gif")
					  	   ->Att("onclick","FilterDel(this)")
					   	   ->Att("align","absmiddle")
                           ->Att("class","nowrap");
					$div_flt->Add("$k: $v");
			}
			$div_flt;
		}
    }
    
    protected static function __build_tab__()
    {
        if (empty(self::$__par['tab-query'])) { return; }
        $sql = env::ReplaceVariable(self::$__par['tab-query']);
        $div = self::$form->add(new tag('div'))->att('id','tabs')->att('style','border-top: 1px solid white; padding-top: 3px;');
        $hdn = self::$form->add(new hidden_box('osy[tid]'));
        $ul  = $div->add(new tag('ul'));
        $rs  = env::$dba->exec_query(env::ReplaceVariable($sql),null,'NUM');
        foreach($rs as $i => $rec)
        {
            $cel = $ul->add(new tag('li'))->add(new tag('a'));
            $cel->att('id',$rec[0])->att('href', '#datagrid-'.$i);
            $cel->add(new tag('p'),0)->add($rec[1]);
            $sub_div = $div->add(tag::create('div'))->att('id','datagrid-'.$i);
            if ((empty($_REQUEST['osy']['tid']) && empty($i)) || $_REQUEST['osy']['tid'] == $rec[0])
            {
                $div->att('tabsel',$i);
                $hdn->value = $_REQUEST['osy']['tid'] = $rec[0];
                self::$__bod = $sub_div->add(new data_grid('data-view'));
                self::$__bod->add_filter('_tab',$rec[0]);
            } 
             else
            {
                $sub_div->add('<div style="text-align: center; margin-top: 100px;">Data loading.... Please wait</div>');
            }
        }
	}
}


//echo memory_get_usage().'<br>';

osy_view_grid::init('main1');
//echo memory_get_usage().'<br>';

echo osy_view_grid::get();
echo '<div id="microtime">'.(microtime(true) - $_GLOBALS['timestart']).' sec. - '.number_format(memory_get_usage(),0,',','.').' byte</div>';
ob_end_flush();
?>
