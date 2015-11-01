<?php
/*
    +-----------------------------------------------------------------------+
    | core/Model/BaseModel.php                                              |
    |                                                                       |
    | This file is part of the Opensymap                                    |
    | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
    | Licensed under the GNU GPL                                            |
    |                                                                       |
    | PURPOSE:                                                              |
    |   Base Model for astraction db operation                              |
    |                                                                       |
    +-----------------------------------------------------------------------+
    | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
    +-----------------------------------------------------------------------+
    
    $Id:  $
    
/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   24/07/2015
 * @date-update     05/08/2015
 */
namespace Opensymap\App;

use Opensymap\Osy;
use Opensymap\Driver\DboFactory;
use Opensymap\Event\Dispatcher as EventDispatcher;
use Opensymap\Event\Listener as EventListener;
use Opensymap\Helper\HelperModel;
use Opensymap\Lib\Dictionary;
use Opensymap\Lib\Message;
use Opensymap\Response\JsonResponse;

class Model
{
    public $dba;
    public $dbo;
    public $dictionary;
    public $dispatcher;
    public $instanceId;
    public $request;
    public $response;
    
    public function __construct($objectId, &$request, $dbOsy)
    {
        $this->dictionary = new Dictionary(
            array(
                'id'=>null,
                'model' => array(
                    'field'=>array(),
                    'pkeyValue'=>array(),
                    'pkeyField' => array(),
                    'table'=>null
                ),
                'dbvalues'=>array()
            )
        );
        $this->dbo = $dbOsy;
        $this->request = $request;
        $this->instanceId = $this->request->get('instance.id');
        $this->dictionary->set('id', $objectId);
        $this->loadDefinition($objectId);
        $this->dba = DboFactory::connectionViaAppID(
            $this->instanceId,
            $this->dictionary->get('form.owner')
        );
        $this->dispatcher = $this->getEventDispatcher('data-manager', $this->response);
    }
    
    public function &__get($key)
    {
        if (!$this->dictionary->keyExists($key)) {
            $this->dictionary->set($key, array());
        }
        $elm =& $this->dictionary->get($key);
        return $elm;
    }

    private function dataModelInit($action)
    {
        //return false;
        $dataModels = $this->datamodel;
        
        if (empty($dataModels)) {
            return false;
        }
        $this->dba->begin();
        foreach ($dataModels as $dataModelId => $dataModelDef) {
            
            $class = str_replace(
                array('org.opensymap/','/'), 
                array('','\\'),
                rtrim($dataModelDef['subtype'],'/')
            );
            
            $dataModel = new $class(
                $this->dba,
                $dataModelDef,
                $_REQUEST['pkey'],
                $this->response,
                $this->dispatcher
            );
            
            //Add field to datamodel
            foreach ($this->{'model-field'} as $fieldId => $field) {
                if ($field['owner'] != $dataModelDef['id']) {
                    continue;
                }
                $dataModel->map($field['name'], $field);
            }
            
            $dataModel->{$action}();
            //If activeRecord execute a soft delete break delete operation
            if ($action == 'delete' && $dataModel->isSoftDelete()) {
                break;
            }
        }
        $this->dba->commit();
        return true;
    }
    
    public function loadDefinition($objectId)
    {
        if (empty($objectId)){
            return;
        }
        $res = $this->dbo->exec_query(
            'SELECT o.o_id   AS "id",
                    o.o_own  as "owner",
                    o.o_nam  AS "name", 
                    o.o_lbl  AS "label",
                    o.o_typ  AS "type",
                    o.o_sty  AS "subtype",
                    r.p3     AS "phpClass",
                    p.p_id   AS "parameterId",
                    pcat.p2  AS "parameterType",
                    p.p_vl   AS "parameterValue" 
            FROM osy_obj o
            LEFT JOIN osy_res r
                ON (o.o_sty = r.v_id AND r.k_id = \'osy-object-subtype\')
            LEFT JOIN  osy_obj_prp p
                ON (o.o_id = p.o_id)
            LEFT JOIN  osy_res pcat
                ON (p.p_id = pcat.v_id AND pcat.k_id in (\'osy-propertie-form\',\'osy-propertie-field\',\'osy-propertie-trigger\'))
            WHERE o.o_id LIKE ?
            ORDER BY coalesce(o.o_pri,0)',
            array("{$objectId}%"),
            'ASSOC'
        );
        foreach ($res as $k => $rec) {
            $objectId = $rec['id'];
            $objectType = $rec['type'];
            
            //Se il dictionary di tipo = objectType non contiene un subdictionarysitory di nome id lo creo
            $keyPath = $this->dictionary->buildKey($objectType, $objectId);
            
            if (!$this->dictionary->keyExists($keyPath)) {
                $this->dictionary->set($keyPath, array_slice($rec, 0, 7));
            }
            $param = array_slice($rec, 7, 3);
            $paramId  = $param['parameterId'];
            $paramTyp = $param['parameterType'];
            $paramVal = $param['parameterValue'];
            
            $keyPath = $this->dictionary->buildKey($keyPath, trim($paramId));
            $this->dictionary->set($keyPath,  $paramVal);
            //Organize parameter for tipology.
            if (!empty($paramTyp)) {
                $keyPath = $this->dictionary->buildKey($objectType, $objectId, $paramTyp, trim($paramId));
                $this->dictionary->set($keyPath, $this->dictionary->get($objectType.'.'.$objectId.'.'.trim($paramId)));
            }
            $i = 0;
            switch ($paramId) {
                case 'db-field-is-pkey':
                    if ($paramVal == '1') {
                        $this->dictionary->set('model.pkeyField', $this->dictionary->get($objectType.'.'.$objectId), true);
                    }
                    break;
                case 'db-field-connected':
                    $fieldName = $rec['name'];
                    if (!empty($_REQUEST['pkey']) && array_key_exists($paramVal, $_REQUEST['pkey'])) {
                        $_REQUEST[$fieldName] = $_POST[$fieldName] = $_REQUEST['pkey'][$paramVal];
                    }
                    if (!empty($paramVal)) {
                        $this->dictionary->set('model.fields.'.$paramVal, $_REQUEST[$rec['name']]);
                    }
                    break;
                case 'foreign-key':
                    $this->grabFkey($rec);
                    break;
            }
        }
        
        if ($this->form) {
            $form = array_pop($this->form);
            $this->dictionary->set('form', $form);
            $this->dictionary->set('model.table', $form['db-table-linked']);
        }
        //var_dump($this->{'model-field'});
        //Adjust pkey field;
        if ($pkeys = $this->dictionary->get('model.pkeyField')) {
            $totalPk = 0;
            foreach ($pkeys as $k => $field) {
                $fieldName = $field['db-field-connected'];
                if (!empty($_REQUEST['pkey']) && array_key_exists($fieldName, $_REQUEST['pkey'])) {
                    $this->dictionary->set('model.pkeyValue.'.$fieldName, $_REQUEST['pkey'][$fieldName]);
                    $totalPk++;
                }
            }
            if (count($this->dictionary->get('model.pkeyField')) != $totalPk) {
                $this->dictionary->set('model.pkeyValue', array());
            }
        }
        
        return $this->dictionary;
    }

    public function getEventDispatcher($context, $response) {
        $dispatcher = new EventDispatcher($this->dba, $response, $context);
        $triggersRaw = $this->dictionary->get('trigger');
        if (!empty($triggersRaw)){
            foreach ($triggersRaw as $triggerId => $trigger) {
                if (!empty($trigger[$context])) {
                    foreach ($trigger[$context] as $event => $enabled) {
                        if ($enabled == 'yes' && !empty($trigger['code'])) {
                            $listener = new EventListener($triggerId, $event, $trigger['owner']);
                            if ($error = $listener->setClosure($trigger['code'])) {
                                $dispatcher->addError($error);
                                continue;
                            } 
                            $dispatcher->addListener($listener);
                        }
                    }
                }
            }
        }
        return $dispatcher;
    }
    
    public function initResponse()
    {
        $this->response = new JsonResponse();
        $this->response->set('content.errors',array());
        $this->response->set('content.command',array());
    }
    
    public function checkField($field)
    {
        if ($field['value'] !== '0' && empty($field['value'])) {
            if (!empty($field['field-required'])) {
                return 'Il campo '.$field['label'].' è obbligatorio.'.print_r($field['value'],true);
            }
        } elseif ($class = $field['field-control']) {
            // se al campo è associato un controllo lo eseguo e rilevo l'eventuale errore
            $controlClass = "\\Opensymap\\Validator\\".$class;
            $control = new $controlClass($field);
            return $control->check();
        }
        return '';
    }
    
    /*
     * Metodo richiamato per salvare i valori sul database
     */
    public function save()
    {
        $this->initResponse();
        $this->dispatcher = $this->getEventDispatcher('data-manager', $this->response);
        $table = $this->dictionary->get('model.table');
        //Se non specifico una tabella di riferimento allora richiamo i trigger di tipo form-exec ed esco.
        if (empty($table)) {
            $this->dispatcher->dispatch('form-exec');
            return $this->response;
        }
        $this->dispatcher->dispatch('before-save');
        //Classe necessaria per gli aggiustamenti
        $helper = new HelperModel();
        $fields = array();
        $errors = array();
        //Scorro i campi del model per effettuare le operazioni preSave ed i controlli di validità
        foreach ($this->dictionary->get('field') as $field) {
            $fieldName = $field['name'];
            $fieldDb = $field['db-field-connected'];
            $preSave = 'preSave'.$field['phpClass'];
            if (is_callable(array($helper, $preSave))) {
                $helper->{$preSave}($field, $this->request);
            }
            //predatamodel
            //if (!empty($fieldDb) && array_key_exists($fieldName, $_REQUEST)) {
            if (array_key_exists($fieldName, $_REQUEST)) {
                if ($_REQUEST[$fieldName] !== '0' && empty($_REQUEST[$fieldName])) {
                    //This command is necessary for set null into db field else pdo driver insert '';
                    $_REQUEST[$fieldName] = null;
                }
                //TODO : Da eliminare dopo che la migrazione ai DataModel sarà completa
                if (!empty($fieldDb)) {
                    $fields[$fieldDb] =& $_REQUEST[$fieldName];
                }
                $field['value'] =& $_REQUEST[$fieldName];
                if ($errorMessage = $this->checkField($field)) {
                    $errors[] =  $this->response->appendMessage(
                        new Message('#'.$field['name'], $errorMessage, 'model.checkField')
                    );
                }
            }
        }
        //Se si sono verificati errori li segnalo all'utente stoppando l'esecuzione
        if (!empty($errors)) {
            $this->response->error(
                'alert', 
                'Si sono verificati i seguenti errori : <ul><li>' . implode('</li><li>',$errors) . '</li><ul>'
            );
            return $this->response;
        }
        
        $this->dispatcher->dispatch('form-exec');
        $pkeyValues = $this->dictionary->get('model.pkeyValue');
        //If datamodel exists
        if ($this->dataModelInit('save')) {
            return $this->response;
        }
        if (empty($pkeyValues)) {
            return $this->insert($table, $fields);
        }
        return $this->update($table, $fields, $pkeyValues);
    }
    
    private function insert($table, $fields)
    {
        $this->dispatcher->dispatch('insert-before');
        if (!$this->response->error()) {
             $newid = $this->dba->insert($table, $fields);
             $this->setPkey($newid);
             $this->dispatcher->dispatch('insert-after');
        }
        $this->dispatcher->dispatch('after-save');
        return $this->response;
    }
    
    private function update($table, $fields, $condition)
    {
        $this->dispatcher->dispatch('update-before');
        if (!$this->response->error()) {
            $this->dba->update($table, $fields, $condition);
            $this->setPkey();
            $this->dispatcher->dispatch('update-after');
        }
        $this->dispatcher->dispatch('after-save');
        return $this->response;
    }
    
    public function delete()
    {
        $this->initResponse();
        $this->dispatcher = $this->getEventDispatcher('data-manager', $this->response);
        if ($this->dataModelInit('delete')) {
            return $this->response;
        }
        $table = $this->dictionary->get('model.table');
        $pkeys = $this->dictionary->get('model.pkeyValue');
        if (empty($table) || empty($pkeys)) {
            return;
        }
        $this->dispatcher->dispatch('delete-before');
        $this->dba->delete($table, $pkeys);
        $this->dispatcher->dispatch('delete-after');
        return $this->response;
    }

    /**
     ** @abstract Metodo che recupera i dati necessari a valorizzare i diversi campi della form.
     **           I dati vengono ripresi dal DB e posizionati nel data set $data.
     **           Subito dopo viene scorso l'elenco dei field per decidere quale valore assegnare
     **           al field (se il dato ripreso dal db o il dato proveniente dall'array globale $_REQUEST)
     ** @private
     ** @return void
     **/
    public function select()
    {
        if ($this->dataModelInit('load')) {
            return;
        }
        $table = $this->dictionary->get('model.table');
        $pkeys = $this->dictionary->get('model.pkeyValue');
        if (empty($table) || empty($pkeys)) {
            return;
        }
        $sql = '';
        $par = array();
        foreach ($pkeys as $k => $value) {
            $sql .= !empty($sql) ? ' AND ' : '';
            $sql .= $k;
            $sql .= ' = ';
            $sql .= $this->dba->par('query-parameter-dummy') == 'pos' ? ':'.count($par) : '?';
            $par[] = $value;
        }
        try {
            $sql = "SELECT * FROM {$table} WHERE ".$sql;
            $data = $this->dba->exec_unique($sql, $par, 'ASSOC');
        } catch(Exception $e) {
            die("La query ".$sql." ha generato il seguente errore :<br>".$e->getMessage());
        }
        if (is_array($this->field)) {
            $fields = $this->field;
            foreach ($fields as $prop) {
                $fieldName = $prop['name'];
                if (!isset($_REQUEST[$fieldName]) && $dbField = $prop['db-field-connected']) {
                    //$_REQUEST[$fieldName] = $data[$dbField];
                    $this->request->set('input.'.$fieldName, $data[$dbField]);
                }
            }
        }
        $this->dictionary->set('dbvalues', $data);
        return $data;
    }

    public function setPkey($newid=null)
    {
        $pkeys = $this->dictionary->get('model.pkeyField');
        
        if (empty($pkeys)) {
            return;
        }
        //For primarykey  with autoincrement
        if (!empty($newid)) { 
            $field = $pkeys[0];
            $fieldDb = $field['db-field-connected'];
            $this->response->command('setpkey', array($fieldDb,$newid));
            $_REQUEST[$field['name']] = $_POST[$field['name']] = $newid;
            return;
        }  
        
        //For primary key with manual insert
        foreach ($pkeys as $field) {
            //mail('pietro.celeste@gmail.com','Primary key',print_r($field,true));
            $fieldDb = $field['db-field-connected'];
            $fieldName = $field['name'];
            $this->response->command(
                'setpkey', 
                array(
                    $fieldDb, 
                    $_REQUEST[$fieldName]
                )
            );
        }
    }
    
    private function grabFkey($field)
    {
        $contexts = array('fkey','par','osy');
        $idList = explode('|', $field['parameterValue']);
        foreach ($contexts as $context) {
            foreach ($idList as $id) {
                $id = trim($id);
                if (array_key_exists($context, $_REQUEST) && !empty($_REQUEST[$context][$id])) {
                    $this->request->set('input.'.$field['name'], $_REQUEST[$context][$id]);
                    $_REQUEST[$field['name']] = $_REQUEST[$context][$id];
                    return;
                }
            }
        }
    }
}
