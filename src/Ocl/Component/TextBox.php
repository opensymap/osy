<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/TextBox.php                                        |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  textbox component                                           |
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

use Opensymap\Ocl\Component\InputBox as InputBox;

class TextBox extends InputBox
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('text',$nam,nvl($id,$nam));
        $this->par('get-request-value',$nam);
    }
    
    protected function __build_extra__()
    {
        parent::__build_extra__();
        if ($this->get_par('field-control') == 'is_number'){
            $this->att('type','number')
                 ->att('class','right osy-number',true);
        }
    }
}
