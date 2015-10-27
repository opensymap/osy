<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/InputBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create inputbox component                                           |
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

use Opensymap\Ocl\Component\AbstractComponent;

class InputBox extends AbstractComponent
{
    public function __construct($typ,$nam,$id=null)
    {
        parent::__construct('input',$id);
        $this->att('type',$typ);
        $this->att('name',$nam);       
    }

    protected function build()
    {
        $val = get_global($this->name,$_REQUEST);
        if (!empty($val)){
            $this->att('value',$val);
        } 
    }
}
