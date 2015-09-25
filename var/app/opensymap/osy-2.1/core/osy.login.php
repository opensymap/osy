<?php
/*
 +-----------------------------------------------------------------------+
 | OpenSymap - Sistema per la gestione di applicazioni modulari          |
 | Version 0.9                                                           |
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
require('../lib/l.env.php');
require(OSY_PATH_LIB.'c.user.php');
env::init(false);
$resp = array();

if (IS_POST)
{
    $resp = osy_user::login($_REQUEST['txt_username'],$_REQUEST['txt_password']);
    
    if ($resp[0] == 'ok'){?>
        <script language="JavaScript" type="text/javascript">
           window.frameElement.win.get('env').window_menu('<?php echo $resp[1]; ?>');
           window.frameElement.win.close();
        </script><?php
        return;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
<link rel="stylesheet" href="<?php echo OSY_WEB_ROOT;?>/css/style.css">
<style>
    .head {	min-height: 60px;}
    .head > div {
        font-size: 12px;
        font-weight: bold;
        margin-top: 10px;
    }
</style> 
<script language="JavaScript" src="<?php echo OSY_WEB_ROOT;?>/js/osy.login.js"></script>  
</head>

<body style="background-color: #ceddef;">    
<form method="post">
<input id="iid" type="hidden" name="iid">
 <div align="center" style="text-align: center;">
 	<div class="head"> 
		<div id="instance-html"></div>
	</div>
    <table align="center">
    <tr>
    <td>
    <fieldset style="width: 200px;">    
    <table style="padding-top: 10px;">
        <tr>
            <td>Username</td>
            <td><input type="text" name="txt_username" size="20" maxlength="20"></td>
        </tr>
        <tr>
            <td>Password</td>
            <td><input type="password" name="txt_password"  maxlength="20"></td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center"><input type="submit" name="btn_login" value="Login"></td>                    
        </tr>        
    </table>
    </fieldset>
    </td>    
    </tr>
    </table>
    <?php if (!empty($resp[0])){?><div id="ErrMsg" style="color:red; text-align:center;"><b><?php echo $resp[0]; ?></b></div><?php }?>
    <br>    
    Tutti i diritti sono riservati.
 </div>
</form>
</body>

</html> 
