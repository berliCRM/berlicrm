/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

jQuery.Class("Settings_EmailConfigurator_Index_Js",{
},
{
    
    registerDeleteButton : function() {
        jQuery(".deleteRecordButton").on("click",function() {
            var message = "Sind Sie sicher, dass Sie diese Email-Adresse löschen möchten?";
            var recordid= $(this).data("id");
            Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(function(e) {
                var params = {};
                params['action'] = 'DeleteAjax';
                params['module'] = 'EmailConfigurator';
                params['parent'] = 'Settings';
                params['record'] = recordid;
                
                AppConnector.request(params).then(
                    function(data) {
                        location.reload();
                    });
                
            });
        });
    },
    
    registerEvents : function(){
		this.registerDeleteButton();
	}
});