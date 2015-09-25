<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Component/TextArea.php                                           |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  textarea component                                          |
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

use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;

class TextArea extends AbstractComponent
{
    public function __construct($name)
    {
        parent::__construct('textarea',$name);
        $this->name = $name;
    }
    
    protected function build()
    {
        $this->add($_REQUEST[$this->id]);
    }
}
