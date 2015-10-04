<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/TextSearch.php                                     |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  TextSearch component                                        |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   28/08/2005
 * @date-update     28/08/2005
 */
 
namespace Opensymap\Ocl\Component;

use Opensymap\Osy as env;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Helper\HelperOsy;
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox;



class TextSearch extends AbstractComponent implements DboAdapterInterface
{
    private $textBox = null;
    private $spanSrc = null;
    private $db;
    private $datasource;
    
    public function __construct($name)
    {
        parent::__construct('div');
        $this->class = 'osy-textsearch';
        $this->id = $name;
        $this->add(new HiddenBox($name));
        $this->textBox = $this->add(tag::create('input'))
                              ->att('type','text')
                              ->att('name',$name.'_lbl')
                              ->att('readonly','readonly');
        $this->spanSrc = $this->add(tag::create('span'))->att('class','fa fa-search');
    }
    
    public function build()
    {
        $str_par = array();
        foreach (array('form-related-search','form-related') as $par) {
            if (!$form = $this->get_par($par)) {
                continue;
            }
            if ($par=='form-related') {
                if (!empty($_REQUEST[$this->id])) {
                    $str_par[] = 'pkey[id]='.$_REQUEST[$this->id];
                    $this->textBox->att('onclick',"oform.command.open_window64(this,'form-related'); return false;")
                                  ->att('variables-child-post',implode(',',$str_par));
                }
            } else {
                    $this->spanSrc->att('onclick',"oform.command.open_window64(this,'form-related-search'); return false;")
                                  ->att('variables-child-post',implode(',',$str_par));
            }
        }
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
