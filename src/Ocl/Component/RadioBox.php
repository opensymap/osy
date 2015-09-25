<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/RadioBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create radiobox component                                           |
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

use Opensymap\Ocl\Component\InputBox as InputBox;

class RadioBox extends InputBox
{
    public function __construct($name)
    {
        parent::__construct('radio',$name);
    }
    
    public function build()
    {
        if (array_key_exists($this->name,$_REQUEST) && $_REQUEST[$this->name] == $this->value) {
            $this->att('checked','checked');
        }
    }
}
