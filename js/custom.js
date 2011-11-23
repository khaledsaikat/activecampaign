
jQuery(document).ready(function(){
    jQuery( "#accordion" ).accordion();
    jQuery( "#tabs" ).tabs();             
});


function save_config(role_name){
    form_id = '#config_form_' + role_name;
    loading = '#saving_' + role_name;
    response = '#saving_response_' + role_name;
    jQuery(response).empty();
    jQuery(".saveSetting").attr('disabled', 'disabled');
	jQuery.ajax({
		type: "post",url: ajaxurl, data: jQuery(form_id).serialize(),
		beforeSend: function() {jQuery(loading).fadeIn('slow');},
		success: function(data){
            jQuery(loading).fadeOut('slow');
            jQuery(".saveSetting").removeAttr('disabled');
            jQuery(response).empty();                           
            jQuery(response).append(data);
            jQuery(response).fadeIn('slow');
            jQuery(response).delay(5000).fadeOut('slow');
		}
	}); 
}
    
function call_sync_dialog(user_role){
    jQuery("#sync_from").val(1);
    jQuery("#sync_to").val(10); 
    jQuery("#user_role").val(user_role);      
    jQuery("#sync_dialog").dialog({
         modal: true
    });    
}    
    
var sync_request;
            
function sync(){
    (function($){
        $("#sync_status").empty();
        $("#sync_button").fadeOut('slow');
        $("#sync_stop").show('slow');
        $("#sync_close").fadeOut('slow');
        var diff = $("#sync_to").val() - $("#sync_from").val() + 1;
    	sync_request = $.ajax({
    		type: "post",url: ajaxurl, data: $("#sync_form").serialize(),
    		beforeSend: function() {$("#loading_sync").fadeIn('slow');},
    		success: function(data){
                $("#loading_sync").fadeOut('slow'); 
                $("#sync_stop").hide('slow');
                $("#sync_button").fadeIn('slow');
                $("#sync_close").fadeIn('slow');                                              
                $("#sync_status").append(data);                
                if( $("#sync_status").text() != "" ){
                    $("#sync_from").val( Number($("#sync_from").val()) + Number(diff) );
                    $("#sync_to").val( Number($("#sync_to").val()) + Number(diff) );
                    if( $("#auto_sync").attr("checked") == 'checked' ){
                        sync();
                    }                    
                }                
    		}
    	});                        
    })(jQuery);
}  

function sync_stop(){
    sync_request.abort(); 
    jQuery("#loading_sync").fadeOut('slow');      
    jQuery("#sync_button").fadeIn('slow');
    jQuery("#sync_close").fadeIn('slow');
    jQuery("#sync_stop").hide('slow');
}   

function close_sync_dialog(){
    jQuery("#sync_dialog").dialog("close");
}       