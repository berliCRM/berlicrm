/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

 jQuery.Class("Settings_Mailchimp_Index_Js",{

    saveMailchimpParameter : function() {
		jQuery('#savemailchimpconfig').click(function(e){
             e.preventDefault();
			// e.stopPropagation();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
            var form = jQuery(e.currentTarget);
            var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.apikey == '')) {
                data = {
                    title : app.vtranslate('KEY_REQUIRED'),
                    animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }

			var params = 'index.php?module=Mailchimp&view=List&action=UpdateMailchimpSettings&inputdata='+jQuery.param(inputdata);
			AppConnector.request(params).then(
				function(result) {
					if(result=='OK'){
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_SUCCESS'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					} 
				}
			);
			return true;
        });
    }
},
{
    saveMailchimp : function() {
		jQuery("#savemailchimpconfig").unbind().click(function(event){
             event.preventDefault();
			// event.stopPropagation();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
            var form = jQuery('form[name="mailchimpsettings"]');
            var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.apikey == '')) {
                data = {
                    title : app.vtranslate('KEY_REQUIRED'),
                    animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }

			var params = 'index.php?module=Mailchimp&view=List&parent=Settings&action=UpdateMailchimpSettings&inputdata='+jQuery.param(inputdata);
			AppConnector.request(params).then(
				function(result) {
					if(result=='OK'){
						params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_SUCCESS'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
						document.getElementById('savemailchimpconfig').style.visibility='hidden'; 
						document.getElementById('editmailchimpconfig').style.visibility='visible'; 
						
						document.getElementById('makeContact').setAttribute('disabled',true); 
						document.getElementById('makeLead').setAttribute('disabled',true); 
						document.getElementById('apikey').setAttribute('disabled',true); 
					}
					else {
						data  = {
							title : result,
							animation: 'show',
							type: 'error'
						};
						Vtiger_Helper_Js.showPnotify(data);
					}					
				}
			);
			return true;
		});
    },
    switchButton : function() {
		jQuery("#editmailchimpconfig").unbind().click(function(event){
			document.getElementById('savemailchimpconfig').style.visibility='visible'; 
			document.getElementById('editmailchimpconfig').style.visibility='hidden'; 
			document.getElementById('makeContact').removeAttribute('disabled'); 
			document.getElementById('makeLead').removeAttribute('disabled'); 
			document.getElementById('apikey').removeAttribute('disabled'); 
		});
    },

    registerEvents : function(){
        var container = jQuery('.MailchimpContainer');
		this.saveMailchimp();
		this.switchButton();
	}

});
