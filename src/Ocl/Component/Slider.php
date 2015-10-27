<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Components/Slider.php                                        |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Slider component                                             |
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
use Opensymap\Lib\Tag as tag;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox as HiddenBox;

class Slider extends AbstractComponent
{
    private $bar;

    public function __construct($id)
    {
        parent::__construct('div',$id);
        $this->att('class','osy-slider');
        $this->addRequire('Ocl/Component/Slider/style.css');
        $this->addRequire('Ocl/Component/Slider/controller.js');
    }

    protected function build()
    {
        if ($range = $this->getParameter('slider-range')) {
            $this->att('data-range',$range);
            $this->add(new HiddenBox($this->id.'_min'));
            $this->add(new HiddenBox($this->id.'_max'));
        } else {
            $this->add(new HiddenBox($this->id));
        }
        $min = $this->getParameter('min');
        $div_min_max = $this->add(tag::create('div'));
        $div_min_max->att('class','osy-slider-min-max');
        $div_min_max->add('&nbsp');
        if ($min == '0' or !empty($min)) {
            if ($min[0] == '$'){ eval('$min = '.$min.';'); }
            $div_min_max->add('<span class="lbl-min">'.$min.'</span>');
            $this->att('data-min',$min);
        }
        $bar = $this->add(tag::create('div'))->att('class','osy-slider-bar');
        if ($max = $this->getParameter('max')){
            if ($max[0] == '$'){ eval('$max = '.$max.';'); }
            $div_min_max->add('<span class="lbl-max">'.$max.'</span>');
            $this->att('data-max',$max);
        }
        if (!empty($_REQUEST[$this->id.'_min']) &&
            !empty($_REQUEST[$this->id.'_max'])){
            $this->att('data-values',$_REQUEST[$this->id.'_min'].','.$_REQUEST[$this->id.'_max']);
        }
        $this->add('<script>
        oslider.onevent("onstop","'.$this->id.'",function(event,ui){
        '.$this->getParameter('onstop').'
        });
        </script>');
        //$this->add('<span class="osy-slider-result"></span>');
    }
}
