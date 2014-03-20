(function($)
{  
 $.fn.osy_autocomplete = function() 
 {     
    return this.each(function() 
    {  
        
        $(this)
        .keydown(function(event){
            switch(event.keyCode)
            {
                case 13:
                case 38:
                case 40:
                        event.preventDefault();
                        break;
            }
            return this;
        })
        .keyup(function(event)
        {
            bid = $(this).attr('id');
            box = $('.'+bid+'.autocomplete-box');
            switch(event.keyCode)
            {
                case 8  :
                          $('.autocomplete-item.sel',box).removeClass('sel');
                          break;
                case 13 :
                          if ($('.autocomplete-item.sel',box).length > 0){
                            $('.autocomplete-item.sel',box).click();
                          } else if($('.autocomplete-item[title*=\'' + $(this).val() +'\']',box).length > 0) {
                            $('.autocomplete-item[title*=\'' + $(this).val() +'\']:first',box).click();
                          } else {
                            val = $(this).val();
                            setTimeout(function(){
                                $('.autocomplete-item[title*=\'' + val +'\']:first').click();
                            },2000);
                          }
                          return this;
                          break;
                case 40 :                         
                          if ($('.autocomplete-item.sel:visible',box).length == 0){
                            $('.autocomplete-item:visible:first',box).addClass('sel');
                          } else {
                            $('.autocomplete-item.sel:visible',box).removeClass('sel').next().addClass('sel');
                          }
                          return this;  
                          break;
                case 38 :                         
                         if ($('.autocomplete-item.sel:visible',box).length > 0){
                            if ($('.autocomplete-item.sel:visible',box).is(':first')) {
                                $('.autocomplete-item.sel:visible',box).removeClass('sel');
                            } else{
                                $('.autocomplete-item.sel:visible',box).removeClass('sel').prev().addClass('sel');
                            }
                         }
                         return this;
                         break;
                case 37 :
                case 39 : 
                         return this;
                         break;
            }
            switch($(this).val().length)
            {
                case 0:
                        $('div.autocomplete-box').remove();
                        break;
                case 1:
                        dat = $('form').serialize() + '&ajax=' + $(this).attr('id');
                        $.ajax({
                            type : 'post',
                            context : this,
                            data : dat,
                            dataType : 'json',                    
                            success : function(rsp)
                            {
                               if (rsp.length == 0) return;
                               var hgt  = Math.min(200,rsp.length*24);
                               var list = $('<div class="autocomplete-box '+$(this).attr('id')+'"></div>');
                               for (i in rsp)
                               { 
                                   item = $('<div class="autocomplete-item"></div>');
                                   j=0;
                                   for (k in rsp[i])
                                   {
                                        if (j == 0 || k == 'label')
                                        {
                                            item.attr('title',rsp[i][k]);
                                        }
                                        item.append('<span target="' + k + '">' + rsp[i][k] + '</span>');
                                        j++;
                                   }
                                   list.append(item);
                               }
                               list.on('click','.autocomplete-item',function(){
                                    $('span',this).each(function(){
                                        $('#'+$(this).attr('target')).val($(this).text());
                                     });
                                     $(this).closest('.autocomplete-box').hide();
                               }); 
                               pos = $(this).offset();
                               list.css('top',pos.top+20+'px')
                                   .css('left',pos.left+'px')
                                   .width($(this).width())
                                   .height(hgt);
                               $('body').append(list).click(function(){
                                    $('.autocomplete-box').hide();
                               });                            
                            }
                        });
                        break;
                  default:                           
                           $('.'+bid+'.autocomplete-box:hidden').show();                           
                           $('.'+bid+'.autocomplete-box > .autocomplete-item:not([title*=\''+ $(this).val()+'\'])').hide().removeClass('sel');
                           n = $('.'+bid+'.autocomplete-box > .autocomplete-item[title*=\'' + $(this).val() +'\']').show().length;
                           $('.'+bid+'.autocomplete-box').height((n*24)+1);
                           break;          
            
            }
            return this;
        }); 
    });  
 };  
})(jQuery);
