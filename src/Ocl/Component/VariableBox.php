<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/VaribleBox.php                                     |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create variablebox component                                        |
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

class VariableBox extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;
    
    private $db;
    private $datasource;
    private $mapComponentMethod = array(
        'CMB' => 'buildComboBox',
        'TAR' => 'buildTextArea',
        'TXT' => 'buildTextBox'
    );
                                        
    public function __construct($name)
    {
        parent::__construct('dummy',$name);
    }

    public function build()
    {
        /*$sqlvar = $this->getParameter('datasource-sql');
        if (empty($sqlvar)) {
            die('[ERROR] - variable box '.$this->id.' - query builder assente');
        }
        $sql = $this->replacePlaceholder($sqlvar);*/
        
        $res = $this->datasource->get()[0];
        if (!empty($res)){
            list($componentBuilderId,$sqlBuilder) = array_values($res);
        }
        
        if (!array_key_exists($componentBuilderId, $this->mapComponentMethod)){
            $this->buildTextBox();
            return;
        }
        $this->{$this->mapComponentMethod[$componentBuilderId]}($sqlBuilder);
    }
    
    private function buildComboBox($sql)
    {
        $datasource = new \Opensymap\Datasource\DatasourceDbo($this->db);
        $datasource->setQuery($sql);
        
        $combo = new ComboBox($this->id);
        $combo->setDatasource($datasource);
        $this->add($combo)
             ->att('label',$this->label); //Setto la risorsa per popolare la combo e la connessione al DB necessaria ad effettuare le query.
    }
    
    private function buildTextArea()
    {
        $this->add(new TextArea($this->id))
             ->att('style','width: 95%;')
             ->att('rows','20')
             ->setModel($this->getModel());
    }
    
    private function buildTextBox()
    {
        $this->add(new TextBox($this->id))
             ->att('style','width: 95%')
              ->setModel($this->getModel());
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
