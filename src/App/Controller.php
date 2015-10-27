<?php
namespace Opensymap\App;

use Opensymap\Ocl\Component\ComponentFactory;
use Opensymap\Response\JsonResponse;
use Opensymap\Response\PageError;

/**
 * Opensymap controller class
 *
 * PHP Version 5
 *
 * @category Main
 * @package  Opensymap
 * @author   Pietro Celeste <p.celeste@opensymap.org>
 * @license  GPL http://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     http://docs.opensymap.org/ref/Osy
 */
class Controller
{
    private $accessLevel = 'public';
    private $action = 'indexAction';
    private $dbo = null;
    private $model = null;
    private $dispatcher;
    private $request;
    private $session;
    private $viewId = null;
    private $viewClass = '\\Opensymap\\Ocl\\View\\Desktop';
    private $viewFactory = false;
    
    /**
     * Controller conctructor
     *
     * @param string $viewId contains ViewId to load
     * @param string $action contains action to exec
     *
     * @return void
     */
    public function __construct($dbo, $request, $session)
    {
        $this->dbo = $dbo;
        $this->request = $request;
        $this->session = $session;
        //Load action requested if present in input
        if ($action = $request->get('input.osy.cmd')) {
            $this->action = $action;
        }
        //Load model view request if present in input
        $this->viewId = $this->request->get('input.osy.fid');
        $this->loadModel();
    }

    public function getAccessLevel()
    {
        return $this->accessLevel; 
    } 

    /**
     * Decides what action to take
     *
     * @return void
     */
    private function loadModel()
    {
        $this->model = new Model(
            $this->viewId,
            $this->request,
            $this->dbo
        );
        if (empty($this->viewId)) {
            return;
        }
        switch ($this->viewId) {
            case 'osy.login':
                $this->accessLevel = 'public';
                $this->viewClass = '\\Opensymap\\Ocl\\View\\Login';
                break;
            case 'osy.menu':
                $this->accessLevel = 'private';
                $this->viewClass = '\\Opensymap\\Ocl\\View\\Menu';
                break;
            default:
                $this->accessLevel = $this->model->dictionary->get('form.access');
                $this->viewClass = '\\Opensymap\\Ocl\\View\\'.$this->model->dictionary->get('form.phpClass');
                break;
        }
    }
    
    /**
     * Exec delete record action
     *
     * @return void
     */
    public function delete()
    {
        $this->model->delete();
    }
    
    /**
     * Exec action request ad return response
     *
     * @return mixed
     */
    public function getResponse()
    {
        if (!$this->request->get('instance.id')) {
            return PagerError(404,'Not found');
        }
        //If not public access to form, check if user is authenticated
        if ($this->accessLevel != 'public') {
            if (!($userId = $this->session->isAuthenticated())) {
                return new PageError(401,'Access denied');
            } 
            $this->request->set('input._uid', $userId);
            $this->request->set('instance.userid', $userId);
        }
        return $this->{$this->action}();
    }
    
    public function getModel()
    {
        return $this->model;
    }
    
    /**
     * Init view and return html string of view
     *
     * @return string
     */
    public function indexAction()
    {
        if ($this->model) {
            $this->dispatcher = $this->model->getEventDispatcher('view-context', null);
            $this->dispatcher->setContext($this);
            if ($ajaxCommand = $this->request->get('input.ajax')) {
                if ($response = $this->ajaxAction($ajaxCommand)) {
                    return $response;
                }
            }
        }
        $view = new $this->viewClass($this->dispatcher, $this->model);
        return $view->get();
    }
    
    /**
     * Exec action ajax request from the view
     *
     * @return bool
     */
    public function ajaxAction($action)
    {
        //se il dispatch dell'evento "form-ajax + action" da risultato positivo (almeno 1 listener eseguito) rispondo.
        $response = new JsonResponse();
        $this->dispatcher->setResponse($response);
        if ($this->dispatcher->dispatch('form-ajax', $action) > 0) {            
            return $response;
        }
        //se non è stato richiamato un trigger allora controllo se è stato richiamato l'aggiornamento di un componente      
        foreach ($this->model->field as $fieldId => $fieldProp) {
            if ($fieldProp['name'] == $action) {
                ComponentFactory::init('ajax', $this->model);
                ComponentFactory::create($fieldProp)->ajaxResponse($this, $response);              
                return $response;
            }
        }
    }
    
    /**
     * Exec next action (save)
     *
     * @return void
     */
    public function next()
    {
        return $this->model->save();
    }
    
    /**
     * Exec prev action (save)
     *
     * @return void
     */
    public function prev()
    {
        return $this->model->save();
    }

    /**
     * Exec printpdf action
     *
     * @return void
     */
    public function printpdf()
    {
        //$this->model = new Model($this->viewId, $this->dbo);
        $view = new \Opensymap\Ocl\View\Pdf();
        $view->run($this->model);
        return $view->get();
    }
    
    /**
     * Exec save action
     *
     * @return void
     */
    public function save()
    {
        return $this->model->save();
    }

    /**
     * Exec download action and print blob
     *
     * @return void
     */
    public function download()
    {
        \Opensymap\Ocl\View\Download::init($this->model);
        return  \Opensymap\Ocl\View\Download::getBlob();
    }   
}
