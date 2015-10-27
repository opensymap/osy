<?php 
/*
 +-----------------------------------------------------------------------+
 | core/Component/TagList.php                                            |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2015, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create TagList component                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/12/2014
 * @date-update     09/12/2014
 */
 
namespace Opensymap\Ocl\Component;

use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Helper\HelperOsy;
use Opensymap\Lib\Tag;
use Opensymap\Ocl\AjaxInterface;

class TagList extends AbstractComponent implements DboAdapterInterface, AjaxInterface
{
    private $db;
    private $datasource;
    
    public function __construct($id = null)
    {
        parent::__construct('div',$id);
        $this->class = 'osy-taglist';
        $this->addRequire('Ocl/Component/TagList/style.css');
        //Add javascript controller
        $this->addRequire('Ocl/Component/TagList/controller.js');
    }
    
    protected function build() 
    {
        $this->add(new HiddenBox($this->id));
        $build_tag_from_datasource = false;
        if ($this->getParameter('database-save-parameters')) {
            $this->att('class','osy-taglist-onextab',true);
            $build_tag_from_datasource = true;
        }
        $ul = $this->add(new Tag('ul'));
        if ($sql = $this->getParameter('datasource-sql')) {
            $sql = HelperOsy::replacevariable($sql);
            $res = $this->db->exec_query($sql,null,'NUM');
            $datalist = $this->add(new Tag('datalist'));
            $datalist->att('id',$this->id.'_data');
            foreach($res as $k => $rec) {
                if ($rec[2] == 1) {
                    $ul->add('<li class="osy-taglist-entry" tid="'.$rec[0].'"><span class="osy-taglist-entry-text">'.$rec[1].'</span><a href="#" class="osy-taglist-entry-remove">remove</a></li>');
                }
                $datalist->add(new Tag('option'))->add($rec[1]);
            }
        }
        
        if(!$build_tag_from_datasource && !empty($_REQUEST[$this->id])) {
            $item_list = explode(',',$_REQUEST[$this->id]);
            foreach($item_list as $k => $v){
                $ul->add('<li class="osy-taglist-entry" pos="'.$k.'"><span class="osy-taglist-entry-text">'.$v.'</span><a href="#" class="osy-taglist-entry-remove">remove</a></li>');
            }
        }
        $txt = $ul->add(new Tag('li'))
                  ->att('class','listbuilder-entry-text')
                  ->add(new Tag('input'))
                  ->att('name',$this->id.'_add')
                  ->att('type','text')
                  ->att('class','add osy-taglist-input');
        if (isset($datalist)) {
            $txt->att('list',$this->id.'_data');
        }
        $ul->add('<br style="clear: both">');        
    }

    public function ajaxResponse($controller, &$response)
    {
        $rawParameters = $this->getParameter('database-save-parameters');
        if (empty($rawParameters)) {
            $response->error('alert', 'Parameter database-save-parameters empty!');
            return;
        } elseif (!array_key_exists('pkey',$_REQUEST)) {
            $response->error('alert', 'Pkey empty. Impossible save data');
            return;
        }
        $rawParameters = explode(',',$rawParameters);
        //Get first element - Table where save data
        $table = array_shift($rawParameters);
        //Get last element - Field where save data
        $fieldTag = array_pop($rawParameters);
        //Restant $rawParameters are the foreign key field. 
        //If number $rawParameters are not equal number of pkey there is error. Send error to user.
        if (count($rawParameters) != count($_REQUEST['pkey'])) {
            $response->error('alert', "Number of fkey don't match number di fkey filed. ");
            return;
        }
        //Load from db the list of tag admitted.
        $tagList = array();
        if ($sql = $this->getParameter('datasource-sql')) {
            $res = $this->db->exec_query($sql, null, 'NUM');
            foreach($res as $k => $rec) {
                $tagList[$rec[1]] = $rec[0];
            }
        }
        //If Tag send from the user is not present into list of tag admitted send error to user.
        if (!array_key_exists($_REQUEST['tag'],$tagList)){
            die("Tag {$_REQUEST['tag']} don't exists in datalist");//.print_r($_REQUEST['tag'])."\n".print_r($tagList,true));
        } 
        //Load Sql parameter into array
        $sqlParameters = array();                
        $i = 0;
        foreach ($_REQUEST['pkey'] as $k => $pkey) {
            $sqlParameters[$rawParameters[$i]] = $pkey;
            $i++;
        }        
        //Prendo il tag ID e lo aggiungo ai parametri sql per l'inserimento in tabella.
        $sqlParameters[$fieldTag] = $tagList[$_REQUEST['tag']];
        //Exec requested command (del=delete, add=insert)
        switch ($_POST['ajax-cmd']) {
            case 'add':
               $this->db->insert($table, $sqlParameters);
                break;
            case 'del':
                $this->db->delete($table, $sqlParameters);
                break;
        }
        $response->message('result',true);
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
    
    public function setDatasource($ds)
    {
        $this->datasource = $ds;
    }
}
