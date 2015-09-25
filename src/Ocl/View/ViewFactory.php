<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/View.php                                           |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Base component for generate standard osy form                       |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   31/10/2014
 * @date-update     31/10/2014
 */
namespace Opensymap\Ocl\View;

use Opensymap\Osy as env;
use Opensymap\Lib\Tag;
use Opensymap\Event\Dispatcher as EventDispatcher;
use Opensymap\Helper\HelperOsy;
use Opensymap\Ocl\Component\Form;
use Opensymap\Ocl\Component\HiddenBox;
use Opensymap\Ocl\Component\ComponentFactory;
use Opensymap\Response\PageHtmlResponse;

/**
 * View e' la classe base che permette di costruire "FORM HTML" caricandone i metadati descrittivi da
 * alcune tabelle base memorizzate su un DB. Tali tabelle rappresentano il cuore del sistema.
 * La tabella "osy_obj" interfaccia la struttura fisica del db alle form HTML necessarie a
 * popolare le tabelle medesime.
 *
 * @author PIETRO CELESTE
 * @version 1.0
 */
class ViewFactory
{
    protected static $fields = array();
    protected static $model;
    protected static $param = array();
    protected static $page;
    public static $form = null;
    private static $par_man = array();
    private static $dispatcher;
    
    public static function run($dispatcher, $model)
    {
        //Init new page html response;
        self::$page = new PageHtmlResponse();
        self::$page->printMicrotime = true;
        self::$model = $model;
        self::$model->select();
        self::$dispatcher = $dispatcher;
        self::$dispatcher->setContext(self);
        self::$dispatcher->setResponse(self::$page);
        //Init view;
        self::$param = $model->form;
        self::$param['command']            = array();
        self::$param['field-value-db-fix'] = array();
        if (!array_key_exists('state', $_REQUEST['osy']) && array_key_exists('state-initial', self::$param)) {
            $_REQUEST['osy']['state'] = self::$param['state-initial'];
        }
        if ($_REQUEST['osy']['state'] == 'read-only') {
            self::$param['mode'] = 'VIEW';
        }
        if (!array_key_exists('after-exec', self::$param)) {
            self::$param['after-exec'] = 'close';
        }
        self::$page->setTitle(self::$param['label']);
        self::$form = self::$page->getBody()
                                 ->att('class', 'osy-body')
                                 ->add(new Form('osy-form'))
                                 ->att('method', 'post');
        //End init view
        //Dispatch form-init event
        self::$dispatcher->dispatch('form-init');
        //Dispatch form-before-load-component event
        self::$dispatcher->dispatch('form-before-load-component');
        //Initialize component factory
        ComponentFactory::init(self::getParam('mode'), self::$model);
        //Init extra view;
        static::initExtra();
        //Exec last init;
        self::initEnd();
        //Append Fields on Form
        self::buildForm();
        //Build Js function
        self::buildJsScript();
    }
    
    /**
     ** @abstract Metodo che verrà sovrascritto dalle childform contiene le istruzioni extra da eseguire
     ** @private
     ** @return void
     **/
    protected static function initExtra()
    {
    }
    
    private static function initEnd()
    {
        //Se ci sono script da caricare sulla pagina li aggiungo
        //E' necessario posizionare html-script in questo metodo
        //al fine di permettere prima il caricamento delle librerie
        if (!empty(self::$param['html-script'])) {
            self::$page->addJsCode(str_replace(PHP_EOL, "\n      ", self::$param['html-script']));
        }
        if (!empty($_REQUEST['osy']['prev']) && $_REQUEST['osy']['prev'] != self::getParam('rel_frm_ins_id')) {
            self::$form->add(new HiddenBox('osy-form-prev'), 'first')
                       ->att('class', 'osyRequired');
        } else {
            unset($_REQUEST['osy-form-prev']);
        }
        
        foreach (array('par','pkey','osy') as $cat) {
            if (empty($_REQUEST[$cat]) || !is_array($_REQUEST[$cat])) {
                continue;
            }
            foreach ($_REQUEST[$cat] as $par => $val) {
                self::$form->add(new HiddenBox($cat.'['.$par.']'), 'first')
                           ->att('value',$val)
                           ->att('class', 'osyRequired');
            }
        }
    }

    /**
     ** @abstract Metodo che assembla la form
     ** @private
     ** @return void
     **/
    protected static function build()
    {
        self::$dispatcher->dispatch('form-build');
        //Build footer if there is command
        if (is_array(self::$param['command'])) {
            $foot = self::$form->add(new Tag('div'))->att('class', 'osy-form-footer');
            foreach (self::$param['command'] as $cmd) {
                $foot->add($cmd, 'first');
            }
        }
        self::$dispatcher->dispatch('form-show');
    }
    
    protected function buildJsScript()
    {
        //Controllo se ci sono funzioni javascript da scrivere sulla pagina.
        if ($jsFunctions = ComponentFactory::getJsFunction()) {
            $fnc = 'function osyview_init(){'.PHP_EOL;
            foreach ($jsFunctions as $name => $code) {
                //if (!array_key_exists($name,self::$model->field)) continue;
                if (get_class(self::$fields[$name]['object']) == 'check_box') {
                    $name = 'chk_'.$name;
                }
                $fnc .= PHP_EOL."   oform.command.eventpush(document.getElementById('".$name."'),'".$code[0]."',";
                $fnc .= "   function (e){\n";
                $fnc .= $code[1];
                $fnc .= PHP_EOL."   });".PHP_EOL;
            }
            $fnc .= '}'.PHP_EOL;
            $fnc .= "if (window.addEventListener) {\n";
            $fnc .= "   window.addEventListener('load', osyview_init);\n";
            $fnc .= "} else {\n";
            $fnc .= "   window.attachEvent('onload', osyview_init);\n";
            $fnc .= "}\n";
            self::$page->addJsCode($fnc);
        }
    }
    
    /**
     ** @abstract Metodo che si occupa di caricare i parametri dei diversi campi prelevati dal db
     **           all'interno dell'oggetto form al fine di instanziarli e visualizzarli.
     ** @private
     ** @return void
     **/
    private static function buildForm()
    {
        foreach (self::$model->field as $id => $f) {
            /*Istanzio il componente attraverso la ComponentFactory*/
            self::$model->field[$id]['object'] = ComponentFactory::create($f);
            //If ComponentFactory don't return a valid component obscure visibility
            if (!is_object(self::$model->field[$id]['object'])) {
                self::$model->field[$id]['visible'] = false;
                continue;
            }
            //Define visible the component (model will work on the visible component)
            self::$model->field[$id]['visible'] = true;
            if (array_key_exists('ajax', $_REQUEST) && $_REQUEST['ajax'] == $f['name']) {
                //Da migliorare creando metodo separato di gestione
                self::$form->put( self::$model->field[$id]['object'] , 'dummy', 'dummy', 1, 1, 0);
                return;
            }
            self::$model->field[$id]['object']->appendRequired(self::$page);
            //If component is displayed on foot add is to command array
            if (!empty($f['in-command-panel'])) {
                self::$model->field[$id]['object']->att('class', 'extra wmin80');
                self::$param['command'][] = self::$model->field[$id]['object'];
                continue;
            }
            //Add component on the form
            self::$form->put(
                self::$model->field[$id]['object'],
                $f['label'],
                $f['name'],
                HelperOsy::nvl($f['position-row'], -1),
                HelperOsy::nvl($f['position-column'], 0),
                HelperOsy::nvl($f['position-panel-parent'], 0)
            );
        }
    }

    public static function get()
    {
        static::build();
        self::$form->status = array_key_exists('osy://form/status', $GLOBALS) ?
                              implode(' + ', $GLOBALS['osy://form/status']) :
                              '';
        return self::$page;
    }
    
    public static function getParam($key)
    {
        return key_exists($key, self::$param) ? self::$param[$key] : null;
    }
    
    public static function setParam($key, $val)
    {
        self::$param[$key] = $val;
    }
}
