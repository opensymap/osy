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
namespace Opensymap\Ocl\View;

use Opensymap\Ocl\View\ViewFactory;
/*
 *  TODO 
 * Caricare layout
 */

class Base extends ViewFactory
{
    public static $pdf;

    protected static function initExtra()
    {
        self::$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        self::set_par('mode','VIEW');
    }
    
    public static function get()
    {
        self::$pdf->AddPage();
        self::$form->buildPdf(self::$pdf);
        self::$pdf->Output('example_048.pdf', 'I');
        exit;
    }
}
