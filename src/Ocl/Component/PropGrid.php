<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/PropGrid.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create property grid component                                      |
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

use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Lib\Tag as Tag;
use Opensymap\Ocl\AjaxInterface;

class PropGrid extends AbstractComponent implements DboAdapterInterface, AjaxInterface
{
    use DboHelper;
    
    protected $data = array();
    private $db;
    
    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->att('class','osy-grid-property');
        $this->addRequire('css/PropGrid.css');
        $this->addRequire('js/component/PropGrid.js');
    }

    protected function build()
    {
        if (!empty($_REQUEST['ajax'])) {
            die($this->ajaxResp());
        }
        if ($sql = $this->get_par('datasource-sql')) {
            $this->dataLoad($sql);
        }
        $tbl = $this->add(tag::create('table'));
        foreach ($this->data as $k => $rec) {
            $tr = $tbl->add(tag::create('tr'))
                      ->att('data-param',base64_encode(serialize($rec)));
            $td_prp = $tr->add(tag::create('td'))
                         ->att('class','osy-prop')
                         ->add($rec['p_label']);
            $td_val = $tr->add(tag::create('td'))
                         ->att('class','osy-prop')
                         ->add(substr(htmlentities($rec['p_value']),0,100));
        }
    }

    public function ajaxResponse($controller, &$response)
    {
        if (!empty($_REQUEST['ajax-param'])) {
            $param = unserialize(base64_decode($_REQUEST['ajax-param']));
            $_REQUEST[$this->id.'_set'] = $param['p_value'];
            $sql = $param['component_datasource'];
        }

        switch ($param['component_type']) {
            case 'CMB':
                $sql = $this->replacePlaceholder($sql);
                $cmp = new ComboBox($this->id.'_set');
                $cmp->att('label',$this->label)
                    ->par('datasource-sql',$sql) //Setto la risorsa per popolare la combo e la connessione al DB necessaria ad effettuare le query.
                    ->setDboHandler($this->db);
                break;
            case 'TAR':
                $cmp = new TextArea($this->id.'_set');
                $cmp->att('style','width: 95%;')
                    ->att('rows','20');
                break;
            default :
                $cmp = new TextBox($this->id.'_set');
                $cmp->att('style','width: 95%');
                break;
        }

        $response->message('result', '<div class="osy-grip-property-popup">'.$cmp.'&nbsp;<span class="fa fa-save"></span>&nbsp;<span class="fa fa-save"></span></div>');
    }

    protected function dataLoad($sql)
    {
        $sql = $this->replacePlaceholder($sql);
        $this->data = $this->db->exec_query($sql);
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
}
