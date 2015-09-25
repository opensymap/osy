<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/PasswordBox.php                                    |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create passwordbox component                                        |
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

class PasswordBox extends InputBox
{
    public function __construct($nam, $id=null)
    {
        parent::__construct('password',$nam,nvl($id,$nam));
        $this->par('get-request-value',$nam);
        $this->att('autocomplete','off');
    }
    
    protected function build()
    {
    }
}
