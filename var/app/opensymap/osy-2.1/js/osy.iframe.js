oiframe = function(id,idx)
{
    var self = this;
    var repo = {};
    
    var __get = function(k){
        return repo[k];
    }
    
    var __set = function(k,v){
        repo[k] = v;
        return self;
    }
    
    var __init = function(){
        d = document.createElement('div');
        d.setAttribute('id',id);
        __set('div',d);
        i = document.createElement('iframe');
        i.setAttribute('name',id);
        i.setAttribute('idx',idx);
        i.win = self;
        d.appendChild(i);
        __set('iframe',i);
    }();
    
    
    this.get = function(arg){
        return osywindow.get(arg);
    }
    
    this.set = function(k,v){
        __set(k,v);
    }
    
    this.close = function()
    {
      o = __get('iframe');
      //t = ;
      l = $('li a[href="#'+ o.getAttribute('name') +'"]').parent();
      if (l.next().is('li')){ 
        $('a',l.next()).click(); 
      } else if(l.prev().is('li')) {
        $('a',l.prev()).click();
      }
      l.remove();
      $(o).closest('div').remove();
      $(o).closest('.tabs').tabs( "refresh" );
    }
    
    this.set_title = function(title)
    {        
        if (__get('subtitle')) title += ' - ' + __get('subtitle');
        var tabs = $(__get('div')).closest('.tabs').tabs();
        $('li a p span',tabs).each(function(){
            if ($(this).text() == '')
            {
                $(this).text(title);
                $(this).click();
                $(this).closest('li').show();
            }
        }        
        );
    },
    this.set_status = function() {};
    this.get_component = function()
    {
        return __get('div');
    }
    this.open_child = osywindow.open_child;
}
osyiframetab = {
    'itab' : 0,
    'self' : null,
    'init' : function(){
        $('.osy-iframe-tab').each(function(){
            $(this)[0].win = osyiframetab;
            $(this)[0].win.self = $(this)[0];
        });
        $('.ui-tabs-nav').removeClass('ui-corner-all');        
    },
    'load' : function(form_url,subtitle){
        var tid  = $(this.self).attr('id');
        var tabs = $(this.self).tabs();
        var ul = tabs.find( "ul" );
        var cc = $('ul li',tabs).length;
        var nam = tid+'_'+this.itab;
        this.itab++;
        $( "<li><a href='#"+nam+"'><p><span></span></p></a></li>" ).hide().appendTo( ul );
        itm = new oiframe(nam,cc);
        itm.set('subtitle',subtitle.trim());
        tabs.append(itm.get_component('div'));
        tabs.tabs( "refresh" );
        $('.ui-tabs-nav').removeClass('ui-corner-all');
        form_tmp = osywindow.get('env').form_loader(form_url);
        form_tmp.target = nam;
        form_tmp.submit();
        $(form_tmp,osywindow.get('env')).remove();
    },
    'set_title' : function(title){
        var tabs = $(osytabiframe.self).tabs();
        $('li a p span',tabs).each(function(){
            if ($(this).text() == '')
            {
                $(this).text(title);
                $(this).click();
                $(this).closest('li').show();
            }
        }        
        );
    },
    'set_dimension' : function(){
    
    },
    'set_status' : function(){
    
    },
    'get'   : function(arg){
        return osywindow.get(arg);
    },
    parent_refresh : function(){
      return true;
    }
}
if (osyview)
{
    osyview['component-init'].push(osyiframetab.init);
}
