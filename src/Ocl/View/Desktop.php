<?php
namespace Opensymap\Ocl\View;

class Desktop extends AbstractView
{
    public function build()
    {
        $this->response->printmicrotime=true;
        if ($cmd = $this->model->request->get('input.ajax')) { 
            $this->execAjax($cmd);
        }
        $this->response->setTitle(strip_tags($this->request->get('instance.title')));
        //Append required css file
        $this->response->addCss('/vendor/font-awesome-4.2.0/css/font-awesome.min.css');
        $this->response->addCss('Ocl/View/Desktop/style.css');
        //Append required js file
        $this->response->addJsFile('/vendor/jquery/jquery-1.10.2.min.js');
        $this->response->addJsFile('Ocl/View/Desktop/base64.js');
        $this->response->addJsFile('Ocl/View/Desktop/moment-2.10.2.js');
        $this->response->addJsFile('Ocl/View/Desktop/controller.js');
        $this->response->addJsFile('Ocl/View/Desktop/notifications.js');
        $this->response->addJsFile('Ocl/View/Desktop/window.js');
        $this->response->addJsCode(
            "$(document).ready(function(){   
                osy.set('instance','".$this->request->get('instance.id')."')
                   .set('route','http://".$this->request->get('instance.uri')."')
                   .set('instance-html','".$this->request->get('instance.title')."')
                   .init();
            });"
        );
        $_SESSION['instance-id'] = $this->request->get('instance.id');
    }
    
    public function execAjax($cmd)
    {
        //env::check_auth();
        //if (!env::$is_auth) return;
        switch($cmd)
        {
            case 'save-object-position' :
                ob_clean();
                $par = array($_POST['pos_top'],'position-top',$_POST['osy']['oid']);
                $this->model->dbo->exec_cmd('UPDATE osy_obj_prp SET p_vl = ? WHERE p_id = ? AND o_id = ? ',$par);
                $par = array($_POST['pos_left'],'position-left',$_POST['osy']['oid']);
                $this->model->dbo->exec_cmd('UPDATE osy_obj_prp SET p_vl = ? WHERE p_id = ? AND o_id = ? ',$par);
                die('position saved ['.$_POST['pos_left'].','.$_POST['pos_top'].']');
                break;
            case 'desktop-remove-object':
                ob_clean();
                if ($this->model->dbo->exec_cmd("DELETE FROM osy_obj WHERE o_id = ? AND o_typ = ?",array($_REQUEST['osy']['oid'],'desktop-object'))){
                        $this->model->dbo->exec_cmd("DELETE FROM osy_obj_prp WHERE o_id = ?",array($_REQUEST['osy']['oid']));
                }
                break;
            case 'desktop-add-object' : 
                ob_clean();
                $onm = uniqid();
                $oid = $this->request->get('intance.uid').'icon:'.$onm."/";
                $ttl = $this->model->dbo->exec_unique("SELECT ifnull(o_lbl,o_nam) FROM osy_obj WHERE o_id = ?",array($_POST['osy']['oid']));
                $par = array($this->request->get('intance.uid'),$oid,$_POST['osy']['oid'],$onm,$ttl,'desktop-object','icon');
                $cmd = "INSERT INTO osy_obj (o_own,o_id,o_par,o_nam,o_lbl,o_typ,o_sty) VALUES (?,?,?,?,?,?,?)";
                if ($this->model->dbo->exec_cmd($cmd,$par)) {
                    $this->model->dbo->exec_cmd('INSERT INTO osy_obj_prp (o_id,p_id,p_vl,p_ord) VALUES (?,?,?,?)',array($oid,'position-top','0',10));
                    $this->model->dbo->exec_cmd('INSERT INTO osy_obj_prp (o_id,p_id,p_vl,p_ord) VALUES (?,?,?,?)',array($oid,'position-left','0',10));
                }
            case 'desktop-restore':
                $par = array($this->request->get('intance.uid'),'desktop-object');
                $sql = "SELECT o.o_id as object_id,
                                           IFNULL(o.o_lbl,o.o_nam) AS label,
                                       pt.p_vl AS position_top,
                                           pl.p_vl AS position_left,
                                           ifnull(ic64.p_vl,ic.p_vl) AS icon,
                                           fi.p_vl AS fid,
                                           fiw.p_vl AS width,
                                           fih.p_vl AS height,
                                           ifnull(ur.p_vl,fre.p1)   AS view_manager,
                                           ifnull(frm.o_own,o.o_own) as app_id
                                FROM osy_obj o
                                INNER JOIN osy_obj_prp pt  ON (o.o_id = pt.o_id   AND pt.p_id = 'position-top')
                                INNER JOIN osy_obj_prp pl  ON (o.o_id = pl.o_id   AND pl.p_id = 'position-left')
                                LEFT JOIN osy_obj_prp  fi  ON (o.o_par = fi.o_id  AND fi.p_id = 'osy-form')
                                LEFT JOIN osy_obj_prp  fiw ON (fi.p_vl = fiw.o_id AND fiw.p_id = 'width')
                                LEFT JOIN osy_obj_prp  fih ON (fi.p_vl = fih.o_id AND fih.p_id = 'height')
                                LEFT JOIN osy_obj_prp  ic  ON (o.o_par = ic.o_id  AND ic.p_id = 'url-icone')
                                LEFT JOIN osy_obj_prp  ic64 ON (o.o_par = ic64.o_id  AND ic64.p_id = 'icon-base64')
                                LEFT JOIN osy_obj_prp  ur  ON (o.o_par = ur.o_id AND ur.p_id = 'url-fisic-page')
                                LEFT JOIN osy_obj      frm ON (fi.p_vl = frm.o_id)
                                LEFT JOIN osy_res      fre ON (frm.o_sty = fre.v_id AND fre.k_id = 'osy-object-subtype')
                                WHERE o.o_own = ? AND o.o_typ = ?";
                if (!empty($oid)){
                        $sql .= " AND o.o_id = ?";
                        $par[] = $oid;
                }

                $res = $this->model->dbo->exec_query($sql,$par,'ASSOC');
                header('Content-type: application/json');
                die(json_encode($res));
                break;
        }
    }
}
