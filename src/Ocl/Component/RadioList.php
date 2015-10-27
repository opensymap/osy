<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/RadioList.php                                      |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create radiolist component                                          |
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

use Opensymap\Osy as env;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Helper\HelperOsy;
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\RadioBox;
use Opensymap\Ocl\Component\Panel;

class RadioList extends Panel implements DboAdapterInterface
{
    private $db;
    private $datasource;
    
    public function __construct($name)
    {
        parent::__construct($name);
        $this->att('class','osy-radio-list');
    }
   
    protected function build()
    {
        $a_val = array();
        if ($val = $this->getParameter('values')) {
            $a_val_raw = explode(',',$val);
            foreach($a_val_raw as $k => $val) {
                $a_val[] = explode('=',$val);
            }
        }
        if ($sql = $this->getParameter('datasource-sql')) {
            $sql = HelperOsy::replaceVariable($sql);
            $sql = HelperOsy::parseString($sql);
            $a_val = $this->db->exec_query($sql,null,'NUM');
        }
        $dir = $this->getParameter('direction');
        foreach ($a_val as $k => $val) {
            //$tr = $this->add(tag::create('tr'));
            //$tr->add(tag::create('td'))->add('<input type="radio" name="'.$this->id.'" value="'.$val[0].'"'.(!empty($_REQUEST[$this->id]) && $_REQUEST[$this->id] == $val[0] ? ' checked' : '').'>');
            //$tr->add(tag::create('td'))->add($val[1]);
            $rd = new RadioBox($this->id);
            $rd->value = $val[0];
            if ($this->cols){
                $rst = $k % $this->cols;
                if (empty($rst)) $row += 10;
                $col = ($resto * 10)+10;
                $this->put(null,$rd.'&nbsp;'.$val[1],$row,$col);
            } elseif ($dir == 'O'){
                $this->put(null,$rd.'&nbsp;'.$val[1],10,($k*10)+9);
            } else {
                $this->put(null,$rd.'&nbsp;'.$val[1],($k*10)+9,10);
            }
            //$this->put(null,$val[1],$k+9,10);
        }
        parent::build();
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
    
    public function setDatasource($ds)
    {
        $this->datasource =$ds;
    }
}
