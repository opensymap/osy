<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Iframe.php                                         |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create iframe component                                             |
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

//Field iframe
class Iframe extends AbstractComponent
{
    public function __construct($name)
    {
        parent::__construct('iframe',$name);
        $this->att('name',$name);
        $this->att("style",'border: 1px solid gray; width: 99%;')->add('&nbsp;');
    }

    protected function build()
    {
        $src = $this->getParameter('src');
        if (!key_exists($this->id,$_REQUEST) && !empty($src)) {
            $_REQUEST[$this->id] = $src;
        }
        if(key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id])) {
            $this->att('src',$_REQUEST[$this->id]);
        }
    }
}
