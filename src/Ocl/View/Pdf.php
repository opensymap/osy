<?php
/*
 +-----------------------------------------------------------------------+
 | osy/2.osy.frm.dm.php                                                  |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create page form for data manipulation                              |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   27/08/2013
 * @date-update     27/08/2013
 */
namespace Opensymap\Ocl\View;

use Opensymap\Osy as Osy;

class Pdf 
{
    public $pdf;
    public $model;
    
    public function run($model)
    {
        $this->model = $model;
    }
    
    public function get() 
    {
        $model = $this->model;
        eval($this->model->form['code-php']);
    }
}
