<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/TextSearchInLine.php                               |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create TextSearchInLine component                                   |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/04/2015
 * @date-update     09/04/2015
 */
namespace Opensymap\Ocl\Component;

use Opensymap\Osy as env;
use Opensymap\Lib\Tag;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Ocl\AjaxInterface;

class TextSearchInLine extends AbstractComponent implements DboAdapterInterface,AjaxInterface
{
    use DboHelper;
    
    private $hdnBox = null;
    private $textBox = null;
    private $spanSrc = null;
    private $db;
    
    public function __construct($name)
    {
        parent::__construct('div');
        $this->class = 'osy-textsearch-inline';
        $this->id = $name;
        $this->hdnBox = $this->add(new HiddenBox($name));
        $this->textBox = $this->add(tag::create('input'))
                              ->att('type','text')
                              ->att('name',$name.'_lbl');
        $this->spanSrc = $this->add(tag::create('span'))->att('class','fa fa-search');
        $this->addRequire('css/TextSearchInLine.css');
        $this->addRequire('js/component/TextSearchInLine.js');
    }
    
    public function build()
    {
        $form_pkey = array();       
        if ($form = $this->get_par('form-related')) {
            $form_pkey = $form_par['pkey'];
            $str_par = "obj_src=".$this->id;
            $frm_par = (key_exists('rel_fields',$this->__par)) ? explode(',',$this->__par['rel_fields']) : array();            
            foreach($frm_par as $fld) { 
                $str_par .= '&'.$fld.'='.get_global($fld,$_REQUEST);
            }
            if (!empty($_REQUEST[$this->id])) {
                $str_par.='&pkey[id]='.$_REQUEST[$this->id];                
            }
            $this->att('data-form',$form)
                 ->att('data-form-dim',$form_par['width'].','.$form_par['height'])
                 ->att('data-form-nam',$form_par['name'])
                 ->att('data-form-pag',$form_par['page'])
                 ->att('data-form-par',$str_par);
        }
        
        if ($_REQUEST['ajax'] == $this->id) {
            $this->ajaxResp($form_pkey);
            return;
        } elseif (!empty($_REQUEST[$this->id]) && ($sql = $this->get_par('datasource-sql-label'))) {            
            $sql = env::replaceVariable($sql);          
            $this->textBox->value = env::$dba->exec_unique($sql,null,'NUM');            
        }
    }
    
    public function ajaxResponse($controller, &$response)
    {
        $resp = $this->buildAjax();
        $response = new \Opensymap\Response\PageHtmlResponse();
        $response->getBody()->add('<div id="response">'.$resp.'</div>');
    }
    
    public function buildAjax() 
    {
        $form_pkey = array();
        if ($form = $this->get_par('form-related')) {
            $form_par = $this->getFormParam($form,true);          
            $form_pkey = $form_par['pkey'];
        }
        $tbl = new Tag('div');          
        $sql = $this->replacePlaceholder($this->get_par('datasource-sql'));
        $rs  = $this->db->exec_query("SELECT * FROM (".$sql.") a",null,'ASSOC');
        $cols = $this->db->get_columns();
        foreach($cols as $col) {
            if ($col['name']=='_group') {
                $rs = $this->groupRs($rs);
            }
        }
        $__g = '';
        foreach($rs as $rec) {
            $tr = tag::create('div')->att('class','row');
            $__k = array(); 
            $_oid = array();
            foreach($rec as $key=> $fld) {
                $val = $fld;
                if (in_array($key, $form_pkey)) {
                    $__k[] = 'pkey['.$key.']='. $val;
                    $_oid[] = $val;
                    continue;
                }
                $print = true;
                if ($key[0]=='_') {
                    $print = false;
                    switch($key) {
                        case '_id' :  
                            $tr->att('data-oid',$val);
                            $print=false;
                            break;
                        case '_label' : 
                            $tr->att('data-label',$val);
                            $print=false;
                            break;
                        case '_group' :
                            if ($val != $__g) {
                              $__g = $val;  
                            } else {
                              $val = '&nbsp;';
                            }
                            $val = '<span class="osy-textsearch-inline-group">'.$val.'</span>';
                            $print = true;
                            break;
                        case '_img64x2' :
                             $dimcls = 'osy-image-med';
                             //no-break
                        case '_img64' :                                                          
                            $val = '<span class="'.(empty($dimcls) ? 'osy-image-min' : $dimcls).'">'.(empty($fld) ? '<span class="fa fa-ban"></span>': '<img src="data:image/png;base64,'.base64_encode($fld).'">').'</span>';
                            $print = true;
                            break;
                        case '_label' :
                            $tr->att('data-label',$val);                                        
                            break;             
                    }
                }
                if ($print) {
                    $tr->add(tag::create('div'))->add($val);
                }
            }
            //$tr->add('<br class="clear">');
            $tbl->add($tr);
            if (!empty($__k)) {
                $tr->att('data-pkey',implode('&',$__k));                
                $tr->att('data-oid',implode('&',$_oid));   
            }
        }
        return $tbl;   
    }
    
    private function groupRs($rs)
    {
        $rsg = array();
        foreach($rs as $rec) {
            $grp = $rec['_group'];            
            $rsg[$grp][] = $rec;            
        }        
        $rs = array();
        $extra_get = 0;
        ksort($rsg);
        foreach($rsg as $k => $group) {
            $nget = min(5,count($group)) + $extra_get;
            $group = array_slice($group, 0,$nget);
            $extra_get = ($nget < 5) ? 5 - $nget : 0;
            foreach($group as $rec) {
                $rs[] = $rec;
            }
        }
        return $rs;
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
}