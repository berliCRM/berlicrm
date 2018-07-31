/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("Vtiger_createpdfstexttemplate_Js",{},{
    savePdfTemplate : function() {
		jQuery('#save_template').click(function(e){
			e.preventDefault();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
            var form = jQuery('form[name="createform"]');
            var inputdata = form.serializeFormData();
			var saveURL = 'index.php?parent=Settings&module=Vtiger&view=listpdftexttemplates&action=savepdftexttemplate&textmodules='+inputdata.textmodules+'&displaymodul='+inputdata.displaymodul+'&templateid='+inputdata.templateid+'&templatename='+encodeURIComponent(inputdata.templatename)+'&body='+encodeURIComponent(inputdata.body);
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.templatename.trim() == '')) {
                data = {
                    title : app.vtranslate('LBL_PROVIDE_NAME'),
                    animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			var actionParams = {
						"type":"POST",
						"url":saveURL,
						"dataType":"html",
						"data" : {}
			};
			AppConnector.request(actionParams).then(
				function(result) {
					if(result.trim() =='OK'){
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_SUCCESS'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
						window.location.href = 'index.php?parent=Settings&module=Vtiger&view=listpdftexttemplates';
					}
					else {
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
							text: app.vtranslate('LBL_FAILURE'),
							animation: 'show',
							type: 'error'
						};
						Vtiger_Helper_Js.showPnotify(params);
						return false;
					}
 				}
			);
       });
    },

	/**
	 * Function to register form for validation
	 */
	registerFormForValidation : function(){
		var editViewForm = this.getForm();
		editViewForm.validationEngine(app.validationEngineOptions);
	},
	
	/**
	 * Function which will handle the registrations for the elements 
	 */
	registerEvents : function() {
		this.savePdfTemplate();
		//this.registerFormForValidation();
	}

	
})