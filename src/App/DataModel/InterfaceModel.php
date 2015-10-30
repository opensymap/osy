<?php
namespace Opensymap\App\DataModel;

interface InterfaceModel
{
    public function __construct($db, $properties, $identityValues, $response = null, $dispatcher = null);
    
    public function delete();
    
    public function load();
    
    public function map($dbName, $fieldProp);
    
    public function save();
}