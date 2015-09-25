<?php
/*
 +-----------------------------------------------------------------------+
 | core/opage.php                                                  |
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
 * @date-creation   02/05/2015
 * @date-update     02/05/2015
 */

/*
 *  TODO 
 * Caricare layout
 */

namespace Opensymap\Ocl\View;

require_once(OSY_PATH_LIB_EXT."/tcpdf_6/tcpdf_import.php");

class Pdf extends View
{

    public static $pdf;

    protected static function initExtra()
    {
        self::$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        self::setPar('mode','VIEW');
    }
    
    public static function get()
    {
        self::$pdf->AddPage();
        self::$form->buildPdf(self::$pdf);
        self::$pdf->Output('example_048.pdf', 'I');
        exit;
    }
}