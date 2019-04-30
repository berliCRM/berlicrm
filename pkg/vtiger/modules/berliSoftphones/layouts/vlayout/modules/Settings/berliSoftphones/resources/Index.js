/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_Index_Js("Settings_berliSoftphones_Index_Js", {},{
});

jQuery(document).ready(function(e){
	$('input[name=radiocheck]').click( function() {
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
				'enabled' : true
			}
		});
		$('input[id^="phactive_"]').attr("checked", false);
		$(this).attr('checked', true);
		var checkboxid = $(this).attr("id");
		var params = {
				'checkboxid' : checkboxid,
				'module' : 'Settings:berliSoftphones',
				'action' : 'setSoftphone',
				'mode' : 'setSoftphone'
		}
		AppConnector.request(params).then(
				function(responseData){
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					if(responseData.success){
						var mparams = {
							title : app.vtranslate('LBL_PHONE_CONFIGURATION'),
							text: app.vtranslate(responseData.result[0]),
							animation: 'show',
							type: 'info'
							};
						Vtiger_Helper_Js.showPnotify(mparams);
					}
					else {
						var mparams = {
							title : app.vtranslate('BIG PROBLEM'),
							text: responseData.error.code,
							animation: 'show',
							type: 'error'
						};
						Vtiger_Helper_Js.showPnotify(mparams);
						return false;
					}
				},

				function(textStatus, errorThrown){
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
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
	});
});
