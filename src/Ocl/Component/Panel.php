<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Components/Panel.php                                         |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Panel component                                              |
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

use Opensymap\Lib\Tag as tag;
use Opensymap\Ocl\Component\AbstractComponent;

//Costruttore del pannello html
class Panel extends AbstractComponent
{
    private $__cell = array();
    private $__crow = null;
    private $__tag = array('tr','td');

    public function __construct($id,$tag='table')
    {
        parent::__construct($tag,$id);
        $this->par('label-position','outside');
        if ($tag=='div') $this->__tag = array('div','div');
    }

    protected function build()
    {
        ksort($this->__cell);

        foreach($this->__cell as $irow => $row) {
            ksort($row);
            $this->__row();
            foreach($row as $icol => $col) {
                //ksort($col);
                foreach($col as $icnt => $obj) {
                    $colspan=null;
                    if (is_object($obj['obj']) && ($obj['obj']->tag == 'button' || $obj['obj']->get_par('label-hidden') == '1')) {
                        unset($obj['lbl']);
                        if ($this->get_par('label-position') == 'outside') {
                            $colspan=2;
                        }
                    } elseif (!empty($obj['lbl'])) {
                        $label_text = $obj['lbl'];
                        if (is_object($obj['obj'])) {
                            if ($prefix = $obj['obj']->get_par('label-prefix')) {
                               $label_text = '<span class="label-prefix">'.$prefix.'</span>'.$label_text;
                            }
                            if ($postfix = $obj['obj']->get_par('label-postfix')) {
                               $label_text .= '<span class="label-postfix">'.$postfix.'</span>';
                            }
                        }
                       //$obj['lbl'] = '<label class="'.(get_class($obj['obj']) == 'panel' ? 'osy-form-panel-label' : 'osy-component-label').'">'.$prefix.$obj['lbl'].'</label>';
                        $obj['lbl'] = new tag('label');
                        $obj['lbl']->att('class',($obj['obj'] instanceof panel ? 'osy-form-panel-label' : 'osy-component-label'))
                                   ->att('class',(is_object($obj['obj']) ? $obj['obj']->get_par('label-class') : ''),true)
                                   ->add(trim($label_text));
                        if (is_object($obj['obj'])) {
                            $obj['lbl']->att('for',$obj['obj']->id);
                        }
                    }
                    switch($this->__par['label-position']) {
                        case 'outside':
                            if (key_exists('lbl',$obj)) {
                                $cl = $this->__cell($obj['lbl']);
                                if (is_object($obj['obj'])) {
                                    if ($cls = $obj['obj']->get_par('label-cell-class')) {
                                        $cl->att('class',$cls,true);
                                    }
                                    if ($sty = $obj['obj']->get_par('label-cell-style')) {
                                        $cl->att('style',$sty);
                                    }
                                }
                            }
                            $this->__cell($obj['obj'],$colspan);
                            break;
                        case 'outside-rear':
                            $this->__cell($obj['obj'],$colspan);
                            if (array_key_exists('lbl',$obj)) {
                                $this->__cell($obj['lbl']);
                            }
                            break;
                        default :
                            $this->__cell($obj,$colspan);
                            break;
                    }
                }
            }
        }
    }

    private function __row()
    {
        return $this->__crow = $this->add(tag::create($this->__tag[0]));
    }

    private function __cell($content=null,$colspan=null)
    {
        if (is_null($content)) return;
        $cel = $this->__crow->add(tag::create($this->__tag[1]));
        if (!empty($colspan)) $cel->att('colspan',$colspan);
        $cel->add2($content);
        return $cel;
    }

    public function put($lbl,$obj,$row=0,$col=0)
    {
        $this->__cell[$row][$col][] = array('lbl'=>$lbl,'obj'=>$obj);
    }

    public function buildPdf($pdf,$xwidth=190,$xstart=10)
    {
        //Scorro le righe;
        ksort($this->__cell);
        foreach ($this->__cell as $k => $row){
            if (!is_array($row)) continue;
            ksort($row);
            $ncel = count($row);
            $wcel = $xwidth / $ncel;
            $cury = $pdf->GetY();
            $h = 0;
            foreach($row as $i => $cels) {
                foreach($cels as $j => $obj) {
                    $pdf->SetXY($wcel * $h+$xstart,$cury);
                    $pdf->setFont('helvetica','B',10);
                    if (is_object($obj['obj']) && method_exists($obj['obj'],'build_pdf')) {
                        $pdf->SetFillColor(230,230,230);
                        $pdf->Cell($wcel,7,strtoupper($obj['lbl']),($ncel == ($h+1) ? 'LTR' : 'LT'),0,'C',1);
                    } else {
                        $pdf->Cell($wcel,7,$obj['lbl'],($ncel == ($h+1) ? 'LTR' : 'LT'),0,'L',0);
                    }
                    $pdf->SetFillColor(0);
                    $pdf->setFont('helvetica','',12);
                    $pdf->Ln();
                    if (is_object($obj['obj']) && method_exists($obj['obj'],'build_pdf')) {
                        $obj['obj']->build_pdf($pdf,$wcel,$wcel*$h+$xstart);
                        continue;
                    }
                    $val = '';
                    if (is_object($obj['obj']) && method_exists($obj['obj'],'get_value')) {
                        $val = $obj['obj']->get_value();
                        $wdt = $pdf->GetStringWidth($val);
                        if ($wcel < $wdt) {
                            $min = floor(($wcel / $wdt) * strlen($val));
                            $val = substr($val,0,$min);
                        }
                    }
                    $pdf->SetX($wcel * $h + $xstart);
                    $pdf->Cell($wcel,7,$val,($ncel == ($h+1) ? 'LRB' : 'LB'));
                    $pdf->Ln();
                }
                $h++;
            }
        }
    }
}