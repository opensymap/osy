if (window.top == window.self){ window.location = '../../../../../'; }

var osywindow = null;
function OpenDebug(e) { osywindow.StopPropagation(e); alert('ci sono');}        
function MenuTurn(o){ $(o).next().toggle('slow') }

osymenu = 
{
    init : function()
    {
        //alert('ci sono');
        $('.osy-std').on('click',function(){
            osywindow.get('env').window_open2($(this).attr('__v'));
        });
        if (window.menu_init){ menu_init(); }
    }
}
        
$(document).ready(function()
{			

    osywindow = window.frameElement.win;           
    $('dt[startupopen=1]').each(function(){
        alert($(this).text());
    });
    osymenu.init();
});
        

