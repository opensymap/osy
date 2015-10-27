<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/IframeTab.php                                      |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create iframetab component                                          |
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

use Opensymap\Osy;
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;

class IframeTab extends AbstractComponent
{
    private $iframe;
    
    public function __construct($name)
    {
       parent::__construct('div',$name);
       $this->att('class','osy-iframe-tab tabs');
       $this->addRequire('Ocl/Component/IFrame/controller.js');
       $this->addRequire('Ocl/Component/IFrameTab/controller.js');
    }
    
    protected function build()
    {
        $this->add(tag::create('ul'));
        //$this->iframe = $this->add(tag::create('iframe'));
        //$this->iframe->att('name',$this->id)->att("style",'width: 100%;');
        $src = $this->getParameter('src');
        if (!array_key_exists($this->id,$_REQUEST) && !empty($src)) {
            $_REQUEST[$this->id] = $src;
        }
        if(array_key_exists($this->id,$_REQUEST) && !empty($_REQUEST[$this->id])) {
            $this->att('src',$_REQUEST[$this->id]);
        }
    }
}