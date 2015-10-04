<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/MultiBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create multibox component                                           |
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

use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Helper\HelperOsy;
use Opensymap\Lib\Tag;

class MultiBox extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;
    
    private $db;
    private $datasource;
    
    public function __construct($nam, $id=null)
    {
        $this->addRequire('js/component/DateBox.js');
        parent::__construct('div',$nam,nvl($id,$nam));
        $this->att('class','osy-multibox');
    }

    protected function build()
    {
        $sql = $this->get_par('datasource-sql');
        if (empty($sql)) {
            die('[ERROR] - Multibox '.$this->id.' - query builder assente');
        }
        $sql = $this->replacePlaceholder($sql);
        $res = $this->db->exec_query($sql,null,'ASSOC');
        //die($sql);
        if (empty($res))
            return;
        $mlt_tbl = $this->add(tag::create('table'));
        $val_from_db = (array_key_exists($this->id, $_REQUEST) && is_array($_REQUEST[$this->id])) ? false : true;
        foreach($res as $k => $cmp_raw) {
            if ($val_from_db) {
               $_REQUEST[$this->id][$cmp_raw['id']] = $cmp_raw['val'];
            }
            $mlt_row = $mlt_tbl->add(tag::create('tr'));
            $cmp = $lbl = null;
            if (strlen($this->readonly) > 4) {
                $this->readonly = HelperOsy::exec_string(null,'return '.$this->readonly.';');
            }
            if ($this->readonly) {
                $cmp = tag::create('span');
                if ($cmp_raw['typ'] == 'CMB') {
                    $cmp_raw['val'] = label::getFromDatasource($cmp_raw['val'],$cmp_raw['sql_qry'], $this->db);
                }
                $cmp->add($cmp_raw['val']);
            } else {
                $is_req = $cmp_raw['is_req'];
                $cmp_nam = "{$this->id}[{$cmp_raw['id']}]";
                switch ($cmp_raw['typ']) {
                    case 'DAT' :
                        $cmp = new DateBox($cmp_nam);
                        $cmp->par('date-format','dd/mm/yyyy');
                        break;
                    case 'TXT' :
                    case 'NUM' :
                        $cmp = new TextBox($cmp_nam);
                        if ($cmp_raw['typ'] == 'NUM') {
                            $cmp->att('class','numeric',true);
                        } else {
                            $cmp->att('class','text',true);
                        }
                        break;
                    case 'CMB' :
                        $cmp = new ComboBox($cmp_nam);
                         //echo $cmp_raw['sql_qry'];
                        $cmp->par('datasource-sql',HelperOsy::replaceVariable($cmp_raw['sql_qry']));
                        break;
                }
                $cmp->att('label',$cmp_raw['nam']);
                if (!empty($is_req)) {
                    $lbl = '(*) ';
                    $cmp->att('class','is-request',true);
                }
            }
            if (!is_null($cmp)) {
                $lbl = "<label class=\"multibox\">{$lbl}{$cmp_raw['nam']}</label>";
                $mlt_row->add(new Tag('td'))->add($lbl);
                $mlt_row->add(new Tag('td'))->add($cmp);
            }
        }
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
    
    public function setDatasource($datasource)
    {
        $this->datasource = $datasource;
    }
}
