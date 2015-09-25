<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/AddressBook.php                                              |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2015, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create AdressBook component                                         |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/12/2014
 * @date-update     09/12/2014
 */
 
namespace Opensymap\Ocl\Component;

use Opensymap\Ocl\Component\AbstractComponent;

class AddressBook extends AbstractComponent
{
    private $db  = null;
    private $sql = null;

    public function __build_extra__()
    {
        if (!$this->get_par('ajax'))
        {
            if ($this->get_par('form-related')) $this->setFormExternal();
            $this->buildHead();
            $this->buildFilter();
        }
          elseif ($this->get_par('ajax') != "#".$this->att('id'))
        {
            return;
        }
        $this->buildBody();
        ///parent::__build__();
    }
    
    public function __construct($id,$sql='',$pag=0)
    {
        parent::__construct('div',$id);
        $this->__par['pag_cur'] = empty($pag) ? 0 : $pag;        
        $this->att('class','osy-addressbook')
             ->att('pag',$this->__par['pag_cur']);
        $this->__par['record-add']     = false;
        $this->__par['record-add-label'] = " + Aggiungi";
        $this->__par['record-update']     = false;
        $this->__par['record-update-label'] = "Modifica";
        $this->__par['form']        = null;
        $this->__par['title']       = '';
        $this->__par['row_shw']     = 16;
        $this->__par['ajax']        = false;
        $this->__par['filter']      = array();
        $this->__par['scroll-master'] = 'self';
        $this->__par['datasource-sql'] = $sql;
        env::$page->add_css(OSY_WEB_ROOT.'/css/AddressBook.css');
        env::$page->add_script(OSY_WEB_ROOT.'/js/component/AddressBook.js');
        $this->addRequire(OSY_WEB_ROOT.'/css/AddressBook.js',Component::CSS);
        $this->addRequire(OSY_WEB_ROOT.'/js/component/AddressBook.js',Component::JS);
    }
    
    private function pageView()
    {
        if ($this->__par['row_shw'] < 1) 
        {
            return true;
        }
        try
        {
            $sql = env::replaceVariable($this->get_par('datasource-sql'));            
            $sql = env::parseString($sql);
            $this->__par['datasource-sql'] = $sql;
            $this->__par['row_tot'] = env::$dba->exec_unique("SELECT COUNT(*) FROM (".$this->__par['datasource-sql'].") a");
        } catch (Exception $e){
            echo $e->getMessage();
            echo $this->__par['datasource-sql'];
        }
        $this->__par['pag_tot'] = ceil($this->__par['row_tot'] / $this->__par['row_shw']);
        if ($this->__par['pag_cur'] >= $this->__par['pag_tot'] && !empty($_REQUEST['ajax']))
        {
           return false;
        }
        $where = '';
        if ($filter = $this->get_par('filter')){
            $where = 'where '.implode(' AND ',$filter);
        }
        if (array_key_exists('osy',$_REQUEST) && !empty($_REQUEST['osy']['rid'])){
            $where = (empty($where) ? 'WHERE ' : $where.' AND ') . str_replace(array('pkey[',']','&'),array('','',' AND '),$_REQUEST['osy']['rid']);
        }
        $this->__par['datasource-sql'] = 'SELECT a.* 
                                          FROM ('.$this->__par['datasource-sql'].') a
                                         '.$where.'
                                          ORDER BY 2
                                          LIMIT '.($this->__par['pag_cur'] * $this->__par['row_shw']).','.$this->__par['row_shw'];
        return true;
    }
    
    private function buildBody()
    {
        if (!$this->pageView())
        { 
            ob_clean();
            die('');
            return;
        }
        //$rs = $this->db->execquery($this->sql);
        $b = new tag('div');
        $b->att('class','osy-addressbook-body')
          ->att('pag',$this->get_par('pag_cur'))
          ->att('scroll-master',$this->get_par('scroll-master'));
        try{
            $par = $this->get_par('filter-parameters');
            $rs = env::$dba->exec_query($this->__par['datasource-sql'],$par,'ASSOC');
        } catch (Exception $e){
            die($e->getMessage(). ' - ' .$this->__par['datasource-sql']);
        }
        //while($rec = $this->db->getnextrecord($rs,'ASSOC'))
        if (empty($rs)){
            if (empty($_REQUEST['ajax'])){
                $title = $this->get_par('title');
                if (!empty($title)){
                    $title = in_array(strtolower($title[0]),array('a','e','i','o','u')) ? "L'".strtolower($title) : $title;
                }
                $this->add($b)->add('<div class="osy-addressbook-empty">'.$title.' &egrave; vuota</div>');
            }
            return;
        }
        //Query columns
        $col = '';
        foreach (array_keys($rs[0]) as $k => $v){
            if ($v[0] != '_'){
                $col .= (empty($col) ? '' : ',').$v;
            }
        }
        $this->att('data-columns',$col);
        
        foreach ($rs as $k => $rec) {
            $a = $b->add(tag::create('div'))->att('class','osy-addressbook-item');
            $p0 = $a->add(tag::create('div'))->att('class','p0');
            $p1 = $a->add(tag::create('div'))->att('class','p1');
            $p2 = $a->add(tag::create('div'))->att('class','p2');
            //$p2->add('&nbsp;');
            $i = 0;
            $href = null;
            foreach ($rec as $k => $v) {
               if (
                    key_exists('pkey',$this->__par) && 
                    is_array($this->__par['pkey']) && 
                    in_array($k,$this->__par['pkey'])
                ) {
                    if (!empty($v)) {
                        $pk = $v;
                        $a->att('__k',"pkey[$k]=$v",'&');
                        if (!$a->oid) $a->att('oid',$v);
                    } else {
                       $a->__k = str_replace('pkey','fkey',$a->__k);
                    }
                    $t++;
                    continue;
                }
                
                switch ($k) {
                    case '_class':
                        $a->att('class',$v,true);
                        break;
                    case '_pk':
                        $a->att('pid',$v);
                        if ($this->get_par('form') != null && $this->get_par('cmd_upd')!=false) {
                            $cmd = str_replace(array('<pk>','<fid>','640','480'),array($v,$this->get_par('form')),$this->__par['cmd_upd']);
                            $a->att('onclick',$cmd);
                        }
                        break;
                    case '_href':
                        $href = $v;
                        break;
                    case '_p0':
                        $img = '<img src="'.$v.'">';
                        if (!empty($href)) {
                            $img = '<a href="'.$href.'">'.$img.'</a>';
                        }
                        $p0->add($img);
                        break;
                    case '_p2':
                        $p2->add('<div>'.$v.'</div>');
                        break;
                    case '_image' : 
                        if (!empty($v)) {
                            $p1->add(tag::create('img'))
                               ->att('src','data:image/png;base64,'.base64_encode($v));
                        }
                        break;
                    default:
                        if ($k[0] == '_') { 
                            break; 
                        }
                        $i++;
                        if (empty($v)) {
                            break;
                        }
                        if (empty($href)) {
                           $p1->add('<div class="s'.$i.'">'.$v.'</div>');
                        } else {
                           $p1->add('<a class="s'.$i.'" href="'.$href.'">'.$v.'</a><br>');
                        }
                        break;
                }
           }
        }
        
        $this->add($b);
        $this->add('<br id="qnn-end" style="clear: both">');
    }
    
    private function buildFilter()
    {
        if (empty($this->__par['filter'])) return;
        $filter = new tag('div');
        $filter_cont = $filter->att('class','addressbook-filter')->add(new tag('div'));
        foreach ($this->__par['filter'] as $k => $flt) {
            $filter_cont->add($flt);
        }
        $filter_cont->add('<br style="clear: both" />');
        $this->__content__[][] = $filter->get();
    }
    
    private function buildHead()
    {
            //Head
        if (!$this->get_par('title')) return;
        $head = new tag('div');
        $head->att('class','osy-addressbook-head');
        $head->add('<h3>'.$this->get_par('title').'</h3>');
        if ($this->get_par('form') != null && $this->get_par('cmd_add') != false)
        {
            $cmd = str_replace(array('<lbl>','<pk>','<fid>'),array($this->get_par('cmd_add_lbl'),"''",$this->get_par('form')),$this->get_par('cmd_add'));
            $head->add('<div class="add">'.$cmd.'</div>');
            $head->add('<br style="clear: both" />');
        }
        
        $this->__content__[][0] = $head->get();
    }
    
    public function add_filter($filter,$val=null,$op='like')
    {
        if (is_null($val) && is_null($op)){
            $this->__par['filter'][] = $filter;
        } else {
            $this->__par['filter'][] = $filter.' '.$op.' ?';
            $this->__par['filter-parameters'][] = $val;
        }
    }
    
    public function cmd_upd($cmd,$lbl='Modifica')
    {
        $this->get_par('cmd_upd',$cmd);
        if ($this->get_par('cmd_add')) $this->get_par('cmd_add','<a href="#" onclick="'.$cmd.'"><lbl></a>');
        $this->get_par('cmd_upd_lbl',$lbl);
    }
    
    private function setFormExternal()
    {
        $add = $this->get_par('record-add');
        if (is_null($add)) $this->par('record-add',true);
        $this->par('record-update',true);
        $res = $this->db->exec_query("SELECT frm.o_id  AS form_id,
                                             fty.p1    AS form_man,
                                             dfld.p_vl AS field_pkey,
                                             hprp.p_vl AS height,
                                             wprp.p_vl AS width
                                      FROM  osy_obj frm
                                      INNER JOIN osy_obj      app  ON (frm.o_own = app.o_id)
                                      INNER JOIN osy_obj      fld  ON (frm.o_id = fld.o_own)
                                      INNER JOIN osy_obj_prp  pfld ON (fld.o_id = pfld.o_id AND pfld.p_id = 'db-field-is-pkey')
                                      INNER JOIN  osy_obj_prp dfld ON (fld.o_id = dfld.o_id AND dfld.p_id = 'db-field-connected')
                                      LEFT JOIN  osy_obj_prp  hprp ON (frm.o_id = hprp.o_id AND hprp.p_id = 'height')
                                      LEFT JOIN  osy_obj_prp  wprp ON (frm.o_id = wprp.o_id AND wprp.p_id = 'width')
                                      LEFT JOIN  osy_res      fty  ON (frm.o_sty = fty.v_id AND fty.k_id = 'osy-object-subtype')
                                      WHERE frm.o_id = ? AND pfld.p_vl = '1'
                                      UNION
                                      SELECT frm.o_id  AS form_id,
                                             fty.p1    AS form_man,
                                             null      AS field_pkey,
                                             hprp.p_vl AS height,
                                             wprp.p_vl AS width
                                      FROM  osy_obj frm
                                      INNER JOIN osy_obj      app  ON (frm.o_own = app.o_id)
                                      LEFT JOIN osy_obj_prp hprp   ON (frm.o_id = hprp.o_id AND hprp.p_id = 'height')
                                      LEFT JOIN osy_obj_prp wprp   ON (frm.o_id = wprp.o_id AND wprp.p_id = 'width')
                                      LEFT JOIN osy_res      fty   ON (frm.o_sty = fty.v_id AND fty.k_id = 'osy-object-subtype')
                                      WHERE frm.o_id = ?",array($this->get_par('form-related'),$this->get_par('form-related-ins')),'NUM');
        $pkey = array();
        foreach($res as $k => $rec)
        {
            if ($this->get_par('form-related') == $rec[0])
            {
                $pkey[] = $rec[2];
                $this->att('data-form',base64_encode(OSY_WEB_ROOT.$rec[1].'[::]'.$rec[0].'[::]'.nvl($rec[4],'640').'[::]'.nvl($rec[3],'480')));
            }
             elseif($this->get_par('form-related-ins') == $rec[0])
            {
                $this->att('data-form-insert',base64_encode(OSY_WEB_ROOT.$rec[1].'[::]'.$rec[0].'[::]'.nvl($rec[4],'640').'[::]'.nvl($rec[3],'480')));
            }
            
            /*if ($this->get_par('form-related') == $rec[0])
            {
                $pkey[] = $rec[2];
                $this->att('data-form',$rec[5]);
                $this->att('data-form-parameter',nvl($rec[4],'640').'&'.nvl($rec[3],'480'));
            }
             elseif($this->get_par('form-related-ins') == $rec[0])
            {
                $this->att('data-form-insert',$rec[5]);
                $this->att('data-form-parameter',nvl($rec[4],'640').'&'.nvl($rec[3],'480'));
            }*/
        }
       
        $this->par('pkey',$pkey);
   }
}
?>

