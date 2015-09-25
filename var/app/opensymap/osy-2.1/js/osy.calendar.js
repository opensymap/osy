/*
 +-----------------------------------------------------------------------+
 | sys/js/cmp.cal.js                                                     |
 |                                                                       |
 | This file is part of the Gestional Framework                          |
 | Copyright (C) 2005-2008, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 |-----------------------------------------------------------------------|
 | Description : Javascript calendar component                           |
 |-----------------------------------------------------------------------|
 | Creation date : 2007-06-01                                            |
 +-----------------------------------------------------------------------+

 $Id:  $

*/
//Crea il metodo pad per l'oggetto string
String.prototype.osy_pad = function(l, s, t){
    return s || (s = " "), (l -= this.length) > 0 ? (s = new Array(Math.ceil(l / s.length) + 1).join(s)).substr(0, t = !t ? l : t == 1 ? 0 : Math.ceil(l / 2)) 	+ this + s.substr(0, l - t) : this;
};

//Crea il metodo indexOf all'oggetto Array a tutti i browser che non hanno tale metodo.
/*if(!Array.indexOf){
    Array.prototype.indexOf = function(obj, start){
        for(var i=(start||0); i<this.length; i++){
            if(this[i]==obj){
                return i;
            }
        }
    }
}*/

window.getElementPosition = function(obj) 
{
	var curleft = curtop = 0;
	if (obj.offsetParent){
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent){
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	} else if (obj.x) {
		curleft += obj.x;
		curtop  += obj.y;
	}
	 
	return [curleft,curtop];
}

osy_calendar = {
    repo        : {},
    month       : null,
    year        : null,
    par         : function()
    {
       switch(arguments.length)
       {
          case 1:
                  return  (arguments[0] in this.repo) ? this.repo[arguments[0]] : null;
                  break;
          case 2:
                  this.repo[arguments[0]] = arguments[1];                  
                  return this;
                  break;                  
       }
       return null;
    },    
    init        : function()
    {                
        
        var l_elm = (document.getElementsByClassName) ? document.getElementsByClassName('osy-datebox') : document.querySelectorAll('.osy-datebox');        
        for (var i = 0; i < l_elm.length; i++)
        {           
            if (window.addEventListener) { l_elm[i].addEventListener('click',osy_calendar.show); } else { l_elm[i].attachEvent('onclick',osy_calendar.show); }
        }
    }, 
    init_calendar : function(){
        if (arguments.length == 2)
        {
          this.month = parseInt(arguments[0]);
          this.year  = parseInt(arguments[1]);
        } 
          else 
        {
            var d      = new Date();
            this.month = d.getMonth();
            this.year  = d.getFullYear();
        }   
        this.par('month-label',new Array('Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'));
        this.par('month-length',new Array(31,28,31,30,31,30,31,31,30,31,30,31));
        this.par('day-label', new Array('Lunedi','Martedi','Mercoledi','Giovedi','Venerdi' ,'Sabato','Domenica'));
        this.par('day-color',new Array('black', 'black', 'black', 'black', 'black', 'blue', 'red'));
        this.check_bisestile();   
        table                       = document.createElement('table');				              				
        table.border                = 0;             
        table.cellSpacing           = '1';
        table.style.backgroundColor = '#cccccc';
        if (this.par('calendar-cont') == null){
        this.par('calendar-cont',document.createElement('div'));
        }
        this.par('calendar-head',document.createElement('thead'));
        this.par('calendar-body',document.createElement('tbody'));
        table.appendChild(this.par('calendar-head'));
        table.appendChild(this.par('calendar-body'));
        this.par('calendar-cont').appendChild(table);                 
        this.build_head(); 
        this.build_body();      
    },
    build_head : function(){                
        //Prima riga d'intestazione
        var tr = document.createElement('tr');                
            tr.onclick = function(e){ osy_calendar.stop_propagation(e); }
		    tr.appendChild(this.build_head_button('&laquo;&laquo;','backyear'));
            tr.appendChild(this.build_head_button('&laquo;','back'));
        var th = document.createElement('th');
            th.colSpan   = 3;
            th.align     = 'center';
            th.innerHTML = this.par('month-label')[this.month] + ' '  + ' <input type="text" size="4" maxlength="4" value="' + this.year +'" style="text-align: center;" onchange="osy_calendar.set_year(1,this.value)">' ;
            th.style.backgroundColor = '#dddddd';
            tr.appendChild(th);                
            tr.appendChild(this.build_head_button('&raquo;','next'));
			tr.appendChild(this.build_head_button('&raquo;&raquo;','nextyear'));
        this.par('calendar-head').appendChild(tr);
        //Seconda riga d'intestazione
        var tr = document.createElement('tr');                
        for (x = 0; x < this.par('day-label').length; x++){
            var th = document.createElement('th');
                th.style.width = '25px';
                th.align       = 'center';
                th.innerHTML   = this.par('day-label')[x].charAt(0);
                th.style.color = this.par('day-color')[x]; 
                th.style.backgroundColor = '#dddddd';					    
                tr.appendChild(th);
        }
        //Join con il tbody
        this.par('calendar-head').appendChild(tr);
    },
    build_head_button : function(lbl,cmd)
    {
        var p  = this.time_call(cmd);				                    
        var td = document.createElement('td');				
            td.setAttribute('mese',p[0]);
            td.setAttribute('anno',p[1]);					
            td.innerHTML    = lbl;
            td.align        = 'center';
            td.style.cursor = 'pointer';
            td.onclick      = function(e){  osy_calendar.rebuild(e,this); }
        return td;
    },
    build_day_cell : function(day,day_of_week){
        var hCell = document.createElement('td');
            hCell.style.padding         = '3px';
            hCell.style.textAlign       = 'center';
        if (day > 0)
        {	 
            var curDate = new Date(this.year,this.month,day);           
            hCell.onclick               = function(){ osy_calendar.select_date(this) };
            hCell.innerHTML             = day.toString().osy_pad(2,'0',0);            
            hCell.style.color           = this.par('day-color')[day_of_week];
            hCell.style.cursor          = 'pointer';						
            hCell.style.backgroundColor = '#ffffff';
            hCell.setAttribute('date',day.toString().osy_pad(2,'0',0) + '/' + (this.month+1).toString().osy_pad(2,'0',0) + '/' + this.year);
            if (this.par('date-max')!=null)
            {
                if (curDate.getTime() > this.par('date-max').getTime())
                {
                    hCell.onclick = null;
                    hCell.style.color = '#aaaaaa';
                }
            }
            //console.log(this.par('date-max'));
            if (this.par('date-min')!=null)
            {
                if (curDate.getTime() < this.par('date-min').getTime())
                {
                    hCell.onclick = null;
                    hCell.style.color = '#aaaaaa';
                }
            }
            if (this.par('date-selected'))
            {
                var curDate = new Date(this.year,this.month,day);
                if (curDate.toString() == this.par('date-selected').toString()){
                    /*hCell.style.fontWeight = 'bold';*/
                    hCell.style.backgroundColor = 'blue';
                    hCell.style.color           = 'white';
                }
            }
        } 
         else 
        {
            hCell.innerHTML             = '&nbsp;';
            hCell.style.backgroundColor = '#eeeeee';
            hCell.setAttribute('date','');
            hCell.onclick               = function(){ osy_calendar.select_date(this) };
        }
        return hCell;
  },			
  build_body : function ()
  {                               
        var cur_date        = new Date(this.year,this.month,1);
        var day_cell_start  = cur_date.getDay() == 0 ? 6 : cur_date.getDay() - 1;
        var day_cell_end    = day_cell_start + this.par('month-length')[this.month];
        var cur_day         = 0;     
        var cur_num_cell    = 0;                           
        for (var i_row = 0; i_row < 6; i_row++)
        {
            var hRow = document.createElement('tr');
            hRow.onmouseover = function(){ this.style.backgroundColor = '#eeeeee'; }
            hRow.onmousout = function(){ this.style.backgroundColor = '#ffffff'; }
            for (var i_col = 0; i_col < 7; i_col++)
            {
                cur_num_cell = (i_row * 7) + i_col;
                cur_day     = ((cur_num_cell < day_cell_start) || (cur_num_cell >= day_cell_end)) ? 0 : cur_day + 1;
                hRow.appendChild(this.build_day_cell(cur_day,i_col));
            }
            this.par('calendar-body').appendChild(hRow);
        }                                            
  },
  check_bisestile : function (){
        if ((this.year % 4 == 0 && this.year%100 != 0) || this.year%400 == 0){
            this.par('month-length')[1] = 29;
        } else {
            this.par('month-length')[1] = 28;
        }
  },
  date_to_string : function(datDate){
        if (datDate != ''){
           var gg = datDate.getDate().toString().osy_pad(2,'0',0);
           var mm = (datDate.getMonth()+1).toString().osy_pad(2,'0',0);
           var aa = datDate.getFullYear();           
           return gg + '/' + mm + '/' +aa;
        }
  },                    
  target_click : function(obj){
        var pos = window.getElementPosition(obj);
        var div = osy_calendar.get(obj);
            div.style.position = 'absolute';
            div.style.top  = (pos[1]+obj.offsetHeight)+'px';
            div.style.left = pos[0]+'px';
            div.style.width = '200px';
            div.style.height = '200px';
        document.body.appendChild(div);
  },			    
  month_call : function (p){
        var m = this.month;
        var y = this.year;
        if (p == 'back')
        {
            m = m - 1;
            if (m < 0){
                m = 11;
                y = y - 1;
            }
        } 
         else if (p == 'next')
        {
            m = m + 1;
            if (m > 11){
                m = 0;
                y = y + 1;
            }
        }    
        return new Array(m,y);
  },  
  year_call : function(p)
  {
      var m = this.month;
      var y = this.year;
	  if (p == 'nextyear'){
	      y += 1;
	  }else if (p == 'backyear'){
	      y -= 1;
	  }
	  return new Array(m,y);
  },  
  time_call : function(p)
  {
      var m = this.month;
      var y = this.year;
	  switch(p){
	      case 'backyear':
		                   y -= 1;
						   break;
		  case 'nextyear':
		                   y += 1;
						   break;
		  case 'back'    :
		  				   m = m - 1;
            			   if (m < 0){
                			 m = 11;
                			 y = y - 1;
            			   }	
						   break;
		  case 'next'    :
		                    m = m + 1;
            				if (m > 11){
                				m = 0;
                				y = y + 1;
            				}
							break;		  
	  }
	   return new Array(m,y);
  },            
  rebuild : function(e,obj){
        this.stop_propagation(e);
        var m = obj.getAttribute('mese');
        var a = obj.getAttribute('anno');
        this.par('calendar-cont').innerHTML = '';
        this.init_calendar(m,a);
  },            
  select_date : function(obj){
        if (this.par('date-selected') != null){
            //this.par('date-selected').style.fontWeight = 'normal';
        }
        obj.style.fontWeight = 'bold';
        if (this.par('target'))
        {
            this.par('target').value = obj.getAttribute('date');
            if (this.par('target').onchange){
                this.par('target').onchange();
            }
        }        
        //document.body.removeChild(this.Container);
        this.par('date-selected',obj);
  },  
  set_date_max : function(o)
  {
    app = (document.forms[0].elements[o]) ? document.forms[0].elements[o].value : o;      
	this.par('date-max',this.string_to_date(app));
  },  
  set_date_min : function(o){
    app = (document.forms[0].elements[o]) ? document.forms[0].elements[o].value : o;    
	this.par('date-min',this.string_to_date(app));
  },  
  string_to_date : function(str_dat){
        dat = null;
        switch (str_dat)
        {
            case '' : 
                      break;
            case 'CURRENT_DATE' :
                                  dat = new Date();
                                  break;
            default:
                        var a_dat = str_dat.split('/');                
                        dat = new Date(a_dat[2],(a_dat[1]-1),a_dat[0]);
                        break;
        }
        return dat;
  },  
  stop_propagation : function(e){
        if (!e) var e = window.event;
        if(!e.target) e.target = e.srcElement;
        if (e.stopPropagation) {
            e.stopPropagation();
        } else {
            e.cancelBubble = true;
        }
        return e;
  },
  show : function(e){
    osy_calendar.stop_propagation(e);
    obj = (e.target) ? e.target : this;
    osy_calendar.par('target',obj).par('calendar-cont',null);
    //Controllo se e' impostata una data min
    if (obj.getAttribute('date-min')){  
        osy_calendar.set_date_min(obj.getAttribute('date-min'));
    }
    //Controllo se e' impostata una data max    
    if (obj.getAttribute('date-max')){
        osy_calendar.set_date_max(obj.getAttribute('date-max'));
    }
    var pos = window.getElementPosition(obj);
	if (window.innerWidth < (pos[0] + 200)){
	   pos[0] = (pos[0] + this.offsetWidth) - 200;
	   pos[1]++;
	}
    if (window.innerHeight < (pos[1] + 200)){	  
       pos[1] = (pos[1] - 200);
	}
    if (obj.value != '')
    {                    
        var p = obj.value.split('/');
        obj.setAttribute('mese',(p[1] - 1).toString());
        obj.setAttribute('anno',p[2].toString());            
        osy_calendar.par('date-selected',new Date(p[2],(p[1] - 1),p[0]));
        osy_calendar.init_calendar((p[1] - 1),p[2]);
    }
     else
    {
        osy_calendar.init_calendar();
    }
    var div = osy_calendar.par('calendar-cont');				    			
        div.style.position = 'absolute';
        div.style.top  = (pos[1]+obj.offsetHeight)+'px';
        div.style.left = pos[0]+'px';
        div.style.width = '200px';
        div.style.height = '200px';
        document.body.appendChild(div);
        document.onclick = function() {document.body.removeChild(div); document.onclick='';}
  },  
  set_year : function(m,y){
         this.par('calendar-cont').innerHTML = '';
         this.init_calendar(this.month,y);
  }
}
if (osyview)
{
    osyview['component-init'].push(osy_calendar.init);
}