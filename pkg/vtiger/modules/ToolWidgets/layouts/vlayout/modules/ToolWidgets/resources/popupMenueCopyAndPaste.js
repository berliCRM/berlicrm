    /*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("ToolWidgets_popupMenueCopyAndPaste_Js",{
	/*
	 * Function to register button event
	 */
	registerCopyPasteButtonEvent: function(){
		var thisInstance = this;
		jQuery('#copypasteButton').on('click',function(e){
			var element = jQuery(e.currentTarget);
			ToolWidgets_popupMenueCopyAndPaste_Js.showmenu(element);
		});
	},

	registerCopyButton: function () {
		jQuery('#copy-button').on('click', function () {
			var textToCopy = jQuery('#copy-text').value;
			navigator.clipboard.writeText(textToCopy);
			jQuery('#copied').show();
		})
	},

	registerCloseCopied: function () {
		jQuery('#close-copied').on('click', function () {
			$("#copied").hide();
		})
	},

	showmenu: function(element) {
		var recordid = jQuery('#recordid').val();
		var sourcemodule = jQuery('#sourcemodule').val();
		var aDeferred = jQuery.Deferred();
		var params = {
			module: 'ToolWidgets',
			view: 'createCopyAndPasteMenu',
			sourcemodule: sourcemodule,
			recordid: recordid
			}

		AppConnector.request(params).then(
			function(data){
					app.showScrollBar(jQuery('#transferPopupScroll'), {
						height: '400px',
						railVisible: true,
						size: '6px'
					});
					var callbackFunction = function(data) {
						app.showScrollBar(jQuery('#transferPopupScroll'), {
							height: '400px',
							railVisible: true,
							size: '6px'
						});
					}
					app.showModalWindow(data, function(data){
						if(typeof callbackFunction == 'function' && jQuery('#transferPopupScroll').height() > 400){
							callbackFunction(data);
						}
					});
			},
			function(error){
				alert('Error Ajax: '+error.toString());
				aDeferred.reject();
			}
		)
	},
	
	registerEvents : function(){
		this.registerCopyPasteButtonEvent();
		this.registerCopyButton();
		this.registerCloseCopied();
	}

},{


});