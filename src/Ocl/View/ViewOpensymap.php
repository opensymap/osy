<?php
namespace Opensymap\Ocl\View;


use Opensymap\App\Model;
use Opensymap\Lib\Tag;
use Opensymap\Event\Dispatcher as EventDispatcher;
use Opensymap\Helper\HelperOsy;
use Opensymap\Ocl\Component\Form;
use Opensymap\Ocl\Component\HiddenBox;
use Opensymap\Ocl\Component\ComponentFactory;
use Opensymap\Request\Request;
use Opensymap\Response\PageHtmlResponse;

class ViewOpensymap extends AbstractView
{
    protected $fields = array();
    protected $param = array();
    public $form;
    
    public function __construct($dispatcher, Model $model)
    {
        parent::__construct($dispatcher, $model);
        //Load data from database
        $this->model->select();
        //Init dispatcher
        $this->dispatcher = $dispatcher;
        $this->dispatcher->setContext($this);
        $this->dispatcher->setResponse($this->response);
        //Turn on print of elaboration time on the response
        $this->response->printMicrotime = true;
        //Grab parameters from del model;
        $this->param = $this->model->form;
        $this->param['command']            = array();
        $this->param['field-value-db-fix'] = array();
        if (!array_key_exists('state', $_REQUEST['osy']) && array_key_exists('state-initial',  $this->param)) {
            $_REQUEST['osy']['state'] =  $this->param['state-initial'];
        }
        if ($_REQUEST['osy']['state'] == 'read-only') {
            $this->param['mode'] = 'VIEW';
        }
        if (!array_key_exists('after-exec', $this->param)) {
            $this->param['after-exec'] = 'close';
        }
        $this->response->setTitle($this->param['label']);
        $this->form = $this->response->getBody()
                                     ->att('class', 'osy-body')
                                     ->add(new Form('osy-form'))
                                     ->att('method', 'post');
        //End init view
        //Dispatch form-init event
        $this->dispatcher->dispatch('form-init');
        //Dispatch form-before-load-component event
        $this->dispatcher->dispatch('form-before-load-component');
        //Initialize component factory
        ComponentFactory::init($this->getParam('mode'), $this->model);
        //Init derived class
        $this->init();
    }
    
    /**
     ** @abstract Metodo che costruisce la view
     ** @private
     ** @return void
     **/
    
    private function _build()
    {
        //Append Fields on Form
        $this->buildForm();
        //Build Js function
        $this->buildJsScript();
        //Recall build of child view;
        $this->build();
        //Dispatch form-build event
        $this->dispatcher->dispatch('form-build');
        //Build footer if there is command
        if (is_array($this->param['command'])) {
            $foot = $this->form->add(new Tag('div'))->att('class', 'osy-form-footer');
            foreach ($this->param['command'] as $cmd) {
                $foot->add($cmd, 'first');
            }
        }
        //Se ci sono script da caricare sulla pagina li aggiungo
        //E' necessario posizionare html-script in questo metodo
        //al fine di permettere prima il caricamento delle librerie
        if (!empty($this->param['html-script'])) {
            $this->response->addJsCode(str_replace(PHP_EOL, "\n      ", $this->param['html-script']));
        }
        if (!empty($_REQUEST['osy']['prev']) && $_REQUEST['osy']['prev'] != self::getParam('rel_frm_ins_id')) {
            $this->form->add(new HiddenBox('osy-form-prev'), 'first')
                       ->att('class', 'osyRequired');
        } else {
            unset($_REQUEST['osy-form-prev']);
        }
        
        foreach (array('par','pkey','osy') as $cat) {
            if (empty($_REQUEST[$cat]) || !is_array($_REQUEST[$cat])) {
                continue;
            }
            foreach ($_REQUEST[$cat] as $par => $val) {
                $this->form->add(new HiddenBox($cat.'['.$par.']'), 'first')
                           ->att('value',$val)
                           ->att('class', 'osyRequired');
            }
        }
    }
    
    protected function buildJsScript()
    {
        //Controllo se ci sono funzioni javascript da scrivere sulla pagina.
        if ($jsFunctions = ComponentFactory::getJsFunction()) {
            $fnc = 'function osyview_init(){'.PHP_EOL;
            foreach ($jsFunctions as $name => $code) {
                //if (!array_key_exists($name,$this->model->field)) continue;
                if (get_class($this->fields[$name]['object']) == 'check_box') {
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
            $this->response->addJsCode($fnc);
        }
    }
    
    /**
     ** @abstract Metodo che si occupa di caricare i parametri dei diversi campi prelevati dal db
     **           all'interno dell'oggetto form al fine di instanziarli e visualizzarli.
     ** @private
     ** @return void
     **/
    private function buildForm()
    {
        foreach ($this->model->field as $id => $f) {
            /*Istanzio il componente attraverso la ComponentFactory*/
            $this->model->field[$id]['object'] = ComponentFactory::create($f);
            //If ComponentFactory don't return a valid component obscure visibility
            if (!is_object($this->model->field[$id]['object'])) {
                $this->model->field[$id]['visible'] = false;
                continue;
            }
            //Define visible the component (model will work on the visible component)
            $this->model->field[$id]['visible'] = true;
            if (array_key_exists('ajax', $_REQUEST) && $_REQUEST['ajax'] == $f['name']) {
                //Da migliorare creando metodo separato di gestione
                $this->form->put( $this->model->field[$id]['object'] , 'dummy', 'dummy', 1, 1, 0);
                return;
            }
            $this->model->field[$id]['object']->appendRequired($this->response);
            //If component is displayed on foot add is to command array
            if (!empty($f['in-command-panel'])) {
                $this->model->field[$id]['object']->att('class', 'extra wmin80');
                $this->param['command'][] = $this->model->field[$id]['object'];
                continue;
            }
            //Add component on the form
            $this->form->put(
                $this->model->field[$id]['object'],
                $f['label'],
                $f['name'],
                HelperOsy::nvl($f['position-row'], -1),
                HelperOsy::nvl($f['position-column'], 0),
                HelperOsy::nvl($f['position-panel-parent'], 0)
            );
        }
    }
    
    public function getParam($key)
    {
        return key_exists($key, $this->param) ? $this->param[$key] : null;
    }
    
    public function setParam($key, $val)
    {
        $this->param[$key] = $val;
    }
    
    public function __toString()
    {
        return $this->get();
    }
    
    public function get()
    {
        $this->_build();
        $this->dispatcher->dispatch('form-show');        
        $this->form->status = array_key_exists('osy://form/status', $GLOBALS) ? implode(' + ', $GLOBALS['osy://form/status']) : '';
        return $this->response;
    }
    
    protected function build()
    {
    }
    
    protected function init()
    {
    }
}
