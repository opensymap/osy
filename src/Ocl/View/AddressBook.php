<?
namespace Opensymap\Ocl\View;

use Opensymap\Ocl\View\ViewFactory;
use Opensymap\Ocl\Component\Addressbook as AddressbookComponent;
use Opensymap\Ocl\Component\Toolbar;

class of_addressbook extends oform
{
    protected static $__bod;
    protected static $__tol;

    protected static function __init_extra__()
    {
        if ($_REQUEST['ajax'] == 'excel' && !empty(self::$__par['sql-query-excel'])) {
            self::excelBuild(self::$__par['sql-query-excel']);
        }
        self::$page->add_css(OSY_WEB_ROOT.'/css/font-awesome-4.2.0/css/font-awesome.min.css');
        self::$page->add_css(OSY_WEB_ROOT.'/css/smoothness/jquery-ui-1.9.1.custom.css');
        self::$page->add_css(OSY_WEB_ROOT.'/css/style.css');
        self::$page->add_script('/lib/jquery/jquery-1.10.2.min.js');
        self::$page->add_script('/lib/jquery/jquery-ui-1.10.3.custom.min.js');
        self::$page->add_script(OSY_WEB_ROOT.'/js/form/main.js','',true);
        self::$page->add_script(OSY_WEB_ROOT.'/js/form/addressbook.js','',true);
        self::$page->body->att('class','osy-body');
        self::$form->add(new hidden_box('osy[rid]'));
        self::$__par['default_component_parent'] = 1;
        self::__build_toolbar__();
        self::__build_field_search__();
        //Costruisco il tab se necessario;
        self::__build_tab__();
        if (!self::$__bod) { 
            //self::$__bod = self::$form->add(new data_grid('data-view')); 
            self::$__bod = new oaddressbook('data-view',env::$dbo,'',$_REQUEST['pag']);
            self::$form->put(self::$__bod,'','',100,10); 
        }
        self::$__bod->par('colspan','100',function($key,$val,$self) {
            $self->man('onbuild','colspan',function($val,$self) {
                $cel = $self->closest('td,th');
                if (!is_object($cel)) return;
                $cel->att('colspan',$val);
            });
        });
        self::$__bod->par('datasource-sql',env::parse_string(self::$__par['sql-query']));
        self::$__bod->par('row-num',self::get_par('row-num'));
        self::$__bod->par('record-add',false);
        self::$__bod->par('title',self::get_par('form-title'));
        self::$__bod->par('form-related',self::get_par('form-related'));
        self::$__bod->par('form-related-ins',self::get_par('form-related-insert'));
        //self::$__bod->att('class','osy-dataview-body');
       
        if (!empty($_POST['filter'])) {
            foreach($_POST['filter'] as $field => $value) {
                self::$__bod->add_filter($field,'%'.$value.'%','like');
            }
        }
    }
    
    protected static function __build__()
    {
        if (key_exists('ajax',$_REQUEST) && $_REQUEST['ajax'] == 'data-view') {
            die('<div>'.self::$__bod.'</div>');
        }
    }
    
    protected static function __build_toolbar__()
    {
        self::$__tol = new toolbar('toolbar');
        self::$form->put(self::$__tol,'','toolbar',1,1);
       
        //Aggiungo eventuali comandi
        if( self::get_par('button-insert')=='1' ) {
            self::$__tol->add(new tag('img'))->att(Array('src'    => '../img/rec_new.gif',
                                                         'class'  => 'toolbar insert',
                                                         'alt'    => 'Aggiungi un nuovo record'));
        }
        if ( self::get_par('TYP') != 'DTS' ) {
            self::$__tol->add(new tag('img'))->att(Array('src'    => '../img/rec_src.gif',
                                                         'alt'    => 'Ricerca dati',
                                                         'class'  => 'toolbar search',
                                                         'id'     =>  'button_show_search'));
        }
        if ( self::get_par('FRMPDF') ) {
            self::$__tol->Add(new tag('img'))->att(Array('src'    => '../img/ico.pdf.gif',
                                                         'alt'    => 'Modifica il record selezionato',
                                                         'class'  => 'toolbar',
                                                         'onclick'=> "OpenPage('".self::$Properties['FrmPdf']."',800,600)"));
        }
        if ( !empty(self::$__par['field-list-data-import']) ) {
            self::$__tol->Add(new tag('img'))->att(Array('src'    => '../img/ico.xls.import.png',
                                                         'alt'    => 'Importa dati da excel',
                                                         'class'  => 'toolbar',
                                                         'onclick'=> "osyview.excel_import()"));
        }
        if ( !empty(self::$__par['sql-query-excel']) ) {
            self::$__tol->Add(new tag('img'))->att(Array('src'    => '../img/ico.xls.png',
                                                         'alt'    => 'Esporta in excel',
                                                         'class'  => 'toolbar',
                                                         'onclick'=> "osyview.excel_export()"));
       }
    }
    
    protected static function __build_field_search__()
    {
        if (!empty($_POST['ajax'])) return;
        $div = new dummy('osy-dataview-search');
        $div->att('class',"osy-dataview-search");
        $div->par('colspan','100',function($key,$val,$self){
             $self->man('onbuild','colspan',function($val,$self)
             {
                 $cel = $self->closest('td,th');
                 if (!is_object($cel)) return;
                 $cel->att('colspan','100');
             });
        });
        self::$form->put($div,'','bar-search',2,1);
        $div->add("Cerca");
        $div->add(new text_box('search_value'))->att('size','30');
        $div->add(" in ");
        $select = $div->add(new combo_box('search_field'));
        //Tasto ricerca
        $div->add(new button('btn_search'))->att('label','Avvia ricerca');
       //Tasto pulisci filtro
        $div->add(new button('btn_search_reset'))->att('label','Elimina filtro');
        //Contenitore dei filtri attivi
        $div_flt_cnt = $div->add(tag::create("div"))->att('class','filter-active');
        
        if (key_exists('filter',$_POST) && is_array($_POST['filter']))
        {
            foreach($_POST['filter'] as $k => $v)
            {
              $div_flt = $div_flt_cnt->add(tag::create('div'))->att("class","filter");
              $div_flt->add(new hidden_box("filter[$k]"))->Att('value',$v);
              switch($k[0]){
                case '!':
                case '€':
                case '$':
                case '#':
                          $k = substr($k,1);
                          break;
                case '_':
                          list($a,$k) = explode(',',$k);
                          break;
              }
              $div_flt->add("$k : $v");
            }
            $div->add($div_flt_cnt);
        } else {
            $div->par('init-cell','hidden',function($key,$val,$self){
                $self->man('onbuild','init-cell',function($val,$self)
                {
                 $cel = $self->closest('td,th');
                 if (!is_object($cel)) return;
                 $cel->att('class',$val)->att('colspan','100');
                });
            });
        }
        $div_flt_cnt->add(tag::create("div"))->att("style","clear: both");
    }
    
    protected static function __build_tab__(){
        if (empty(self::$__par['tab-query'])) { return; }
        $hdn = self::$form->add(new hidden_box('osy[tid]'));
        $sql = env::ReplaceVariable(self::$__par['tab-query']);
        $rs  = env::$dba->exec_query(env::ReplaceVariable($sql),null,'NUM');
        $tab = new tab('tabs');
        
        $tab->att('style','border-top: 1px solid white; padding-top: 3px;');
        foreach($rs as $i => $rec)
        {
            if ((empty($_REQUEST['osy']['tid']) && empty($i)) || $_REQUEST['osy']['tid'] == $rec[0])
            {
                $tab->att('tabsel',$i);
                $hdn->value = $_REQUEST['osy']['tid'] = $rec[0];
                //self::$__bod = new addressbook('data-view');
                self::$__bod = new oaddressbook('data-view',env::$dbo,'',$_REQUEST['pag']);
                self::$__bod->att('filter_id',$rec[0]);
                self::$__bod->add_filter('_tab',$rec[0]);
                $tab->put($rec[1],self::$__bod,$i*10,10);
            } 
             else
            {
                $tab->put($rec[1],'<div style="text-align: center; margin-top: 100px;" filter_id="'.$rec[0].'">Data loading.... Please wait</div>',$i*10,10);
            }
        }
        self::$form->put($tab,'','',100,10);
    }
    
    
    protected static function excelBuild($sql)
    {
        $sql =  env::parse_string($sql);
        require_once(OSY_PATH_LIB_EXT.'/phpexcel-1.8.0/PHPExcel.php');
        require_once(OSY_PATH_LIB_EXT.'/phpexcel-1.8.0/PHPExcel/Writer/Excel2007.php');
        $rs = env::$dba->exec_query($sql,null,'ASSOC');
        $exc = new PHPExcel();
        $exc->getProperties()->setCreator("Service Portal");
        $exc->getProperties()->setLastModifiedBy("Service Portal");
        $exc->getProperties()->setTitle("Order export");
        $exc->getProperties()->setSubject("Order export");
        $exc->getProperties()->setDescription("Esportazione ordini generata dal Service Portal");
        $letters = array_unshift(range('A','Z'),'');
        $cell = '';
        function calc_pos($n){
             $l = range('A','Z');
            if ($n <= 26) return $l[$n-1];
            $r = ($n % 26);
            $i = (($n - $r) / 26) - (empty($r) ? 1 : 0);
            return calc_pos($i).(!empty($r) ? calc_pos($r) : 'Z');
        }
        for ($i = 0; $i < count($rs); $i++)
        {
                $j = 0;
                foreach($rs[$i] as $k => $v)
                {
                        if ($k[0] == '_') continue;
                        $col = calc_pos($j+1);
                        $cel = $col.($i+2);
                        try{
                            if (empty($i))
                            {
                                 $exc->getActiveSheet()->SetCellValue($col.($i+1), str_replace(array('_X','!'),'',strtoupper($k)));
                            }
                            $exc->getActiveSheet()->SetCellValue($cel, str_replace('<br/>',' ',$v));
                        } catch (Exception $e){
                        }
                        $j++;
                }
        }
        $exc->getActiveSheet()->setTitle('Ordini');
        $objWriter = new PHPExcel_Writer_Excel2007($exc);
        $filename = OSY_PATH_VAR.'/tmp/'.str_replace(' ','-',strtolower(self::$__par['form-title'])).date('-Y-m-d-H-i-s').'.xlsx';
        $objWriter->save($filename);
        
        die( str_replace(OSY_PATH_VAR,'/var',$filename ));
    }
}

