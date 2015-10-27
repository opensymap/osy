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
        $this->addRequire('Ocl/Component/Splitter/style.css');
        $this->addRequire('Ocl/Component/Splitter/controller.js');
    }
    
    protected function build()
    {
    }
}