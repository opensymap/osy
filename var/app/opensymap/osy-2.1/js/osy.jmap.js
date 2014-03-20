    'init_map'     : function()
    {
        if ($('.map').length == 0) return;      
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
