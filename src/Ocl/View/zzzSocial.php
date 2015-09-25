<?php
  require_once('home.post.lib.vw.php');
  if (!empty($_POST['ajax'])){
    ob_clean();
if (!empty($_POST['pst']))
{
    $_POST['tid'] = ($_POST['ajax'] == 'comment') ? 2 : 1;
    Site::$Db->ExecQuery('SET NAMES utf8');
    Site::$Db->ExecQuery2('INSERT INTO tbl_pst 
                             (id_typ,id_usr,id_par,dat_ins,cnt)
                          VALUES
                             (?,?,?,NOW(),?)',array($_POST['tid'],UID,$_POST['pid'],$_POST['pst']));
    $ObjID = Site::$Db->GetLastId();
}

switch($_POST['ajax']) {
    case 'post':
                       die('POST_OK[;]');
                       break;
    case 'comment':
                       $strSQL = "SELECT p.cnt as cmt_msg,
                                         a.nik_nam as cmt_usr,
                                         a.fld_6   as cmt_img,
                                         get_time_stamp(p.dat_ins) as cmt_tim,
                                         DATE_FORMAT(dat_ins,'%d %M %Y %H:%i') as cmt_dat
                                  FROM   tbl_pst p
                                  INNER JOIN tbl_ana a ON (p.id_usr = a.id)
                                  WHERE  p.id = '{$ObjID}'";
                        list($cmt) = Site::$Db->GetAll($strSQL);
                        echo 'COMMENT_OK[;]';
                        PrintComment($cmt);
                        break;
    case 'get_notify':
                        PrintPostList(UID,$_POST['pid']);
                        break;
    case 'scroll_down':
                        PrintPostList(UID,$_POST['pid'],$_POST['npst']);
                        break;
    case 'get_webpage':
                        echo GetWebPage($_POST['url']);
                        break;
}
exit;
  }
?>
<script>
    var StopUpdate = false;
    function Post(obj){
        var tid = 'post';
        var pid = '';
        switch($(obj).attr('class')){
            case 'comment': 
                            tid = 'comment';
                            pid = $(obj).closest('div.cnt').attr('pid');
                            break;
        }
        $.ajax({
                context: obj,
                type: 'post',
                data: 'ajax='+tid+
                      '&pid='+pid+
                      '&pst=' + $(obj).val(),
                success : function (dat){
                    var dat = dat.split('[;]');
                    if (dat[0] == 'POST_OK'){
                        document.location.reload();
                    } else if (dat[0] == 'COMMENT_OK'){
                        $(this).parent().before(dat[1]).show('slow');
                        $(this).val('');
                    } else {
                        alert(dat);
                    }
                }
              });
    }
    function GetNotify(){
        var pid  = $('ul.notify').children(':first-child').find('div.cnt').attr('pid');
        if ($.type(undefined) === 'undefined') return;
        $.ajax({
            type : 'POST',
            data : 'ajax=get_notify&pid='+pid,
            success : function (dat){
                if (dat!=''){
                    $('ul.notify').prepend(dat).show('slow');
                }
            }
        });
    }
    $(document).ready(function (){
        $('.comment').keypress(
          function (e){             
              if (e.keyCode == 13){
                  Post(this);
                  e.preventDefault();
              }
        });
        $('#btn_pst').hide();
        $('#txt_pst').focus(function(){
            $(this).attr('rows',5);
            $('#btn_pst').show();
        });
        $(window).scroll(function (){
            var s = $(window).scrollTop();
            var d = $(document).height();
            var c = $(window).height();
            var sp = (s / (d-c)) * 100;
            if (sp>= 90 && !StopUpdate){
                StopUpdate = true;
                $.ajax({type : 'POST',
                       data : 'ajax=scroll_down&pid=&npst=' + $('ul.notify li').length,
                       success : function (dat){
                                      if (dat!=''){
                                          $('ul.notify').append(dat).show('slow');
                                      }
                                      StopUpdate = false;
                                 }
                       });
            }
            //$('#dbg').html(sp);
        });
        setInterval('GetNotify()',10000);
        $('#txt_pst').bind('paste',function(){
           var el = $(this);
           setTimeout(function() {
                        var v = $(el).val();
                        if (v.length > 8 && (v.indexOf('http://') == 0 || v.indexOf('https://') == 0)){
                           $('#prova').attr('src',v);
                           $.ajax({
                            type : 'post',
                            data : 'ajax=get_webpage&url='+v,
                            success : function(res){
                                    $('#post_canvas').html(res).show('slow');
                            }
                           });
                        }
                      }, 100);
        });
    });
</script>
<div class="sec1">
    <div class="post">
        <span><img src="/img/ico/page_white_edit.png"> Post</span> <span><img src="/img/ico/qnn_bar.png"> Questionario</span>
        <div class="ipost">
        <textarea id="txt_pst" rows="1" class="post"></textarea>            
        <div id="post_canvas"></div>
        <input type="button" name="btn_pst" id="btn_pst" onclick="Post($('#txt_pst'))" value="Pubblica">
        </div>
    </div>
    <ul class="notify" style="border-top: 1px solid #D7D7D7">
    <?PrintPostList(UID);?>
    </ul>    
</div>
<style>
    .hdn{
        display: none;
    }
    .cmd{
        cursor: pointer;
        color:  #6B84B4;
    }
    .cmd:hover{
        text-decoration: underline;
    }
    #dbg{
        position: fixed;
        top: 5px;
        left: 10px;
    }
</style>
