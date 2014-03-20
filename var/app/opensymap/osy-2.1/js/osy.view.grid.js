/*
 +-----------------------------------------------------------------------+
 | js/osy.view.grid.js                                                   |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description :                                                         |
 |-----------------------------------------------------------------------|
 | Creation date : 2013-10-01                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/
/*Start inserted (2008-12-26)*/
if (window.top == window.self){ window.location = '../../../../../'; }
var osywindow = window.frameElement.win; 
//End inserted

function OsyAlert(msg){
    $('#windialog').html(msg)
    $('#windialog').dialog('open');
}
       
osyview = 
{
	'component-init': Array(),
    'hgrid' : 0,
    'refresh': function()
    {
      document.forms[0].submit();
    },
    'reinit' : function()
    {
        document.forms[0].submit();
    },
    'init' : function()
	{
		window.onresize = osyview.resize;
        if (osywindow) osywindow.set_status($('form#osy-form').attr('status'));
        if (osywindow) osywindow.set_status($('#microtime').text());
        $('#microtime').remove();
		this.init_button();
		this.init_tab();	
		this.init_body();
        this.init_form();	
        this.init_field_search();
		osytree.init();	
	},
	'init_button' : function()
	{
        $("input:submit,input:button,button").button();
		$('img.insert').click(function(){ osyview.open_detail_2(this)});
		$('img.update').click(function(){ osyview.open_detail_2(this)});
	},
	'init_body' : function()
	{		
    	$('#data-view').delegate('tbody tr','click',function()
        {
            if ($('#osy\\[rid\\]').val() != $(this).attr('__k'))
            {		
            	$('tr.sel').removeClass('sel'); 	
        	    $('#osy\\[rid\\]').val($(this).attr('__k'));
         		$(this).addClass('sel');
            }		
        });
        $('#data-view').delegate('tbody tr','dblclick',function(){
            osyview.open_detail_2(this);
        });
	    $('.datagrid-2 tbody tr td').disableSelection();
        this.resize();
	},
	'init_tab' : function()
	{
		$("#tabs").tabs({active : $('#tabs').attr('tabsel')});
		$('#tabs ul').removeClass('ui-corner-all').addClass('ui-corner-top');    
    	$('#tabs ul li a').click(function()
		{            
          	  var f = document.forms[0];
              $('#osy\\[tid\\]').val($(this).attr('id'));
    	      setTimeout(function()
			  {
        	      var f = document.forms[0];            
                  f.submit();
          	  }, 500);
          
          	  return false;
    	});
	},
    'init_form' : function(){
        osytree.refresh_ajax_after = function()
        {               
            osyview.init_body();
        }
        $('#osy-form').submit(function ()
        {
            var par = $("#osy-form").serialize() + '&ajax=yes';
            $.post("2.osy.form.view.php", par).success(function(data)
            {
                var src = $(data).children('div#data-view');
                $('div#data-view').html(src.html());
                osyview.init_body();
                $("input:submit,input:button").button();
            });
            return false;
        });
    },
    'init_field_search' : function()
    {
        $('#button_show_search').click(function(){                              
            $('div.osy-dataview-2-search').toggle('fast',window.onresize);            
        });
        
        $('#btn_search').click(function()
        {
            if ($('select#search_field').val() == '')
            {
                OsyAlert('[Errore] - Non hai selezionato nessun campo in cui effettuare la ricerca');
            } 
             else if ($('input#search_value').val() == '') 
            {
                OsyAlert('[Errore] - Non hai specificato nessun valore da ricercare per il campo <b>' + $('input#search_value').val() + '</b>');
            } 
             else 
            {
                field = $('select#search_field').val();
                label = $('select#search_field option:selected').text();
                value = $('input#search_value').val()
        		filter = $('<div class="filter"><input type="hidden" name="filter['+ field +']" value="'+ value +'">'+label+' : '+value+'</div>');
                filter.click(function(){$(this).remove(); $('#osy-form').submit();});
                $('select#search_field').val('');
                $('input#search_value').val('');
                $('div.osy-dataview-2-search div.filter-active').prepend(filter);
                $('#osy-form').submit();
            }            
        });                
        //sel.append('<option value="">- select -</option>');
        $('#data-view-body table th').each(function(){
            txt = $(this).text();
            $('select#search_field').append('<option value="' + $(this).attr('real_name') + '">'+ txt +'</option>');
        });
    },    
	'open_detail_2' : function(obj)
	{
    	var recid = $(obj).hasClass('insert') ? '' : $('#osy\\[rid\\]').val();
        var attr  = ($(obj).hasClass('insert') && $('#data-view').attr('oform-insert')) ? 'oform-insert' : 'oform';
        var fraw  = $('#data-view').attr(attr);
        if (fraw==undefined || fraw==''){ alert('No form');  }
        farr = osywindow.get('env').base64.decode(fraw).split('[::]');
        /*if (osyfrm == '')
        {
            osyapp = (arguments.length > 2) ? arguments[2] : $('#data-view').attr('defapp');
          	osyfrm = (arguments.length > 1) ? arguments[1] : $('#data-view').attr('deffrm');
        }
        */
    	var str_par = '';
		var lst_par ={fid       : farr[1],
            	      sid       : $('#osy\\[sid\\]').val(),
                      lid       : $('#osy\\[lid\\]').val(),
                      debug     : 1,
                      record_id : recid,
                      height    : farr[2],
               	      width     : farr[3]};
		for (k in lst_par)
		{
       		if (!lst_par[k]) { continue; }
   	   		switch(k)
			{
            	case 'record_id':
                	             str_par += (str_par=='' ? '' : '&')+lst_par[k];
                    	         break;
            	default:    
                	             str_par += (str_par=='' ? '' : '&')+k+'='+lst_par[k];
                    	         break;
       		}
		}
        //alert(farr[0]+'?'+str_par);
    	//osywindow.open_child($('#data-view').attr('frmurl')+'?'+str_par,lst_par['fid'],lst_par['height'],lst_par['width'],lst_par['debug']);
        osywindow.open_child(farr[0]+'?'+str_par,lst_par['fid'],lst_par['height'],lst_par['width'],lst_par['debug']);
	},
	'resize' : function(){
        var grid  = $("div.datagrid-2-body");
    	var hhead = grid.offset().top;
    	var hfoot = $("div.datagrid-2-foot").height(); 
    	var hwin  = $(window).height();
    	var hgrid = (hwin - (hhead + hfoot + 3));
   		grid.height(((hgrid > 20) ? hgrid : 20));
        //console.log(hwin+' - '+hhead+' - '+hfoot + ' - ' + hgrid); 
    }
}

$(document).ready(function()
{
	osyview.init();        
    window.focus();	
    $('#windialog').dialog({autoOpen: false, modal: true, buttons: {Ok: function(){ $( this ).dialog( "close" ); } } });    		        
});
