/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

 jQuery.Class("Settings_berliCleverReach_Index_Js",{
},
{
	newsubscribertype : function() {
		
		jQuery("#makeContact").unbind().click(function(event){
			jQuery('#savecleverreachconfig').fadeIn();
		});
		jQuery("#makeLead").unbind().click(function(event){
			jQuery('#savecleverreachconfig').fadeIn();
		});
	},
	
    saveCleverReach : function() {
		jQuery("#savecleverreachconfig").unbind().click(function(event){
             event.preventDefault(); 
			// event.stopPropagation();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
            var form = jQuery('form[name="CleverReachSettings"]');
            var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
			
			// if API credential table is visible show error if incomplete
 			if( jQuery("#berliCleverReachCredentials").is(":visible") && (inputdata.customerid == '' || inputdata.customername == '' || inputdata.customerpassword == '')) {
                data = {
                    title : app.vtranslate('CR_CREDS_REQUIRED'),
                    animation: 'show',
					delay: 5000,
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
				jQuery("#berliCleverReachCredentials").effect("shake",{times:2},100);
                return false;
            }

			var params = 'index.php?module=berliCleverReach&view=List&parent=Settings&action=saveberliCleverReachSettings&inputdata='+jQuery.param(inputdata);

			AppConnector.request(params).then(
				function(responseData){
				   responseData = JSON.parse(responseData);
					if(responseData.success){
						var mparams = {
							title : responseData.result[0],
							animation: 'show',
							type: 'info'
							};
						Vtiger_Helper_Js.showPnotify(mparams);
						jQuery('#savecleverreachconfig').fadeOut();
						jQuery("#berliCleverReachApiState").text(responseData.result[1]);
						jQuery("#berliCleverReachCredentials").fadeOut(500, function(){jQuery("#berliCleverReachApiState").fadeIn(500);jQuery('#deletecleverreachconfig').fadeIn();}); 
					}
					else {
						var mparams = {
							title : responseData.error.code,
							text: responseData.error.message,
							animation: 'show',
							delay: 5000,
							type: 'error'
						};
						
						jQuery("#berliCleverReachCredentials").effect("shake",{times:2},100);
						Vtiger_Helper_Js.showPnotify(mparams);
						return false;
					}
				},

				function(textStatus, errorThrown){
					var mparams = {
						title : textStatus,
						text: errorThrown,
						animation: 'show',
						type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(mparams);
					return false;
				}
			);
			return true;
		});
    },
    switchButton : function() {
		jQuery("#deletecleverreachconfig").unbind().click(function(event){

			bootbox.confirm(app.vtranslate('LBL_CONFIRM_DELETION'), app.vtranslate('LBL_NO'), app.vtranslate('LBL_YES'), function(result) {
				
				if (!result) return;
				
				var params = 'index.php?module=berliCleverReach&view=List&parent=Settings&action=saveberliCleverReachSettings&removeAPI=true';
				AppConnector.request(params).then(
				function(responseData){
				   responseData = JSON.parse(responseData);
					if(responseData.success){
					
						jQuery('#CleverReachSettings').find("input[type=text]").val("");
						jQuery("#berliCleverReachCredentials").fadeIn();
						jQuery("#berliCleverReachApiState").hide();
						jQuery("#berliCleverReachApiState").html("&nbsp;");
						jQuery('#savecleverreachconfig').fadeIn();
						jQuery('#deletecleverreachconfig').hide();
						
					}
				});
				
			
			});
		});
    },
	
    registerEvents : function(){
        //var container = jQuery('.CleverReachContainer');
		this.saveCleverReach();
		this.newsubscribertype();
		this.switchButton();
	}
});
