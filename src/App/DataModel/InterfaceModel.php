<?php
namespace Opensymap\App\DataModel;

interface InterfaceModel
{
    public function __construct($db, $properties, $fields, $identityValues, $response = null, $dispatcher = null);
    
    public function save();
    
    public function delete();
}