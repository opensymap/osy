<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Components/PanelMobile.php                                   |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create PanelMobile component                                        |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/04/2015
 * @date-update     09/04/2015
 */
 
namespace Opensymap\Ocl\Component; 

use Opensymap\Osy as env;
use Opensymap\Lib\Tag as Tag; 
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\Panel as Panel;

class PanelMobile extends AbstractComponent
{
    protected $panel = null;
    protected $title = null;
    
    public function __construct($id) 
    {
        parent::__construct('div',$id);
        $this->panel = $this->add(new panel($id.'_body'));
        $this->panel->att('class','osy-panel-1')
                    ->par('label-position','inside');
        $this->att('class','osy-panel-mobile');
        //Load css
        $this->addRequire('Ocl/Component/PanelMobile/style.css');
        //Add javascript controller
        $this->addRequire('Ocl/Component/PanelMobile/controller.js');
    }
    
    protected function build() 
    {       
        if ($lp = $this->getParameter('label-position')) {
            $this->panel->par('label-position',$lp);
        }
        if (!$this->getParameter('disable-head')) {
            $this->title = $this->add(tag::create('div'))
                                ->att('class','osy-panel-mobile-title');
            $this->title->add(tag::create('span'))
                        ->att('class','osy-win-ico-set fright')
                        ->add('&nbsp;');           
            $this->title->add($this->getParameter('label'));
        }   
    }
    
    public function put($lbl,$obj,$row=0,$col=0) 
    {
        $this->panel->put($lbl,$obj,$row,$col);
    }
}
