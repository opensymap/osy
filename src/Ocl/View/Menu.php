<?php
/*
 +-----------------------------------------------------------------------+
 | OpenSymap - Sistema per la gestione di applicazioni modulari          |
 | Version 3.1                                                           |
 |                                                                       |
 | Copyright (C) 2005-2008, Pietro Celeste                               |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | Redistribution and use in source and binary forms, with or without    |
 | modification, are permitted provided that the following conditions    |
 | are met:                                                              |
 |                                                                       |
 | o Redistributions of source code must retain the above copyright      |
 |   notice, this list of conditions and the following disclaimer.       |
 | o Redistributions in binary form must reproduce the above copyright   |
 |   notice, this list of conditions and the following disclaimer in the |
 |   documentation and/or other materials provided with the distribution.|
 | o The names of the authors may not be used to endorse or promote      |
 |   products derived from this software without specific prior written  |
 |   permission.                                                         |
 |                                                                       |
 | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
 | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
 | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
 | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
 | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
 | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
 | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
 | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
 | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
 | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
 | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+
**/
namespace Opensymap\Ocl\View;

use Opensymap\Osy as Osy;
use Opensymap\Lib\Tag as Tag;

class Menu extends AbstractView
{
    private $dataApp = array();
    private $dataMenu = array();
    private $jsFunction = array();
    private $userId;
    public  $css = 'css/style.css';

    private function exec($cod)
    {
        $f = create_function(null,$cod);
        $f();
    }

    protected  function build()
    {
        $this->loadApplication();
        $this->response->setTitle('Menù');
        $this->response->addCss('/vendor/font-awesome-4.2.0/css/font-awesome.min.css');
        $this->response->addCss(OSY_WEB_ROOT.$this->css);
        $this->response->addJsFile('/vendor/jquery/jquery-1.10.2.min.js');
        $this->response->addJsFile('js/view/Menu.js');
        $this->response->addJsCode($this->javascriptFunction());
        $form = $this->response->getBody()->add(new Tag('form'));
        $form->add('<input type="hidden" id="osy[fid]" name="osy[fid]" value="'.$this->request->get('input.osy.fid').'">');
        $form->add('<input type="hidden" id="osy[sid]" name="osy[sid]" value="'.$this->request->get('input.osy.sid').'">');
        $form->add($this->show());
        //$form->add('<div id="current-user" style="display: none;"><span class="fa fa-user"></span> '.$Menu->get_current_user().'</div>');
        $form->add($this->getFormIconify());
        $this->response->getBody()->add('<div id="popupmenu" style="display: none">
            <a href="javascript:void(0);" class="popupmenu-item" data-cmd="send-to-desktop">Invia sul desktop</a>
        </div>');
    }
    
    private function loadApplication()
    {
        $css = $this->model->dbo->exec_unique(
            "SELECT p_vl
             FROM osy_obj_prp
             WHERE o_id = ?
               AND p_id = 'css-form-path'",
            array('instance://'.$this->request->get('instance.id').'/'));

        $this->css = empty($css) ? '/css/style.css' : '/'.$css;
        $cmd = "SELECT a.o_id   AS id,
                            a.o_lbl  AS tit,
                            iaur.o_4 AS rul,
                            1        AS dev,
                            case
                                 when iaur.p_1 is null
                                 then '10'
                                 else iaur.p_1
                            end   AS lng,
                            p.p_vl as js,
                            ph.p_vl as php,
                            CONCAT('<span class=\"fa fa-',ifa.p_vl,'\"></span> ') as ico
                     FROM osy_obj_rel       ia
                     INNER JOIN osy_obj_rel iaur ON (iaur.o_1 = ia.o_1 AND iaur.o_2 = ia.o_2)
                     INNER JOIN osy_obj     a    ON (iaur.o_2 = a.o_id)
                     LEFT JOIN  osy_obj_prp p    ON (a.o_id = p.o_id AND p.p_id = 'onload-menu')
                     LEFT JOIN  osy_obj_prp ph   ON (a.o_id = ph.o_id AND ph.p_id = 'menu-php-code')
                     LEFT JOIN osy_obj_prp  ifa  ON (a.o_id = ifa.o_id AND ifa.p_id = 'ico-fontawesome')
                     WHERE ia.r_typ = 'instance+application'
                       AND iaur.r_typ = 'user+role'
                       AND a.o_typ = 'app'
                       AND iaur.o_1 = ?
                       AND iaur.o_3 = ?";
        $par = array(
            'instance://'.$this->request->get('instance.id').'/',
            trim($this->request->get('input._uid'))
        );
        $rs = $this->model->dbo->exec_query($cmd, $par, 'ASSOC');
        foreach($rs as $rec) {
            $this->dataApp[$rec['id']]['prp'] = $rec;
            $this->dataApp[$rec['id']]['men'] = $this->loadMenu($rec['id'], $rec['rul'], $rec['lng'], $rec['dev']);
            if (!empty($rec['js'])) {
                $this->jsFunction[$rec['id']] = $rec['js'];
            }
            if (!empty($rec['php'])) {
                $this->exec($rec['php']);
            }
        }
    }

    private  function loadMenu($a, $r, $l, $d=0)
    {
        $cast = '';
        switch($this->model->dbo->get_type()) {
            case 'pgsql':
                $cast = '::integer';
                break;
        }
        $cmd = "SELECT *
                FROM (
                       SELECT men.o_id  AS id,
                                       men.o_par AS par,
                                       case
                                            when lng.p_vl is not null then lng.p_vl
                                            else men.o_lbl
                                       end AS ttl,
                                       coalesce(CONCAT('<span class=\"fa fa-',ifa.p_vl,'\"></span>'),mic64.p_vl,mic.p_vl)  AS ico,
                                       men.o_own AS app,
                                       mpr.p_vl  AS frm,
                                       fre.p1    AS pag_man,
                                       mfp.p_vl  AS lnk,
                                       if(mst.p_vl = 1,mst.p_id,'')  as opn_str,
                                       case
                                            when fwd.p_vl is not null then fwd.p_vl
                                            when mwd.p_vl is not null then mwd.p_vl
                                            else '640'
                                       end AS w,
                                       case
                                           when fhg.p_vl is not null then fhg.p_vl
                                           when mhg.p_vl is not null then mhg.p_vl
                                           else '480'
                                       end AS h,
                                    mor.p_vl as ord
                               FROM  osy_obj          men
                         INNER JOIN osy_obj_rel rul ON (men.o_id = rul.o_1) -- ruoli
                         LEFT JOIN osy_obj_prp  lng ON (men.o_id = lng.o_id AND lng.p_id = 'language' AND lng.p_ord = $l)
                         LEFT JOIN osy_obj_prp  mic ON (men.o_id = mic.o_id AND mic.p_id = 'url-icone')
                         LEFT JOIN osy_obj_prp  mic64 ON (men.o_id = mic64.o_id AND mic.p_id = 'icon-base64')
                         LEFT JOIN osy_obj_prp  ifa ON (men.o_id = ifa.o_id AND ifa.p_id = 'ico-fontawesome')
                         LEFT JOIN osy_obj_prp  mpr ON (men.o_id = mpr.o_id AND mpr.p_id = 'osy-form')
                         LEFT JOIN osy_obj_prp  mfp ON (men.o_id = mfp.o_id AND mfp.p_id = 'url-fisic-page')
                         LEFT JOIN osy_obj_prp  mor ON (men.o_id = mor.o_id AND mor.p_id = 'order')
                         LEFT JOIN osy_obj_prp  mst ON (men.o_id = mst.o_id AND mst.p_id = 'startup-open')
                         LEFT JOIN osy_obj      frm ON (mpr.p_vl = frm.o_id)
                         LEFT JOIN osy_res      fre ON (frm.o_sty = fre.v_id AND fre.k_id = 'osy-object-subtype')
                         LEFT JOIN osy_obj_prp  fwd ON (frm.o_id = fwd.o_id AND fwd.p_id = 'width')
                         LEFT JOIN osy_obj_prp  fhg ON (frm.o_id = fhg.o_id AND fhg.p_id = 'height')
                         LEFT JOIN osy_obj_prp  mwd ON (men.o_id = mwd.o_id AND mwd.p_id = 'window-width')
                         LEFT JOIN osy_obj_prp  mhg ON (men.o_id = mhg.o_id AND mhg.p_id = 'window-height')
                         WHERE men.o_typ = 'menu' AND
                               men.o_own = ?      AND
                               rul.r_typ = 'object+role' AND
                               rul.o_2   = ? AND
                               mor.p_vl >= 0
                        UNION
                            SELECT l.o_id,NULL,l.o_lbl,mic64.p_vl,l.o_own,NULL,NULL,p.p_vl,null,ifnull(lw.p_vl,'640'),ifnull(lh.p_vl,'480'),9999
                            FROM osy_obj l
                            INNER JOIN osy_obj_prp p  ON (l.o_id = p.o_id AND p.p_id = 'url-fisic-page')
                            LEFT JOIN osy_obj_prp  lw ON (l.o_id = lw.o_id AND lw.p_id = 'window-width')
                            LEFT JOIN osy_obj_prp  lh ON (l.o_id = lh.o_id AND lh.p_id = 'window-height')
                            LEFT JOIN osy_obj_prp  mic64 ON (l.o_id = mic64.o_id AND mic64.p_id = 'icon-base64')
                            WHERE l.o_typ = 'menu'
                              AND l.o_sty = 'menu-link'
                              AND l.o_own = ?
                ) a
                ORDER BY coalesce(a.ord{$cast},999)+1,a.ttl";
        $par = array($a,$r,$a);
        $rs = $this->model->dbo->exec_query($cmd,$par,'ASSOC');
        $m = array();
        foreach($rs as $r) {
            $m[$r['id']] = $r;
            $m[$r['id']]['devel'] = $d;
            $m[$r['id']]['lang'] = $l;
        }
        return $m;
    }

    //Funzione ricorsiva che costruisce il menu
    private  function buildMenu($ul, $parent=null)
    {
        foreach($this->dataMenu as $k => $menu) {
            if ($parent == $menu['par']) {
                if (empty($menu['lnk']) && empty($menu['frm'])) {
                    $this->buildBranch($ul,$menu);
                } else {
                    $this->buildLeaf($ul,$menu);
                }
            }
        }
    }

    private  function buildBranch($par,$menu)
    {
        $li = $par->add(tag::create('li'));
        $ul = tag::create('ul')->att('style','display: none;');
        $this->buildMenu($ul,$menu['id']);
        $ico = $li->add(tag::create('img'))
                  ->att('src',OSY_WEB_ROOT."/img/ico.folder.gif")
                  ->att('align','absmiddle')
                  ->att('width','16')
                  ->att('height','16');
        $li->add(" ".$menu['ttl']);
        if ($ul->isEmpty()){
            $ico->att('src',OSY_WEB_ROOT.'/img/ico.alert.gif');
            return;
        }
        $par->add($ul);
        $li->att('onclick',"MenuTurn(this)")
           ->att('onselectstart','return false;')
           ->att('onmousedown','event.preventDefault&&event.preventDefault()');

    }

    private  function buildLeaf($par,$menu)
    {
        $fcnf = array (
            'form' => array (
                'page'   => (!empty($menu['lnk']) ? $menu['lnk'] : 'http://'.$this->request->get('instance.uri')),
                'app'    => $menu['app'],
                'name'   => $menu['ttl'],
                'id'     => $menu['frm'],
                'width'  => $menu['w'],
                'height' => $menu['h']
            ),
            'sessid' => $this->request->get('post.osy.sid'),
            'langid' => $Men['lang']
        );
        $li = $par->Add(new Tag('li'));
        if (strpos($Men['ico'],'data:') === 0) {
            $ico = strpos($menu['ico'],'base64') ?  $menu['ico'] : OSY_WEB_ROOT.$Men['ico'];
        } else {
            $ico = (!empty($menu['ico']) ? $menu['ico'] : OSY_WEB_ROOT.'/img/no.ico.gif');
        }
        $li->att('data-oid',$menu['id'])
           ->att('frm',json_encode($fcnf))
           ->att('__v',  base64_encode(json_encode($fcnf)))
           ->att('class','osy-form-link menu-leaf '.$menu['opn_str'])
           ->att('onselectstart','return false')
           ->att('onmousedown','event.preventDefault&&event.preventDefault()')
           ->add(tag::create('img'))
           ->att('src',$ico)
           ->att('align','absmiddle')
           ->att('width','16')
           ->att('height','16');
        $li->add(" ".$menu['ttl']);
    }

    public  function show()
    {
        $DivCnt = new Tag('div');
        $DivCnt->Att('id','osy-menu-container')->att('data-sid',$this->Sid);
        $DisplaySubmenu = (count($this->dataApp) > 1) ? "display:none;" : "";
        foreach($this->dataApp as $app)
        {
            $DivApp = $DivCnt->Add(tag::create('div'));
            $DivApp->Att('class','osy-menu')
                   ->Att('onclick','MenuTurn(this)')
                   ->Att('onselectstart','return false')
                   ->Att('onmousedown','event.preventDefault&&event.preventDefault();')
                   ->Add(tag::create('p'))->Add($app['prp']['ico'].$app['prp']['tit']);
            $DivSub = $DivCnt->Add(tag::create('div'));
            $DivSub->Att('class','osy-sub-menu')
                   ->Att('style',$DisplaySubmenu)
                   ->Att('onselectstart','return false')
                   ->Att('onmousedown','event.preventDefault&&event.preventDefault();');
            $ul = $DivSub->Add(tag::create('ul'));
            $this->dataMenu = $app['men'];
            $this->buildMenu($ul);
        }
        return $DivCnt->Get();
    }

    public  function javascriptFunction()
    {
       //$jsfnc = 'function menu_init(){'.PHP_EOL;
       $jsfnc = '';
       foreach ($this->jsFunction as $k => $c) {
          //$jsfnc .= $c.PHP_EOL;
          $jsfnc .= "osymenu.application_func['$k'] = function(){".PHP_EOL;
          $jsfnc .= $c.PHP_EOL;
          $jsfnc .= "}".PHP_EOL;
       }
       //$jsfnc .= '}'.PHP_EOL;
       return $jsfnc;
    }

    public  function getCurrentUser()
    {
        return $this->model->dbo->exec_unique(
            "SELECT ifnull(o_lbl,o_nam) 
             FROM osy_obj 
             WHERE o_id = ?",
             array($this->request->get('input._uid'))
        );
    }

    public  function getFormIconify()
    {
        $usr = $this->getCurrentUser();
        $sql = "SELECT f.o_id as form_id,f.o_nam as form_name,ic.p_vl as icon,coalesce(fw.p_vl,'640') as form_width,coalesce(fh.p_vl,'480') as form_height "
             . "FROM osy_obj_rel a "
             . "INNER JOIN osy_obj f ON (a.o_2 = f.o_own AND f.o_typ = 'form') "
             . "INNER JOIN osy_obj_prp i ON (f.o_id = i.o_id AND i.p_id = 'iconify' AND i.p_vl = '1') "
             . "LEFT JOIN osy_obj_prp ic ON (f.o_id = ic.o_id AND ic.p_id = 'iconify-icon') "
             . "LEFT JOIN osy_obj_prp fw ON (f.o_id = fw.o_id AND fw.p_id = 'width') "
             . "LEFT JOIN osy_obj_prp fh ON (f.o_id = fh.o_id AND fh.p_id = 'height') "
             . "WHERE a.o_1 = CONCAT('instance://',?,'/') AND a.r_typ = 'instance+application'";
        //echo $sql;
        $res = $this->model->dbo->exec_query($sql,array($this->request->get('instance.id')));
        $iconify = new Tag('span');
        $iconify->add(print_r($this->request->get('input'),true));
        if (!empty($res)){
            $iconify = new Tag('ul');
            foreach($res as $rec){
                $item = new Tag('span');
                $item->att('id',$rec['form_name'])
                     ->att('data-fid',$rec['form_id'])
                     ->att('data-form-width',$rec['form_width'])
                     ->att('data-form-height',$rec['form_height'])
                     ->add(str_replace('CURRENT_USER',$usr,$rec['icon']));
                $iconify->add(tag::create('li'))->add($item);
            }

        }
        return $iconify->att('id','iconify-forms')
                       ->att('style','display:none;');
    }

    
}
