<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Social.php                                         |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create simil Facebook component                                     |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/04/2015
 * @date-update     09/04/2015
 */

namespace Opensymap\Ocl\Component;

use Opensymap\Osy;
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\TextArea;
use Opensymap\Ocl\Component\Button;

class Social extends AbstractComponent
{    
    public function __construct($name)
    {
        parent::__construct('div',$name);
        $this->att('class','osy-social');
        Osy::$page->add_css(OSY_WEB_ROOT.'/css/Social.css');
        Osy::$page->add_script(OSY_WEB_ROOT.'/js/component/Social.js');
        $this->addRequire('css/Social.css');
        $this->addRequire('js/component/Social.js');
        $pst = $this->add(tag::create('div'))->att('class','osy-social-post');
        $pst->add(new TextArea($name.'_post'))->att('class','osy-social-post');
        $pst->add(tag::create('div'))->att('class','osy-social-post-canvas');
        $pst->add(new Button($name.'send'))->att('class','osy-social-send-post')->att('label','Post');        
        $this->add(tag::create('div'))->att('class','osy-social-body')                          
                                      ->add('<ul class="notify">'.$this->printPostList($_REQUEST['_uid']).'</ul>');
    }  
    
    protected function build()
    {       
        if ($_POST['ajax']==$this->id){             
            $this->execAction();
            return;
        }
    }            
    
    private function execAction()
    {
        //ob_clean();
        $resp = '';
        switch($_POST['ajax-command']) {
            case 'post':
                $_POST['tid'] = ($_POST['ajax-command'] == 'comment') ? 2 : 1;                               
                Osy::$dbo->exec_cmd('INSERT INTO osy_pst 
                                        (id_typ,id_usr,id_par,dat_ins,cnt)
                                      VALUES
                                        (?,?,?,NOW(),?)',array($_POST['tid'],$_REQUEST['_uid'],$_POST['pid'],$_POST['pst']));
                $ObjID = Osy::$dbo->last_id();
                $resp = 'POST_OK[;]';
                break;
            case 'comment':
                $strSQL = "SELECT p.cnt as cmt_msg,
                                 a.nik_nam as cmt_usr,
                                 a.fld_6   as cmt_img,
                                 get_time_stamp(p.dat_ins) as cmt_tim,
                                 DATE_FORMAT(dat_ins,'%d %M %Y %H:%i') as cmt_dat
                          FROM   osy_pst p
                          INNER JOIN osy_obj a ON (p.id_usr = a.id)
                          WHERE  p.id = ?";
                list($cmt) = Osy::$dbo->get_all($strSQL,array($ObjID));
                $resp = 'COMMENT_OK[;]';
                $resp .= $this->printComment($cmt);
                break;
            case 'get_notify':
                $resp = $this->printPostList($_REQUEST['_uid'],$_REQUEST['pid']);
                break;
            case 'scroll_down':
                $resp = $this->printPostList($_REQUEST['_uid'],$_REQUEST['pid'],$_REQUEST['npst']);
                break;
            case 'get_webpage':
                $resp = $this->grabWebPage($_REQUEST['url']);
                break;
        }
        die($resp);
    }
    
    function printPostList($uid, $pid=null, $s=0, $l=10)
    {
        /*if (!empty($_POST['user-diary'])){
            //qui va messo il controllo privacy
            $aid = Osy::$dba->exec_unique("SELECT id 
                                           FROM osy_user 
                                           WHERE lgn = ?",array($_POST['user-diary']));
           if (!empty($aid)){ $uid = $aid; }
        } */   
        $par = array($uid);
        $sql = "SELECT distinct a.id
                   FROM (SELECT p.id,p.dat_ins
                         FROM osy_pst p 
                         INNER JOIN (SELECT ? as id_ana\n";
        if (empty($aid)) {
             $sql .= "UNION
                              SELECT IF(o_1=?,o_2,o_1)
                              FROM osy_obj_rel
                              WHERE ? IN (o_2,o_1)";
             $par[] = $uid;
             $par[] = $uid;
        }
        $sql .= ") r ON (p.id_usr = r.id_ana)
                    WHERE p.id_typ = 1 AND
                          p.dat_del is null \n";
        if (!empty($pid)) {
            $sql .= " AND p.id > ?\n";
            $par[] = $pid;
        }
        if (!empty($aid)) {
            $sql .= " UNION
                        SELECT id_par,dat_ins
                        FROM tbl_pst
                        WHERE id_par is not null AND id_usr = ?\n";
                        $par[] = $uid;
        }
        $sql .="ORDER BY 2 desc) a
                   LIMIT $s,$l";
                               //echo "<pre>$sql</pre>";
        $aPid = Osy::$dbo->exec_query($sql,$par);
        foreach($aPid as $k => $v) {
            $lPid[] = $v['id'];
        }
        if (count($lPid) > 0) {
          $sql = "SELECT 
                            DATE_FORMAT(dat_ins,'%d %M %Y %H:%i') as dat_ins,
                            p.*
                     FROM (SELECT p.id as pid,
                                  p.id_typ,
                                  p.ttl,
                                  p.cnt,
                                  p.dat_ins,
                                  if (p.id_usr = ?,'1','0') as pst_own,
                                  get_time_stamp(p.dat_ins) tim,
                                  m.o_lbl as mit, 
                                  null as img,
                                  pa.cnt     as cmt_msg,
                                  pa.dat_ins as cmt_dat,
                                  ap.o_lbl   as cmt_usr,
                                  null as cmt_img,
                                  get_time_stamp(pa.dat_ins) as cmt_tim
                           FROM   osy_pst p
                           INNER JOIN osy_obj       m ON (p.id_usr = m.o_id)
                           INNER JOIN (SELECT ? as id_ana
                                       UNION
                                       SELECT IF(o_1=?,o_2,o_1)
                                       FROM osy_obj_rel
                                       WHERE ? IN (o_2,o_1)) r ON (p.id_usr = r.id_ana)
                           LEFT JOIN osy_pst pa ON (p.id = pa.id_par)
                           LEFT JOIN osy_obj ap ON (pa.id_usr = ap.o_id)
                           WHERE  p.dat_del is null
                             AND  p.id_typ = 1
                             AND  p.id in (".(implode(',',$lPid)).")
                           ORDER BY p.dat_ins desc
                          ) p";                    
            $rawPst = Osy::$dbo->exec_query($sql,array($uid,$uid,$uid,$uid));
            $i = $pid = 0;
            foreach($rawPst as $k => $v) {
                  if ($pid != $v['pid']) {
                      $i++;
                      $pid = $v['pid'];
                      $r =array_splice($v,-5);
                      $lPst[$i] = $v;
                      if (!empty($r['cmt_msg'])) {
                          $lPst[$i]['cmt'][] = $r;
                      }
                  } else {
                      $r =array_splice($v,-5);
                      $lPst[$i]['cmt'][] = $r;
                  }
            }
            if (is_array($lPst)) {              
                $list = '';
                foreach($lPst as $kpst => $pst) {
                    $list .= '<li class="clearfix">';
                    $list .= empty($pst['img']) ? '<span class="fa fa-user fa-2x"></span>' : '<img src="'.$pst['img'].'" style="border: 1px solid gray;">';
                    $list .= '<div class="cnt'.(!empty($pst['own']) ? ' own' : '').'" pid="'.$pst['pid'].'">';
                    $list .= '<span class="nick">'.$pst['mit'].'</span><br>';
                    $list .= $pst['cnt'];
                    $list .= '<div>';
                    $list .= '<span class="cmd" onclick="$(this).parent().next().toggle()">Commenta </span> <span>&middot;</span> <abbr title="'.$pst['dat_ins'].'" class="timestamp">'.$pst['tim'].'</abbr>';
                    $list .= '</div>';     
                    $list .= '<div class="clearfix" style="padding: 3px; width: 400px; '.(!is_array($pst['cmt']) ? 'display: none;' : '').'">';
                    if (is_array($pst['cmt'])){
                        $ncmt = count($pst['cmt']);
                        $ksta =  $ncmt > 3 ? $ncmt - 2 : 0;
                        if ($ksta){
                            $list .= '<div class="cmt" style="height: 16px;"><img src="/img/ico/comment.png" style="width: 12px; height: 12px;"><span class="cmd" onclick="$(this).parent().parent().find(\'.hdn\').toggle(\'fast\'); $(this).parent().remove();">Mostra tutti e '.$ncmt.' commenti</span></div>';
                        }
                        foreach($pst['cmt'] as $kcmt => $cmt){
                            $hdn = ($kcmt < $ksta) ? true : false;
                            $list .= $this->printComment($cmt,$hdn);
                        }
                    }
                    $list .= '<div class="cmt">';
                    $list .= '<img src="'.UIMG.'" style="width: 30px; height: 30px;">';
                    $list .= '<textarea class="comment" rows="1" style="width: 335px;"></textarea><br/>';
                    $list .= '<span>Premi il tasto Invio per pubblicare il commento</span>';
                    $list .= '           </div>';
                    $list .= '       </div>';       
                    $list .= '   </div>   ';            
                    $list .= '</li>';      
                }
                return $list;
            } 
        }
        
    }
    
    private function printComment($cmt,$hdn=false){
        $msg = '<div class="cmt clearfix'.($hdn ? ' hdn' : '').'">';
        $msg .= '<img src="'.$cmt['cmt_img'].'">';
        $msg .= '<div class="cnt">';
        $msg .= '<span class="nick">'.$cmt['cmt_usr'].'</span>&nbsp;';
        $msg .= $cmt['cmt_msg'];
        $msg .= '<div class="fot">';
        $msg .= '<abbr title="'.$cmt['cmt_dat'].'" class="timestamp">'.$cmt['cmt_tim'].'</abbr>';
        $msg .= '</div>';
        $msg .= '</div>';
        $msg .= '</div>';
        return $msg;
    }
    
    private function grabWebPage($url)
    {
        $fld = array('og:image'=>'','og:title'=>'','og:description'=>'');
        $doc = new DOMDocument();
        @$doc->loadHTMLFile($url);
        $tags = $doc->getElementsByTagName('meta');

        foreach ($tags as $tag){
            $nam = $tag->getAttribute('property');
            if (array_key_exists($nam,$fld)){
                $fld[$nam] = $tag->getAttribute('content');
            }
        }
        $div = '';
        foreach($fld as $k => $v){
            //$v = utf8_decode($v);
            switch($k){
                case 'og:image' :
                          $div .= "<div class=\"image\"><img src=\"{$v}\" align=\"left\" style=\"width: 100px\"></div>";
                          break;
                case 'og:title' :
                          $div .= "<div class=\"title\">{$v}</div>";
                          break;
                case 'og:description' :
                         $div .= "<div class=\"body\">{$v}</div>";
                         break;
            }
        }
        if (!empty($div)){
            $div = '<div class="clearfix">'.$div.'<div>';
        }
        return $div;
    }
}