<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/View/Calendar.php                                            |
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
 * @date-update     27/02/2015
 */
 
namespace Opensymap\Ocl\View;

use Opensymap\Osy as env;
use Opensymap\Ocl\View\ViewFactory;
use Opensymap\Ocl\Component\Calendar as CalendarComponent;

class Calendar extends ViewFactory
{
    protected static function initExtra()
    {
        self::$form->att('class',self::get_par('form-type'));
        self::$__par['default_component_parent'] = 0;
        env::$page->add_css('/vendor/jquery/smoothness/jquery-ui-1.9.1.custom.css');
        env::$page->add_css('css/style.css');
        env::$page->add_css('css/CalendarMain.css');
        env::$page->add_script('/vendor/jquery/jquery-1.10.2.min.js');
        env::$page->add_script('/vendor/jquery/jquery-ui-1.10.3.custom.min.js');
        env::$page->add_script('js/form/Form.js');
        //env::$page->add_script(OSY_WEB_ROOT.'/js/osy.view.det.js');
        self::__build__();
    }

    protected static function __build__()
    {
        $cal = new CalendarComponent($_POST['calendar_init_date'],$_REQUEST['calendar_layout']);
        $sql = env::replacevariable(self::get_par('sql-query'));
        $sql = env::parseString($sql);
        $cal->set_datasource($sql , env::$dba);
        $cal->set_dimension(self::get_par('width'),self::get_par('height'));
        $cal->par('form-related',self::get_par('form-related'));
        self::$form->put($cal,'','',100,10);
    }
}
