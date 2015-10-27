<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/CheckList.php                                      |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create CheckList component                                          |
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

use Opensymap\Lib\Tag;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Ocl\Component\AbstractComponent;


class CheckList extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;

    private $table = null;
    private $db;
    private $datasource;
   
    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->table = $this->add(new Tag('table'));
        $this->att('class','osy-check-list');
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
            $sql = $this->replacePlaceholder($sql);
            $a_val = $this->db->exec_query($sql,null,'NUM');
        }
        $col = $this->cols ? $this->cols : 1;
        foreach($a_val as $k => $val) {
            if ($k == 0 or ($k % $col) == 0) {
                $tr = $this->table->add(new Tag('tr'));   
            } 
            if (!empty($_REQUEST[$this->id]) && in_array($val[0],$_REQUEST[$this->id])) {
                $val[2] = true;
            }
            $tr->add(new Tag('td'))
               ->add('<input type="checkbox" name="'.$this->id.'[]" value="'.$val[0].'"'.(!empty($val[2]) ? ' checked' : '').'>&nbsp;'.$val[1]);
        }
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
    
    public function setDatasource($ds)
    {
        $this->datasource = $ds;
    }
}
