<?php
namespace Opensymap\Datasource;

interface InterfaceDatasourcePaging
{
    public function get();
    
    public function getPage($key);
    
    public function setPage($current, $request, $pageDimRow);
    
    public function orderBy($order);
    
}