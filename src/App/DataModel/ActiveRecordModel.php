<?php
namespace Opensymap\App\DataModel;

use Opensymap\App\DataModel\FieldModel;

class ActiveRecordModel implements InterfaceModel
{
    private $db;
    private $dispatcher;
    private $fields = array();
    private $identityFields = array();
    private $identityValues = array();
    private $properties;
    private $reponse;
    
    public function __construct($db, $properties, $identityValues, $response = null, $dispatcher = null)
    {
        $this->db = $db;
        $this->properties = $properties;
        $this->response = $response;
        $this->dispatcher = $dispatcher;
        $this->identityValues = $identityValues;
    }
    
    public function map($dbField, $fieldProp) 
    {
        $this->fields[$dbField] = new FieldModel($fieldProp);
        if ($this->fields[$dbField]->isPrimaryKey()) {
            $this->identityFields[] =&  $this->fields[$dbField];
        }
    }
    
    private function getIdentityCondition()
    {
        $conditions = array();
        if (empty($this->identityValues)) {
            return null;
        }
        foreach ($this->fields as $dbField => $field) {
            if ($field->isPrimaryKey() && !empty($this->identityValues[$field->name])) {
                $conditions[$field->name] = $this->identityValues[$field->name];
            }
        }
        if (empty($conditions) || count($conditions) != count($this->identityFields)) {
            $conditions = null;
        }
        return $conditions;
    }
    
    public function load()
    {
        $conditions = $this->getIdentityCondition();
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
            $htmlName = $this->fields[$fieldName]->getHtmlName();
            if (array_key_exists($htmlName, $_REQUEST)) {
                continue;
            }
            $_REQUEST[$this->fields[$fieldName]->getHtmlName()] = $fieldValue;
        }
    }
    
    public function isSoftDelete()
    {
        return !empty($this->properties['softDelete']);
    }
    
    public function delete()
    {
        $conditions = $this->getIdentityCondition();
        
        if (empty($this->properties['databaseTable']) || empty($conditions)) {
            return false;
        }
        $this->dispatcher->dispatch('delete-before',null,$this->id);
        
        if ($softDelete = $this->properties['softDelete']) {
            $whr = $par = array();
            foreach ($conditions as $field => $value) {
                $whr[] = $field." = ?";
                $par[] = $value;
            }
            $sql  = "UPDATE {$this->properties['databaseTable']} SET ";
            $sql .= $softDelete.' ';
            $sql .= "WHERE ".implode(' AND ',$whr);
            
            $this->db->exec_cmd($sql, $par);
        } else {
            $this->db->delete($this->properties['databaseTable'], $conditions);
        }
        
        $this->dispatcher->dispatch('delete-after',null,$this->id);
        return true;
    }
    
    private function getValues()
    {
        $values = array();
        foreach ($this->fields as $field) {
            $values[$field->name] = $field->getValue();
        }
        return $values;
    }
    
    public function save()
    {
        $where = $this->getIdentityCondition();
        if (empty($where)) {
            $this->insert();
        } else {
            $this->update($where);
        }
        return $this->response;
    }
    
    private function insert()
    {
        $this->dispatcher->setContext($this);
        //Dispatch event beforeInsert
        $this->dispatcher->dispatch(
            'insert-before',
            null,
            $this->properties['id']
        );
        if ($this->response->error()) {
            return;
        }
        $values = $this->getValues();
        $newId = $this->db->insert(
            $this->properties['databaseTable'], 
            $values
        );
        $this->setIdentity($newId);
        //Dispatch event afterInsert
        $this->dispatcher->dispatch(
            'insert-after',
            null,
            $this->id
        );
    }
    
    private function update($conditions)
    {
        $this->dispatcher->setContext($this);
        //Dispatch event beforeUpdate
        $this->dispatcher->dispatch(
            'update-before',
            null,
            $this->id
        );
        //If response has error exit;
        if ($this->response->error()) {
            return;
        }
        $this->db->update(
            $this->properties['databaseTable'], 
            $this->getValues(), 
            $conditions
        );
        $this->setIdentity();
        //Dispatch event afterUpdate
        $this->dispatcher->dispatch(
            'update-after',
            null,
            $this->id
        );
    }
    
    public function setIdentity($newId=null)
    {
        if (empty($this->identityFields)) {
            return;
        }
        
        //For primarykey  with autoincrement
        if (!empty($newId)) { 
            $field = $this->identityFields[0];
            $this->response->command(
                'setpkey',
                array(
                    $field->name,
                    $newId
                )
            );
            //Datamodel EAV & Detail required.
            $_REQUEST['pkey'][$field->name] = $_POST['pkey'][$field->name] = $newId;
            //For beforeInsert trigger
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
    
    public function setIdentityValue($key, $val)
    {
        $_REQUEST[$key] = $this->identityValues[$key] = $val;
    }
}
