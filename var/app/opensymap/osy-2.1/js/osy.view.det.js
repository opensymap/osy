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
var osywindow = null;

osycommand = 
{
    'after_apply_recall' : null,
    'apply' : function(self,resp,recall)
    {
        this.stay = 0; //Azzero i comandi in attesa di esecuzione.
        this.after_apply_recall = recall;
        for (i in resp)
        {
            if (i in osycommand)
            {
                for (j in resp[i])
                {                    
                    osycommand[i].apply(self,resp[i][j]);
                }
            }
        }
        if (this.stay == 0 && this.after_apply_recall)
        {
            this.after_apply_recall();
        }
    },
    'alert' : function(msg,title,func)
    {
        osycommand.stay += 1;
        title = (arguments.length > 1) ? arguments[1] : 'Alert';
        osyview.wait_mask('remove');
        $('<div id="alert" title="'+title+'" style="z-index: 100;">'+msg.replace('\n','<br>')+'</div>').dialog({
             autoOpen: true,
             modal   : true,
             buttons : { Ok : func}
        });
    },
    'error' : function(msg)
    {
        osycommand.alert(msg,'Error',function() { 
            $( this ).dialog( "close" ); 
        });
    },
    'self'  : null,
    'message' : function (msg)
    {
        osycommand.alert(msg,'Messagge',function() { 
            $( this ).dialog( "close" ); 
            osycommand.stay -= 1;
            if (osycommand.stay == 0 && osycommand.after_apply_recall)
            {
                osycommand.after_apply_recall();
            }
         });
    },    
    'code'  : function(self,code)
    {
        f = new Function('self',code);
        f.apply(null,[self]);
    }, 
    'eventpush' : function (obj, event, handler) 
    {
        if (obj.addEventListener) {
            obj.addEventListener(event, handler, false);
        } else if (obj.attachEvent) {        
            obj.attachEvent('on'+event, handler);
        }
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
        dbg = osywindow.get('sdk') ? 1 : '';
        url = osywindow.get('env').get('core-root') + 'osy.view.det.php?fid='+fid+'&sid='+$('#osy\\[sid\\]').val()+'&'+pid;     
        osywindow.open_child(url,fid,wdt,hgt,dbg);
    },
    'setpkey' : function(name,value)
    {
        $('form#osy-form').append('<input type="hidden" name="pkey[' + name + ']" value="' + value + '" class="req-reinit">');
    },
    'setvalue' : function(oid,value)
    {
        if ($('#'+oid).length > 0){  $('#'+oid).val( value ); }
    },
    'stay' : 0
}

osyview = 
{    
    'repo'    : {},
    'set'     : function(k,v){
        this.repo[k] = v;
        return this;
    },
    'get'     : function(k)
    {
        return (k in this.repo) ? this.repo[k] : null;
    },
    'component-init' : Array(),    
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
    'xdelete' : function()
    {
         //Primo di effettuare il post ne chiedo conferma.
         osyview.confirm("Attenzione stai per eliminare il  corrente "+document.title+".\n\nSei sicuro?",function()
         {         
             $('form#osy-form').append('<input type="hidden" name="CMD" value="DELETE">');
             $.ajax({
                type : 'post',
                url  : 'osy.control.php',
                data : $('form#osy-form').serialize(),
                success : function(){
                    osyview.close();
                },
                error : function (xhr, ajaxOptions, thrownError){
                    osyview.wait_mask('remove');
                    osycommand.error(xhr.responseText);
                   console.log(thrownError);
               }
             });
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
    'exec' : function(obj,trg)
    {
        $.ajax({
          type : 'post',
          context : obj,
          'dataType' : 'json',
          data : $('#osy-form').serialize() + '&ajax='+trg,
          success : function(resp)
          {
            console.log(resp);
            osycommand.apply(this,resp);
          },
          error :  function (xhr, ajaxOptions, thrownError){
               osyview.wait_mask('remove');
               osycommand.error(xhr.responseText);
               console.log(thrownError);
               console.log(xhr);
          }
        });
    },
    'init' : function()
    {
        osywindow = window.frameElement.win;
        if (osywindow) osywindow.set_title(document.title);
        if (osywindow) osywindow.set_status($('form#osy-form').attr('status'));
        if (osywindow) osywindow.set_status($('#microtime').text());
        this.set('caller',null);
        $('#microtime').remove();
        //Workaround for IE. Without this istruction don't accept focus on textbox   
        window.focus();         
        if (osywindow && osywindow.get('env').get('sdk-fm'))
        {
           $('label:not(.normal,.multibox)').hover(function(){$(this).addClass('debug');},function(){$(this).removeClass('debug')}).click(function(){
                  sdk_url  = osywindow.get('env').get('core-root');
                  sdk_url += osywindow.get('env').get('sdk-fm');
                  sdk_url += '&osy[sid]='+$('#osy\\[sid\\]').val();
                  sdk_url += '&pkey[o_id]='+$('#osy\\[fid\\]').val();                  
                  sdk_url += $(this).is(':last-child') ? $(this).parent().next().children(':first').attr('id') : $(this).next().attr('id');
                  sdk_url += '/';
                  osywindow.open_child(sdk_url,'FieldSDK',640,480);
           });
        }
        osyview.init_checkbox();
        //Inizializzazione button;
        osyview.init_button();        
        //Inizializzazione timebox;
        osyview.init_timebox();       
        for (c = 0; c < this['component-init'].length; c++)
        {
            this['component-init'][c]();
        }       
        if ($('div#error').length > 0)
        {
            osycommand.alert($('div#error').html());
        }
        //Inizializzazione tabs;
        osyview.init_tab();
    },
    'init_button'  : function()
    {        
        $('#cmd_close').click(function(){ osywindow.close(); });
        $('#cmd_delete').click(function(){ osyview.xdelete(); });
        $('#cmd_save').click(function(){ 
            switch ($(this).attr('after-exec'))
            {
                case 'next' : osyview.save('next',$(this).attr('next'));
                              break;
                default     : osyview.save($(this).attr('after-exec')); 
                              break;                            
            } 
        });
        $('#cmd_prev').click(function(){ osyview.prev(); });
        //$('#cmd_next').click(function(){ osyview.save('next',$(this).attr('next')); });
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
    'open_detail_2' : function(obj)
    {
        obj = $(obj);
        //Appendo alla form i dati della foreign key
		if ($('input[name^="pkey["]').length == 0 && $('#cmd_save').length > 0)
        {                     
            this.set('caller',obj);
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
    'nothing' : function(){
    },
    'next'    : function()
    {
        /*$('input[type=file]').each(function(){
            $(this).html($(this).html());
        });*/
        $('#osy\\[fid\\]').val(this.get('next-form'));
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
            osycommand.alert('Si sono verificati i seguenti errori: <br><br>'+err+'<br> Impossibile proseguire.');
            return;
        }
        if (arguments.length > 0) { this.set('after-exec',arguments[0]); }
        if (arguments.length > 1) { this.set('next-form',arguments[1]); }
        ajax_par = 
        {
            url : 'osy.control.php',
            type : 'post',
            dataType : 'json',            
            success : function(resp){                
                osyview.wait_mask('remove');                
                osycommand.apply(this,resp,osyview.save_after);
            },
            error :  function (xhr, ajaxOptions, thrownError){
               osyview.wait_mask('remove');
               osycommand.error(xhr.responseText);
               console.log(thrownError);
            }
        }
        if (window.FormData && $('input[type=file]').length > 0)
        {
          this.wait_mask('open','progress');      
          ajax_par['data'] = new FormData(document.getElementById('osy-form'));
          ajax_par['xhr'] = function(){  // Custom XMLHttpRequest
             var myXhr = $.ajaxSettings.xhr();
             if(myXhr.upload)
             { // Check if upload property exists
                myXhr.upload.addEventListener('progress',osyview.upload_progress, false); // For handling the progress of the upload
             }
             return myXhr;
          }
          ajax_par['mimeType'] = "multipart/form-data";
          ajax_par['contentType'] = false;
          ajax_par['cache'] = false;
          ajax_par['processData'] = false;
        }
         else //No file to upload or IE9,IE8,etc browser 
        {
          this.wait_mask('open');      
          ajax_par['data'] = $('#osy-form').serialize(); 
        }
        $.ajax(ajax_par);
    },
    'save_after' : function()
    {                
        osywindow.parent_refresh();

        if (osyview.get('caller'))
        {                           
            if ($(osyview.get('caller')).closest('datagrid-2'))
            {                          
                osyview.open_detail_2(osyview.get('caller'),null);
            } 
            osyview.set('caller',null);
    		return;
       }
       if (osyview.get('after-exec') in osyview)
       {        
           osyview[osyview.get('after-exec')]();       
       }
    },    
    'upload_progress' : function(a)
    {
        //console.log(a);
        if ($('#progress_idx').length>0)
        {
            if (console) console.log(a);
            t = Math.round((a.position / a.total) * 100);
            $('#progress_bar').css('width',t +'%');
            $('#progress_idx').text(t +'%');
        }

    },
    'wait_mask' : function(cmd,typ)
    {
        switch(cmd)
        {
            case 'open' :
                            h = $(document).height();
                            w = $(document).width();                            
                            switch(typ)
                            {
                                case 'progress':
                                                obj = '<div class="progress_msg">Upload in progress .... <span id="progress_idx">0%</span> completed</div>';
                                                obj += '<div class="progress"><div id="progress_bar" style="background-color: #ceddef; width: 0%;">&nbsp;</div></div>';
                                                break;    
                                default: 
                                         obj = '<img src="../img/48.wait.gif">';
                                         break;
                            }
                            d = $('<div id="wait" class="wait"><div class="message">'+obj+'</div></div>');
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




