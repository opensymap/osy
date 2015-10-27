<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/FileBox.php                                        |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  filebox component                                           |
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
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;

class FileBox extends AbstractComponent
{
    public function __construct($name, $personalize = true)
    {
        parent::__construct('dummy',$name);
        $this->add(new Tag('input'))
             ->att('id',$name)
             ->att('name',$name)
             ->att('type','file')
             ->att('class','osy-filebox hidden');
        $this->addRequire('Ocl/Component/FileBox/controller.js');
    }
    
    protected function build()
    {
        $form = $this->closest('form');
        if (is_object($form)) {
            $form->att('enctype','multipart/form-data');
        }
        $label = $this->add(new Tag('label'))
                      ->att('class','osy-filebox-virtual')
                      ->att('for',$this->id);
        $label->add('<span class="osy-filebox-filename"></span>');
        $label->add('<span class="fa fa-cloud-upload osy-filebox-button"></span>');
        if (!empty($_REQUEST[$this->id.'_message'])) {
            $this->add(Tag::create('div'))->att('class','osy-filebox-message')->add($_REQUEST[$this->id.'_message']);
        }
    }
}
