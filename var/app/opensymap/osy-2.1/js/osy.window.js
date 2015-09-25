/*
 +-----------------------------------------------------------------------+
 | js/osy.window.js                                                      |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2014, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description : Osy window builder                                      |
 |-----------------------------------------------------------------------|
 | Creation date : 2014-01-01                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/
function osy_window()
{
    var  self  = this;
    var  repo  = {};
    
    this.close = function ()
    {  
          if (this.get('parent'))
          {
              osy.get_focus(this.get('parent').get('name'));
              this.parent_refresh();
          }          
          osy.window_close(this.get('name'));   
    }
    
    this.get = function(){
         if (arguments.length == 0){ return null; }
         val = repo;
         for (k = 0; k < arguments.length; k++)
         {
           val = (arguments[k] in val) ? val[arguments[k]] : null;
         }
         return val;
         //return (arguments[0] in repo) ? repo[arguments[0]] : null;    
    }
    
    this.set = function(){
         repo[arguments[0]] = arguments[1];
         return this;
    }
    
    var init = function()
    {
        self.set('type','normal')
            .set('state','normal')
            .set('sdk-url','')
            .set('parent','')
            .set('parent-document','')
            .set('dimension',{'width' : 640 , 'height' : 480})
            .set('position' ,{'top'   : null, 'left'   : null})
            .set('desk-mask',null)
            .set('env',osy)
            .set('ico',new Array())
            .set('element',{'window'    : document.createElement('div'),
                            'title_bar' : document.createElement('div'),
                            'desk'      : null,
                            'status_bar': document.createElement('div')});
        self.get('element').window.exec = self;
    }();
    
    this.dimension  = function(w,h)
    {
        if (w && h) { this.set('dimension',{'width' : parseInt(w), 'height' : parseInt(h)}); }
        this.get('element','window').style.width  = this.get('dimension').width  + 'px';
        this.get('element','window').style.height = this.get('dimension').height + 'px'; 
        if (this.get('element').desk)
        {
            title_bar_height = this.get('element').title_bar.offsetHeight;
            status_bar_height = this.get('element').status_bar.offsetHeight;
            this.get('element').desk.style.height   = (this.get('dimension').height - title_bar_height - status_bar_height) +'px';
            //console.log(title_bar_height+','+status_bar_height+','+this.get('dimension').height - title_bar_height - status_bar_height);
        }
        return this;       
    }
    
    this.maximize = function()
    {		        
        parent_height = window.innerHeight-10; 
        parent_width  = this.get('element').window.parentNode.clientWidth-4; 
        this.set('state','maximized')
            .set('pre-position',osy.object_position(this.get('element','window'))) 
            .set('pre-dimension',this.get('dimension'));       
		this.position(0,0).dimension(parent_width,parent_height);
	}
    
    this.minimize = function()
    {
        this.set('state','minimized');
        this.set('minimized',document.createElement('div'));
        this.get('minimized').parent    = this;
        this.get('minimized').className = 'osy-window-mdi-minimized';
        this.get('minimized').innerHTML =  this.get('element').title_bar.firstChild.innerHTML;
        this.get('minimized').onclick   = function()
        {
            this.parent.get('element').window.style.visibility = '';
            this.parent.get('env').get_focus(this.parent.get('name'));
            osy.get('component').minimized.removeChild(this);
            this.parent.set('minimized',null);
        }
        this.get('element').window.style.visibility    = 'hidden';
        osy.get('component').minimized.appendChild(this.get('minimized'));   
    }
    
    this.open_child = function(url,name,width,height,sdk)
    {
         if(!sdk) { sdk = '' };
         //if(url == '') { url = this.get('env').get('core-root') + 'osy.view.det.php'; }
         new_win = osy.window_open(url,name,width,height,sdk);
         new_win.exec.set('parent',this);
         new_win.exec.set('parent-document',this.get('element').desk.contentWindow.document);
    }
    
    this.parent_refresh = function()
    {        
        if (this.get('parent'))
        {
            this.get('parent').get('element').desk.contentWindow.document.forms[0].submit();
            //this.get('parent-document').forms[0].submit(); non funge su ie11
            return true;            
        }        
    }
    
    this.position = function(l,t)
    {
        switch(l)
        {
           case 'center':
                          t = (window.innerHeight - this.get('dimension').height) / 2;
                          l = (window.innerWidth - this.get('dimension').width) / 2;
                          break;                 
        } 
        this.set('position',{'top' : parseInt(t), 'left' : parseInt(l)});
        this.get('element')['window'].style.top  = t+'px';
        this.get('element')['window'].style.left = l+'px';
        return this;
    }
    
    this.resize = function (w,h)
    {
        this.dimension(w,h);
        this.get('element','desk').style.height = (h-50)+'px';                 
    }
                
    this.mask_desk = function(action)
    {
        switch(action)
        {
            case 'remove':
                            if (this.get('element')['desk-mask'])
                            {           
                                this.get('element').window.removeChild(this.get('element')['desk-mask']);
                                this.get('element')['desk-mask'] = null;
                            }
                            break;
            default      :
                            if (this.get('element')['desk-mask'] == null)
                            {
                		        m = document.createElement('div');
                                m.className      = 'osy-window-mdi-maskdesk';
                                m.style.width    = this.get('element').desk.offsetWidth  + 'px';
                                m.style.height   = this.get('element').desk.offsetHeight + 'px';
                                m.onclick        = function(){ osy.get_focus(this.parentNode.exec.get('name')); }
                                this.get('element')['window'].insertBefore(m,this.get('element')['desk']);
                                this.get('element')['desk-mask'] = m;
                            }
                            break;
        }
	}	
    
    this.show = function()
    {
        osy.get('component').desktop.appendChild(this.get('element')['window']);
    }
    
    this.set_title = function (t)
    {
        this.get('element','title_bar').firstChild.innerHTML = t;
    } 
    
    this.set_status = function (t)
    {
        this.get('element','status_bar').firstChild.innerHTML = t;
    }
    
    this.unmaximize = function()
    {
        this.get('element','window').style.left = this.get('position').top +'px';
        this.get('element','window').style.top  = this.get('position').left +'px';
        this.position(this.get('pre-position').x,this.get('pre-position').y)
            .dimension(this.get('pre-dimension').width,
                       this.get('pre-dimension').height);
        this.set('state','normal');
	}
                   
    this.build = function()
    {
        if (!this.get('position')['top'])
        {
            var c = osy.window_get_new_coords();
            this.position(c['x'],c['y']);
        }                                    
        this.dimension();
        __build_title_bar();        
        __build_desk();   
        __build_status_bar();               
        return this.get('element')['window'];
    }
            
    var __build_desk = function ()
    {
        try
        {
            self.get('element').desk =  document.createElement('<iframe name="'+self.get('name')+'"></iframe>');
        } 
         catch (e) 
        {
            self.get('element').desk = self.get('element')['window'].ownerDocument.createElement('iframe');
            self.get('element').desk.setAttribute('name',self.get('name'));
            self.get('element').desk.setAttribute('id',self.get('name'));
        }                                              
        self.get('element').desk.className = 'osy-window-mdi-desk';  
        self.get('element').desk.win = self;                                                                                                       
        if (osy.get('parameter')['desktop-type'] == 'mobile' && self.get('type') == 'normal')
        {
            self.get('element').desk.style.height = self.get('dimension').height + 2 + 'px';     
        } 
         else 
        {
            self.get('element').desk.style.height = self.get('dimension').height - (self.get('type') == 'normal' ? 50 : 29) + 'px';     
        }
        self.get('element').desk.scrolling    = 'no';
        self.get('element').desk.src          = self.get('page_to_load');    
        self.get('element').desk.frameBorder  = '0';    
        self.get('element').window.appendChild(self.get('element').desk);
    }
    
    var __build_icone = function()
    {
        switch(self.get('type'))
        {
            case 'menu':
                          __set_command(self.get('env').get('core-root') + '../img/window_mdi_close_body.gif',function()
                          {                            
                                var win = this.parentNode.parentNode;                                     
                                if (win.style.height == '30px')
                                {
                                    win.style.height =  win.exec.get('dimension').height+'px';
                                    win.exec.get('element').desk.style.visibility = 'visible';
                                    win.exec.get('element').desk.style.height = (win.exec.get('dimension').height-30)+'px';
                                } 
                                 else 
                                {
                                    win.style.height = '30px';
                                    win.exec.get('element').desk.style.visibility = 'hidden';
                                    win.exec.get('element').desk.style.height = '1px';
                                }
                          });
                          break;
            case 'normal':
                          //__set_command('data:image/gif;base64,R0lGODlhDgAOAPcAAAAAAPn39fr69/v7+QAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAP8ALAAAAAAOAA4AAAg9AP8JGEiw4ECB/xIqXJjwIMOHDh8ujOiQogCFAwMMHIDxYseIAj02JDhRpEGTKEd2lMgQJEuXEi+ePPkvIAA7', function(){
                          __set_command(self.get('env').get('core-root') + '../img/window_mdi_close.gif', function(){
                               this.parentNode.parentNode.exec.close();
                          });
                            
                          __set_command(self.get('env').get('core-root') + '../img/window_mdi_maximize.gif', function(){
                               var win = this.parentNode.parentNode;
                               if (win.exec.get('state') == 'normal'){
                                   this.src = self.get('env').get('core-root') + '../img/window_mdi_unmaximize.gif';
                                   win.exec.maximize();
                               } else {
                                   this.src = '../img/osy/window_mdi_maximize.gif';
                                   win.exec.unmaximize();
                               }
                           });
                            
                           __set_command(self.get('env').get('core-root') + '../img/window_mdi_minimize.gif', function(){
                                this.parentNode.parentNode.exec.minimize();
                            });
                           
                           if (self.get('sdk-url'))
                           {
                              __set_command(self.get('env').get('core-root') + '../img/window_mdi_debug.gif', function()
                              {
                                var win = this.parentNode.parentNode;
                                    url = win.exec.get('sdk-url');
                                    if ($('#osy-form',win.exec.get('element').desk.contentWindow.document).length > 0)
                                    {
                                        url += '&pkey[o_id]=' + $('#osy\\[fid\\]',win.exec.get('element').desk.contentWindow.document).val();
                                    }
                                     else
                                    {
                                        url = url.replace('kkey','pkey');                                        
                                    }                                    
                                    win.exec.open_child(url,'FormSDK',800,600,null);
                              });
                           }
                           break;
           
        }    
    }
    
    var __build_title_bar = function ()
    {
        self.get('element')['title_bar'] = document.createElement('div');
        self.get('element')['title_bar'].style.height = '18px';        
        self.get('element')['title_bar'].className   = 'osy-window-mdi-titlebar';
        self.get('element')['title_bar'].onmousedown = function()
        {
             if (this.parentNode.exec.get('type') != 'modal')
             {
        	    osy.get_focus(this.parentNode.exec.get('name'));
             }
             osy.make_draggable(this.parentNode);             
             this.parentNode.exec.mask_desk('show');
        }                        
        
        self.get('element','title_bar').unShow = function(){
            this.style.display = 'none';
        }                              
        
        if (self.get('type')=='normal')
        {
            self.get('element')['title_bar'].ondblclick = function(){            
                if (this.parentNode.exec.get('state') == 'normal'){               
                    this.parentNode.exec.maximize();
                } else {
                    this.parentNode.exec.minimize();
                }
            }
        }
        title_container = document.createElement('div');
        title_container.style.cssText  = 'float: left; font-size: 12px; font-weight: bold;';
		title_container.innerHTML      = self.get('name');
        self.get('element','title_bar').appendChild(title_container);
        self.get('element','window').appendChild(self.get('element')['title_bar']);	
        __build_icone();	     
    }
    
    var __build_status_bar = function()
    {
        if (self.get('type') == 'normal')
        {
            self.get('element')['status_bar'].className   = 'osy-window-mdi-statusbar';
            self.get('element')['window'].appendChild(self.get('element')['status_bar']);
            span = document.createElement('span');
            self.get('element')['status_bar'].appendChild(span);
            resizer = document.createElement('div');
            resizer.className = 'osy-window-mdi-statusbar-resizecorner';
            resizer.onmousedown = function(ev)
            {    
               osy.set('object-resizing',this.parentNode.parentNode);
               this.parentNode.parentNode.exec.mask_desk('show');
               return false;
            }
            resizer.style.margin = '0px';
            self.get('element')['status_bar'].appendChild(resizer); 
            self.get('element')['status_bar'].unShow = function()
            {
               this.style.display = 'none';
            }
        }
    }
            
    var __set_command = function(p,f){
        var i = self.get('ico').length;
        ico = new Image();
        ico.src = p;
        ico.className = 'osy-window-mdi-titlebar-ico';
        ico.align = 'right';
        ico.onclick     = f;
        self.get('element')['title_bar'].appendChild(ico);
        self.get('ico')[i] = ico;
    }        
                            
    this.set_visibility_level = function(l)
    {
        this.get('element')['window'].style.zIndex = l;
    }   
}