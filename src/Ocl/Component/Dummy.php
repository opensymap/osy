<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Dummy.php                                          |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create dummy component                                              |
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

use Opensymap\Osy as env;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Helper\HelperOsy;

class Dummy extends AbstractComponent
{
    public function __construct($nam,$id=null)
    {
        parent::__construct('div',$nam);
        $this->att('class','osy-dummy');
    }
    
    protected function build()
    {
         if (!($txt = get_global($this->id,$_REQUEST))) {
            $txt = $this->get_par('text');
            $txt = HelperOsy::replaceVariable($txt);
         }
         $this->add($txt);
    }
}