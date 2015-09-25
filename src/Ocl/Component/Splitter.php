<?php
/*
 +-----------------------------------------------------------------------+
 | core/Components/Splitter.php                                          |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Splitter component                                           |
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
use Opensymap\Ocl\Component\AbstractComponent;

class Splitter extends AbstractComponent 
{
    public function __construct($nam, $id=null)
    {
        parent::__construct('div',$nam);
        $this->att('class','osy-splitter');
        $this->add('----');
        env::$page->add_css(OSY_WEB_ROOT.'/css/Splitter.css');
        env::$page->add_script(OSY_WEB_ROOT.'/js/component/Splitter.js');
        
        $this->addRequire('css/Splitter.css');
        $this->addRequire('js/component/Splitter.js');
    }
    
    protected function build()
    {
    }
}