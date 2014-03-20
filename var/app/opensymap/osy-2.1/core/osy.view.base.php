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
require_once("../lib/c.form.php");

echo '<div id="microtime">'.(microtime(true) - $_GLOBALS['timestart']).' sec. - '.number_format(memory_get_usage(),0,',','.').' byte - dm2</div>';
ob_end_flush();
?>
