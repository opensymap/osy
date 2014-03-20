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
$_GLOBALS['timestart'] = microtime(true);
ob_start("ob_gzhandler");
require_once("../lib/l.chk.acc.php");
require_once("../lib/c.view.php");

class osy_view_detail extends osy_view
{
   protected static function __init_extra__()
   {
        self::$form->att('class',self::get_par('form-type'));
        self::$__par['default_component_parent'] = 0;
        self::$page->add_css(OSY_WEB_ROOT.'/css/smoothness/jquery-ui-1.9.1.custom.css');
        self::$page->add_css(OSY_WEB_ROOT.'/css/style.css');
        self::$page->add_script('/lib/jquery/jquery-1.10.2.min.js');
        self::$page->add_script('/lib/jquery/jquery-ui-1.10.3.custom.min.js');
        self::$page->add_script(OSY_WEB_ROOT.'/js/osy.view.det.js');
        self::$page->add_script(OSY_WEB_ROOT.'/js/osy.calendar.js');
   }
}
osy_view_detail::init('main1');
echo osy_view_detail::get();
echo '<div id="microtime">'.(microtime(true) - $_GLOBALS['timestart']).' sec. - '.number_format(memory_get_usage(),0,',','.').' byte</div>';
ob_end_flush();
?>
