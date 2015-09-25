/*
 +-----------------------------------------------------------------------+
 | osy/js/2.osy.data.grid.js                                              |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2014, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description : Datagrid manager                                        |
 |-----------------------------------------------------------------------|
 | Creation date : 2013-12-30                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/

(function($){
    $.fn.disableSelection = function() {
        return this
                 .attr('unselectable', 'on')
                 .css('user-select', 'none')
                 .on('selectstart', false);
    };
})(jQuery);

osytree = 
{
  'treegrid-click-after' : false,
  'init' : function()
  {    
    osytree.init_button();
	osytree.parent_open();
    osytree.check_open();
    $('.datagrid-2').each(function(){
       h = $(this).height() - ($('.datagrid-2-foot',this).length > 0 ? $('.datagrid-2-foot',this).height() : 0);
       $('.datagrid-2-body',this).height(h);
    });
  },  
  'get_path_selected' : function(self)
  {
    dg2 = $(self).closest('.datagrid-2');
    pth = $('tr.sel',dg2).text();
	gid = $('tr.sel',dg2).attr('gid');        
    while(gid > 0)
	{
		pth = $('tr[oid='+gid+']').text() + ' &raquo ' + pth;
		gid = $('tr[oid='+gid+']').attr('gid');
	}
    return pth.replace(/\u00a0/g,'');
  },
  'init_button' : function()
  {
  	$('.datagrid-2').on('click','.cmd-add',function(event)
    {
        osyview.open_detail_2($(this).closest('.datagrid-2'));     
    });
    $('.datagrid-2').on('click','input[name=btn_pag]',function(){
        obj = $(this).closest('div.datagrid-2');
        par = 'btn_pag='+$(this).val();
        osytree.refresh_ajax(obj,par);
    });
    $('span.tree').click(function (event)
	{
		event.stopPropagation();
		tr = $(this).closest('tr');
		dt = $(this).closest('div.datagrid-2');
		obj_opn = $('input[name='+ dt.attr('id') + '_open]');
		val_opn = obj_opn.val();		
		gid = tr.attr('oid');
		if ($(this).hasClass('minus'))
		{
			obj_opn.val(val_opn.replace('['+gid+']',''));
			osytree.branch_close(gid);
			tr.attr('__state','close');
		}
	 	 else
		{			
			obj_opn.val(val_opn+'['+gid+']');
			osytree.branch_open(gid);	
			tr.attr('__state','open');		
		} 
		$(this).toggleClass('minus');
	});
  },
  'get_tree_id' : function(tree,gid)
  {
    val = '';
    $('tr[gid="'+gid+'"]',tree).each(function(){
        oid  = Base64.decode($(this).attr('oid'));
        val += '['+oid+']';
        val += osytree.get_tree_id(tree,oid);
    });
    return val;
  },
  'init_row': function()
  {    
    $('div.treegrid').delegate('tbody tr','click',function()
    {
         dg2 = $(this).closest('div.treegrid');
         hdn = $('input[name='+$(dg2).attr('id')+']');
         val = '';               
         if (!$(this).hasClass('sel'))
         {                 
             oid =  osywindow.get('env').base64.decode($(this).attr('oid'));
             val = '[' + oid + ']';
             val += osytree.get_tree_id(dg2,oid);              
             $('tr.sel',dg2).removeClass('sel'); 
             $(this).addClass('sel'); 
          } 
           else
          {
             $('tr.sel',dg2).removeClass('sel'); 
          }
          hdn.val(val);
          if (osytree['treegrid-click-after'])
          {
              osytree['treegrid-click-after'](dg2);
          }
    });
    $('div.datagrid-2').not('.treegrid').delegate('tbody tr','click',function()
    {
   	    dg2 = $(this).closest('.datagrid-2');
		$('tr.sel',dg2).removeClass('sel'); 	
    	$(this).addClass('sel');
   	});
    $('div.datagrid-2').on('dblclick','tbody tr',function()
    {
        if ($(this).hasClass('__f')) return;
        if ($(this).closest('div.datagrid-2').attr('oform') == undefined) return;
      	osyview.open_detail_2(this);
    });
    $('div.datagrid-2 tbody tr td').disableSelection();
  },
  'branch_close' : function(gid)
  {
		$('tr[gid="'+gid+'"]').each(function()
		{
			osytree.branch_close($(this).attr('oid'));
			$(this).hide();
		});		
   },
   'branch_open' : function(gid)
   {
   		$('tr[gid="'+gid+'"]').each(function()
		{			
			$(this).show();
			if ($(this).attr('__state') == 'open')
			{
				osytree.branch_open($(this).attr('oid'));
			}
		});
   },
   'branch_open_2' : function(obj)
   {
        dg2 = obj.closest('.datagrid-2');
        gid = obj.attr('gid');
        $('tr[gid="'+gid+'"]').each(function()
		{	
          	$(this).show();
        });
        $('tr[oid=""'+gid+'""]').each(function()
        {
            $(this).attr('__state','open');
            $('span[class*=tree-plus]',this).addClass('minus');            
            osytree.branch_open_2($(this));            
        });        
   },
   'check_open' : function()
   {
        $('div.datagrid-2').each(function()
		{
            $('input[type=checkbox]:checked',this).each(function()
            {
                osytree.branch_open_2($(this).closest('tr'));
            });
		});
   },
   'parent_open' : function()
   {
   		$('div.datagrid-2').each(function()
		{
			did = $(this).attr('id');
            sel = $('#'+did+'_sel',this).val().split('][')[0];
            
            if (sel)
            {
                sel = sel.replace('[','').replace(']','');
                $('tr[oid="'+sel+'"]',this).addClass('sel');
            }
            obj_opn = $('input[name='+ $(this).attr('id') + '_open]');
			val_opn = obj_opn.val().split('][');
			for (i in val_opn)
			{
				gid = val_opn[i].replace('[','').replace(']','');
				$('tr[oid="'+gid+'"]').attr('__state','open');
				$('span[class*=tree-plus]','tr[oid="'+gid+'"]').addClass('minus');
				osytree.branch_open(gid);
			}
		});
   },
   'refresh_ajax' : function(obj)
   {
        dat  = $('form').serialize() + '&ajax=' + $(obj).attr('id');
        dat += (arguments.length > 1) ? '&'+arguments[1] : '';
        $.ajax({
            type : 'post',
            context : obj,
            data : dat,
            success : function(rsp)
            {
                if (rsp)
                {
                    oid = $(this).attr('id');
                    body = $(rsp).find('#'+oid).html();
                    $(this).html(body);
                    $('#'+oid+' input[name=btn_pag]').button();
                    $('#'+oid+' tbody tr td').disableSelection();
                    osytree.refresh_ajax_after();
                }
            }
        });
        
   },
   'refresh_ajax_after' : function(){
   }
}
if (osyview)
{
    osyview['component-init'].push(osytree.init_row);
    osyview['component-init'].push(osytree.init);
}
