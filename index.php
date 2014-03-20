<?
//Carico la pagina che si preoccupa del controllo accesso;
require_once('var/app/opensymap/osy-2.1/lib/l.env.php');
env::init(false);
list(env::$iid,$i_ttl,) = env::$dbo->exec_unique("SELECT i.o_nam as o_nam,i.o_lbl as o_lbl,1
                                                 FROM osy_obj i 
                                                 LEFT JOIN osy_obj_prp p ON (i.o_id = p.o_id AND p.p_id = 'url-alias') 
                                                 WHERE i.o_id = ?
                                                   OR  p.p_vl = ?
                                                 UNION
                                                 SELECT o_nam,o_lbl,2
                                                 FROM osy_obj
                                                 WHERE o_id = ?
                                                 ORDER BY 3",array('instance://'.env::$iid.'/','%['.env::$iid.']%','instance://default/'));
if (empty($i_ttl)){ env::page_error('400',"Instance do not exists"); }
?>
<!DOCTYPE html>

<html>

<head>
    <title><?php echo strip_tags($i_ttl); ?></title>
    <link rel="stylesheet" href="<?php echo OSY_WEB_ROOT; ?>/css/dsk.css"/>
    <script language="JavaScript" src="/lib/jquery/jquery-1.10.2.min.js"></script>
    <script language="JavaScript" src="<?php echo OSY_WEB_ROOT; ?>/js/osy.desktop.js"></script>
    <script language="JavaScript" src="<?php echo OSY_WEB_ROOT; ?>/js/osy.window.js"></script>
    <script>
    $(document).ready(function(){   
    	osy.set('core-root','<?php echo OSY_WEB_ROOT; ?>/core/')
           .set('instance','<?php echo env::$iid; ?>')
           .set('instance-html','<?php echo $i_ttl; ?>')
           .init();    
    });
    </script>
</head>

<body></body>

</html>