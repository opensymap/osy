<?php
namespace Opensymap\App\DataModel;

use Opensymap\App\DataModel\FieldModel;

class ActiveRecordModel implements InterfaceModel
{
    private $properties;
    private $dispatcher;
    private $reponse;
    private $fields = array();
    private $identityFields = array();
    private $identityValues = array();
    private $db;
    
    public function __construct($db, $properties, $fields, $identityValues, $response = null, $dispatcher = null)
    {
        $this->db = $db;
        $this->properties = $properties;
        $this->response = $response;
        $this->dispatcher = $dispatcher;
        $this->identityValues = $identityValues;
        foreach ($fields as $fieldId => $field) {
            $dbField = $field['name'];
            $this->fields[$dbField] = new FieldModel($field);        
            if ($this->fields[$dbField]->isPrimaryKey()) {
                $this->identityFields[] =&  $this->fields[$dbField];
            }
        }
    }
    
    private function getIdentityCondition()
    {
        $conditions = $values = array();
        foreach ($this->fields as $dbField => $field) {
            $values[$field->name] = $field->getValue();
            if (
                !empty($this->identityValues) && 
                $field->isPrimaryKey() && 
                !empty($this->identityValues[$field->name])
            ) {
                $conditions[$field->name] = $this->identityValues[$field->name];
                
            }
           
        }
        
        if (empty($conditions) || count($conditions) != count($this->identityFields)) {
            $conditions = null;
        }
        return array($conditions, $values);
    }
    
    public function save()
    {
        list($where, $values) = $this->getIdentityCondition();
        if (empty($values)) {
            return false;
        }
        if (empty($where)) {
            $this->insert($values);
        } else {
            $this->update($values, $where);
        }
        return $this->response;
    }
    
    public function load()
    {
        list($conditions, $values) = $this->getIdentityCondition();
        
        if (empty($conditions)) {
            return;
        }
        
        $sql  = "SELECT * ";
        $sql .= "FROM {$this->properties['databaseTable']} ";
        $sql .= "WHERE ".implode(' = ?, ',array_keys($conditions)).' = ?';
        $rec = $this->db->exec_unique($sql, array_values($conditions), 'ASSOC');
        foreach ($rec as $fieldName => $fieldValue) {
            if (!array_key_exists($fieldName, $this->fields)) {
                continue;
            }
            $_REQUEST[$this->fields[$fieldName]->getHtmlName()] = $fieldValue;
        }
    }
    
    public function delete()
    {
        list($condition, ) = $this->getIdentityCondition();
        if (empty($this->properties['databaseTable']) || empty($condition)) {
            return;
        }
        $this->dispatcher->dispatch('delete-before');
        $this->dba->delete($this->properties['databaseTable'], $condition);
        $this->dispatcher->dispatch('delete-after');
        return $this->response;
    }
    
    private function insert($values)
    {
        $this->dispatcher->dispatch('insert-before');
		var_dump($values);
        if (!$this->response->error()) {
             $newId = $this->db->insert($this->properties['databaseTable'], $values);
             $this->setIdentity($newId);
             $this->dispatcher->dispatch('insert-after');
        }
        $this->dispatcher->dispatch('after-save');
        return $this->response;
    }
    
    private function update($values, $conditions)
    {
        $this->dispatcher->dispatch('update-before');
        if (!$this->response->error()) {
            $this->db->update($this->properties['databaseTable'], $values, $conditions);
            $this->setIdentity();
            $this->dispatcher->dispatch('update-after');
        }
        $this->dispatcher->dispatch('after-save');
        return $this->response;
    }
    
    public function setIdentity($newId=null)
    {
        if (empty($this->identityFields)) {
            return;
        }
        
        //For primarykey  with autoincrement
        if (!empty($newId)) { 
            $field = $this->identityFields[0];
            $this->response->command('setpkey', array($field->name, $newId));
            $_REQUEST[$field->getHtmlName()] = $_POST[$field->getHtmlName()] = $newId;
            return;
        }  
        
        //For primary key with manual insert
        foreach ($this->identityFields as $field) {
            $this->response->command(
                'setpkey', 
                array(
                    $field->name, 
                    $this->identityValues[$field->name]
                )
            );
        }
    }
}
