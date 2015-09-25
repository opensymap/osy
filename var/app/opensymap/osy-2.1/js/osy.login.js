if (window.top == window.self){ window.location = '../../../../../'; }
//console.log(window.frameElement);
var osywindow = null;

osy_login = 
{
    init : function()
    {
        document.getElementById('instance-html').innerHTML = osywindow.get('env').get('instance-html');
        document.getElementById('iid').value = osywindow.get('env').get('instance');
        setTimeout("osy_login.hidden_error();",10000);
    },
    hidden_error : function()
    {
       var d = document.getElementById('ErrMsg');
       if (d) d.style.visibility = 'hidden';
    }
}
function oninit()
{
    osywindow = window.frameElement.win;
    osy_login.init();    
}

if (window.addEventListener){ window.addEventListener('load',oninit);} else { window.attachEvent("onload", oninit);}
