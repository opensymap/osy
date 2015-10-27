<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/DataGrid.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  datagrid and treegrid                                       |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   28/08/2013
 * @date-update     28/08/2013
 */

namespace Opensymap\Ocl\Component;

use Opensymap\Lib\Tag as tag;
use Opensymap\Datasource\InterfaceDatasourcePaging;
use Opensymap\Ocl\AjaxInterface;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\Button;
use Opensymap\Ocl\Component\HiddenBox;
use Opensymap\Ocl\Component\ComboBox;
use Opensymap\Ocl\Component\TextBox;

class DataGrid extends AbstractComponent implements AjaxInterface
{
    private $__att = array();
    private $__col = array();
    private $__dat = array();
    private $__sta = array();
    private $datasource = null;

    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->addRequire('Ocl/Component/DataGrid/style.css');
        //Add javascript controller;
        $this->addRequire('Ocl/Component/DataGrid/controller.js');
        $this->att('class', 'osy-datagrid-2');
        $this->__par['type'] = 'datagrid';
        $this->__par['row-num'] = 0;
        $this->__par['pkey'] = array();
        $this->__par['max_wdt_per'] = 96;
        $this->__par['sql_filter'] = array();
        $this->__par['column-object'] = array();
        $this->__sta['col_len'] = array();
        $this->__par['paging'] = true;
        $this->__par['error-in-sql'] = false;
        $this->__par['record-add'] = null;
        $this->__par['record-update'] = null;
        $this->__par['layout'] = null;
        $this->__par['div-stat'] = null;
        $this->__par['head-hide'] = 0;
        
    }
    
    public function ajaxResponse($controller, &$response)
    {
        $response = new \Opensymap\Response\PageHtmlResponse();
        $response->getBody()->add('<div>'.$this->get().'</div>');
    }
    
    protected function build()
    {
        $this->loadColumnObject();
        if ($this->rows) {
            $this->__par['row-num'] = $this->rows;
        }
        if ($this->getParameter('datasource-sql') || $this->datasource) {
            $this->dataLoad();
        }
        if ($this->getParameter('filter-show')) {
           $this->buildFilter(); 
        }
        if ($par = $this->getParameter('mapgrid-parent')) {
            $this->att('data-mapgrid', $par);
        }
        if ($this->getParameter('mapgrid-parent-refresh')) {
            $this->att('class', 'mapgrid-refreshable', true);
        }
        if ($par = $this->getParameter('mapgrid-infowindow-format')) {
            $this->att('data-mapgrid-infowindow-format',$par);
        }
        //Aggiungo il campo che conterrà i rami aperti dell'albero.
        $this->add(new HiddenBox($this->id.'_open'))
             ->att('class','req-reinit');
        $this->add(new HiddenBox($this->id.'_order'))
             ->att('class','req-reinit');
        //Aggiungo il campo che conterrà il ramo selezionato.
        $this->add(new HiddenBox($this->id,$this->id.'_sel'))
             ->att('class','req-reinit');
        $this->buildAdd();
        $tbl_cnt = $this->add(tag::create('div'))
                        ->att('id',$this->id.'-body')
                        ->att('class','osy-datagrid-2-body')
                        ->att('data-rows-num',$this->__par['rec_num']);
        $hgt = $this->getParameter('cell-height');

        if (!empty($this->__par['row-num']) && !empty($hgt)) {
            $hgt = str_replace('px','', $hgt);
            $tbl_cnt->att('style','height : '. ($hgt-30) . 'px',true);
        } elseif(!empty($hgt)) {
            $hgt = str_replace('px','', $hgt);
            $tbl_cnt->att('style','height : '. $hgt . 'px',true);
        }

        $tbl = $tbl_cnt->add(new Tag('table'));
        if ($err = $this->getParameter('error-in-sql')) {
            $tbl->add(tag::create('tr'))->add(tag::create('td'))->add($err);
            return;
        }
        if (is_array($this->getParameter('cols'))) {
            $tbl_hd = $tbl->add(tag::create('thead'));
            $this->buildHead($tbl_hd);
        }
        if (is_array($this->__dat) && !empty($this->__dat)) {
            $tbl_bod = $tbl->add(tag::create('tbody'));
            $lev = ($this->getParameter('type') == 'datagrid') ? null : 0;
            $this->buildBody($tbl_bod,$this->__dat,$lev);
        } else {
            $tbl->add(tag::create('td'))
                ->att('class','no-data')
                ->att('colspan',$this->__par['cols_vis'])
                ->add('Nessun dato presente');
        }
        $t = array_sum($this->__sta['col_len']);
        foreach ($this->__sta['col_len'] as $k => $l) {
            $p = ($this->__par['max_wdt_per'] * $l) / max($t,1);
            //$tbl_hd->Child(0)->Child($k)->style = "width: ".round($p)."%";
        }
        //Setto il tipo di componente come classe css in modo da poterlo testare via js.
        $this->att('class',$this->getParameter('type'),true);
        $this->buildPaging();
    }

    private function buildAdd()
    {
        if (!empty($this->__par['record-add'])) {
            $this->add(tag::create('div'))
                 ->att('class','cmd-add')
                 ->add('<span class="fa fa-plus"></span>');
        }
    }

    private function buildFilter()
    {
        $cols = array();
        if ($filters_raw = $this->getParameter('filter-fields')) {
            $filters_raw = explode("\n",$filters_raw);
            $filters_lst = array();
            foreach ($filters_raw as $k => $filter_raw) {
                $filter = explode(',',$filter_raw);
                $filters_lst[trim($filter[0])] = array_key_exists(1,$filter) ? trim($filter[1]) : trim($filter[0]);
            }
            //var_dump($this->getParameter('cols'));
            foreach ($this->getParameter('cols') as $k => $col) {
                if (array_key_exists($col['name'],$filters_lst)) {
                    $cols[] = array($col['name'].'[::]'.(in_array($col['native_type'],array('VAR_STRING','BLOB')) ? '==-=' : '==>=<='),$filters_lst[$col['name']]);
                }
            }
        } else {
            foreach ($this->getParameter('cols') as $k => $col) {
                $cols[] = array($col['name'].'[::]'.(in_array($col['native_type'],array('VAR_STRING','BLOB')) ? '==-=' : '==>=<='),$col['name']);
            }
        }
        $operator = array(
            '='  => 'is equal to',
            '>=' => 'is greater than',
            '<=' => 'is less than',
            'like' => 'contains'
        );
        $flts = $this->add(new Tag('div'))
                     ->att('class','osy-datagrid-filters');
        $i = 0;
        if (!empty($_REQUEST[$this->id.'_filter'])) {
            $lbls = tag::create('div')->att('class','osy-datagrid-filter-labels');
            foreach ($_REQUEST[$this->id.'_filter'] as $k => $rec) {
                list($rec[0],) = explode('[::]', $rec[0]);
                if (empty($rec[0])) continue;
                $lbl = $lbls->add(tag::create('span'))->att('class','osy-datagrid-filter-label');
                $lbl->add(new HiddenBox($this->id.'_filter['.$k.'][0]'));
                $lbl->add(new HiddenBox($this->id.'_filter['.$k.'][1]'));
                $lbl->add(new HiddenBox($this->id.'_filter['.$k.'][2]'));
                $lbl->add('<span class="fa fa-remove cmd-del-filter"></span> '.(!empty($filters_lst) ? $filters_lst[$rec[0]] : $rec[0]).' '.$operator[$rec[1]].' '.$rec[2]);
                $i++;
            }
            if ($i>0) {
                $flts->add($lbls);
                $i = $k;
            }
            $i++;
        }
        $flt = $flts->add(new Tag('div'))
                    ->att('class', 'osy-datagrid-filter');
        $flt->add('Filter');
        $_REQUEST[$this->id.'_filter_fields'] = $_REQUEST[$this->id.'_filter_operator'] = $_REQUEST[$this->id.'_filter_value'] = '';
        $cmb_flt = $flt->add(new ComboBox($this->id.'_filter_fields'))
                       ->att('class','osy-datagrid-filter-fields')
                       ->att('data-error','Non hai selezionato nessun campo da filtrare');
        $cmb_flt->par('datasource',$cols);
        $cmb_opr = $flt->add(new ComboBox($this->id.'_filter_operator'))->att('class','osy-datagrid-filter-operator');
        $cmb_opr->att('data-error','Non hai selezionato nessun operatore di confronto')->par('datasource',array());
        //$flt->add( print_r($this->getParameter('cols'),true) );
        $flt->add(new TextBox($this->id.'_filter_value'))->att('class','osy-datagrid-filter-value')->att('data-error','Non hai inserito nessun valore');
        $flt->add(new Button($this->id.'_filter_apply'))->att('label','Apply')->att('class','cmd-apply');
    }

    private function buildBody($container,$data,$lev,$ico_arr=null)
    {
        if (!is_array($data)) {
            return;
        }        
        $ico_tre = null;
        foreach ($this->__dat as $k => $row) {
            if (array_key_exists('__groupedLevel',$row) ) {
                $lev = $row['__groupedLevel'];
                $pos = $row['__groupedPos'];
                if ($pos === 'last') {
                    $ico_tre = 3;
                    $ico_arr[$lev] = null;
                } elseif (empty($pos)) {
                    $ico_tre = empty($lev) ? 1 : 2;
                    $ico_arr[$lev] = 4; //$pos != 'last' ? '4' : null;
                } else {
                    $ico_tre = 2;
                    $ico_arr[$lev] = 4; //$pos != 'last' ? '4' : null;
                }                
            }
            
            $this->buildRow($container, $row, $lev, $ico_tre, $ico_arr);
            $i++;
        }
    }
    
    private function buildHead($thead)
    {
        $tr = new Tag('tr');
        if ($this->getParameter('layout') == 'search') {
            $tr->add(tag::create('th'))->add("&nbsp;");
        }
        $list_pkey = $this->getParameter('pkey');
        $cols = $this->getParameter('cols');
        foreach ($cols as $k => $col) {
            if (is_array($list_pkey) && in_array($col['name'],$list_pkey)) {
                continue;
            }
            $opt = array('alignment'=> '',
                         'class'    => '',
                         'color'    => '',
                         'format'   => '',
                         'hidden'   => false,
                         'print'    => true,
                         'realname' => strip_tags($col['name']),
                         'style'    => '',
                         'title'    => $col['name']);
            if (array_key_exists($col['name'],$this->getParameter('column-object'))) {
                foreach ($this->getParameter('column-object')[$col['name']] as $k => $v) {
                    if ($k == 'hidden' && $v == '1') {
                        continue;
                    }
                    if ($k == 'format') {
                        switch($v) {
                            case 'tree' :
                                $this->att('class','osy-treegrid',true);
                                
                                $this->dataGroup();
                                break;
                            case 'checkbox':
                                if ($col['nam'] == 'sel') {
                                    $opt['title'] = '<span class="fa fa-check-square-o osy-datagrid-cmd-checkall"></span>';
                                    $opt['class'] = 'no-ord';
                                }
                                break;
                        }
                    }
                }
            } else {
                switch ($opt['title'][0]) {
                    case '_':
                        $opt['print'] = false;
                        @list($cmd,$nam,$par) = explode(',',$opt['title']);
                        switch ($cmd) {
                            case '_tree':
                                $this->att('class','osy-treegrid',true);
                                $this->par('type','treegrid');
                                //$this->dataGroup();
                                //$this->__dat = $this->datasource->getGrouped('_tree');
                                //var_dump($this->__dat);
                                break;
                            case '_chk'   :
                            case '_chk2'  :
                                if ($nam == 'sel'){
                                    $opt['title'] = '<span class="fa fa-check-square-o osy-datagrid-cmd-checkall"></span>';
                                    $opt['class'] = 'no-ord';
                                }
                            case '_rad'   :
                                $opt['title'] = '&nbsp;';
                                $opt['print'] = true;
                                break;
                            case '_pivot' :
                                //$this->dataPivot($tr);
                                list($hcol,$this->__dat) = $this->datasource->getPivot('_pivot');
                                //$thead->add($tr);
                                return;
                                break;
                            case '_button':
                            case '_html'  :
                            case '_text'  :
                            case '_img64' :
                            case '_img64x2':
                                $opt['title'] = $nam;
                                $opt['print'] = true;
                                break;
                        }
                        break;
                    case '$':
                    case '!':
                        $opt['title'] = str_replace(array('$','?','#','!'),array('','','',''),$opt['title']);
                        break;
                }
            }
            if ($opt['print']) {
                $this->__par['cols_vis'] += 1;
                $cel = $tr->add(new Tag('th'))
                          ->att('real_name',$opt['realname'])
                          ->att('data-ord',$k+1);
                if ($opt['class']) $cel->att('class',$opt['class']);
                if (!empty($_REQUEST[$this->id.'_order'])){
                    if (strpos($_REQUEST[$this->id.'_order'],'['.($k+1).']') !== false){
                        $cel->att('class','osy-datagrid-asc');
                    } elseif (strpos($_REQUEST[$this->id.'_order'],'['.($k+1).' DESC]') !== false){
                        $cel->att('class','osy-datagrid-desc');
                    }
                }
                $cel->att('data-type',$col['native_type'])
                    ->add('<span>'.$opt['title'].'</span>');
            }
        }
        if (($this->getParameter('record-add') || $this->getParameter('record-update')) && $this->getParameter('print-pencil') ){
            $cnt = $this->getParameter('record-add') ? '<span class="fa fa-plus cmd-add-hd"></span>' : '&nbsp;';
            $tr->add(new Tag('th'))->add($cnt);
            $this->__par['cols_vis'] += 1;
        }
        if (!$this->getParameter('head-hide')){
            $thead->add($tr);
        }
    }

    private function buildPaging()
    {
        if ($this->__par['div-stat']) {
            $this->add($this->__par['div-stat']);
        }
        if (empty($this->__par['row-num'])) return '';
        $fot = '<div class="osy-datagrid-2-foot">';
        $fot .= '<input type="button" name="btn_pag" value="&lt;&lt;" class="osy-datagrid-2-paging">';
        $fot .= '<input type="button" name="btn_pag" value="&lt;" class="osy-datagrid-2-paging">';
        $fot .= '<span>&nbsp;<input type="hidden" name="'.$this->id.'_pag" id="'.$this->id.'_pag" value="'.$this->__par['pag_cur'].'" class="req-reinit"> Pagina '.$this->__par['pag_cur'].' di <span id="_pag_tot">'.$this->__par['pag_tot'].'</span>&nbsp;</span>';
        $fot .= '<input type="button" name="btn_pag" value="&gt;" class="osy-datagrid-2-paging">';
        $fot .= '<input type="button" name="btn_pag" value="&gt;&gt;" class="osy-datagrid-2-paging">';
        $fot .= '</div>';
        $this->add($fot);
    }

    private function buildRow(&$grd,$row,$lev=null,$pos=null,$ico_arr=null)
    {
        $t = $i = 0;
        $orw = new Tag('tr');
        $orw->tagdep = (abs($grd->tagdep)+1)*-1;
        $pk = $tree_id = null;
        $opt = array(
            'row' => array(
                'class'  => array(),
                'prefix' => array(),
                'style'  => array(),
                'attr'   => array(),
                'cell-style-inc',array()
            ),
            'cell' => array()
        );
        $primarykey = array();
        foreach ($row as $k => $v) {
            if (array_key_exists('pkey', $this->__par)
                && is_array($this->__par['pkey'])
                && in_array($k, $this->__par['pkey']))
            {
                if (!empty($v)) {
                    $pk = $v;
                    $orw->att('__k',"pkey[$k]=$v",'&');
                    $primarykey[] = array('pkey['.$k.']',$v);
                    if (!$orw->oid) $orw->att('oid',$v);
                } else {
                   $orw->__k = str_replace('pkey','fkey',$orw->__k);
                }
                $t++;
                continue;
            }
            $cel = new Tag('td');
            $opt['cell'] = array(
                'alignment'=> '',
                'class'    => '',
                'color'    => '',
                'command'  => '',
                'format'   => '',
                'hidden'   => false,
                'parameter'=> '',
                'print'    => true,
                'rawtitle' => $k,
                'rawvalue' => $v,
                'style'    => array(),
                'title'    => $k,
                'value'    => htmlentities($v)
            );
            if (array_key_exists($k, $this->getParameter('column-object'))) {
                foreach ($this->getParameter('column-object')[$k] as $par_key => $par_val) {
                    switch ($par_key) {
                        case 'hidden':
                            if ($par_val == '1'){
                                continue 2;
                            }
                            break;
                        case 'format':
                            $opt['cell']['format'] = $par_val;
                            break;
                    }
                }
            } elseif($opt['cell']['rawtitle'][0] == '_') {
                @list($opt['cell']['format'],$opt['cell']['title'],$opt['cell']['parameter']) = explode(',',$opt['cell']['rawtitle']);
            } elseif($opt['cell']['rawtitle'][0] == '$') {
                $opt['cell']['format'] = 'money';
            } elseif($opt['cell']['rawtitle'][0] == '!') {
                $opt['cell']['format'] = 'center';
            }
            if (!empty($opt['cell']['format'])) {
                list($opt,$lev,$pos,$ico_arr) = $this->formatCellValue($row, $opt, $pk, $lev, $pos, $ico_arr);
                //var_dump($opt['row']);
            }
            $t++; //Incremento l'indice generale della colonna
            //Non stampo la colonna se in $opt['cell']['print'] � contenuto false
            if (!$opt['cell']['print']) {
                continue;
            }
            if (!empty($opt['row']['cell-style-inc'])) {
                $cel->att('style',implode(' ',$opt['row']['cell-style-inc']));
            }
            if (!empty($opt['cell']['class'])) {
                $cel->att('class',implode(' ',$opt['cell']['class']));
            }
            //Formatto tipi di dati particolari
            if (!empty($opt['row']['prefix'])) {
                $cel->add2($opt['row']['prefix']);
                $opt['row']['prefix'] = array();
            }
            if (!empty($this->__col[$i]) && is_array($this->__col[$i])) {
                $this->__build_attr($cel,$this->__col[$i]);
            }
            if (array_key_exists($i,$this->__sta['col_len'])) {
                $this->__sta['col_len'][$i] = max(strlen($opt['cell']['rawvalue']),$this->__sta['col_len'][$i]);
            } else {
                $this->__sta['col_len'][$i] = strlen($opt['cell']['rawvalue']);
            }
            $cel->add(($opt['cell']['value'] !== '0' && empty($opt['cell']['value'])) ? '&nbsp;' : nl2br($opt['cell']['value']));
            if (!empty($primarykey)) {
                $orw->att('data-pk',  base64_encode(json_encode($primarykey)));
            }
            $orw->add($cel);
            $i++;//Incremento l'indice delle colonne visibili
        }
        if (!empty($opt['row']['class'])) {
            $orw->att('class',implode(' ',$opt['row']['class']));
        }
        if (!empty($opt['row']['attr'])) {
            foreach ($opt['row']['attr'] as $item) {
              $orw->att($item[0],$item[1]);
            }
        }
        if ($this->getParameter('layout') == 'search' && $orw->oid) {
            $orw->add(tag::create('td'),'first')
                ->att('class','center')
                ->add('<input type="radio" name="rad_search" value="'.$orw->oid.'" class="osy-radiosearch">');
        }
        if ($this->getParameter('print-pencil') && $this->getParameter('record-update')) {
            $orw->add(tag::create('td'))
                ->att('class','center')
                ->att('style','padding: 3px 3px; vertical-align: middle;')
                ->add('<span class="fa fa-pencil cmd-upd fa-lg" style="color: transparent;"></span>');
        }
        $grd->add($orw.'');
    }

    private function checkAndBuildFilter()
    {
        if (!empty($_REQUEST[$this->id.'_filter']) && is_array($_REQUEST[$this->id.'_filter'])) {
            foreach ($_REQUEST[$this->id.'_filter'] as $k => $filter) {
                if (is_array($filter) && count($filter) == 3) {
                    list($filter[0],) = explode('[::]',$filter[0]);
                    if ($filter[1] == 'like') {
                        $filter[2] = '%'.$filter[2].'%';
                    }
                    $this->datasource->addFilter($filter[0],$filter[2],$filter[1]);
                }
            }
        }
        if (empty($_REQUEST[$this->id]) || !is_array($_REQUEST[$this->id])) {
            return;
        }
        foreach ($_REQUEST[$this->id] as $field => $value) {
            $this->datasource->addFilter($field, $value);
        }
        $this->att(
            'class',
            'osy-update-row', 
            true
        );
    }

    private function formatCellValue($row, $opt, $pk, $lev, $pos, $ico_arr=null)
    {
        $opt['cell']['print'] = false;
        switch($opt['cell']['format']) {
            case '_attr':
            case 'attribute':
                $opt['row']['attr'][] = array($opt['cell']['title'],$opt['cell']['value']);
                break;
            case 'color':
            case '_color':
            case '_color2':
            case '_color3':
                $opt['row']['cell-style-inc'][] = 'color: '.$opt['cell']['value'].';';
                break;
            case 'date':
                $dat = date_create($opt['cell']['rawvalue']);
                $opt['cell']['value'] = date_format($dat, 'd/m/Y H:i:s');
                $opt['cell']['class'][] = 'center';
                $opt['cell']['print'] = true;
                break;
            case '_button':
                list($v,$par) = explode('[,]',$opt['cell']['rawvalue']);
                if (!empty($v)) {
                    $opt['cell']['value'] = "<input type=\"button\" name=\"btn_row\" class=\"btn_{$this->id}\" value=\"$v\" par=\"{$par}\">";
                    $opt['cell']['class'][] = 'center';
                } else {
                    $opt['cell']['value'] = '&nbsp;';
                }
                $opt['cell']['print'] = true;
                break;
            case '_chk':
                list($v,$sel) = explode('[,]',$opt['cell']['rawvalue']);
                $vl = empty($pk) ? $v : $pk;
                $opt['cell']['value'] = "<input class=\"osy-datagrid-check\" type=\"checkbox\" name=\"chk_{$this->id}[]\" value=\"$vl\"".(empty($sel) ? '' : ' checked')."> $v";
                $opt['cell']['print'] = true;
                //$cel->att('class','center');
                break;
            case '_chk2' :
                list($v,$sel,$lbl) = explode('#',$opt['cell']['rawvalue']);
                if (!empty($v)){
                    $opt['cell']['value'] = "<input type=\"checkbox\" name=\"chk_{$this->id}[]\" value=\"{$v}\"".(empty($sel) ? '' : ' checked')."> {$lbl}";
                    if (!$lbl){
                        $opt['cell']['class'][] = 'center';
                    }
                } else {
                    $opt['cell']['value'] = '';
                }
                $opt['cell']['print'] = true;
                break;
            case '_rad' :
                if (!empty($opt['cell']['rawvalue'])){
                    $opt['cell']['value'] = "<input type=\"radio\" class=\"rad_{$this->id}\" name=\"rad_{$this->id}\" value=\"{$opt['cell']['rawvalue']}\"".($opt['cell']['rawvalue'] == $_REQUEST['rad_'.$this->id] ? ' checked="checked"' : '').">";
                    $opt['cell']['class'][] = 'center';
                }
                $opt['cell']['print'] = true;
                break;
            case '_tree' :
                //Il primo elemento deve essere l'id dell'item il secondo l'id del gruppo di appartenenza
                @list($tree_id,$tree_group) = explode(',',$opt['cell']['rawvalue']);
               // var_dump($v);
                $opt['row']['attr'][] = array('oid',base64_encode($tree_id));
                $opt['row']['attr'][] = array('gid',base64_encode($tree_group));
                $opt['row']['attr'][] = array('data-treedeep',$lev);
                if (array_key_exists($this->id,$_REQUEST) && $_REQUEST[$this->id] == '['.$tree_id.']'){
                    $opt['row']['class'][] = 'sel';
                }
                if (empty($pk)) { 
                    $pk = $tree_id; 
                }
                if (!is_null($lev)) {
                    $ico = '';
                    for($ii = 0; $ii < $lev; $ii++) {
                        $cls  = empty($ico_arr[$ii]) ? 'tree-null' : ' tree-con-'.$ico_arr[$ii];
                        $ico .= '<span class="tree '.$cls.'">&nbsp;</span>';
                    }
                    $ico .= $row['__groupedType'] == 'branch'
                           ? '<span class="tree tree-plus-'.$pos.'">&nbsp;</span>'
                           : '<span class="tree tree-con-'.$pos.'">&nbsp;</span>';
                    $opt['row']['prefix'][] = $ico;
                    if (!empty($lev)) {
                        $opt['row']['class'][] = 'hide';
                    }
                }
                break;
            case '_form' :
                $opt['row']['attr'][] = array('__f' , base64_encode($opt['cell']['rawvalue']));
                $opt['row']['class'][] = '__f';
                break;
            case '_html' :
            case 'html' :
                $opt['cell']['print'] = true;
                $opt['cell']['value'] = $opt['cell']['rawvalue'];
                break;
            case '_ico'  :
                $opt['row']['prefix'][] = "<img src=\"{$opt['cell']['rawvalue']}\" class=\"osy-treegrid-ico\">";
                break;
            case '_faico'  :
                $opt['row']['prefix'][] = "<span class=\"fa {$opt['cell']['rawvalue']}\"></span>&nbsp;";
                break;
           case '_pk'   :
                $opt['row']['attr'][] = array('_pk',$opt['cell']['rawvalue']);
                break;
            case '_img64x2':
                $dimcls = 'osy-image-med';
            case '_img64':
                $opt['cell']['print'] = true;
                $opt['cell']['class'][] = 'center';
                $opt['cell']['value'] = '<span class="'.(empty($dimcls) ? 'osy-image-min' : $dimcls).'">'.(empty($opt['cell']['rawvalue']) ? '<span class="fa fa-ban"></span>': '<img src="data:image/png;base64,'.base64_encode($opt['cell']['rawvalue']).'">').'</span>';
                break;
            case 'money':
                $opt['cell']['print'] = true;
                if (is_numeric($opt['cell']['rawvalue'])) {
                    $opt['cell']['value'] = number_format($opt['cell']['rawvalue'],2,',','.');
                }
                $opt['cell']['class'][] = 'right';
                break;
            case 'center':
                $opt['cell']['print'] = true;
                $opt['cell']['class'][] = 'center';
                break;
        }
        return array($opt, $lev, $pos, $ico_arr);
    }

    private function dataLoad()
    {
        if ($this->datasource) {
            //Scorro il recordset
            $this->__dat = $this->datasource->get();
            //Salvo le colonne in un option
            $this->__par['cols'] = $this->datasource->getColumns();
            $this->__par['cols_tot'] = count($this->__par['cols']);
            $this->__par['cols_vis'] = 0;
            if (is_array($this->__par['cols'])) {
                $this->__par['cols_tot'] = count($this->__par['cols']);
            }
            $this->__par['pag_cur'] = $this->datasource->getPage('current');
            $this->__par['pag_tot'] = $this->datasource->getPage('total');
        }
    }


    private function dataPivot($tr)
    {
       $data = array();
       $hcol = array();
       $hrow = array();
       $fcol = null;
       foreach ($this->__dat as $i => $rec) {
           $col = $row = null;
           foreach ($rec as $fld=>$val){
               if ($fld == '_pivot'){
                   $col = $val;
                   if (!in_array($col,$hcol)){
                       $hcol[] = $col;
                   }
               } elseif (is_null($col)){
                   if (empty($i)) {
                       $hcol[0] = $fld;
                   }
                   $row = $val;
                   if (!in_array($row,$hrow)) $hrow[] = $row;
               } else {
                   $data[$col][$row][] = $val;
               }
           }
       }

       $data_pivot = array();
       ksort($hrow); ksort($hcol);
       foreach ($hrow as $row){
           foreach ($hcol as $i => $col){
               if (empty($i)){
                   $drow[$col] = $row; //Aggiuno la label della riga
               } else {
                   $drow[$col] = array_key_exists($row,$data[$col]) ? array_sum($data[$col][$row]) : '0';
               }
           }
           $data_pivot[] = $drow;
       }
       $this->__dat = $data_pivot;
       $ncol = array();
       foreach ($hcol as $i => $col){
          if (empty($i)) continue;
          $tr->add(tag::create('th'))->att('class','no-order')->add($col);
       }
       //return ; //Restituisco il record contenente l'header delle colonne in
    }
    
    private function loadColumnObject()
    {
        /*$oid = $this->__par['objectid'];
        $sql = "SELECT o.o_nam as obj_nam,
                       p.p_id  as prp_id,
                       p.p_vl  as prp_vl
                FROM osy_obj o
                INNER JOIN osy_obj_prp p ON (o.o_id = p.o_id )
                WHERE o.o_own = ?";
        $res = env::$dbo->exec_query($sql,array($oid));
        foreach ($res as $rec) {
            $this->__par['column-object'][$rec['obj_nam']][$rec['prp_id']] = $rec['prp_vl'];
        }*/
    }
    
    public function getColumns()
    {
        return $this->__col;
    }
    
    public function setDatasource(InterfaceDatasourcePaging $datasource)
    {
        $this->datasource = $datasource;
        //Set current page, page command, Row for page
        $this->datasource->setPage(
            $_REQUEST[$this->id.'_pag'],
            $_REQUEST['btn_pag'],
            $this->rows
        );
        //Set order by field
        $this->datasource->orderBy(
            $_REQUEST[$this->id.'_order']
        );
        $this->checkAndBuildFilter();
    }
}
