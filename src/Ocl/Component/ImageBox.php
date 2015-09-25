<?php
/*
 +-----------------------------------------------------------------------+
 | core/Component/Ocl/ImageBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create ImageBox component                                           |
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

use Opensymap\Osy;
use Opensymap\Lib\Tag;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Ocl\AjaxInterface;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox;

class ImageBox extends AbstractComponent implements DboAdapterInterface,AjaxInterface
{
    private $db;
    
    public function __construct($id)
    {
        parent::__construct('div',$id);
        $this->att('class','osy-imagebox');
        $file = $this->add(tag::create('input'));
        $file->att('type','file')
             ->att('class','hidden')
             ->att('id',$id.'_file')
             ->name = $id;
        $this->addRequire('js/component/ImageBox.js');        
    }
    
    protected function build()
    {
        $img = '';
        //var_dump($_REQUEST[$this->id]);
        if (!empty($_REQUEST[$this->id])) {
            if ($inblob = $this->get_par('store-in-blob')){
                $img = '<img src="data:image/png;base64,'.base64_encode($_REQUEST[$this->id]).'">';
            } else {
                $filename = $_SERVER['DOCUMENT_ROOT'].$_REQUEST[$this->id];
                if (file_exists($filename)){ 
                    $img = '<img src="'.$filename.'">'; 
                }
            }
            if (!empty($img) && $dim_max = $this->get_par('crop-dimension'))
            {
                $dim_img = getimagesize($filename);
                $dim_max = explode(',',$dim_max);
                
                if ($dim_max[0] < $dim_img[0] &&  $dim_max[1] < $dim_img[1])
                {
                    $this->att('class','image-crop',true);
                    $this->add('<input  type="hidden" id="'.$this->id.'_crop" name="'.$this->id.'_crop" class="osy-imagebox-crop">');
                    $prw = $this->add(tag::create('div'))
                                ->att('class','osy-imagebox-previewbox');
                    $prw->add('<div style="width: 140px; height: 140px; overflow: hidden;"><img src="'.$_REQUEST[$this->id].'" class="osy-imagebox-preview"></div>');
                    $prw->add('<span id="'.$this->id.'_get_crop" class="osy-imagebox-cmd-crop btn_cnf w100 center"><span class="fa fa-cut"></span> Taglia</span>');
                    $this->add('<img src="'.$_REQUEST[$this->id].'" class="osy-imagebox-master">');
                    return;
                }
                $this->add('<div><img src="'.$_REQUEST[$this->id].'" class="osy-imagebox-master" title="'.$_REQUEST[$this->id].'"></div>',true);
            }
        }
        if ($dim = $this->get_par('max-dimension')){
                $dim = explode(',',$dim);
                $sty = ' style="width:'.$dim[0].'px; height: '.$dim[1].'px;"';
        }
        $this->add('<label class="osy-imagebox-dummy"'.$sty.' for="'.$this->id.'_file">'.(empty($img) ? '<span class="fa fa-camera" ></span>' : $img).'</label>');
        if (!empty($img)){
            $this->add(tag::create('div'))
                 ->att('class','osy-imagebox-cmd center')
                 ->add(tag::create('a'))
                 ->att('href','javascript:void(0);')
                 ->att('onclick',"oimagebox.delete('".$this->id."')")
                 ->att('data-cmd','delete')
                 ->add('Elimina <span class="fa fa-trash"></span>');
        }
        //$this->add(tag::create('label'))->att('class','btn_add center')->att('for',$this->id.'_file')->add('Upload');
    }
    
    public function ajaxResponse($controller, &$response)
    {
        $cmd = $_REQUEST['ajax-cmd'];       
        $msg = 'OK';
        $pkey = $_REQUEST['pkey'];
        switch($cmd) {
            case 'crop':    
                list($x,$y,$w,$h) = explode(',',$_REQUEST[$this->id.'_coords']);
                $msg = $this->imageCrop($_REQUEST[$this->id],$x,$y,$w,$h);
                break;
            case 'delete':
                $table = $controller->getModel()->form['db-table-linked'];
                $field = $this->get_par('db-field-connected');
                if (!is_array($pkey)) {
                    $msg = 'Delete impossible.';
                    break;
                }
                try{
                    $this->db->update($table, array($field=>null), $pkey);
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                }
                break;
            default: 
                $msg = empty($cmd) ? 'Command is empty' : 'Command '.$cmd.' is unknown';
                break;
        }
        if (!empty($msg)) {
            $response->message('result', $msg);
        }
    }
    
    protected function imageCrop($fileName,$x,$y,$w,$h)
    {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $fileName;
        try {
            require_once(SITE_LIB.'phpthu/ThumbLib.inc.php');
            $image = PhpThumbFactory::create($full_path);
            $image->crop($x, $y, $w, $h);
            $image->save($full_path);
            return 'OK';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
}
