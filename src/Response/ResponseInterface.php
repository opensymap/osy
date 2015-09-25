<?php
namespace Opensymap\Response;

interface ResponseInterface 
{    
    public function __toString();
    public function error($oid=null, $err=null);   
}