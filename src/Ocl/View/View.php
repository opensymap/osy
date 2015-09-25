<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/View/Detail.php                                              |
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
use Opensymap\Helper\HelperOsy;
use Opensymap\Ocl\View\ViewFactory;
use Opensymap\Ocl\Component\Button;

class View extends ViewOpensymap
{
    protected function init()
    {
        $this->form->att('class',self::getParam('form-type'));
        $this->param['default_component_parent'] = 0;
        $this->response->addCss('/vendor/font-awesome-4.2.0/css/font-awesome.min.css');
        $this->response->addCss('/vendor/jquery/smoothness/jquery-ui-1.9.1.custom.css');
        $this->response->addCss(OSY_WEB_ROOT.HelperOsy::nvl($this->param['css-instance'],'/css/style.css'));
        $this->response->addJsFile('/vendor/jquery/jquery-1.10.2.min.js');
        $this->response->addJsFile('/vendor/jquery/jquery-ui-1.10.3.custom.min.js');
        $this->response->addJsFile('js/view/Form.js');
        $this->response->addJsFile('js/view/FormMain.js');
    }
    
    /**
     ** @abstract Metodo che inizializza i comandi (button) della Form.
     ** @private
     ** @return void
     **/
    protected function build()
    {
        $inUpdate = !empty($_REQUEST['pkey']);
        //Pulsante chiudi
        if (!empty($this->param['button-close'])) {
            $this->param['command']['close'] = new Button('BtnClose', 'cmd_close');
            $this->param['command']['close']->att(array('style' => 'width: 80px','label' =>'Chiudi'))
                                            ->add('<span class=\'fa fa-close\'></span> ');
            if ($this->param['button-close'] == '-1') {
                $this->param['command']['close']->att('class', 'extra', true);
            }
        }
        
        //Pulsante stampa
        if ($inUpdate && !empty($this->param['button-pdf'])) {
            $this->param['command']['print'] = new Button('BtnPrint', 'cmd_print');
            $this->param['command']['print']->att('data-osy-form', $this->param['button-pdf'])
                                            ->att('class', 'w80')
                                            ->att('label', 'Stampa')->add('<span class=\'fa fa-print\'></span> ');
        }
        
        if ($this->param['mode'] == 'VIEW') {
            if ($_REQUEST['osy']['state'] == 'read-only') {
                $this->param['command']['status'] = new Button('BtnChangeState', 'cmd_change_state');
                $this->param['command']['status']->att('class', 'w80 tbold cmb-change-status')
                                                 ->att('id', 'cmd-change-status')
                                                 ->att('label', 'Modifica')
                                                 ->add('<span class=\'fa fa-pencil\'></span> ');
            }
            return;
        }
        switch ($this->param['subtype']) {
            case 'form-wizard':
                $this->param['command']['save'] = new Button('BtnSave', 'cmd_save');
                $this->param['command']['save']->Att('class', 'w80 cblue tbold');
                $par = !empty($this->param['next-form']) 
                       ? array('label' => 'Avanti','after-exec'=> 'next', 'next' => $this->param['next-form'])
                       : array('label'=>'Termina','after-exec'=>'close');
                $this->param['command']['save']->att($par);
                if (!empty($this->param['previous-form'])) {
                    $this->param['command']['prev'] = new Button('BtnPrev', 'cmd_prev');
                    $this->param['command']['prev']->att('label', 'Indietro')->att('class', 'extra');
                    $this->param['command']['prev']->att('previous', $this->param['previous-form']);
                }
                break;
            default:
                if (!empty($this->param['button-save'])) {
                    $this->param['command']['save'] = new Button('BtnSave', 'cmd_save');
                    $this->param['command']['save']->att('label', 'Salva')
                                                   ->att('class', 'w80 cblue tbold')
                                                   ->att('after-exec', self::getParam('after-exec'))
                                                   ->add('<span class=\'fa fa-save\'></span> ');
                    if ($this->param['button-save'] == '-1') {
                        $this->param['command']['save']->att('class', 'extra', true);
                    }
                }
                break;
        }
        if ($inUpdate && !empty($this->param['button-delete'])) {
            $this->param['command']['delete'] = new Button('BtnDelete', 'cmd_delete');
            $this->param['command']['delete']->att('class', 'w80 cred tbold')
                                             ->att('label', 'Elimina')
                                             ->add('<span class=\'fa fa-trash-o\'></span> ');
            if ($this->param['button-delete'] == '-1') {
                $this->param['command']['delete']->att('class', 'extra', true);
            }
        }
        //Pulsante stampa
        /*if (!empty($_REQUEST['pkey']) && !empty($this->param['button-pdf']))
        {
            $this->param['command']['print'] = new Button('BtnPrint','cmd_print');
            $this->param['command']['print']->att('onclick',"View.print('".$this->param['button-pdf']."')")
                                            ->att('class','w80 tbold')
                                            ->att('label','Stampa')->add('<span class=\'fa fa-print\'></span> ');
        }*/
    }
}
