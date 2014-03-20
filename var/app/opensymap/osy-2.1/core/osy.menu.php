<?
/*
 +-----------------------------------------------------------------------+
 | OpenSymap - Sistema per la gestione di applicazioni modulari          |
 | Version 1.0                                                           |
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
include('../lib/l.chk.acc.php');
include('../lib/c.tag.php');
class Menu
{
    private $App = array();
    private $__js = array();
    
    public function __construct()
	{
        $this->Sid = $_REQUEST['osy']['sid'];
        $this->__load_app();
    }
    
    private function __load_app()
	{
       $rs = env::$dbo->exec_query("SELECT a.o_id   AS id,
                                           a.o_lbl  AS tit,
                                           iaur.o_4 AS rul,
                                           1        AS dev,
                                           case
                                                when iaur.p_1 is null 
                                                then '10'
                                                else iaur.p_1
                                           end   AS lng,
                                           p.p_vl as js
                                    FROM osy_obj_rel       ia
                                    INNER JOIN osy_obj_rel iaur  ON (iaur.o_1 = ia.o_1)
                                    INNER JOIN osy_obj     a     ON (iaur.o_2 = a.o_id)
                                    LEFT JOIN  osy_obj_prp  p     ON (a.o_id = p.o_id AND p.p_id = 'onload-menu')
                                    WHERE ia.r_typ = 'instance+application' 
                                      AND iaur.r_typ = 'user+role'
                                      AND a.o_typ = 'app'
                                      AND iaur.o_1 = ? 
                                      AND iaur.o_3 = ?",array('instance://'.env::$iid.'/',trim(env::$uid)),'ASSOC');
       foreach($rs as $rec)
       {
            $this->App[$rec['id']]['prp'] = $rec;
            $this->App[$rec['id']]['men'] = $this->__load_men($rec['id'],$rec['rul'],$rec['lng'],$rec['dev']);
            if (!empty($rec['js'])) { $this->__js[$rec['id']] = $rec['js']; }
       }
    }
    
    private function __load_men($a,$r,$l,$d=0)
	{
        $cast = '';
        switch(env::$dbo->get_type())
        {
            case 'pgsql':
                           $cast = '::integer';
                           break;
        }
        $rs = env::$dbo->exec_query("SELECT men.o_id  AS id,
                                            men.o_par AS par,
                                            case
                                                 when lng.p_vl is not null then lng.p_vl
                                                 else men.o_lbl
                                            end AS ttl,
                                            mic.p_vl  AS ico,
                                            men.o_own AS app,
                                            mpr.p_vl  AS frm,
                                            fre.p1    AS pag_man,
                                            mfp.p_vl  AS lnk,
                                            case
                                                 when fwd.p_vl is not null then fwd.p_vl
                                                 when mwd.p_vl is not null then mwd.p_vl
                                                 else '640'
                                            end AS w, 
                                            case
                                                when fhg.p_vl is not null then fhg.p_vl
                                                when mhg.p_vl is not null then mhg.p_vl
                                                else '480'
                                            end AS h
                                    FROM  osy_obj          men 
                                    INNER JOIN osy_obj_rel rul ON (men.o_id = rul.o_1) -- ruoli
                                    LEFT JOIN osy_obj_prp  lng ON (men.o_id = lng.o_id AND lng.p_id = 'language' AND lng.p_ord = $l)
                                    LEFT JOIN osy_obj_prp  mic ON (men.o_id = mic.o_id AND mic.p_id = 'url-icone')
                                    LEFT JOIN osy_obj_prp  mpr ON (men.o_id = mpr.o_id AND mpr.p_id = 'osy-form')
                                    LEFT JOIN osy_obj_prp  mfp ON (men.o_id = mfp.o_id AND mfp.p_id = 'url-fisic-page')
                                    LEFT JOIN osy_obj_prp  mor ON (men.o_id = mor.o_id AND mor.p_id = 'order')
                                    LEFT JOIN osy_obj      frm ON (mpr.p_vl = frm.o_id)
                                    LEFT JOIN osy_res      fre ON (frm.o_sty = fre.v_id AND fre.k_id = 'osy-object-subtype')
                                    LEFT JOIN osy_obj_prp  fwd ON (frm.o_id = fwd.o_id AND fwd.p_id = 'width')
                                    LEFT JOIN osy_obj_prp  fhg ON (frm.o_id = fhg.o_id AND fhg.p_id = 'height')
                                    LEFT JOIN osy_obj_prp  mwd ON (men.o_id = mwd.o_id AND mwd.p_id = 'window-width')
                                    LEFT JOIN osy_obj_prp  mhg ON (men.o_id = mhg.o_id AND mhg.p_id = 'window-height')
                                    WHERE men.o_typ = 'menu' AND
                                          men.o_own = ?      AND
                                          rul.r_typ = 'object+role' AND
                                          rul.o_2   = ?
                                    ORDER BY coalesce(mor.p_vl{$cast},999)+1,men.o_lbl",array($a,$r),'ASSOC');
         $m = array();
         foreach($rs as $r)
         {
            $m[$r['id']] = $r;
            $m[$r['id']]['devel'] = $d;
            $m[$r['id']]['lang'] = $l;
         }
         return $m;
    }
    
    //Funzione ricorsiva che costruisce il menu
    private function __build(&$cnt,$AMen,$p=null)
    {
        foreach($AMen as $KMen => $Men)
        {
            if ($p == $Men['par'])
            {
              $Dt = $cnt->Add(new Tag('dt'));
              $Fnz ='';
              if (!empty($Men['lnk'])) //Se non è vuoto il campo link
              {
                  $Fnz = "{$Men['lnk']}?aid={$Men['app']}&sid={$this->Sid}";
                  $Fnz2 = '';
              }
               elseif(!empty($Men['frm']))
              {
                  $Fnz = OSY_WEB_ROOT."{$Men['pag_man']}?aid={$Men['app']}&fid={$Men['frm']}&sid={$this->Sid}&lid={$Men['lang']}";
                   $Fnz2 = "[view={$Men['pag_man']}][aid={$Men['app']}][fid={$Men['frm']}][sid={$this->Sid}][lid={$Men['lang']}]";
              }
              //Se la variabile funzione e' vuota
              if (empty($Fnz))
              {
                  $Dl = new Tag('dl');
                  $this->__build($Dl,$AMen,$KMen);
                  $Ico = "../img/ico.folder.gif";
                  if ($Dl->is_empty())
                  {
                      $Dt->Add(new Tag('img'))
                         ->Att('src','../img/ico.alert.gif')
                         ->Att('align','absmiddle')
                         ->Add(" {$Men['ttl']}");
                      continue;
                  }
                  $Dt->Att('onclick',"MenuTurn(this)");
                  $Dd = $cnt->Add(new Tag('dd'));
                  $Dd->Att('style','display: none')
                     ->Att('onselectstart','return false;')
                     ->Add($Dl);
              } 
               else 
              {
                $Ico = (!empty($Men['ico']) ? '../../../../../'.$Men['ico'] : '../img/no.ico.gif');
                $Dt->Att('onclick',"osywindow.get('env').window_open('{$Fnz}','{$Men['ttl']}','{$Men['w']}','{$Men['h']}','{$Men['devel']}','{$Men['lang']}');");
                if ($Fnz2)
                {
                    $VwU = base64_encode("{$Fnz2}[title={$Men['ttl']}][width={$Men['w']}][height={$Men['h']}][lang={$Men['lang']}]");
                    $Dt->att('__v',$VwU)->att('class','osy-std');
                }
              }
              $Dt->Att('onselectstart','return false')
                 ->Att('onmousedown','event.preventDefault&&event.preventDefault()')
                 ->Add(new Tag('img'))
                 ->Att('src',$Ico)
                 ->Att('align','absmiddle');
              $Dt->Add(" {$Men['ttl']}");
            }
        }
    }

    public function Show(){
    
        $DivCnt = new Tag('div');
        $DivCnt->Att('id','OsyMenuContainer');
        $DisplaySubmenu = (count($this->App) > 1) ? "display:none;" : "";
        foreach($this->App as $k => $app)
        { 
            $DivApp = $DivCnt->Add(new Tag('div'));
            $DivApp->Att('class','OsyMenu')
                   ->Att('onclick','MenuTurn(this)')
                   ->Att('onselectstart','return false')
                   ->Att('onmousedown','event.preventDefault&&event.preventDefault();')
                   ->Add(new Tag('p'))->Add($app['prp']['tit']);
            $DivSub = $DivCnt->Add(new Tag('div'));
            $DivSub->Att('class','OsySubMenu')
                   ->Att('style',$DisplaySubmenu)
                   ->Att('onselectstart','return false')
                   ->Att('onmousedown','event.preventDefault&&event.preventDefault();');
            $DlCnt = $DivSub->Add(new Tag('dl'));
            $this->__build($DlCnt,$app['men']);
        }
        return $DivCnt->Get();
    }
    
    public function jsfnc()
    {
       $jsfnc = 'function menu_init(){'.PHP_EOL;
       foreach($this->__js as $k => $c)
       {
          $jsfnc .= $c.PHP_EOL;
       }
       $jsfnc .= '}'.PHP_EOL;
       return $jsfnc;
    }
}
$Menu = new Menu(); 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<title>Menu</title>
</head>
<script language="JavaScript" src="/lib/jquery/jquery-1.10.2.min.js"></script>
<script language="JavaScript" src="../js/osy.menu.js"></script>
<script>
<?php echo $Menu->jsfnc();?>
</script>
<link rel="stylesheet" href="<?php echo OSY_WEB_ROOT;?>/css/style.css" />
<body style="margin: 0px; padding: 0px; background-color: #ceddef;">
<?=$Menu->Show()?>
</body>
</html>
