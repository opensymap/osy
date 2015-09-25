<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/CheckBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create CheckBox component                                           |
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
use Opensymap\Ocl\Component\InputBox;
use Opensymap\Ocl\Component\HiddenBox;

class Checkbox extends AbstractComponent
{
    private $hidden = null;
    private $check = null;
    
    public function __construct($name)
    {
        parent::__construct('span',$name);
        $this->hidden = $this->add(new HiddenBox($name));
        $this->check = $this->add(new InputBox('checkbox','chk_'.$name,'chk_'.$name));
        $this->check->att('class','osy-check')->att('value','1');
        /*
        env::addListener('before-save',$id,function() use ($id) {
            if (empty($_REQUEST[$id])) {
                $_REQUEST[$id] = '00';
            }
        });*/
    }
    
    protected function build()
    {
        if (array_key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id])) {
            $this->check->att('checked','checked');
        }
    }
}