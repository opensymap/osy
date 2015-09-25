/*
 +-----------------------------------------------------------------------+
 | osy/js/2.osy.desktop.js                                               |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2014, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description : Desktop MDI generator                                   |
 |-----------------------------------------------------------------------|
 | Creation date : 2013-12-30                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/

/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/
 
var Base64 = {
 
	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
 
	// public method for encoding
	encode : function (input) 
    {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = Base64._utf8_encode(input);
 
		while (i < input.length) 
        { 
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
 
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
 
			if (isNaN(chr2)) 
            {
				enc3 = enc4 = 64;
			} 
             else if (isNaN(chr3)) 
            {
				enc4 = 64;
			}
 
			output += this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2)
			          this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4); 
		} 
		return output;
	}, 
	// public method for decoding
	decode : function (input) 
    {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
 
		while (i < input.length) 
        {
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
 
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
 
			output += String.fromCharCode(chr1);
 
			if (enc3 != 64) 
            {
				output += String.fromCharCode(chr2);
			}
			if (enc4 != 64) 
            {
				output += String.fromCharCode(chr3);
			}
		} 
		output = Base64._utf8_decode(output); 
		return output;
 
	}, 
	// private method for UTF-8 encoding
	_utf8_encode : function (string) 
    {
		string = string.replace(/\r\n/g,"\n");
		var utftext = ""; 
		for (var n = 0; n < string.length; n++) 
        { 
			var c = string.charCodeAt(n); 
			if (c < 128) 
            {
				utftext += String.fromCharCode(c);
			}
			 else if((c > 127) && (c < 2048)) 
            {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			 else 
            {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			} 
		}
		return utftext;
	},
 
	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) 
    {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
 
		while ( i < utftext.length ) 
        { 
			c = utftext.charCodeAt(i);
 
			if (c < 128) 
            {
				string += String.fromCharCode(c);
				i++;
			}
			 else if((c > 191) && (c < 224)) 
            {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			 else 
            {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
 
		}
 
		return string;
	}
 
}

osy = 
{      
   repository : Array(),
   base64     : Base64,   
   init : function()
   {
        if (document.body.clientHeight)
        {    
            window.innerHeight = document.body.clientHeight;
            window.innerWidth  = document.body.clientWidth;
        } 
         else if(document.documentElement.clientHeight)
        {
            window.innerHeight = document.documentElement.clientHeight;
            window.innerWidth  = document.documentElement.clientWidth;
        }
        this.set('object-dragging',null)
            .set('object-resizing',null)
            .set('sdk-vm',null)
            .set('sdk-fm',null)
            .set('mouse-offset',null)
            .set('queue-windows',Array())
            .set('parameter',{'desktop-type' : 'normal'}) 
            .set('component',{'desktop' : document.createElement('div'),
                              'minimized' : document.createElement('div')});
        this.get('component').desktop.className = 'osy-dektop';
        this.get('component').minimized.className = 'osy-desktop-titlebar';
        this.get('component').desktop.appendChild(this.get('component').minimized); 

        document.body.appendChild(this.get('component').desktop);
        this.window_login();  
        document.onmousemove = function(e) { osy.mouse_move(e); }
        document.onmouseup   = function(e) { osy.mouse_up(e); }
   },
   /*Metodo che crea una form temporanea sulla pagina*/
   create_form : function(m,t,a)
   {
        var f = document.createElement('form');
            f.method = m;
            f.target = t;
            f.action = a;
            document.body.appendChild(f);
        return f;
   }, 
   create_input : function(n,t,v,f)
   {
        var i = document.createElement('input');
            i.name  = n;
            i.type  = t;
            i.value = v;
            f.appendChild(i);
        return i;        
   },  
   logout : function(){
        window.location = '/';
   },   
   get : function(obj)
   {
        return (obj in this.repository) ? this.repository[obj] : null;
   },
   set : function(key,val)
   {
        this.repository[key] = val; 
        return this;   
   },
   get_focus : function(window_target)
   {
        for (cur_windows_name in this.get('queue-windows'))
        {                               
           if (cur_windows_name != window_target 
               && this.get('queue-windows')[cur_windows_name] 
               && this.get('queue-windows')[cur_windows_name].exec.get('type') != 'modal')
           {
              this.get('queue-windows')[cur_windows_name].style.zIndex = '2';
              this.get('queue-windows')[cur_windows_name].exec.mask_desk('show');  
              this.get('queue-windows')[cur_windows_name].className = 'osy-window-mdi-unselected';
           }
        }
        if (this.get('queue-windows')[window_target].exec.get('type') != 'modal')
        {
            this.get('queue-windows')[window_target].style.zIndex = '3';
        }
        this.get('queue-windows')[window_target].exec.mask_desk('remove','getfocus');
        this.get('queue-windows')[window_target].className = 'osy-window-mdi';        
   },   
   get_window_from_desk : function(d)
   {
        for (window_name in this.get('queue-windows'))
        {
            if (this.get('queue-windows')[window_name].contentWindow.document = d)
            {
                return this.get('queue-windows')[window_name];
            }
        } 
   },      
   make_draggable : function(obj,e)
   {    
       if(!obj) return;
       obj.onmousedown = function(e)
       {
           osy.set('object-dragging',this);
           osy.set('mouse-offset',osy.mouse_offset(this, e));
           return false;
       }       
  },
  mouse_move : function(e)
  {
    mouse_pos = this.mouse_position(e);    
    if (this.get('object-dragging'))
    {        	
        y = parseInt(mouse_pos.y - this.get('mouse-offset').y);
        x = parseInt(mouse_pos.x - this.get('mouse-offset').x);
        if (y <= 0) return false;
    	//this.repository['object-dragging'].style.left = x + 'px'
        //this.repository['object-dragging'].style.top  = y +'px';
        this.get('object-dragging').exec.position(x,y);
		return false;
    }
    if(this.get('object-resizing'))
    {
        var p = this.object_position(this.get('object-resizing'));
        var w = mouse_pos.x - p.left; //e.pageX-p.x;
        var h = mouse_pos.y - p.top; //e.pageY-p.y;
        if (h < 100) h = null;
        if (w < 100) w = null;
        this.get('object-resizing').exec.resize(w,h);
        return false;
	}
  },
  mouse_offset : function (target, e)
  {
	e = e || window.event;
	var object_pos = this.object_position(target);
	var mouse_pos  = this.mouse_position(e);
	return {x:mouse_pos.x - object_pos.left, y:mouse_pos.y - object_pos.top};
  },
  mouse_position  : function (ev)
  {
	if(typeof event == 'undefined')
    {
       return {x:ev.pageX, y:ev.pageY};
    }
     else     
    { 
       return {x:event.clientX + document.body.scrollLeft - document.body.clientLeft,	y:event.clientY + document.body.scrollTop  - document.body.clientTop};
    }	
  },  
  mouse_up : function()
  {
    if (this.get('object-dragging'))
    {
        this.get('object-dragging').exec.mask_desk('remove','mouseup');	    
        this.get('object-dragging').onmousedown = function(){};        
    }       
    if (this.get('object-resizing'))
    {
        this.get('object-resizing').exec.mask_desk('remove','mouseup');	          
    }
    this.set('object-dragging',null);   
    this.set('object-resizing',null);
  },  
  object_position : function(obj)
  {
    var left = 0;
	var top  = 0;
	while (obj.offsetParent)
    {
		left += obj.offsetLeft;
		top  += obj.offsetTop;
		obj   = obj.offsetParent;
	}
	left += obj.offsetLeft;
	top  += obj.offsetTop;
	return {'left' : left, 'top' :top};
  },          
  window_close : function (window_name)
  { 
        q = this.get('queue-windows');
        for (w in q)
        {
            if (q[w].exec.get('parent') && q[w].exec.get('parent').get('name') == window_name)
            {
                this.get_focus(win);
                return;
            }
        }              
        this.get('component').desktop.removeChild(this.repository['queue-windows'][window_name]);
        delete this.repository['queue-windows'][window_name];
  },   
  window_get_new_coords : function()
  {
        var xmax = 0;
        var ymax = 0; 
        if (this.type == 'mobile')
        {
            return {x:0,y:0};
        }
        for (window_name in this.repository['queue-windows'])
        {  
           if (this.repository['queue-windows'][window_name] && 
               this.repository['queue-windows'][window_name].exec.get('type') == 'normal')
           {
               var p = this.object_position(this.repository['queue-windows'][window_name]);
               if (p.left > xmax){ xmax = p.left;}
               if (p.top > ymax) { ymax = p.top; }
           }
        }
        return (xmax > 0 && ymax > 0) ? {x:xmax+25,y:ymax+25} : {x:250,y:50};
  }, 
  window_login : function()
  {
        window_login = new osy_window();
        window_login.set('name','Login')
                    .set('type','modal')
                    .set('page_to_load',this.get('core-root') + 'osy.login.php');
        var w = 360;
        var h = 260;
        if (window.innerWidth < 640)
        {
           w = 240;
           h = 180;
           this.get('parameter')['desktop-type'] = 'mobile'; 
        }                                 
        window_login.dimension(w,h)
                    .position('center')
                    .set_visibility_level('100');
        this.window_register(window_login.build());      
  },
  window_menu : function(sessionid)
  {
      window_menu = new osy_window()
      window_menu.set('name','Menu');
      window_menu.set('type','menu');
      window_menu.set('page_to_load','');
      //WinMenu.SetPage('/osy/System.Menu.php?SesID='+SesID);
      if (this.repository['parameter']['desktop-type'] == 'mobile')
      {
        window_menu.dimension(210,250);
        window_menu.position(0,0);
      } 
       else 
      {
        window_menu.dimension(210,460);
        window_menu.position(10,10);
      }
      this.window_register(window_menu.build());            
      temp_form = this.create_form('post','Menu', this.get('core-root') + 'osy.menu.php');
      this.create_input('osy[sid]','hidden',sessionid,temp_form);
      //this.create_input('SesTyp','hidden',this.repository['desktop']_type,temp_form);
      temp_form.submit();
      temp_form.parentNode.removeChild(temp_form);
       //WinMenu.SetVisibilityLevel(10);
  },
  form_loader : function (url_form,title,w,h,dbg)
  {
      if(url_form)
      {
          var url_part = url_form.split('?');          
          var tmp_form = this.create_form('post',title,url_part[0]);
          if (url_part[1])
          {
             var l_param = url_part[1].split('&');
             for (i in l_param)
             {
                  param    = l_param[i].split('=');
                  param[0] = (param[0].indexOf('[') == -1) ? 'osy['+param[0]+']' : param[0];
                  this.create_input(param[0],'hidden',param[1],tmp_form);
             }
          }      
          return tmp_form 
      }
      return false;
  },
  window_open : function (pag,title,w,h,sdk,lng)
  {            
       if (this.repository['queue-windows'][title])
       { 
           this.get_focus(title);
           return this.repository['queue-windows'][title];
       }       
       if(pag)
       {
          var url_page_part = pag.split('?');          
          var temp_form = this.create_form('post',title,url_page_part[0]);
          if (url_page_part[1])
          {
             var sdk_url = this.get('core-root') + this.get('sdk-vm'); //'osy.view.det.php?fid=osy2://osy-sdk/form-manager/'; //Container Url Form Debug
             var l_par = url_page_part[1].split('&');
             for (i in l_par)
             {
                  var par  = l_par[i].split('=');
                  par[0]  = (par[0].indexOf('[') == -1) ? 'osy['+par[0]+']' : par[0];
                  this.create_input(par[0],'hidden',par[1],temp_form);
                  switch(par[0])
                  {
                    case 'osy[fid]':
                                  sdk_url += '&pkey[o_id]='+par[1];
                                  break;
                    case 'osy[sid]':
                                  sdk_url += '&'+l_par[i];                             
                                  break;   
                  }
             }
          }       
       }
       win = new osy_window();
       win.set('page_to_load','')
          .set('name',title)
          .dimension(w,h);       
       if (sdk==1 && sdk_url && this.get('sdk-vm')) { win.set('sdk-url',sdk_url); }
       this.repository['queue-windows'][title] = win.build();
       this.get('component').desktop.appendChild(this.repository['queue-windows'][title]);  
       this.get_focus(title);
       if (temp_form)
       {
           temp_form.submit();
           document.body.removeChild(temp_form);
       } 
       if (this.repository['parameter']['desktop-type'] == 'mobile')
       {
            this.repository['queue-windows'][title].exec.get('element')['title_bar'].unShow();
            this.repository['queue-windows'][title].exec.get('element')['status_bar'].unShow();
       }
       return this.repository['queue-windows'][title];
  },
  window_open2 : function(p)
  {
     var a_p = this.base64.decode(p);
    // alert(a_p);
  }, 
  window_register : function(win)
  {
   		if (!this.get('queue-windows')[win.exec.get('name')])
        {
            win.exec.show(); 
            this.get('queue-windows')[win.exec.get('name')] = win;
            this.get('component').desktop.appendChild(this.get('queue-windows')[win.exec.get('name')]);  
            this.get_focus(win.exec.get('name'));
        } 
         else 
        {
            this.get_focus(win.exec.get('name'));
        }        
  },            
}