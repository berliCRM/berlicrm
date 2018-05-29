/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_Index_Js("Settings_gdpr_Index_Js", {},{

    operationMode : function() {
		jQuery('.globalpicklist').change( function(e) {
			e.preventDefault();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
			var picklist_id = $(this).attr("id");
			var picklist_val = jQuery("#"+picklist_id).val();

            // en-/disable form elements depending on globalMode
            if (picklist_id == "globalMode" && picklist_val == "m") {
                jQuery(".gdpr_auto_delete").prop("disabled",true);
            }
            if (picklist_id == "globalMode" && picklist_val == "a") {
                jQuery(".gdpr_auto_delete").prop("disabled",false).trigger("change");
                jQuery(".gdprRelevantModule").trigger("change");
            }

			var params = 'index.php?module=gdpr&parent=Settings&action=setGdprParameter&picklistid='+picklist_id+'&val='+picklist_val;
			AppConnector.request(params).then(
				function(data) {
					if (data.success==true) {
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_CONFIG_SAVED'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					}
					else {
						var error_msg = data.error;
						var params = {
							title : error_msg.code,
                            text: error_msg.message,
                            animation: 'show',
                            type: 'error'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
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
				}
			);
        });

        // on/off setting per module
        jQuery(".gdprRelevantModule").on("change",function(e){
            var elem = jQuery(e.currentTarget);
            var elemid = elem.attr("id");
            var fieldselect1 = elemid.replace("gdprRelevantModule","pickListDelete");
            var fieldselect2 = elemid.replace("gdprRelevantModule","gdprFields");
            if (elem.val()==1) {
                if (jQuery("#globalMode").val() == "a") {
                    jQuery("#"+fieldselect1).prop('disabled',false);
                }
                jQuery("#"+fieldselect2).select2('enable');
            }
            else {
                jQuery("#"+fieldselect1).prop('disabled',true);
                jQuery("#"+fieldselect2).select2('disable');
            }
        });

        // selection of auto deletion methode per module
        jQuery(".gdpr_auto_delete").on("change",function(e){
            var elem = jQuery(e.currentTarget);
            var elemid = elem.attr("id");
            var fieldselect = elemid.replace("pickListDelete","gdprFields");
            var formElement = jQuery("#gdprModules");

            // val == 2: only delete selected fields, so make field selection required
            if (elem.val() != 2) {
				jQuery("#"+fieldselect).removeAttr('data-validation-engine');
            }
            else {
				jQuery("#"+fieldselect).attr('data-validation-engine','validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]');
            }
        });

        // form validation
        jQuery("#gdprModules").on("submit",function(e){
            var validation = jQuery("#gdprModules").validationEngine('validate');
            if (validation) {
                // re-enable disabled input elements so they get submitted too
                jQuery(".gdpr_auto_delete").prop("disabled",false);
                return true;
            }
            // scroll error msg into view
            app.formAlignmentAfterValidation(jQuery("#gdprModules"));
            return false;
        });

        // init form elements depending on initial globalMode
        if (jQuery("#globalMode").val() == "m") {
            jQuery(".gdpr_auto_delete").prop("disabled",true);
        }
        if (jQuery("#globalMode").val() == "a") {
            jQuery(".gdpr_auto_delete").prop("disabled",false);
        }
        jQuery(".gdprRelevantModule").trigger("change");
        jQuery(".gdpr_auto_delete").trigger("change");

        // init validation
   		var params = app.validationEngineOptions;
        params.custom_error_messages = {'required': { message : app.vtranslate('LBL_CUSTOM_REQUIRED_ERROR')}};
        jQuery("#gdprModules").validationEngine(params);
    },

    registerEvents : function(){
		this.operationMode();
	}

});
