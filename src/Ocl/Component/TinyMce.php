<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/TinyMce.php                                        |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2015, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create TinyMce component                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/12/2014
 * @date-update     09/12/2014
 */

namespace Opensymap\Ocl\Component;

use Opensymap\Ocl\Component\AbstractComponent;

class TinyMce extends AbstractComponent 
{
    public $edit;
    
    public function __construct($name)
    {
        parent::__construct('div',$name.'_main');
        $this->att('class','osy-tinymce');
        $this->add(new TextArea($name));
        $this->addRequire('/vendor/tinymce-4.1.10/tinymce.min.js');
        $this->addRequire('js/component/TinyMce.js');
    }   

    protected function build()
    {
    }
}
