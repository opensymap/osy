<?php
/**
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/ComboBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create ComboBox component                                           |
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

use Opensymap\Lib\Tag as tag;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Ocl\Component\AbstractComponent;

//costruttore del combo box
class ComboBox extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;
    
    public $__dat = array();
    public $__grp = array();
    private $db;
    private $value;
    
    public function __construct($nam,$id=null)
    {
        parent::__construct('select',nvl($id,$nam));
        $this->att('name',$nam);
    }

    public function addOption($value, $label)
    {
        $cmp_val = get_global($this->name, $_REQUEST);
        $opt = $this->add(new Tag('option'))
                    ->att('value',$value);
        $opt->add(nvl($label, $value));
        if ($cmp_val == $value) {
            $opt->att('selected','selected');
        }
    }

    protected function build()
    {
        if ($dsr = $this->get_par('datasource')) {
            $this->__dat = $dsr;
        } elseif ($sql = $this->get_par('datasource-sql')) {
            $sql = $this->replacePlaceholder($sql, $this->getRequest('input'));
            try {
                $this->__dat = $this->db->exec_query($sql,NULL,'BOTH');
            } catch(Exception $e) {
                $this->att(0,'dummy');
                $this->add('<div class="osy-error" id="'.$this->id.'">SQL ERROR - [LABEL]</div>');
                $this->add('<div class="osy-error-msg">'.($e->getMessage()).'<br>'.nl2br($sql).'</div>');
                return;
            }
        }
        if (!empty($this->__dat) && array_key_exists('_group',$this->__dat[0])) {
            if (!$this->get_par('option-select-disable')) {
                array_unshift($this->__dat, array('','- select -','_group'=>''));
            }
            $this->buildTree($this->__dat);
            return;
        } 
        if (!$this->get_par('option-select-disable')) {
            if (!($lbl = $this->get_par('label-inside'))) {
                $lbl = '- select -';
            }
            array_unshift($this->__dat, array('',$lbl));
        }
        $val = get_global($this->name,$_REQUEST);
        $idx = array(0,1);
        if ($this->get_par('fields-order')) {
            $idx = explode(',',$this->get_par('fields-order'));
        }
        foreach ($this->__dat as $k => $itm) {
            $sel = ($val == $itm[$idx[0]]) ? ' selected' : '';
            $opt = $this->add(Tag::create('option'))->att('value',$itm[$idx[0]]);
            $opt->add(nvl($itm[$idx[1]], $itm[$idx[0]]));
            if ($val == $itm[$idx[0]]) {
                $opt->att('selected','selected');
            }
        }
    }

    private function buildTree($recordSet)
    {
        $dat = array();
        foreach($recordSet as $k => $rec) {
            if (empty($rec['_group'])) {
                $dat[] = $rec;
            } else {
                $this->__grp[$rec['_group']][] = $rec;
            }
        }
        $this->buildBranch($dat);
    }

    private function buildBranch($dat,$lev=0)
    {
        if (empty($dat)) return;
        $len = count($dat)-1;
        $cur_val = get_global($this->name,$_REQUEST);
        foreach($dat as $k => $rec) {
            $val = array();
            foreach($rec as $j => $v) {
                if (!is_numeric($j)) continue;
                if (count($val) == 2) continue;
                $sta = (empty($lev)) ? '' : '|';
                $end = $len == $k    ? "\\" : "|";
                $val[] = empty($val) ? $v : str_repeat('&nbsp;',$lev*5).$v;
            }
            $sel = ($cur_val == $val[0]) ? ' selected' : '';
            $opt = $this->add(tag::create('option'))
                        ->att('value',$val[0]);
            $opt->add(nvl($val[1],$val[0]));
            if ($cur_val == $val[0]) {
                $opt->att('selected','selected');
            }
            if (array_key_exists($val[0],$this->__grp)) {
                $this->buildBranch($this->__grp[$val[0]],$lev+1);
            }
        }
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
}
