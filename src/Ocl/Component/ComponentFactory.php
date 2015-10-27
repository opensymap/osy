<?php
namespace Opensymap\Ocl\Component;

use Opensymap\Ocl\Component\DateBox;
use Opensymap\Helper\HelperOsy;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Datasource\DatasourceDbo;

class ComponentFactory
{
    private static $model;
    private static $state = 'Normal';
    private static $jsFunctions = array();
    private static $mapParameterMethod = array(
        'height' => 'setStyle',
        'width'  => 'setStyle',
        'cell-class' => 'setCellAttribute',
        'cell-width' => 'setCellStyle',
        'cell-height' => 'SetCellStyle',
        'cell-style'  => 'SetCellStyle',
        'colspan' =>'setCellAttribute',
        'rowspan' =>'setCellAttribute',
        'default-value' => 'setDefaultValue',
        'onclick' => 'addJsEventListener',
        'onclick-trigger' => 'addJsActionListener',
        'onchange' => 'addJsEventListener',
        'onkeypress' => 'addJsEventListener',
        'onkeyup' => 'addJsEventListener',
        'onkeydown' => 'addJsEventListener',
        'form-related' => 'setFormRelated',
        'form-related-ins' => 'setFormRelated',
        'form-related-search' => 'setFormRelated',
        'datasource-sql' => 'setDatasourceDbo'
    );
    
    public static function init($state, $model)
    {
        self::$model = $model;
        self::$state = $state;
        
    }
    
    public static function create($properties)
    {
        if (empty($properties['phpClass'])) {
            return false;
        }
        //Work around da spostare sulle propietà del componente in osy_res
        $componentClass = 'Opensymap\\Ocl\\Component\\'.str_replace('_', '', $properties['phpClass']);
        /*
         * Se presente, eseguo il codice php collegato alla propietà visibility-condition per testare 
         * se l'elemento sia da visualizzare o meno.
         * Nel caso il test dia esito negativo (false) viene restituito il valore booleano false in modo
         * che non il componente non sia costruito e quindi visualizzato sulla form.
         */
        if ($condition = $properties['visibility-condition']) {
            if (!self::testCondition($condition)) {
                return false;
            }
        }
        if (strtolower(self::$state) == 'view') {
            switch ($componentClass) {
                case 'Opensymap\\Ocl\\Component\\Button':
                case 'Opensymap\\Ocl\\Component\\DataGrid':
                case 'Opensymap\\Ocl\\Component\\Dummy':
                case 'Opensymap\\Ocl\\Component\\HiddenBox':
                case 'Opensymap\\Ocl\\Component\\Autocomplete':
                case 'Opensymap\\Ocl\\Component\\Panel':
                case 'Opensymap\\Ocl\\Component\\Tab':
                case 'Opensymap\\Ocl\\Component\\Multibox':
                case 'Opensymap\\Ocl\\Component\\TagList':
                case 'Opensymap\\Ocl\\Component\\ImageBox':
                    break;
                default:
                    $componentClass = '\\Opensymap\\Ocl\\Component\\Label';
                    break;
            }
        } elseif ($condition = $properties['check-label-view']) {
            $componentClass = self::testLabelViewCondition($condition, $componentClass);
        }
       
        try {
            $component = new $componentClass($properties['name']);
            $component->setModel(self::$model);
            if ($component instanceOf DboAdapterInterface) {
                $component->setDboHandler(self::$model->dba);
            }
            $component->label = $properties['label'];
            //Add attributes to component
            if (array_key_exists('attribute', $properties)) {
                self::appendAttributes($component, $properties['attribute']);
            }
            //Apply parameter to component
            foreach(array('cell-attribute','parameter','event') as $category){
                self::applyParameters($component, $properties[$category]);
            }
        } catch (Exception $e) {
            $component = $e->getMessage(). ' ' . $properties['phpClass'];
        }
        return $component;
    }
    
    private static function addJsActionListener($component, $par, $val)
    {
        if (!empty($val)) {
            $par = explode('-',$par);
            $par = substr($par[0],2);
            self::$jsFunctions[$component->id] = array($par,"    oform.command.exec(this,'".sha1($val)."');");
        }
    }
    
    private static function addJsEventListener($component, $par, $val)
    {
        if (!empty($val)) {
           self::$jsFunctions[$component->id] = array(substr($par,2), $val);
        }
    }
    
    private static function appendAttributes($component, $attributes)
    {
        if (is_array($attributes)) {
            foreach ($attributes as $name => $value) {
                $component->att($name, $value, true);
            }
        }
    }
    
    private static function applyParameters($component, $parameters)
    {
        if (is_array($parameters)) {
            foreach ($parameters as $par => $val) {
                $component->par($par, $val);
                if (array_key_exists($par, self::$mapParameterMethod)) {
                    $function = self::$mapParameterMethod[$par];
                    self::$function($component, $par, $val);
                }
            }
        }
    }
    
    public static function getJsFunction()
    {
        return self::$jsFunctions;
    }
    
    private static function setCellAttribute($component, $par, $val)
    {
        $component->man('onbuild', $par, 
            function ($param, $value, $self) {  
                $cell = $self->closest('td,th');
                if (is_object($cell)) {
                    $cell->att(str_replace('cell-','',$param), $value, true);
                }
            }
        );
    }
    
    private static function setCellStyle($component, $par, $val)
    {
        $component->man('onbuild', $par, 
            function ($param, $value, $self) {
                $cell = $self->closest('td,th');
                if (is_object($cell)) {
                    if ($param != 'cell-style') {
                        $value = str_replace('cell-','',$param).' : '. $value .';';
                    }
                    $cell->att('style', $value, true);
                }
            }
        );
    }
    
    private static function setDefaultValue($component, $par, $val)
    {
        if (!array_key_exists($component->id,$_REQUEST)){
            $_REQUEST[$component->id] = $val;
        }
    }
    
    private static function setStyle($component, $par, $val)
    {
        $component->att('style',' '.$par.' : '.$val.';', true);
    }
    
    private static function setDatasourceDbo($component, $par, $query)
    {
        $datasource = new DatasourceDbo(self::$model->dba);
        $datasource->setQuery($query);
        $component->setDatasource($datasource);
    }
    
    public function setFormRelated($component, $par, $val)
    {
        if (empty($par) || empty($val)) {
            return;
        }
        $add = $component->getParameter('record-add');
        if (is_null($add)) {
            $component->par('record-add',true);
        }
        $component->par('record-update',true);
        $res = self::$model->dbo->exec_query(
            "SELECT frm.o_id                  AS oid,
                    frm.o_nam                 AS onam,
                    frm.o_typ                 as otyp,
                    frm.o_own                 AS app,
                    prp.p_id                  AS pid,
                    prp.p_vl                  AS pval
             FROM  osy_obj frm
             LEFT JOIN  osy_obj_prp prp ON (
                frm.o_id = prp.o_id AND 
                prp.p_id IN (
                    'height',
                    'width',
                    'db-field-is-pkey',
                    'db-field-connected'
                )
             )
             WHERE frm.o_typ IN ('form','field') AND  frm.o_id LIKE ?",
             array($val.'%'), 
             'ASSOC'
        );
        $form = array();
        foreach ($res as $k => $rec) {
            if (empty($form[$rec['oid']])) {
                $form[$rec['oid']]['id']   = $rec['oid'];
                $form[$rec['oid']]['name'] = $rec['onam'];
                $form[$rec['oid']]['type'] = $rec['otyp'];
            }
            if (!empty($rec['pid'])){
                $form[$rec['oid']][$rec['pid']] =  $rec['pval'];
            }
        }
        $pkey = [];
        $fres = [];
        foreach ($form as $rec) {
            if ($rec['type'] == 'form') {
                $fres['id']     = $rec['id'];
                $fres['name']   = $rec['name'];
                $fres['height'] = empty($rec['height'])  ? 480 : $rec['height'];
                $fres['width']  = empty($rec['width'])  ? 640 : $rec['width'];
                continue;
            }
            if (!empty($rec['db-field-connected']) && !empty($rec['db-field-is-pkey'])) {
                $pkey[] = $rec['db-field-connected'];
            }
        }
        $fres['field_pkey'] = implode(',',$pkey);
        $component->att('data-'.$par, base64_encode(json_encode($fres)));
        if (!empty($pkey)) {
            $component->par('pkey',$pkey);
        }
    }
    
    private static function testCondition($condition)
    {
        return HelperOsy::execString('', 'return ('.str_replace('TEST', '', $condition).');');
    }
    
    private static function testLabelViewCondition($condition, $componentClass)
    {
        if (self::testCondition($condition)) {
            if  ( $componentClass == '\\Opensymap\\Ocl\\Component\\DateBox') {
                $_REQUEST[$fieldName] = DateBox::convert($_REQUEST[$fieldName]);
            }
            $componentClass = '\\Opensymap\\Ocl\\Component\\Label';
        }
        return $componentClass;
    }
}
