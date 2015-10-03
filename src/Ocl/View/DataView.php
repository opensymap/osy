<?php
namespace Opensymap\Ocl\View;

use Opensymap\Osy as env;
use Opensymap\Lib\Tag as tag;
use Opensymap\Ocl\View\ViewFactory;
use Opensymap\Ocl\Component\Form;
use Opensymap\Ocl\Component\Button;
use Opensymap\Ocl\Component\Dummy;
use Opensymap\Ocl\Component\ComboBox;
use Opensymap\Ocl\Component\HiddenBox;
use Opensymap\Ocl\Component\DataGrid;
use Opensymap\Ocl\Component\TextBox;
use Opensymap\Ocl\Component\Tab;
use Opensymap\Ocl\Component\Toolbar;
use Opensymap\Ocl\Component\ComponentFactory;
use Opensymap\Helper\HelperOsy;
use Opensymap\Datasource\DatasourceDbo;

class DataView extends ViewOpensymap
{
    protected $viewBody;
    protected $viewToolbar;
    protected $datasource;
    protected $__cmd_flt;
    
    protected function init()
    {
        $this->response->addCss('/vendor/font-awesome-4.2.0/css/font-awesome.min.css');
        $this->response->addCss('/vendor/jquery/smoothness/jquery-ui-1.9.1.custom.css');
        $this->response->addCss('css/style.css');
        $this->response->addJsFile('/vendor/jquery/jquery-1.10.2.min.js');
        $this->response->addJsFile('/vendor/jquery/jquery-ui-1.10.3.custom.min.js');
        $this->response->addJsFile('js/view/Form.js'); 
        $this->response->addJsFile('js/view/Dataview.js'); 
    }
    
    protected function build()
    {
        if ($_REQUEST['ajax'] == 'excel' && !empty($this->param['sql-query-excel'])){
           $this->__excel_build__($this->param['sql-query-excel']);
        }
        if ($_REQUEST['ajax'] == 'excel-import' && !empty($this->param['field-list-data-import'])){
           $this->__excel_import__($this->param['field-list-data-import']);
        }
        
        $this->form->add(new HiddenBox('osy[rid]'));
        $this->param['default_component_parent'] = 1;
        $this->viewToolbar = new toolbar('toolbar');
        $this->form->put($this->viewToolbar, '', 'toolbar', 1, 1);
        //$this->form->add($this->viewToolbar);
        $this->buildFieldSearch();
        //Costruisco il tab se necessario;
        
        $parameters = [
            'name'      => 'data-view',
            'phpClass'  => 'DataGrid',
            'attribute' => [
                'class' => 'dataview-2-body osy-datagrid-2 osy-maximize'
            ],
            'cell-attribute' => [
                'colspan' => 100
            ],
            'parameter' => [
                //'datasource-sql'   => $this->getParam('sql-query'),
                'row-num'          => $this->getParam('row-num'),
                'record-add'       => false,
                'form-related'     => $this->getParam('form-related'),
                'form-related-ins' => $this->getParam('form-related-insert')
            ]
        ];
        
        $this->viewBody = ComponentFactory::create($parameters);
        $this->viewBody->appendRequired($this->response);
        $this->datasource = new DatasourceDbo($this->model->dba);
        $this->datasource->setQuery(
            $this->getParam('sql-query')
        );
        //Set current page, page command, Row for page
        $this->datasource->setPage(
            $_REQUEST['data-view_pag'],
            $_REQUEST['btn_pag'], 
            $this->getParam('row-num')
        );
        //Set order by field
        $this->datasource->orderBy(
            $_REQUEST['data-view_order']
        );
        if (!$this->buildTab()) {
            $this->form->put($this->viewBody,'','',100,10); 
            if (!empty($_REQUEST['osy']) && !empty($_REQUEST['osy']['layout'])) {
                $this->viewBody->par('layout',$_REQUEST['osy']['layout']);
            }
        }
        
        $this->viewBody->setDatasource($this->datasource);
        
        if (!empty($_POST['filter'])) {
            foreach($_POST['filter'] as $field => $value) {
                //$this->viewBody->addFilter($field,'%'.str_replace("'","''",$value).'%','like');
                $this->datasource->addFilter($field, '%'.str_replace("'","''",$value).'%', 'like');
            }
        }
       
        if($this->getParam('button-insert')=='1') {
            $this->viewToolbar->add(new Tag('span'))->att('class','insert fa fa-plus-square fa-lg');
        }

        //Aggiungo eventuali comandi
        if ($this->getParam('FRMCMD')) {
            $this->viewToolbar->add($this->param['FRMCMD']);
        }
        
        if ($this->getParam('TYP') != 'DTS') {
        
            $this->viewToolbar->add(tag::create('span'))->att('class','search fa fa-filter fa-lg');
        }
        if ($this->getParam('FRMPDF')) {
            $this->viewToolbar->Add(new tag('img'))->att(Array('src'    => '../img/ico.pdf.gif',
                                                         'alt'    => 'Modifica il record selezionato',
                                                         'class'  => 'toolbar',
                                                         'onclick'=> "OpenPage('".$this->Properties['FrmPdf']."',800,600)"));
        }
        if (!empty($this->param['field-list-data-import'])) {
            $this->viewToolbar->Add(new tag('img'))->att(Array('src'    => '../img/ico.xls.import.png',
                                                         'alt'    => 'Importa dati da excel',
                                                         'class'  => 'toolbar',
                                                         'onclick'=> "oform.main.excel_import()"));
            $this->form->att('enctype','multipart/form-data');
        }
        if (!empty($this->param['sql-query-excel'])) {
            $this->viewToolbar->Add(new tag('img'))->att(Array('src'    => '../img/ico.xls.png',
                                                         'alt'    => 'Esporta in excel',
                                                         'class'  => 'toolbar',
                                                         'onclick'=> "oform.main.excel_export()"));
        }      
    }
    
    protected function __build__()
    {
        if (array_key_exists('ajax',$_REQUEST) && $_REQUEST['ajax'] == 'yes') {
            die('<div>'.$this->viewBody.'</div>');
        }
    }
    
    protected function buildFieldSearch()
    {
        if (!empty($_POST['ajax'])) {
            return;
        }
        $div = new Dummy('osy-dataview-2-search');
        $div->att('class',"osy-dataview-2-search");
        $div->par('colspan', '100', function($key,$val,$self) {
            $self->man(
                'onbuild', 
                'colspan', 
                function($key, $val,$self) {
                    $cel = $self->closest('td,th');
                    if (!is_object($cel)) {
                        return;
                    }
                    $cel->att('colspan','100');
                }
            );
        });
        $this->form->put($div,'','bar-search',2,1);
        $div->add("Cerca");
        $div->add(new TextBox('search_value'))->att('size','40');
        $div->add(" in ");
        $select = $div->add(new ComboBox('search_field'));
        //Tasto ricerca
        $div->add(new Button('btn_search'))->att('label','Ricerca');
       //Tasto pulisci filtro
        $div->add(new Button('btn_search_reset'))->att('label','Elimina filtro');
        //Contenitore dei filtri attivi
        $div_flt_cnt = $div->add(tag::create("div"))->att('class','filter-active');
        
        if (array_key_exists('filter',$_POST) && is_array($_POST['filter'])) {
            foreach($_POST['filter'] as $k => $v) {
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
                $self->man('onbuild','init-cell',
                    function($key, $val,$self)
                    {
                        $cel = $self->closest('td,th');
                        if (!is_object($cel)) return;
                        $cel->att('class',$val)->att('colspan','100');
                    }
                );
            });
        }
        $div_flt_cnt->add(new Tag("div"))->att("style","clear: both");
    }
    
    protected function buildTab()
    {
        if (empty($this->param['tab-query'])) { 
            return false; 
        }
        $hdn = $this->form->add(new HiddenBox('osy[tid]'));
        $sql = HelperOsy::replaceVariable($this->param['tab-query'], $this->request->get('input'));
        $rs  = $this->model->dba->exec_query($sql,null,'NUM');
        $tab = new Tab('tabs');
        
        $tab->att('style','border-top: 1px solid white; padding-top: 3px;');
        foreach($rs as $i => $rec) {
            if ((empty($_REQUEST['osy']['tid']) && empty($i)) || $_REQUEST['osy']['tid'] == $rec[0]) {
                $tab->att('tabsel',$i);
                $hdn->value = $_REQUEST['osy']['tid'] = $rec[0];
                //$this->viewBody = new DataGrid('data-view');
                $this->viewBody->att('filter_id',$rec[0]);
                $this->viewBody->addFilter('_tab',$rec[0]);
                $this->datasource->addFilter('_tab',$rec[0]);
                $tab->put($rec[1],$this->viewBody,$i*10,10);
            } else {
                $tab->put($rec[1],'<div  class="osy-maximize osy-tab-dummy" filter_id="'.$rec[0].'">Data loading.... Please wait</div>',$i*10,10);
            }
        }
        $this->form->put($tab,'','',100,10);
        return true;
    }
    
    protected function excelBuild($sql)
    {
        $sql =  HelperOsy::parseString($sql);
        require_once(OSY_PATH_LIB_EXT.'/phpexcel-1.8.0/PHPExcel.php');
        require_once(OSY_PATH_LIB_EXT.'/phpexcel-1.8.0/PHPExcel/Writer/Excel2007.php');
        $sql = HelperOsy::replaceVariable($sql);
        $sql = HelperOsy::parseString($sql);
        if (!empty($_REQUEST['osy']['tid'])) {
            $sql = "SELECT a.* 
                    FROM ($sql) a 
                    WHERE a.\"_tab\" = '{$_REQUEST['osy']['tid']}'
                    ORDER BY 1";
                    //mail('pietro.celeste@gmail.com','errore',$sql);
        }
        $rs = env::$dba->exec_query($sql,null,'ASSOC');
        $exc = new PHPExcel();
        $exc->getProperties()->setCreator("Service Portal");
        $exc->getProperties()->setLastModifiedBy("Service Portal");
        $exc->getProperties()->setTitle("Order export");
        $exc->getProperties()->setSubject("Order export");
        $exc->getProperties()->setDescription("Esportazione ordini generata dal Service Portal");
        $letters = array_unshift(range('A','Z'),'');
        $cell = '';
        function calc_pos($n) {
             $l = range('A','Z');
            if ($n <= 26) return $l[$n-1];
            $r = ($n % 26);
            $i = (($n - $r) / 26) - (empty($r) ? 1 : 0);
            return calc_pos($i).(!empty($r) ? calc_pos($r) : 'Z');
        }
        for ($i = 0; $i < count($rs); $i++) {
                $j = 0;
                foreach($rs[$i] as $k => $v) {
                        if ($k[0] == '_') continue;
                        $col = calc_pos($j+1);
                        $cel = $col.($i+2);
                        try {
                            if (empty($i)) {
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
        $filename = OSY_PATH_VAR.'/tmp/'.str_replace(' ','-',strtolower($this->__par['form-title'])).date('-Y-m-d-H-i-s').'.xlsx';
        $objWriter->save($filename);
        
        die( str_replace(OSY_PATH_VAR,'/var',$filename ));
    }
    
    protected function excelImport($s_fld)
    {
        $raw_fld = explode('][',$s_fld);
        $lst_fld = $a_fld = array();
        if (is_array($raw_fld)){
            foreach($raw_fld as $k => $fld){
                $fld = explode('*',str_replace('[','',$fld));
                $a_fld[] = $fld[0];
                $lst_fld[] = $fld;
            }
        }
        require_once(OSY_PATH_LIB_EXT.'/phpexcel-1.8.0/PHPExcel/IOFactory.php');

        //  Read your Excel workbook
        try {
             
            $file_type = PHPExcel_IOFactory::identify($_FILES['excel_import']['tmp_name']);
            $o_read = PHPExcel_IOFactory::createReader($file_type);
            $o_excel = $o_read->load($_FILES['excel_import']['tmp_name']);
            //  Get worksheet dimensions
            $sheet = $o_excel->getSheet(0); 
            $h_row = $sheet->getHighestRow(); 
            $h_col = $sheet->getHighestDataColumn();
            //  Loop through each row of the worksheet in turn
            $qok = 0;
            for ($row = 1; $row <= $h_row; $row++){ 
                //  Read a row of data into an array
                $rec = $sheet->rangeToArray('A' . $row . ':' . $h_col . $row, NULL, TRUE, FALSE);
                //  Insert row data array into your database of choice here
                if (empty($rec)) continue;
                $qry_par = array();
                foreach($a_fld as $k => $field){
                    
                    $qry_par[$field] = !empty($rec[0][$k]) ? $rec[0][$k] : null ;
                }
                if (!empty($qry_par)){
                    try {
                        env::$dba->insert('tbl_ana',$qry_par);
                        $qok++;
                    } catch (Exception $e) {
                        $msg .= "Errore alla riga $row ".$e->getMessage()."<br>";
                    }
                }
            }
            die("record inseriti : $qok<br>$msg<br>".print_r($qry_par,true).print_r($rec[0],true));
        } catch(Exception $e) {
            die($h_col.'Errore nell\'apertura del file "'.pathinfo($_FILES['excel_import']['tmp_name'],PATHINFO_BASENAME).'": '.$e->getMessage());
        }
        die($a);
    }
}
