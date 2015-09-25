<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Label.php                                          |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create label component                                              |
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
use Opensymap\Driver\DboHelper;
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox;

class Label extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;
    
    private $db;
    
    public function __construct($name)
    {
        parent::__construct('label',$name);
        $this->att('class','normal');
        $this->add(new HiddenBox($name));
    }
    
    protected function build()
    {
        $val = get_global($this->id,$_REQUEST);
        if ($sql = $this->get_par('datasource-sql')) {
            $sql = $this->replacePlaceholder($sql, $this->getRequest('input'));
            $val = $this->get_par('db-field-connected') ? $val : '[get-first-value]';
            $val = $this->getFromDatasource($val, $sql, $this->db);
        }
        if ($pointer = $this->get_par('global-pointer')) {
            $ref = array(&$GLOBALS,&$_REQUEST,&$_POST);
            foreach ($ref as $global_arr) {
                if (key_exists($pointer,$global_arr)) {
                    $val = $global_arr[$pointer];
                    break;
                }
            }
        }
        if (strstr($val,"\n")){
            $this->add(nvl('<pre>'.$val.'</pre>','&nbsp;'));
        } else {
            $this->add(nvl($val,'&nbsp;'));
        }
    }
    
    public static function getFromDatasource($val, $lst, $db=null)
    {
        $lbl = $val;
        if (!is_array($lst) && !is_null($db)) {
            try {
                $lst = $db->exec_query($lst,null,'NUM');
            } catch(Exception $e) {
               echo $lst;
               $this->att(0,'dummy');
               $this->add('<div class="osy-error" id="'.$this->id.'">SQL ERROR - [LABEL]</div>');
               $this->add('<div class="osy-error-msg">'.($e->getMessage()).'</div>');
               return;
            }
        }
        
        if ($val == '[get-first-value]') {
            return !empty($lst[0]) ? nvl($lst[0][1],$lst[0][0]) : null;
        } elseif (is_array($lst)) {
            foreach($lst as $k => $rec) {
                if ($rec[0] == $val) {
                    return nvl($rec[1],$rec[0]);
                }
            }
        }
        return $lbl;
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
}
