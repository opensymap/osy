<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Form.php                                               |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  form component                                              |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   28/08/2013
 * @date-update     28/08/2013
 */
 
namespace Opensymap\Ocl\Component;

use Opensymap\Lib\Tag as Tag;
use Opensymap\Ocl\Component\AbstractComponent;

class Form extends AbstractComponent
{
    private $component = array();
    public  $corner = null;
    
    public function __construct($name)
    {
        parent::__construct('form', $name);
        $this->att('name',$name);
        /*
         * Creo una div cornice che conterrà il panel principale in modo da avere
         * un componente a cui poter assegnare un altezza fissa e quindi far comparire
         * le barre di scorrimento
         */
        $this->corner = $this->add(new Tag('div'))->att('id',$name.'-corner');
        $this->corner->deep =& $this->deep;
        /*
         * Aggiungere il panel nella posizione 0 serve ad assegnare un panel di default
         * alla form su cui verranno aggiunti tutti i componenti che non hanno un panel-parent 
         * settatto.
         */
        $this->component[0] = $this->corner->add(new Panel($name.'-panel'));
        $this->component[0]->par('label-position','inside');
    }
    
    public function put($obj,$lbl,$nam,$x=0,$y=0,$par=0)
    {
        //if (!class_exists($typ)) {echo $typ; return;}
        //$obj  = new $typ($nam);
        //$obj->label = $lbl;
        if ($x == -1) {//Se l'oggetto non ha position lo aggiungo in testa
            $this->add($obj,'first');
            $par = empty($par) ? -1 : $par; //$par = -1;
        }
         // se il component ha dei childs nella sua posizione li aggiungo al componente
        if (array_key_exists($nam,$this->component) && is_array($this->component[$nam])) {
            foreach($this->component[$nam] as $c)
            {
                $obj->put($c[0],$c[1],$c[2],$c[3]);
            }
        }
        //Aggiungo il componente alla lista dei componenti.
        $this->component[$nam] = $obj;
        //Se il parent del componente esiste lo associo direttamente al suo interno
        if (array_key_exists($par,$this->component) && is_object($this->component[$par])) {
            $this->component[$par]->put($lbl,$this->component[$nam],$x,$y);
        } else {
            //Altrimenti lo metto nella posizione del parent in attesa che venga creato
            $this->component[$par][] = array($lbl,&$this->component[$nam],$x,$y);
        }
        return $this->component[$nam];
    }
    
    public function buildPdf($pdf)
    {
        $this->component[0]->buildPdf($pdf);
    }
    
    protected function build()
    {
    }
}
