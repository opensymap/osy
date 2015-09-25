osy_gmap = {
    'init' : $('.map').gmap3({
            action:'init',
            options:{center:[$('.map').attr('clat'),$('.map').attr('clng')],zoom: 12},
            events : {
                       bounds_changed : function()
                       {
                          $(this).gmap3({
                          action:'getBounds',
                          callback: function(vertex)
                          {                               
                            if (!vertex) return;
                            var Vne = vertex.getNorthEast();
                            var Vso = vertex.getSouthWest();
                            var FrmData = $('#main1,#osy-form').serialize();
                            $.ajax({
                               url  : 'osy.ajax.cmp.php',
                               type : 'POST',  
                               context : this,              
                               dataType: 'json',
                               data : FrmData +
                                      '&FldID='+$('.map').attr('name')+
                                      '&VLat0='+Vso.lat()+
                                      '&VLat1='+Vne.lat()+
                                      '&VLng0='+Vso.lng()+
                                      '&VLng1='+Vne.lng()+
                                      '&ajax=1',
							   error  : function(err)
                               {
							   		alert($.param(err));
							   },
                               success: function(res)
                               { 
                                      osyform.map_datagrid_refresh(res);
                                      var lMarkers = [];
                                      $.each(res,function(k,v)
                                      {
                                         if (v['html'] != null) v['html'] = v['html'].replace(/\:/g,'<br>');
                                         var m = {lat : v['latitude'], lng : v['longitude'], data : v['html']}
                                         lMarkers.push(m);
                                      });
                                      $(this).gmap3(
                                      {
                                          action : 'AddMarkers',
                                          radius : 100,
                                          markers : lMarkers,
                                          events:
                                          {
                                             click: function(marker, event, data)
                                             {
                                                  var map = $(this).gmap3('get'),
                                                  infowindow = $(this).gmap3({action:'get', name:'infowindow'});
                                                  if (infowindow)
                                                  {
                                                      infowindow.open(map, marker);
                                                      infowindow.setContent(data);
                                                   } 
                                                    else 
                                                   {
                                                      $(this).gmap3({action:'addinfowindow', anchor:marker, options:{content: data}});
                                                   }
                                              }
                                          }
                                      });                                                                                            
                               }                       
                            });
                      }
                   });
              }
         }              
        },{
        action : 'addMarker',
        latLng : [$('.map').attr('clat'),$('.map').attr('clng')],
        options : {
            icon : new google.maps.MarkerImage('http://maps.gstatic.com/mapfiles/icon_greenB.png')
        }

}
      });