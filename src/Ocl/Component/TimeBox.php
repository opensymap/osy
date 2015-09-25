<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/TimeBox.php                                        |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2015, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create TimeBox component                                            |
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

use Opensymap\Ocl\Component\InputBox as InputBox;

class TimeBox extends InputBox
{
    public function __construct($name)
    {
        //env::$page->add_script('/lib/jquery/jquery.timepicker.js');
        parent::__construct('time',$name,$name);
        $this->att('autocomplete','off')
             ->att('class','osy-time');
    }

    protected function build()
    {
    }
}
