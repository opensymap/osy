<?
/*
 +-----------------------------------------------------------------------+
 | lib/l.chk.acc.php                                                     |
 |                                                                       |
 | This file is part of the Gestional Framework                          |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description :                                                         |
 |-----------------------------------------------------------------------|
 | Creation date : 2003-07-20                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/

// BEGIN
require_once('l.env.php');
env::set_page_header('Pragma',' public');
env::set_page_header('Cache-Control','max-age=86400');
env::set_page_header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
env::set_page_header('Content-type',CONTENT_TYPE);
env::init();
// END
?>
