/*
 +-----------------------------------------------------------------------+
 | js/osy.view.det.js                                                    |
 |                                                                       |
 | This file is part of the Gestional Framework                          |
 | Copyright (C) 2005-2014, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <p.celeste@opensymap.org>                      |
 |-----------------------------------------------------------------------|
 | Description : View manager                                            |
 |-----------------------------------------------------------------------|
 | Creation date : 2013-10-01                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/
if (window.top == window.self){ window.location = '../../../../../'; }
var osywindow = window.frameElement.win;

osycommand = 
{
    'apply' : function(self,resp)
    {
        for (i in resp)
        {
            if (i in osycommand)
            {
                osycommand[i](self,resp[i]);
            }
        }
    },
    'self'  : null,
    'message' : function (self,msg)
    {
        alert(msg);
    },
    'code'  : function(self,code)
    {
        f = new Function('self',code);
        f.apply(null,[self]);
    },    
    'is_numeric' : function(txt)
    {
       if (!txt) return false;
       var valid_chars = "0123456789.";
       var is_number=true;
       var Char;    
       for (i = 0; i < txt.length && IsNumber == true; i++)
       { 
            Char = txt.charAt(i); 
            if (valid_chars.indexOf(Char) == -1)
            {
                is_number = false;
            }
       }
       return is_number;   
    },
    'is_time' : function (sText)
    {
       var HPart = sText.split(':');
       switch(HPart.length)
       {
           case 1:
                   HPart[1] = '00';
                   break;
           case 2: 
                   break;
           default:
                    return false;
                    break;
       }
       if(!this.is_numeric(HPart[0])) return false;  
       if(!this.is_numeric(HPart[1])) return false;  
       if(HPart[1]>59 || HPart[1]<0) return false;
       return true;   
    },
    'is_hour' : function(sText)
    {
       var HPart = sText.split(':');
       switch(HPart.length)
       {
           case 1:
                   HPart[1] = '00';
                   break;
           case 2: 
                   break;
           default:
                    return false;
                    break;
        }
       if (HPart.length != 2) return false;
       if(!this.is_numeric(HPart[0])) return false;
       if(HPart[0]>23 || HPart[0]<0) return false;
       if(!this.is_numeric(HPart[1])) return false;
       if(HPart[1]>59 || HPart[1]<0) return false;
       return true;   
    },
    'open_window' : function (fid,pid,wdt,hgt)
    {
        dbg = osywindow.get('debug') ? 1 : '';
        url = osywindow.get('env').get('core-root') + 'osy.view.det.php?fid='+fid+'&sid='+$('#osy\\[sid\\]').val()+'&'+pid;     
        osywindow.open_child(url,fid,wdt,hgt,dbg);
    },
    'setpkey' : function(name,val){
        $(Frm).append('<input type="hidden" name="pkey[' + name + ']" value="' + val + '" class="req-reinit">');
    },
    'setvalue' : function()
    {
    }
}

osyview = 
{    
    'component-init' : Array(),
    'alert' : function(msg)
    {
        this.wait_mask('remove');
        $('<div id="alert" title="Alert" style="z-index: 100;">'+msg.replace('\n','<br>')+'</div>').dialog(
        {
         autoOpen: true,
         modal   : true,
         buttons : 
         {
		    Ok : function() 
            {
               $( this ).dialog( "close" );
            }
		 }
        });
    },
    'caller'  : null,
    'close'   : function() { osywindow.close(); },
    'confirm' : function(msg,fnc)
    {
        this.wait_mask('remove');
        $('<div id="alert" title="Conferma" style="z-index: 100;">'+msg.replace('\n','<br>')+'</div>').dialog({
         autoOpen: true,
         modal   : true,
         buttons : 
         [
		   { 
             text  : 'Ok',
             click : function()
             {
               $( this ).dialog( "close" );
               fnc();
             }
           },
           {
            text : 'Annulla',
            click :  function()
            {
                   $( this ).dialog( "close" );
            }
           }
		 ]
        });
    },
    'delete' : function()
    {
         //Primo di effettuare il post ne chiedo conferma.
         osyview.confirm("Attenzione stai per eliminare il  corrente "+document.title+".\n\nSei sicuro?",function()
         {         
            var f = $('form#osy-form');
                f.append('<input type="hidden" name="CMD" value="DELETE">')
                 .attr('action','osy.control.php')
                 .attr('target','msgbox')
                 .submit();
             f.attr('action','').attr('target','');
         });

    },
    'download' : function(o)
    {
         f = $(o).closest('form');
         f.attr('action','osy.download.php').attr('target','_new');
         f.submit();
         f.attr('action','').attr('target','');
         return false;
    },
    'form'      : $('form#osy-form'),
    'eventpush' : function (obj, event, handler) 
    {
        if (obj.addEventListener) {
            obj.addEventListener(event, handler, false);
        } else if (obj.attachEvent) {
            obj.attachEvent('on'+event, handler);
        }
    },
    'exec' : function(obj,trg)
    {
        $.ajax({
          type : 'post',
          context : obj,
          'dataType' : 'json',
          data : $('#osy-form').serialize() + '&ajax='+trg,
          success : function(resp)
          {
            osycommand.apply(this,resp);
          }
        });
    },
    'init' : function()
    {
        if (osywindow) osywindow.set_title(document.title);
        if (osywindow) osywindow.set_status($('form#osy-form').attr('status'));
        if (osywindow) osywindow.set_status($('#microtime').text());
        $('#microtime').remove();
        //Workaround for IE. Without this istruction don't accept focus on textbox   
        window.focus();         
        if (osywindow && osywindow.get('debug')!='')
        {
           $('label:not(.normal,.multibox)').hover(function(){$(this).addClass('debug');},function(){$(this).removeClass('debug')}).click(function(){
              var pk='&pkey[o_id]='+$('#osy\\[fid\\]').val();
                  pk += $(this).is(':last-child') ? $(this).parent().next().children(':first').attr('id') : $(this).next().attr('id');
                  pk += '/';
                  osywindow.open_child(osywindow.get('debug').replace('form-manager','field-manager')+pk,'Debug',640,480);
           });
        }
        osyview.init_autocomplete();
        osyview.init_checkbox();
        //Inizializzazione button;
        osyview.init_button();
        //Inizializzazione oggetto mappa;
        osyview.init_map();
        
        //Inizializzazione timebox;
        osyview.init_timebox();
        //Inizializzo il message box
        //$('#msgbox').ready(function(){
        for (c = 0; c < this['component-init'].length; c++)
        {
            this['component-init'][c]();
        }
        setTimeout(function()
        {
            $('#msgbox').bind('load',function(){ osyview.save_after(); });
        },500);
        if ($('div#error').length > 0)
        {
            this.alert($('div#error').html());
        }
        //Inizializzazione tabs;
        osyview.init_tab();
        //});             
    },
    'init_autocomplete' : function()
    {               
        //$('.autocomplete').osy_autocomplete();               
    },
    'init_button'  : function()
    {        
        $('#cmd_close').click(function(){ osywindow.close(); });
        $('#cmd_delete').click(function(){ osyview.delete(); });
        $('#cmd_save').click(function(){ if ($(this).attr('next') == undefined){ osyview.save(); } else { osyview.save('next',$(this).attr('next')); } });
        $('#cmd_prev').click(function(){ osyview.prev(); });
        $('#cmd_next').click(function(){ osyview.save('next',$(this).attr('next')); });
        $("input:button,button").button();
    },
    'init_checkbox'  : function()
    {   
        $('input.osy-check').click(function(){
            val = $(this).is(':checked') ? 1 : 0;
            $(this).prev().val(val);
        });    
        //Inizializzazione checkbox
        $("input:checkbox.osy").each(function()
        {
            if ($(this).attr('onchange'))
            {
                $(this).prev().attr('onchange',$(this).attr('onchange'));
                $(this).removeAttr('onchange');
            }
            $(this).change(function()
            {
                var v = $(this).prop('checked') ? '1' : '0';
                $(this).prev().val(v);
                $(this).prev().change();
            });
        });
    },
    'init_map'     : function()
    {
        if ($('.map').length == 0) return;      
    },
    'init_tab' : function()
    {
	    $('.tabs').each(function(){
	        sel = $(this).children(':first').val();
	        $(this).tabs({active : sel, collapsible : true});
	    });
	    $('.tabs').on('click','ul li a',function(){
		  $(this).closest('.tabs').children(':first').val($(this).attr('idx'));
        });    
    	$('.tabs ul').removeClass('ui-corner-all').addClass('ui-corner-top');
    },
    'init_timebox' : function()
    {
        if ($('.timebox').length == 0) return;
        $('.timebox').timepicker({
                      timeOnlyTitle: 'Seleziona l\'ora',
                      timeText: 'Orario',
                      hourText: 'Ora',
                      minuteText: 'Minuti',
              	      secondText: '???????',
                      currentText: 'Adesso',
                      closeText: 'Fatto'}); 
    },
    'map_datagrid_refresh' : function(res)
    {
        var fldhdn = new Array('_pos','html','latitude','longitude','icon');
        var bod = '';
        var hd  = '';
        var alg = '';
        for (i in res)
        {            
            bod += '<tr>';
            for(j in res[i])
            {
               if ($.inArray(j,fldhdn) == -1 && j.charAt(0)!='_')
               {
                   if (i == 0)
                   { 
                       hd += '<th>' + j.replace('!','') + '</th>'; 
                   }
                   if (j == 'id')
                   {
                       res[i][j] = '<input type="checkbox" name="chk_id[]" value="'+ res[i][j] +'">'; 
                   }
                   if (j.charAt(0) == '!')
                   {
                       alg = ' style="text-align: center"';
                   } else {
                       alg = '';
                   }
                   if (res[i][j] == null)
                   {
                       res[i][j] = '&nbsp;' 
                   }
                   bod += '<td'+alg+'>' + res[i][j] + '</td>';
              }
            }
            bod += '</tr>';
        }
		//OsyAlert(bod);
        $('#mapgrd_body').html(bod);
        $('#mapgrd_head').html('<tr>'+hd+'</tr>');
        var app = $('#mapgrd_head tr:first-child th').first();
        $.each($('#mapgrd_body tr:first-child').children(),function(){
            $(app).width($(this).width()-1);
            app = $(app).next();
        });    
    },    
    'open_detail_2' : function(obj)
    {
        obj = $(obj);
        //Appendo alla form i dati della foreign key
		if ($('input[name^="pkey["]').length == 0 && $('#cmd_save').length > 0)
        {         
            //$('#msgbox').attr('caller',dg2.attr('id'));
            osyview.caller = obj;
            osyview.save();
            return;
        }
        dg2 = obj.closest('div.datagrid-2');
        fraw = dg2.attr('oform');
        dbg = osywindow.get('debug') ? '1' : ''; 
        form_to_load = '';        		        
        $('input[name^="pkey["]').each(function()
	    {
            form_to_load += '&' + $(this).attr('name').replace('pkey','fkey') + '=' + $(this).val();
        });
        if (obj.attr('__k') != undefined)
        {
            form_to_load += '&'+obj.attr('__k');
        }
		 else if(dg2.attr('oform-insert') != undefined)
		{
            fraw = dg2.attr('oform-insert');
		}
        if (fraw)
        {
            farr = osywindow.get('env').base64.decode(fraw).split('[::]');
            form_to_load = farr[0]+'?fid='+farr[1] +'&sid='+$('#osy\\[sid\\]').val() + form_to_load;
        }               
        d = new Date();
        nam = farr[1] +'-' + d.getTime();
        $('.post-child').each(function(){
            id = $(this).attr('id');
            vl = ($(this).is('[value]') || this.nodeName == 'SELECT') ? $(this).val() : $(this).children('input').val();
            form_to_load += '&par['+this.id+']='+vl;
        });
        form_to_load += '&par[component]='+dg2.attr('id');
        osywindow.open_child(form_to_load,nam,farr[2],farr[3],dbg);
    },
    'next'    : function()
    {
        nxt = $('#msgbox').attr('next');
        if ($('#osy\\[prev\\]').length == 0)
        {
            $('form#osy-form').append('<input type="hidden" name="osy[prev]" value="'+  $('#osy\\[fid\\]').val() +'">');
        }
         else
        {
            $('#osy\\[prev\\]').val($('#osy\\[fid\\]').val());
        }
        $('#osy\\[fid\\]').val(nxt);
        $('form#osy-form').submit();
    },
    'prev'    : function()
    {         
         val = $('#cmd_prev').attr('previous'); 
         $('#osy\\[fid\\]').val(val);
         $('#osy\\[prev\\]').remove();
         $('form#osy-form').submit();
    },
    'print'   : function(frm)
    {
        var CForm       = document.forms[0]; 
        osywindow.open_child('','Stampa '+$('#osy\\[fid\\]').val(),640,480);    
        CForm.target = 'Stampa '+$('#osy\\[fid\\]').val();
        CForm.action = frm;
        CForm.submit();
        CForm.target = '';
        CForm.action = '';
    },
    'refresh' : function()
    {
        document.forms[0].submit();
    },
    'reinit' : function()
    {
        $('.datagrid-2').each(function(){
            a = $(this).scrollTop();
            if (a > 0)
            {
                osywindow.param($('this').attr('id') +'-scrolltop',a);
                //console.log(osywindow.param($('this').attr('id') + '-scrolltop'));
            }
        });
        f = $('<form id="reinit" method="post"></form>');
        $('.req-reinit').each(function(){
            f.append('<input type="hidden" name="' + $(this).attr('name') +'" value="' + $(this).val() + '">');
        });
        $('body').append(f);
        $('form#osy-form').remove();
        $('form#reinit').submit();        
    },
    'save' : function()
    {        
        err = '';
        $('input.is-request,select.is-request,textarea.is-request').each(function()
        {
            if ($(this).val() == '')
            {
                err += '- Il campo <b>' + $(this).attr('label') +'</b> non e\' valorizzato<br>';
            }
        });
        if (err!='')
        {
            osyview.alert('Si sono verificati i seguenti errori: <br><br>'+err+'<br> Impossibile proseguire.');
            return;
        }
        mbox = $('#msgbox');
        if (arguments.length > 0) { mbox.attr('exit',arguments[0]); }
        if (arguments.length > 1) { mbox.attr('next',arguments[1]); }
        wait_msg = ' '; //(arguments.length > 1) ? arguments[1] : '';
        this.wait_mask('open',wait_msg);
        frm = $('form#osy-form');
        frm.attr('action','osy.control.php').attr('target','msgbox');
        frm.submit();
        frm.attr('action','').attr('target','');
    },
    'save_after' : function()
    {
        var Frm = document.forms[0];
        var MsgBox = arguments.length == 0 ? $('#msgbox').contents() : $(arguments[0]);
        //console.log(MsgBox.text());
        //Se il tag con id err dell'iframe non è vuoto visualizzo l'errore
    	if (MsgBox.find('error').length > 0)
        {
            err = false;
            MsgBox.find('error').each(function()
            {
                $(this).text().trim();
                if ($(this).text().trim() != '')
                {             
                    err = true;
                    osyview.alert('<pre>'+$(this).text()+'</pre>');
                }
            });
            if (err)
            {
                osyview.caller = null;
                return;
            }
        }        
        if (MsgBox.find('resp').length > 0)
        {            
            msg = '';
            MsgBox.find('resp').children().each(function()
            {
                switch($(this)[0].tagName)
                {
                    case 'pkey':
                                 
                                 break;
                    case 'setvalue':
                                 if ($(this).attr('name') != '')
                                 {
                                     if ($('#' + $(this).attr('name')).length > 0)
                                     {
                                         $('#' + $(this).attr('name')).val( $(this).html() );
                                     }
                                  }
                                  break;
                    case 'message':
                                     msg += $(this).html() + '<br>\n';
                                     break;            
                }
            });
            if (msg != '')
            {
                osyview.confirm(msg,function(){
                       act = $('#msgbox').attr('exit');               
                       if (act == undefined) return;
                       osyview[act]();
                });
                return;
            }            
        }
         else 
        {
            osyview.alert('[Warning] - Si e\' verificato il seguente errore imprevisto:\n\n' + MsgBox.text());
            osyview.caller = null;
            return;
        }	
        	    
        if (osyview.caller)
        {               
            osywindow.parent_refresh();
            if ($(osyview.caller).closest('datagrid-2'))
            {                          
                osyview.open_detail_2(osyview.caller,null);
            } 
            osyview.caller = null;
            //osyview.reinit();
    		return;
        } 
       
    	 //Nel caso non ci siano errori refresh del parent e chiusura della finestra;
        osywindow.parent_refresh()
        switch($('#msgbox').attr('exit'))
        {
            case 'RSH':
                           osyview.alert('<pre>Salvataggio avvenuto correttamente</pre>');
                           break;                
            case 'next'   :
                           osyview.next();
                           break;
            case 'refresh':
                           osyview.refresh();
                           break;
            case 'reinit' :
                           osyview.refresh();
                           break;
            default:
                          if ($('#msgbox').attr('debug') == 'off')
                          {
                            osywindow.close();
                          }
                          break;
        } 
        /*} 
         else 
        {
            Frm.submit();
        }*/
    },
    'wait_mask' : function(cmd,msg)
    {
        switch(cmd)
        {
            case 'open' :
                            h = $(document).height();
                            w = $(document).width();
                            d = $('<div id="wait" class="wait"><div class="message"><img src="../img/48.wait.gif"><br>' + msg + '</div></div>');
                            d.width(w).height(h);
                            $('body').append(d);
                            break;
            case 'remove' :       
                            $('#wait').remove();
                            break;
        }
    }
}


$(document).ready(function()
{  
     osyview.init();	    
     $('.datagrid-2').each(function()
     {
        $(this).scrollTop(osywindow.get($('this').attr('id') + '-scrolltop'));
     });
});




