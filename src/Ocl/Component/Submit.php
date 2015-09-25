<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Submit.php                                         |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Submit component                                             |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/04/2005
 * @date-update     09/04/2005
 */

namespace Opensymap\Ocl\Component;

use Opensymap\Ocl\Component\Button;

class Submit extends Button
{
    public function __construct($nam, $id=null)
    {
        parent::__construct($nam,nvl($id,$nam),'submit');
    }
     
    protected function build()
    {        
    }
}