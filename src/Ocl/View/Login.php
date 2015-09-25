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
namespace Opensymap\Ocl\View;

use Opensymap\User\User;

class Login extends AbstractView
{
    
    protected function build()
    {
        if ($this->request->get('post')) {
            $user = new User(
                $this->model->dbo, 
                $this->model->dba,
                $this->request
            );
            $resp = $user->login(
                $this->request->get('post.txt_username'),
                $this->request->get('post.txt_password')
            );
            if ($resp[0] == 'ok') {
                $this->openOpensymap($resp[1]);
                return;
            }
        }
        $css = $this->model->dbo->exec_unique(
            "SELECT p_vl FROM osy_obj_prp  WHERE o_id = ? AND p_id = 'css-form-path'",
            array('instance://'.$_REQUEST['iid'].'/')
        );
        $css = empty($css) ? 'css/style.css' : OSY_WEB_ROOT.'/'.$css;
        $this->response->addCss($css);
        $this->response->addCss('css/login.css');
        $this->response->addJsFile('js/view/Login.js');
        $this->response->addbody('<form method="post">
        <input id="iid" type="hidden" name="iid">
        <div id="form-container" align="center">
            <div id="login-container">
                    <div>
                        <label>Username</label> 
                        <input type="text" name="txt_username" size="20" maxlength="20">
                    </div>
                    <div>
                        <label>Password</label>
                        <input type="password" name="txt_password"  maxlength="20">
                    </div>
                '.(!empty($resp[0]) ? '<div id="ErrMsg" style="color:red; text-align:center;"><b>'.$resp[0].'</b></div>' : '').'
            <div>
        </div>  
        <div class="login-foot">
            <div id="msg_copy">Framework version : Opensymap '.OSY_VER.'</div>
            <input id="btn_login" type="submit" name="btn_login" value="Login">
        </div> 
        </form>');
    }
    
    private function openOpensymap($sessionKey)
    {
        $this->response->addJsCode("window.frameElement.win.get('env').window_menu('".$sessionKey."');"
                                  ."window.frameElement.win.close();");
    }
}
