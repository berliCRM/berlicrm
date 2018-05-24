/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

Vtiger_Header_Js.extend('MailManager_QuickCreate_Js', {
	foldersClicked : false
}, {

	registerQuickCreatePostLoadEvents: function(form, params) {
		var thisInstance = this;
		form.find('#goToFullForm').remove();

		form.on('click','.cancelLink',function() {
			MailManager.resetLinkToDropDown();
		});

		form.on('submit', function(e){
			var invalidFields = form.data('jqv').InvalidFields;

			if (invalidFields.length > 0) {
				//If validation fails, form should submit again
				form.removeData('submit');
				form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
					'mode': 'hide'
				});
				e.preventDefault();
				return;
			}
			var recordPreSaveEvent = jQuery.Event(Vtiger_Edit_Js.recordPreSave);
			form.trigger(recordPreSaveEvent, {'value': 'edit', 'module': form.find('[name="module"]').val()});
			if (!(recordPreSaveEvent.isDefaultPrevented())) {
				MailManager.mail_associate_create(form, jQuery('form[name="relationship"]'));
			}
			e.preventDefault();
		});
		thisInstance.registerTabEventsInQuickCreate(form);
	}
});

if (typeof(MailManager) == 'undefined') {

	// Legacy classes used
	if (typeof VtigerJS_DialogBox == 'undefined') {
		VtigerJS_DialogBox = {
			block: function() { },
			unblock: function() { }
		}
	}
	if (typeof Form == 'undefined') {
		Form = {
			serialize: function(form) {
				return jQuery(form).serialize();
			}
		}
	}

	/*
	 * Namespaced javascript class for MailManager
	 */
	MailManager = {

		MailManagerUploadLimit : 6,

		/*
		* Utility function
		* Usage:
		* var output = MailManager.sprintf("String format %s, Number format %s", "VALUE", 10);
		*/
		sprintf: function(){
			var printString = arguments[0];
			for (var i = 1; i < arguments.length; ++i) {
				// Replace any %s, %d, %c with the variables.
				// TODO Format the argument w.r.t to format specifier
				printString = printString.replace(/%[a-z]+/, arguments[i]);
			}
			return printString;
		},

		/* Show error message */
		show_error: function(message){
			var errordiv = jQuery('#_messagediv_');

			if (message == '') {
				errordiv.text('').hide();
			} else {
				errordiv.html('<p>' + message + '</p>').css('display','block').addClass('mm_error').removeClass('mm_message');
				MailManager.placeAtCenter(errordiv);
			}
			MailManager.hide_error();
		},

		hide_error: function() {
			setTimeout( function() {
				jQuery('#_messagediv_').hide();
			}, 5000);
		},

		show_message: function(message){
			var errordiv = jQuery('#_messagediv_');
			if (message == '') {
				errordiv.text('').hide();
			} else {
				errordiv.html('<p>' + message + '</p>').css('display','block').removeClass('mm_error').addClass('mm_message');
				MailManager.placeAtCenter(errordiv);
			}
			MailManager.hide_error();
		},

		/* Base url for any ajax actions */
		_baseurl: function(){
			return "index.php?module=MailManager&view=Index&";
		},

		/* Translation support */
		i18n: function(key){
			return app.vtranslate(key);
		},

		/* Build the main ui */
		mainui: function(){
			var isMailBoxExists = jQuery('#isMailBoxExists').val();
			if (isMailBoxExists == 1) {
				MailManager.openCurrentFolder();
			}
			setTimeout(function() {
				if (isMailBoxExists == 1) {
					jQuery("#_folderprogress_").show();
					MailManager.mail_open_meta = {};
					if (MailManager.mail_reply_rteinstance) {
						MailManager.mail_reply_rteinstance.destroy();
						MailManager.mail_reply_rteinstance = false;
					}
					var message = app.vtranslate('JSLBL_Loading_Please_Wait')+' ....';
					var progressIndicatorElement = jQuery.progressIndicator({
						'message' : message,
						'position' : 'html',
						'blockInfo' : {
							'enabled' : true
						}
					});
				}
				AppConnector.request(MailManager._baseurl() + "_operation=mainui").then(function(response) { 
						//var response = MailManager.removeHidElement(transport.responseText);
						response = JSON.parse(response);
						MailManager._mainui_callback(response);
						if (isMailBoxExists == 1) {
							progressIndicatorElement.progressIndicator({
								'mode' : 'hide'
							})
							jQuery("#_folderprogress_").hide();
							var timeOut = jQuery("#refresh_timeout").val();
							if(timeOut != "" && timeOut !=0) {
								setInterval(MailManager.updateMailFolders, timeOut);
							}
							// Update the seleted folders to highlight them.
							jQuery('.defaultContainer').addClass('show');
							jQuery('.defaultContainer').removeClass('hide');
						} else {
							jQuery('.defaultContainer').addClass('hide');
							jQuery('.defaultContainer').removeClass('show');
						}
						var folderName = jQuery('#mm_selected_folder').val();
						MailManager.updateSelectedFolder(folderName);
					});
			}, 400);
		},

		openCurrentFolder : function() {
			if(jQuery("#mailbox_folder")) {
				var currentFolder = jQuery("#mailbox_folder").val();
				// This is added as we will be settings mailbox_folder with the current selected folder.
				// By this time we would have lost the last mailbox folder also
				if(currentFolder == 'mm_drafts') currentFolder = 'INBOX';

				if(currentFolder) {
					MailManager.folder_open(currentFolder);
				}else {
					MailManager.folder_open('INBOX');
				}
			} else {
				MailManager.folder_open('INBOX');
			}
		},

		updateMailFolders : function() {
			 AppConnector.request(MailManager._baseurl() + "_operation=mainui").then(function(response) { 
					//var response = MailManager.removeHidElement(transport.responseText);
					response = JSON.parse(response);
					jQuery('#_mainfolderdiv_').html(response['result']['ui']);
					MailManager.refreshCurrentFolder(); // this is used to refresh the mails in the folders

					var folderName = jQuery('#mm_selected_folder').val();
					MailManager.updateSelectedFolder(folderName);

					MailManager.triggerUI5Resize();
				}
				);
		},

		quicklinks_update: function() {
                        AppConnector.request(MailManager._baseurl() + "_operation=mainui&_operationarg=_quicklinks").then(function(response) { 
					//var response = MailManager.removeHidElement(transport.responseText);
					response = JSON.parse(response);
					jQuery("#_quicklinks_mainuidiv_").html(response['result']['ui']);
				}
				);
		},

		showSelectFolderDesc: function() {
			jQuery(".selectFolderValue").addClass('hide');
			jQuery(".selectFolderDesc").show();
			jQuery(".selectFolderDesc").removeClass('hide');
		},

		/* Intermedidate call back to build main ui */
		_mainui_callback: function(responseJSON){
			jQuery('#_mainfolderdiv_').html(responseJSON['result']['ui']);

			if (!responseJSON['result']['mailbox']) {
				MailManager.open_settings_detail();
			}

			MailManager.triggerUI5Resize();
		},


		moveMail : function(element) {

			function execute() {
				var temp = new Array();

				function getCheckedMails() {
					var cb_elements = jQuery('[name="mc_box"]');

					for (var i = 0; i < cb_elements.length; i++) {
						if (cb_elements[i].checked) {
							temp.push(cb_elements[i].value);
						}
					}
				}

				function validate() {
					getCheckedMails();	// Check the selected mails
					if(temp.length < 1) {
						alert(app.vtranslate('JSLBL_PLEASE_SELECT_ATLEAST_ONE_MAIL'));
						MailManager.resetFolderDropDown();
						return false;
					}
					return true;
				}

				function callbackFunction(response) {
					for(var i = 0; i<temp.length; i++){
						jQuery("#_mailrow_"+temp[i]).fadeOut(1500,function() {
							jQuery("#_mailrow_"+temp[i]).remove();
						});
					}
				}

				if(validate()) {
					var message = app.vtranslate('JSLBL_MOVING')+' ....';
					var progressIndicatorElement = jQuery.progressIndicator({
						'message' : message,
						'position' : 'html',
						'blockInfo' : {
							'enabled' : true
						}
					});
					VtigerJS_DialogBox.block();
					var moveToFolderName = jQuery("#moveFolderList").val();
					var currentFolderName = jQuery("#mailbox_folder").val();
					var params = {
						'_operation': 'mail',
						'_operationarg' : 'move',
						'_msgno' : encodeURIComponent(temp),
						'_folder' : encodeURIComponent(currentFolderName),
						'_moveFolder' : moveToFolderName.replace('�','')
					};
					MailManager.Request(MailManager._baseurl() , params, callbackFunction).then( function () {
						MailManager.folder_open(currentFolderName);
						progressIndicatorElement.progressIndicator({
							'mode' : 'hide'
						})
						alert(app.vtranslate('JSLBL_MAIL_MOVED'));
					});
				}
			}
			execute();
		},

		/* Refresh the main ui */
		reload_now: function(){
			MailManager.mainui();
		},

		/* Close all the div */
		close_all: function(){
			if (jQuery('#_contentdiv_'))    jQuery('#_contentdiv_').hide();
			if (jQuery('#_contentdiv2_'))   jQuery('#_contentdiv2_').hide();
			if (jQuery('#_messagediv_'))    jQuery('#_messagediv_').hide();
			if (jQuery('#_settingsdiv_'))   jQuery('#_settingsdiv_').hide();
			if (jQuery('#_replydiv_'))      jQuery('#_replydiv_').hide();
		},

		/* Open settings page */
		open_settings: function(){
			var message = app.vtranslate('JSLBL_Settings')+' ....';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=settings&_operationarg=edit").then(function(response) { 
                                        response = JSON.parse(response);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					MailManager.close_all();
					jQuery('#_settingsdiv_').show();
					//var response = MailManager.removeHidElement(transport.responseText);
					jQuery('#_settingsdiv_').html(response.result);

					// Update the seleted folders to highlight them.
					MailManager.updateSelectedFolder('mm_settings');
					jQuery('#mm_selected_folder').val('mm_settings');
					
					MailManager.triggerUI5Resize();
				}
				);
		},

		/* Open settings detail page */
		open_settings_detail: function(){
			var message = app.vtranslate('JSLBL_Settings')+' ....';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=settings&_operationarg=detail").then(function(response) { 
                                        response = JSON.parse(response);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					MailManager.close_all();
					jQuery('#_settingsdiv_').show();
					//var response = MailManager.removeHidElement(transport.responseText);
					jQuery('#_settingsdiv_').html(response.result);

					// Update the seleted folders to highlight them.
					MailManager.updateSelectedFolder('mm_settings');
					jQuery('#mm_selected_folder').val('mm_settings');

					MailManager.triggerUI5Resize();
				}
				);
		},

		handle_settings_confighelper: function(selectBox){
			var form = selectBox.form;

			var useServer = '', useProtocol = '', useSSLType = '', useCert = '';
			if (selectBox.value == 'gmail' || selectBox.value == 'yahoo') {
				useServer = 'imap.gmail.com';
				if(selectBox.value == 'yahoo') {
					useServer = 'imap.mail.yahoo.com';
				}
				useProtocol = 'IMAP4';
				useSSLType = 'ssl';
				useCert = 'novalidate-cert';
				jQuery('.settings_details').show();
				jQuery('.additional_settings').hide();
			} else  if (selectBox.value == 'fastmail') {
				useServer = 'mail.messagingengine.com';
				useProtocol = 'IMAP2';
				useSSLType = 'tls';
				useCert = 'novalidate-cert';
				jQuery('.settings_details').show();
				jQuery('.additional_settings').hide();
			} else if (selectBox.value == 'other') {
				useServer = '';
				useProtocol = 'IMAP4';
				useSSLType = 'ssl';
				useCert = 'novalidate-cert';
				jQuery('.settings_details').show();
				jQuery('.additional_settings').show();
			} else {
				jQuery('.settings_details').hide();
			}
			jQuery('.refresh_settings').show();
			// Clear the User Name and Password field
			jQuery('#_mbox_user').val('');
			jQuery('#_mbox_pwd').val('');

			if (useProtocol != '') {
				form._mbox_server.value = useServer;

				jQuery(form._mbox_protocol).each(function(node){
					node.checked = (node.value == useProtocol);
				});
				jQuery(form._mbox_ssltype).each(function(node){
					node.checked = (node.value == useSSLType);
				});
				jQuery(form._mbox_certvalidate).each(function(node){
					node.checked = (node.value == useCert);
				});
			}
		},

		/* Save the settings */
		save_settings: function(form){
			if(form._mbox_server.value == "") {
				alert(app.vtranslate('JSLBL_SERVERNAME_CANNOT_BE_EMPTY'));
				return false;
			}
			if(form._mbox_user.value == "") {
				alert(app.vtranslate('JSLBL_USERNAME_CANNOT_BE_EMPTY'));
				return false;
			}
			if(form._mbox_pwd.value == "") {
				alert(app.vtranslate('JSLBL_PASSWORD_CANNOT_BE_EMPTY'));
				return false;
			}
			var message = app.vtranslate('JSLBL_Saving_And_Verifying')+' ....';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var url = MailManager._baseurl() + "_operation=settings&_operationarg=save&" + Form.serialize(form);
                        AppConnector.request(url).then(function(data) { 
                                data = JSON.parse(data);
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				//var response = MailManager.removeHidElement(transport.responseText);
				//var responseJSON = JSON.parse(response);
				if (data['success']) {
					jQuery('#isMailBoxExists').val(1);
					jQuery('#folders').find(':last-child').remove();
					MailManager_QuickCreate_Js.foldersClicked = false;
					var imageEle = jQuery('.imageElement');
					var imagePath = imageEle.data('rightimage');
					imageEle.attr('src', imagePath);
					MailManager.mainui();
				} else {
					alert(app.vtranslate(data['error']['message']));
				}
			}
			);
		},
		
		/* Remove the settings */
		remove_settings: function(form){
			var message = app.vtranslate('JSLBL_Removing')+' ....';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=settings&_operationarg=remove&" + Form.serialize(form)).then(function(responseJSON) { 
                                        responseJSON = JSON.parse(responseJSON);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					MailManager.close_all();
					jQuery('#folders').find(':last-child').remove();
					jQuery('#isMailBoxExists').val(0);
					MailManager_QuickCreate_Js.foldersClicked = false;
					var imageEle = jQuery('.imageElement');
					var imagePath = imageEle.data('rightimage');
					imageEle.attr('src', imagePath);
					jQuery('#quickLinksInfo').html('');
					if (responseJSON['success']) {
						MailManager.reload_now();
					} else {
						alert(app.vtranslate(data['error']['message']));
					}
				}
				);
		},
		
		/* Close the settings */
		close_settings: function(){
			MailManager.close_all();
			jQuery('#_contentdiv_').show();

			// Toggle highlighting previous folder and current folder selection
			var folderName = jQuery('#mailbox_folder').val();
			MailManager.updateSelectedFolder(folderName);
			jQuery('#mm_selected_folder').val(folderName);
		},

		/* Open the folder listing */
		folder_open: function(name, page){
			if (typeof(page) == 'undefined')
				page = 0;

			var query = "";
			// Consider search string too
			if(jQuery('#search_txt').val()) {
				query = "&q=" +encodeURIComponent(jQuery('#search_txt').val());
			}
			if(jQuery('#search_type').val()) {
				query += "&type=" + encodeURIComponent(jQuery('#search_type').val());
			}
			var message = app.vtranslate('JSLBL_Loading')+' ' + name + ' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=folder&_operationarg=open&_folder=" + encodeURIComponent(name)  +
				"&_page=" + encodeURIComponent(page) + query).then(function(response) { 
                                        response = JSON.parse(response);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})

					// Toggle highlighting previous folder and current folder selection
					MailManager.updateSelectedFolder(name);

					// Update the selected MailBox folder name
					jQuery('#mailbox_folder').val(name);

					// Update the current selected folder, which will be used to highlight the selected folder
					jQuery('#mm_selected_folder').val(name);
					
					MailManager.mail_close();
					//var response = MailManager.removeHidElement(transport.responseText);
					jQuery('#_contentdiv_').html(response.result);
					
					// Clear last open mail
					jQuery('#_contentdiv2_').html('');
					
					// Updates the drop down used for move emails
					MailManager.updateMoveFolderList();

					// Bind "Enter" key for search on the Search text box
					MailManager.bindEnterKeyForSearch();
					var type = jQuery('#search_type').val();
					var dateformat = jQuery('#jscal_dateformat').val();
					var element = jQuery('#search_txt');
					if(type == 'ON') {
						if(element.length != 0) {
							element.closest('div').addClass('date')
							element.addClass('dateField').attr('data-date-format', dateformat);
							element.after(" <span class='add-on'><i class='icon-calendar'></i></span>");
							app.registerEventForDatePickerFields(element, true);
						}
					}else {
						element.closest('div').removeClass('date');
						element.removeClass('dateField').removeAttr('data-date-format');
						element.unbind('focus');
						element.next().remove("span.add-on");
						jQuery('#jscal_trigger_fval').hide();
					}
					MailManager.triggerUI5Resize();
				}
				);
		},

		updateSelectedFolder : function(currentSelectedFolder) {
			var prevFolderName = jQuery('#mm_selected_folder').val();
			if (jQuery('[id="_mailfolder_' + prevFolderName +'"]') && prevFolderName != currentSelectedFolder) {
				jQuery('[id="_mailfolder_' + prevFolderName +'"]').removeClass('mm_folder_selected');
				jQuery('[id="_mailfolder_' + prevFolderName +'"]').parent().removeClass('mm_folder_selected_background');
			}
			jQuery('[id="_mailfolder_'+ currentSelectedFolder +'"]').addClass('mm_folder_selected');
			jQuery('[id="_mailfolder_'+ currentSelectedFolder +'"]').parent().addClass('mm_folder_selected_background');
		},

		bindEnterKeyForSearch : function() {
			jQuery("#search_txt").keyup(function (event) {
				if(event.keyCode == 13){
					jQuery("#mm_search").click();
				}
			});
		},

		updateMoveFolderList : function() {
			if(jQuery('#mailbox_folder') && jQuery('#moveFolderList')) {
				var currentFolder = jQuery('#mailbox_folder').val();
				jQuery('#moveFolderList').find("option[value='"+currentFolder+"']").remove();

			}
		},

		refreshCurrentFolder: function(){
			var selectedFolder = jQuery('#mm_selected_folder').val();
			var currentFolderName = jQuery("#mailbox_folder").val();

			//check if the mail is open
			var mail = jQuery('#_contentdiv2_').css('display');
			if(selectedFolder == currentFolderName && currentFolderName !='mm_drafts' && mail != 'block') {
				MailManager.folder_open(currentFolderName, 0);
			}
		},

		/* Update count of unread mails on folder */
		folder_updateCount: function(folder, count){
			if (jQuery('#_mailfolder_' + folder)) {
				if (count) {
					jQuery('#_mailfolder_' + folder).addClass('mm_folder_selected').html(MailManager.sprintf("<b>%s (%s)</b>", folder, count));
				} else {
					jQuery('#_mailfolder_' + folder).addClass('mm_folder_selected').html(MailManager.sprintf("%s", folder));
				}
			}
		},

		/* Basic search for folder emails */
		search_basic: function(form){
			var frmparams = Form.serialize(form);

			var message = app.vtranslate('JSLBL_Searching')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=folder&_operationarg=open&" + frmparams).then(function(response) { 
                                        response = JSON.parse(response);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})

					MailManager.mail_close();
					//var response = MailManager.removeHidElement(transport.responseText);
					jQuery('#_contentdiv_').html(response.result);

					MailManager.triggerUI5Resize();
				}
				);

			return false;
		},

		// Meta information of currently opened mail
		mail_open_meta: {},

		/* Open email */
		mail_open: function(folder, msgno){

			var message = app.vtranslate('JSLBL_Opening')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			jQuery('#_mailrow_' + msgno).removeClass('fontBold');
			jQuery('#_mailrow_' + msgno).addClass('mm_normal');

                         AppConnector.request(MailManager._baseurl() + "_operation=mail&_operationarg=open&_folder=" + encodeURIComponent(folder) + "&_msgno=" + encodeURIComponent(msgno)).then(function(responseJSON) { 
                                        responseJSON = JSON.parse(responseJSON);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					//var response = MailManager.removeHidElement(transport.responseText);
					//var responseJSON = JSON.parse(response);
					var resultJSON = responseJSON['result'];
					if (!resultJSON['ui']) {
						Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_Failed_To_Open_Mail'));
						return;
					}

					MailManager.close_all();
					jQuery('#_contentdiv2_').show();
					jQuery('#_contentdiv2_').html(resultJSON['ui']);

					MailManager.mail_open_meta = resultJSON['meta'];
					var folderName = resultJSON['folder'];

					// Update folder count on UI
					MailManager.folder_updateCount(folderName, resultJSON['unread']);

					MailManager.mail_find_relationship();
				}
				);
		},


		/* Close email */
		mail_close: function(){
			MailManager.close_all();
			jQuery('#_contentdiv_').show();
			MailManager.mail_open_meta = {};
		},

		/* Mark mail as read */
		mail_mark_unread: function(folder, msgno){

			var message = app.vtranslate('JSLBL_Updating')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=mail&_operationarg=mark&_markas=unread&_folder=" + encodeURIComponent(folder) + "&_msgno=" + encodeURIComponent(msgno)).then(function(responseJSON) { 
                                        responseJSON = JSON.parse(responseJSON);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					//var response = MailManager.removeHidElement(transport.responseText);
					//var responseJSON = JSON.parse(response);
					var resultJSON = responseJSON['result'];

					if (responseJSON && resultJSON['status']) {
						MailManager.mail_close();

						var msgno = resultJSON['msgno'];
						jQuery('#_mailrow_' + msgno).removeClass('mm_normal');
						jQuery('#_mailrow_' + msgno).addClass('fontBold');

						MailManager.folder_updateCount(resultJSON['folder'], resultJSON['unread']);
					}
				}
				);
		},

		/*Print email */
		mail_print: function(){

			var subject = jQuery('#_mailopen_subject').html();
			var from = jQuery('#_mailopen_from').html();
			var to = jQuery('#_mailopen_to').html();
			var cc = jQuery('#_mailopen_cc') ? jQuery('#_mailopen_cc').html() : '';
			var date = jQuery('#_mailopen_date').html();
			var body = jQuery('#_mailopen_body').html();

			var content = window.open();
			content.document.write("<b>"+subject+"</b><br>");
			content.document.write("<br>From :" +from +"<br>");
			content.document.write("To :" +to+"<br>");
			cc == null ? '' : content.document.write("CC :" +cc+"<br>");
			content.document.write("Date :" + date+"<br>");
			content.document.write("<br>"+body +"<br>");

			content.print();
		},

		/* Lookup for mail relations in CRM */
		mail_find_relationship: function(){
			jQuery('#_mailrecord_findrel_btn_').html(MailManager.i18n('JSLBL_Finding_Relation') + '...');
			jQuery("#_mailrecord_findrel_btn_").attr('disabled', true);

			var meta = MailManager.mail_open_meta;
                        AppConnector.request(MailManager._baseurl() + "_operation=relation&_operationarg=find&_mfrom=" + encodeURIComponent(meta['from']) +
                        '&_msendto='+ encodeURIComponent(meta['sendto']) +
				'&_folder=' +encodeURIComponent(meta['folder']) +'&_msgno=' +encodeURIComponent(meta['msgno']) +'&_msguid=' +
				encodeURIComponent(meta['msguid'].replace('<', '&lt;').replace('>', '&gt;'))).then(function(responseJSON) { 
                                        responseJSON = JSON.parse(responseJSON);
					jQuery('#_mailrecord_findrel_btn_').html(MailManager.i18n('JSLBL_Find_Relation_Now'));
					jQuery("#_mailrecord_findrel_btn_").attr('disabled', false);
					jQuery('#_mailrecord_findrel_btn_').hide();
					//var response = MailManager.removeHidElement(transport.responseText);
					//var responseJSON = JSON.parse(response);
					var resultJSON = responseJSON['result'];

					jQuery('#_mailrecord_relationshipdiv_').html(resultJSON['ui']);

					MailManager.triggerUI5Resize();
				}
				);
		},

		/* Associate email to CRM record */
		mail_associate: function(form){

			var frmparams = Form.serialize(form);
			// No record is selected for linking?
			if (frmparams.indexOf('_mlinkto') == -1)
				return;

			var message = app.vtranslate('JSLBL_Associating')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=relation&_operationarg=link&" + frmparams).then(function(responseJSON) { 
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					//var response = MailManager.removeHidElement(transport.responseText);
					//var responseJSON = JSON.parse(response);
					var resultJSON = responseJSON['result'];
					if (resultJSON['ui']) {
						jQuery('#_mailrecord_relationshipdiv_').html(resultJSON['ui']);
					}

					MailManager.triggerUI5Resize();
				}
				);
		},

		/* Extended support for creating and linking */
		mail_associate_create_wizard: function(form){
			if (form._mlinktotype.value == '') {
				MailManager.mail_associate_create_cancel();
				return;
			}
			var thisInstance = this;

			var message = app.vtranslate('JSLBL_Loading')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var frmparams = Form.serialize(form);
                         AppConnector.request(MailManager._baseurl() + "_operation=relation&_operationarg=create_wizard&" + frmparams).then(function(response) {
					var quickCreateController = new MailManager_QuickCreate_Js();
					quickCreateController.handleQuickCreateData(response);
				}
				);
		},

		/* This will be used to perform actions on mails with an Linked record*/
		mail_associate_actions : function(form) {
			var selected = false;

			if(form._mlinkto.length != undefined) {
				for(i=0; i<form._mlinkto.length; i++) {
					if(form._mlinkto[i].checked) {
						selected = true;
					}
				}
			} else {
				if(form._mlinkto && form._mlinkto.checked) {
					selected = true;
				} else {
					form._mlinkto.checked = true;
					selected = true;
				}
			}

			// No record is selected for linking?
			if (selected == false) {
				Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_PLEASE_SELECT_ATLEAST_ONE_RECORD'));
				MailManager.resetLinkToDropDown();
				return false;
			}

			if(form._mlinktotype.value == 'Emails') {
				MailManager.mail_associate(form);
			} else if(form._mlinktotype.value == 'ModComments') {
				MailManager.showCommentWidget(form);
			} else {
				MailManager.mail_associate_create_wizard(form);
			}
		},

		mail_associate_create_cancel: function(){
			jQuery('#_relationpopupdiv_').hide();
			MailManager.resetLinkToDropDown();
			app.hideModalWindow();
		},

		mail_associate_create: function(form, mainform){

			var frmparams = Form.serialize(form);
			frmparams = frmparams.replace('module=', 'xmodule=').replace('action=', 'xaction=');
			frmparams += '&' + Form.serialize(mainform);

			jQuery('#globalmodal').find('.modal-header').progressIndicator({smallLoadingImage : true, 'mode' : 'show'});
			var message = app.vtranslate('JSLBL_Associating')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=relation&_operationarg=create&" + frmparams).then(function(responseJSON) {
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					//var response = MailManager.removeHidElement(transport.responseText);
					responseJSON = JSON.parse(response);
					var resultJSON = responseJSON['result'];
					if (resultJSON['ui']) {
						MailManager.mail_associate_create_cancel();
						jQuery('#_mailrecord_relationshipdiv_').html(resultJSON['ui']);
						MailManager.resetLinkToDropDown();

						MailManager.triggerUI5Resize();
						return true;
					}
				}
				);
		},

		// function to show the comment widget
		showCommentWidget : function(form) {
			var frmparams = Form.serialize(form);
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : '',
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=relation&_operationarg=commentwidget&" + frmparams).then(function(response) {
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					var callBackFunction = function(data){
						jQuery('.cancelLink', data).on('click',function(e){
							MailManager.resetLinkToDropDown();
						});

						jQuery('[name="saveButton"]',data).on('click',function(e){
							var valid = MailManager.addCommentValidate(data);
							if(valid){
								MailManager.saveComment(data);
							}
						});
					}
					app.showModalWindow(response,function(response){
						if(typeof callBackFunction == 'function'){
							callBackFunction(response);
						}
					},{
						'text-align' : 'left'
					});
				}
				);
		},

		addCommentValidate : function(form) {
			var element = jQuery('[name=commentcontent]', form);
			var comment = jQuery.trim(element.val());
			if(comment == '') {
				element.validationEngine('showPrompt',app.vtranslate('JSLBL_CANNOT_ADD_EMPTY_COMMENT'),'',"topLeft",true);
				return false;
			}
			return true;
		},

		saveComment : function(form){
			var _mlinkto = jQuery('[name="_mlinkto"]:checked').val();
			var _mlinktotype = jQuery('[name="_mlinktotype"]').val();
			var _msgno = jQuery('[name="_msgno"]').val();
			var _folder = jQuery('[name="_folder"]').val()
			var commentcontent = jQuery('[name="commentcontent"]').val();
			var frmparams = 'commentcontent='+commentcontent+'&_mlinkto='+_mlinkto+'&_mlinktotype='+_mlinktotype+'&_msgno='+_msgno+'&_folder='+_folder;
			var message = app.vtranslate('JSLBL_Saving')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=relation&_operationarg=create&" + frmparams).then(function(responseJSON) {
                            responseJSON = JSON.parse(responseJSON);
                    progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
                    var resultJSON = responseJSON['result'];
					if (resultJSON['ui']) {
						app.hideModalWindow(form);
						MailManager.resetLinkToDropDown();
					}
                }
            );
		},

		// Place an element at the center of the page
		placeAtCenter : function(element) {
			element.css("position","absolute");
			element.css("top", ((jQuery(window).height() - element.outerHeight()) / 4) + jQuery(window).scrollTop() + "px");
			element.css("left", ((jQuery(window).width() - element.outerWidth()) / 2) + jQuery(window).scrollLeft() + "px");

		},

		/* Compose new mail */
		mail_compose: function(){
			var params = {step: "step1", module: "MailManager", view: "MassActionAjax", mode: "showComposeEmailForm",
				selected_ids : "[]", excluded_ids : "[]"};
			Vtiger_Index_Js.showComposeEmailPopup(params);
		},

		createUploader : function (){
			var uploader = new qq.FileUploader({
				element: document.getElementById('file-uploader'),
				action : 'index.php?module=MailManager&action=MailManagerAjax&file=index&mode=ajax&_operation=relation&_operationarg=saveattachment',

				template: '<div class="qq-uploader">' +
				'<div class="qq-upload-drop-area"><span>'+MailManager.i18n('JSLBL_UPLOAD_DROPFILES')+'</span></div>' +
				'<div class="qq-upload-button">'+MailManager.i18n('JSLBL_UPLOAD_FILE')+'</div>' +
				'<ul class="qq-upload-list"></ul>' +
				'</div>',

				// template for one item in file list
				fileTemplate: '<li>' +
				'<span class="qq-upload-file small"></span>' +
				'<span class="qq-upload-spinner small"></span>' +
				'<span class="qq-upload-size small"></span>' +
				'<a class="qq-upload-cancel small" href="#">'+MailManager.i18n('JSLBL_UPLOAD_CANCEL')+'</a>' +
				'<a class="qq-upload-deleteupload small" href="#">\n\
									<img height="12" border="0" width="12" title='+MailManager.i18n('JSLBL_Delete')+' src="themes/images/no.gif"></a>' +
				'<span class="qq-upload-failed-text small">'+MailManager.i18n('JSLBL_UPLOAD_FAILED')+'</span>' +
				'</li>',
				multiple: false,
				classes: {
					// used to get elements from templates
					button: 'qq-upload-button',
					drop: 'qq-upload-drop-area',
					dropActive: 'qq-upload-drop-area-active',
					list: 'qq-upload-list',

					file: 'qq-upload-file',
					spinner: 'qq-upload-spinner',
					size: 'qq-upload-size',
					cancel: 'qq-upload-cancel',
					deleteupload: 'qq-upload-deleteupload',
					// added to list item when upload completes
					// used in css to hide progress spinner
					success: 'qq-upload-success',
					fail: 'qq-upload-fail'
				}
			});
			return uploader;
		},

		//draft
		mail_draft: function(id, edit){
			var params = {module: "Emails", view: "ComposeEmail", mode: "emailPreview", record: id}
			if (typeof edit != 'undefined' && edit) params['mode'] = 'emailEdit';
			var emailEditInstance = new Emails_MassEdit_Js();
			var win = emailEditInstance.showComposeEmailForm(params);
			var folder = jQuery('#mailbox_folder').val();
			if (folder == 'mm_drafts') {
				var timer = setInterval(function() {
					if(win.closed) {
						clearInterval(timer);
						MailManager.folder_drafts(0);
					}
				}, 500);
			}
		},

		deleteAttachment : function(id, docid, ele) {
			var message = app.vtranslate('JSLBL_Loading')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
                        AppConnector.request(MailManager._baseurl() + "_operation=mail&_operationarg=deleteAttachment&emailid="+ encodeURIComponent(id)
				+"&docid="+ encodeURIComponent(docid)).then(function(responseJSON) {
                                        responseJSON = JSON.parse(responseJSON);
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					//var responseJSON = JSON.parse(response.responseText);
					if(responseJSON.result.success == true) {
						jQuery(ele).parent().fadeTo('slow', 0.0, function(){
							var count = jQuery('#attachmentCount').val();
							jQuery('#attachmentCount').val(--count);
							jQuery(ele).parent().remove();
						});
					} else {
						Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_ATTACHMENT_NOT_DELETED'));
					}
				}
				);
		},

		/* Reply to mail */
		mail_reply: function(all){
			if (typeof(all) == 'undefined')
				all = true;

			// TODO Strip invalid HTML?
			var from = jQuery('#_mailopen_from').html();
			var cc = all ? jQuery('#_mailopen_cc').html() : '';
			var subject = jQuery('#_mailopen_subject').html();
			var body = jQuery('#_mailopen_body').html();
			var date = jQuery('#_mailopen_date').html();

			var replySubject = (subject.toUpperCase().indexOf('RE:') == 0) ? subject : 'Re: ' + subject;
			var replyBody = MailManager.sprintf('<p></p><p style="margin:0;padding:0;">%s, %s, %s:</p><blockquote style="border:0;margin:0;border-left:1px solid gray;padding:0 0 0 2px;">%s</blockquote><br />', 'On ' + date, from, 'wrote', body);

			function fillComposeEmailForm(win) {
				var formValues = {
					'#ccContainer input' : cc,
					'[name="subject"]': replySubject
				}
				var rteValues = {
					'description': replyBody
				}
				win['app']['setFormValues'](formValues);
				win['app']['setRTEValues'](rteValues);
				if (cc) {
					win['jQuery']('#ccLink').trigger('click');
				}
			}

			var params = {step: "step1", module: "MailManager", view: "MassActionAjax", mode: "showComposeEmailForm", selected_ids:"[]", excluded_ids: "[]", to:'["'+from+'"]'}
			Vtiger_Index_Js.showComposeEmailPopup(params, function(win){
				if (typeof win != 'undefined') {
					setTimeout(function() {fillComposeEmailForm(win);}, 2000);
				}
			});
		},

		/* Track and Initialize RTE instance for reply */
		mail_reply_rteinstance: false,
		mail_reply_rteinit: function(data){
			if (MailManager.mail_reply_rteinstance == false) {
				var textAreaName = '_mail_replyfrm_body_';
				CKEDITOR.replace(textAreaName, {
					toolbar: 'Full',
					extraPlugins: 'uicolor',
					uiColor: '#dfdff1'
				});
				MailManager.mail_reply_rteinstance = CKEDITOR.instances[textAreaName];
			}

			MailManager.mail_reply_rteinstance.setData(data, function(){
				});
			MailManager.mail_reply_rteinstance.focus();
		},

		/* Close reply UI */
		mail_reply_close: function(){
			jQuery('#_replydiv_').hide();
			if(jQuery('#mm_selected_folder').val()=='mm_settings'){
				MailManager.open_settings();
			}
			else{
				var contentDiv2 = jQuery('#_contentdiv2_').html();
				if (contentDiv2 == '') {
					jQuery('#_contentdiv_').show();
				} else {
					jQuery('#_contentdiv2_').show();
				}

				// Updated to highlight selected folder
				var currentSelectedFolder = jQuery('#mailbox_folder').val();
				MailManager.updateSelectedFolder(currentSelectedFolder);
				jQuery('#mm_selected_folder').val(currentSelectedFolder);
			}

			MailManager.triggerUI5Resize();
		},

		/* Forward email */
		mail_forward: function(messageId){

			/**
			 * If mail has no attachment - open the popup in compose mode.
			 * Else create a draft with attachment - open the popup as draft edit mode.
			 */

			var from = jQuery('#_mailopen_from').html();
			var to = jQuery('#_mailopen_to').html();
			var cc = jQuery('#_mailopen_cc') ? jQuery('#_mailopen_cc').html() : '';
			var subject = jQuery('#_mailopen_subject').html();
			var body = jQuery('#_mailopen_body').html();
			var date = jQuery('#_mailopen_date').html();
			var folder = jQuery('#mailbox_folder').val();

			var fwdMsgMetaInfo = MailManager.i18n('JSLBL_FROM') + from + '<br/>'+MailManager.i18n('JSLBL_DATE') + date + '<br/>'+MailManager.i18n('JSLBL_SUBJECT') + subject;
			if (to != '' && to != null)
				fwdMsgMetaInfo += '<br/>'+MailManager.i18n('JSLBL_TO') + to;
			if (cc != '' && cc != null)
				fwdMsgMetaInfo += '<br/>'+MailManager.i18n('JSLBL_CC') + cc;
			fwdMsgMetaInfo += '<br/>';

			var fwdSubject = (subject.toUpperCase().indexOf('FWD:') == 0) ? subject : 'Fwd: ' + subject;
			var fwdBody = MailManager.sprintf('<p></p><p>%s<br/>%s</p>%s', MailManager.i18n('JSLBL_FORWARD_MESSAGE_TEXT'), fwdMsgMetaInfo, body);

			var attachmentCount = jQuery("#_mail_attachmentcount_").val();
			if(attachmentCount) {
				VtigerJS_DialogBox.block();
                                AppConnector.request(MailManager._baseurl() + "_operation=mail&_operationarg=forward&messageid=" + 
					encodeURIComponent(messageId) +"&folder=" + encodeURIComponent(folder) +"&subject=" + encodeURIComponent(fwdSubject) +
					"&body=" + fwdBody).then(function(responseJSON) {
                                            responseJSON = JSON.parse(responseJSON);
						VtigerJS_DialogBox.unblock();
						// Open the draft the was saved.
						if (responseJSON['success']) {
							MailManager.mail_draft(responseJSON['result']['emailid'], true);
						}
					}
					);

			} else {
				// Populate the popup window
				function fillComposeEmailForm(win) {
					var formValues = {
						'[name="subject"]': fwdSubject
					}
					var rteValues = {
						'description': fwdBody
					}
					win['app']['setFormValues'](formValues);
					win['app']['setRTEValues'](rteValues);
				}
				var params = {step: "step1", module: "MailManager", view: "MassActionAjax", mode: "showComposeEmailForm", selected_ids:"[]", excluded_ids: "[]"}
				Vtiger_Index_Js.showComposeEmailPopup(params, function(win){
					if (typeof win != 'undefined') {
						setTimeout(function() {fillComposeEmailForm(win);}, 2000);
					}
				});
			}
		},

		/* Send reply to email */
		mail_reply_send: function(form){
			if (MailManager.mail_reply_rteinstance) {
				MailManager.mail_reply_rteinstance.updateElement();
			}
			var meta = MailManager.mail_open_meta;

			var msguid = encodeURIComponent(meta['msguid'] ? meta['msguid'].replace('<', '&lt;').replace('>', '&gt;') : '');

			if(!MailManager.validateEmailFields(form.to.value, form.cc.value, form.bcc.value)) {
				return false;
			}

			if (form.to.value == '') {
				Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_Recepient_Cannot_Be_Empty'));
				return false;
			}
			if (form.subject.value == '') {
				Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_Subject_Cannot_Be_Empty'));
				return false;
			}
			var bodyval = $('_mail_replyfrm_body_').value.trim();
			if (bodyval == '<br />' || bodyval == '') {
				Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_Body_Cannot_Be_Empty'));
				return false;
			}
			var message = app.vtranslate('JSLBL_Sending')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'mail',
				'_operationarg':'send',
				'_msgid':msguid,
				'to':encodeURIComponent(form.to.value),
				'cc':encodeURIComponent(form.cc.value),
				'bcc':encodeURIComponent(form.bcc.value),
				'subject':encodeURIComponent(form.subject.value),
				'body':encodeURIComponent(form.body.value),
				'linkto':encodeURIComponent(form.linkto.value),
				'emailid':encodeURIComponent(form.emailid.value)
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function(transport) {
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				var response = MailManager.removeHidElement(transport.responseText);
				var responseJSON = JSON.parse(response);
				if (responseJSON['success']) {
					MailManager.mail_reply_close();
					MailManager.show_message(MailManager.i18n('JSLBL_MAIL_SENT'));
				} else {
					Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_Failed_To_Send_Mail') +
						': ' + responseJSON['error']['message']);
				}
			});
		},

		/* Send reply to email */
		save_draft: function(form){
			if (MailManager.mail_reply_rteinstance) {
				MailManager.mail_reply_rteinstance.updateElement();
			}

			if(!MailManager.validateEmailFields(form.to.value, form.cc.value, form.bcc.value)) {
				return false;
			}

			if (form.subject.value == '' ) {
				if(!confirm(MailManager.i18n('JSLBL_SaveWith_EmptySubject'))) {
					return false;
				}
			}

			var message = app.vtranslate('JSLBL_Saving')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'mail',
				'_operationarg':'save',
				'emailid':encodeURIComponent(form.emailid.value),
				'to':encodeURIComponent(form.to.value),
				'cc':encodeURIComponent(form.cc.value),
				'bcc':encodeURIComponent(form.bcc.value),
				'subject':encodeURIComponent(form.subject.value),
				'body':encodeURIComponent(form.body.value),
				'linkto':encodeURIComponent(form.linkto.value),
				'currentid':encodeURIComponent(form.emailid.value)
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function(transport) {
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				var response = MailManager.removeHidElement(transport.responseText);
				var responseJSON = JSON.parse(response);

				if (responseJSON['success']) {
					MailManager.show_message(MailManager.i18n('JSLBL_DRAFT_MAIL_SAVED'));
				} else {
					Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_Failed_To_Save_Mail'));
				}
			});
		},

		folder_drafts: function(page){
			var message = app.vtranslate('JSLBL_Loading')+' '+app.vtranslate('JSLBL_Loading');
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'folder',
				'_operationarg':'drafts',
				'_page':encodeURIComponent(page)
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function(transport) {
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				MailManager.mail_close();
				var resultObject = JSON.parse(transport.responseText);
				jQuery('#_contentdiv_').html(resultObject.result);
				// Initialize upload
				//MailManager.createUploader();

				MailManager.bindEnterKeyForSearch();

				// Update the selected folder to highlight selected folder
				MailManager.updateSelectedFolder('mm_drafts');
				jQuery('#mm_selected_folder').val('mm_drafts');
				jQuery('#mailbox_folder').val('mm_drafts');

				MailManager.triggerUI5Resize();
			});
		},

		search_popupui: function(target, handle){
			var message = app.vtranslate('JSLBL_Loading')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'search',
				'_operationarg':'popupui'
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function(transport) {
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				var response = MailManager.removeHidElement(transport.responseText);
				jQuery('#_popupsearch_').html(response);
				MailManager.placeAtCenter(jQuery('#_popupsearch_'));
				jQuery('#_popupsearch_').show().draggable();
				MailManager.search_popup_init(target);

				MailManager.triggerUI5Resize();
			});
		},

		search_popup_init: function(target){
			var url = MailManager._baseurl() + "_operation=search&_operationarg=email&";

			if (jQuery('#_search_popupui_target_')) {
				jQuery('#_search_popupui_target_').val(target);
			}

			var elem = jQuery('#_search_popupui_input_');
			if (elem) {
				if (elem.attr('_tokeninput_init_'))
					return;
				elem.tokenInput(url, {
					hintText: MailManager.i18n('JSLBL_Search_For_Email') + '...',
					noResultsText: MailManager.i18n('JSLBL_Nothing_Found'),
					searchingText: MailManager.i18n('JSLBL_Searching_Please_Wait') + '...',
					minChars : 3,
					classes: {
						tokenList: "token-input-list-facebook",
						token: "token-input-token-facebook",
						tokenDelete: "token-input-delete-token-facebook",
						selectedToken: "token-input-selected-token-facebook",
						highlightedToken: "token-input-highlighted-token-facebook",
						dropdown: "token-input-dropdown-facebook",
						dropdownItem: "token-input-dropdown-item-facebook",
						dropdownItem2: "token-input-dropdown-item2-facebook",
						selectedDropdownItem: "token-input-selected-dropdown-item-facebook",
						inputToken: "token-input-list-facebook"
					}
				});
				elem.attr('_tokeninput_init_', true);
			}
		},

		search_consume_input: function(form){
			var inputstr = form._search_popupui_input_.value;
			var target = form._search_popupui_target_.value;

			// Based on target perform the operation
			var targetnode = $(target);
			if (targetnode) {
				if (targetnode.value.length > 0 && targetnode.value.substr(-1) != ',') {
					inputstr = ',' + inputstr;
				}
				targetnode.value += inputstr;
			}
			MailManager.popup_close();
		},

		popup_close: function(){
			jQuery('#_popupsearch_').html('');
			jQuery('#_popupsearch_').hide();

			MailManager.triggerUI5Resize();
		},

		clear_input: function(id){
			if (jQuery("#"+id))
				jQuery("#"+id).val('');
		},

		selectTemplate: function() {
			url = 'module=EmailTemplate&parent=Settings&view=List';
			var popupInstance = Vtiger_Popup_Js.getInstance();
			popupInstance.show(url,function(data){
				var responseData = JSON.parse(data);
				for(var key in responseData){
					responseData = responseData[key];
					break;
				}
				jQuery('#_mail_replyfrm_subject_').val(responseData['name']);
				CKEDITOR.instances['_mail_replyfrm_body_'].setData(responseData['info']);
			});
		},

		removeHidElement: function(jsonresponse){
			// PHPSESSID is General value
			// Session Name should be picked from php.ini
			var replaceJsonTxt = jsonresponse.replace('/<input type="hidden" name="PHPSESSID" value=["]{1}[a-z0-9]+["]{1}\s{0,1}[/]?[>]?/', '');
			return replaceJsonTxt;
		},

		massMailDelete: function(folder){
			var cb_elements = jQuery('[name="mc_box"]');
			var temp = new Array();
			var len = jQuery('[name="mc_box"]').length;
			for (var i = 0; i < len; i++) {
				if (cb_elements[i].checked) {
					temp.push(cb_elements[i].value);
				}
			}
			if (temp.length == 0) {
				return alert(app.vtranslate('JSLBL_NO_EMAILS_SELECTED'));
			} else {
				MailManager.maildelete(folder, temp, true);
			}
		},

		maildelete: function(foldername, msgno, reloadfolder){
			var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
			if (!confirm(message)) return;

			var message = app.vtranslate('JSLBL_Deleting')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'mail',
				'_operationarg':'delete',
				'_folder':encodeURIComponent(foldername),
				'_msgno':encodeURIComponent(msgno)
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function() {
				for(var i = 0;i<msgno.length;i++) {
					var ele ="#_mailrow_"+msgno[i];
					jQuery(ele).fadeOut(1500,function() {
						jQuery(ele).remove();
					});
				}
				if(reloadfolder == true) {
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
					if(foldername == "__vt_drafts") {
						MailManager.folder_drafts();
					} else {
						MailManager.folder_open(foldername);
					}
				}
			});
		},

		show: function(ele){
			jQuery('#' + ele).css('display', 'block');
		},

		getDocuments : function(){
			if(!MailManager.checkUploadCount()) {
				return false;
			}
			var emailId = jQuery('#emailid').val();
			if(emailId == "") {
				var body = CKEDITOR.instances['_mail_replyfrm_body_'];
				if(body != "")
					body =  body.getData();

				var to = jQuery('#_mail_replyfrm_to_').val();
				var cc = jQuery('#_mail_replyfrm_cc_').val();
				var bcc = jQuery('#_mail_replyfrm_bcc_').val();
				var subject = jQuery('#_mail_replyfrm_subject_').val();
				VtigerJS_DialogBox.block();

				var params = {
					'_operation':'mail',
					'_operationarg':'save',
					'to':encodeURIComponent(to),
					'cc':encodeURIComponent(cc),
					'bcc':encodeURIComponent(bcc),
					'subject':encodeURIComponent(subject),
					'body':encodeURIComponent(body)
				};
				var baseurl = MailManager._baseurl();
				MailManager.Request(baseurl, params, function(response){
					var responseText = JSON.parse(response.responseText);
					emailId = responseText.result.emailid;
					jQuery('#emailid').val(emailId);
					window.open('index.php?module=Documents&return_module=MailManager&action=Popup&popuptype=detailview&form=EditView&form_submit=false&recordid='+emailId+'&forrecord='+emailId+'&parenttab=Marketing&srcmodule=MailManager&popupmode=ajax&RLreturn_module=MailManager&RLparent_id='+emailId+'&parenttab=My Home Page&callback=MailManager.add_data_to_relatedlist','test','width=640,height=602,resizable=0,scrollbars=0');
				});
			} else {
				window.open('index.php?module=Documents&return_module=MailManager&action=Popup&popuptype=detailview&form=EditView&form_submit=false&recordid='+emailId+'&forrecord='+emailId+'&parenttab=Marketing&srcmodule=MailManager&popupmode=ajax&RLreturn_module=MailManager&RLparent_id='+emailId+'&parenttab=My Home Page&callback=MailManager.add_data_to_relatedlist','test','width=640,height=602,resizable=0,scrollbars=0');
			}
			VtigerJS_DialogBox.unblock();
		},

		search_drafts: function(){
			var string = jQuery('#search_txt').val();
			if(string == '') {
				alert(app.vtranslate('JSLBL_ENTER_SOME_VALUE'));
				return false;
			}

			var type   = jQuery('#search_type').val();
			var message = app.vtranslate('JSLBL_Searching')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'folder',
				'_operationarg':'drafts',
				'q':encodeURIComponent(string),
				'type':encodeURIComponent(type)
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function(response){
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				MailManager.mail_close();
				var responseText = MailManager.removeHidElement(response.responseText);
				jQuery('#_contentdiv_').html(responseText.result);

				MailManager.triggerUI5Resize();
			});

			return false;
		},

		search_mails: function(foldername){
			var string = jQuery('#search_txt').val();
			if(string == '') {
				alert(app.vtranslate('JSLBL_ENTER_SOME_VALUE'));
				return false;
			}
			var type   = jQuery('#search_type').val();
			var dateformat = jQuery('#jscal_dateformat').val();
			var message = app.vtranslate('JSLBL_Searching')+' ...';
			var progressIndicatorElement = jQuery.progressIndicator({
				'message' : message,
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			var params = {
				'_operation':'folder',
				'_operationarg':'open',
				'q':encodeURIComponent(string),
				'type':encodeURIComponent(type),
				'_folder':encodeURIComponent(foldername)
			};
			var baseurl = MailManager._baseurl();
			MailManager.Request(baseurl, params, function(response){
				progressIndicatorElement.progressIndicator({
					'mode' : 'hide'
				})
				MailManager.mail_close();
				response = JSON.parse(response['responseText']);
				jQuery('#_contentdiv_').html(response.result);

				jQuery('#_mailfolder_' + foldername).addClass('mm_folder_selected');
				var element = jQuery('#search_txt');
				if(type == 'ON') {
					if(element.length != 0) {
						element.closest('div').addClass('date')
						element.addClass('dateField').attr('data-date-format', dateformat);
						element.after(" <span class='add-on'><i class='icon-calendar'></i></span>");
						app.registerEventForDatePickerFields(element, true);
					}
				}else {
					element.closest('div').removeClass('date');
					element.removeClass('dateField').removeAttr('data-date-format');
					element.unbind('focus');
					element.next().remove("span.add-on");
					jQuery('#jscal_trigger_fval').hide();
				}

				MailManager.triggerUI5Resize();

				MailManager.bindEnterKeyForSearch();

			});

			return false;
		},

		add_data_to_relatedlist: function(res){
			var fileSize, attachContent, element;
			fileSize = MailManager.computeDisplayableFileSize(res['size']);
			if(res.error != undefined) {
				attachContent = "<li class='qq-upload-success small'><span class='qq-upload-file small'>"+res['name']+"</span>\n\
								<span class='qq-upload-size small' style='display: inline;'>"+fileSize+"</span>\n\
								<span class='qq-upload-failed-text small' style='display: inline;'>Failed</span>";
				element = jQuery(window.opener.document).find('.qq-upload-list');
				jQuery(element[0]).append(attachContent);
				window.close();
				return false;
			}

			attachContent = "<li class='qq-upload-success small'><span class='qq-upload-file small'>"+res['name']+"</span>\n\
							<span class='qq-upload-size small' style='display: inline;'>"+fileSize+"</span>\n\
							<a class='qq-upload-deleteupload small' onclick='MailManager.deleteAttachment(\""+res['emailid']+"\", \""+res['docid']+"\", this);' href='#'>\n\
							<img height='12' border='0' width='12' title='Delete' src='themes/images/no.gif'></a></li>";

			try
			{
				element = jQuery(window.opener.document).find('.qq-upload-list');

				if(element[0]) {
					jQuery(element[0]).append(attachContent);
				} else {
					element = jQuery.find('.qq-upload-list');
					jQuery(element[0]).append(attachContent);
				}
				window.close();
			} catch(e) {
				element = jQuery.find('.qq-upload-list');
				jQuery(element[0]).append(attachContent);
			}
			// Update the attachment counter
			MailManager.uploadCountUpdater();

		},

		computeDisplayableFileSize : function(size) {
			var fileSize;
			if(size <= 1024) {
				fileSize = size+"b";
			} else if(size > 1024 && size < 1048576) {
				fileSize = (Math.round(size/1024))+"kB";
			} else if(size > (1024*1024)) {
				fileSize = (Math.round(size/(1024*1024)))+"MB";
			} else {
				fileSize = size;
			}
			return fileSize;
		},

		validateEmailFields :  function(to, cc, bcc) {
			if(to != "") {
				if(!MailManager.mail_validate(to)) {
					return false;
				}
			}
			if(cc != "") {
				if(!MailManager.mail_validate(cc)) {
					return false;
				}
			}
			if(bcc != "") {
				if(!MailManager.mail_validate(bcc)) {
					return false;
				}
			}
			return true;
		},

		mail_validate : function(str) {
			var email_regex = /^[_/a-zA-Z0-9]+([!"#$%&'()*+,./:;<=>?\^_`{|}~-]?[a-zA-Z0-9/_/-])*@[a-zA-Z0-9]+([\_\-\.]?[a-zA-Z0-9]+)*\.([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)?$/;
			arr = new Array();
			arr = str.split(",");
			var tmp;
			for(var i=0; i<=arr.length-1; i++) {
				tmp = arr[i];
				if(tmp.match('<') && tmp.match('>')) {
					if(!MailManager.findAngleBracket(arr[i])) {
						var errorMsg = app.vtranslate('JSLBL_EMAIL_FORMAT_INCORRECT');
						Vtiger_Helper_Js.showPnotify(errorMsg+": "+arr[i]);
						return false;
					}
				} else if(trim(arr[i]) != "" && !(email_regex.test(trim(arr[i])))) {
					var errorMsg2 = app.vtranslate('JSLBL_EMAIL_FORMAT_INCORRECT');
					Vtiger_Helper_Js.showPnotify(errorMsg2+": "+arr[i]);
					return false;
				}
			}
			return true;
		},

		findAngleBracket : function (mailadd) {
			var strlen = mailadd.length;
			var gt = 0;
			var lt = 0;
			var ret = '';
			for(i=0 ;i<strlen; i++) {
				if(mailadd.charAt(i) == '<' && gt == 0) {
					lt = 1;
				}
				if(mailadd.charAt(i) == '>' && lt == 1) {
					gt = 1;
				}
				if(mailadd.charAt(i) != '<' && lt == 1 && gt == 0) {
					ret = ret + mailadd.charAt(i);
				}
			}
			if(/^[a-z0-9]([a-z0-9_\-\.]*)@([a-z0-9_\-\.]*)(\.[a-z]{2,3}(\.[a-z]{2}){0,2})$/.test(ret)){
				return true;
			} else {
				return false;
			}
		},

		uploadCountUpdater : function() {
			var countElement;
			if(jQuery('#attachmentCount').length) {
				countElement = jQuery('#attachmentCount');
			} else {
				countElement = jQuery(window.opener.document).find('#attachmentCount');
			}
			var MailManagerCurrentUploadCount = countElement.val();
			if(MailManagerCurrentUploadCount == null || MailManagerCurrentUploadCount == "") {
				MailManagerCurrentUploadCount = 0;
			}
			countElement.val(++MailManagerCurrentUploadCount);
		},

		checkUploadCount : function() {
			var MailManagerCurrentUploadCount = jQuery("#attachmentCount").val();
			if(MailManagerCurrentUploadCount >= MailManager.MailManagerUploadLimit) {
				Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_FILEUPLOAD_LIMIT_EXCEEDED'));
				return false;
			}
			return true;
		},


		AjaxDuplicateValidate : function (module, fieldname, form) {
			var deferred = jQuery.Deferred();

			function execute() {
				var fieldvalue = encodeURIComponent(trim(getObj(fieldname).value));
				var recordid = getObj('record').value;

				function validate() {
					if(fieldvalue == '') {
						Vtiger_Helper_Js.showPnotify(app.vtranslate('JSLBL_ACCOUNTNAME_CANNOT_EMPTY'));
						deffered.reject(form);
						return false;
					}
					return true;
				}

				function requestOnComplete(response) {
					var str = response.responseText;
					VtigerJS_DialogBox.unblock();
					if(str.indexOf('SUCCESS') > -1) {
						deferred.resolve(form);
					} else {
						alert(str);
						deferred.reject(form);
					}
				}

				if(validate()) {
					VtigerJS_DialogBox.block();
					var params = {
						'module':encodeURIComponent(module),
						'action':encodeURIComponent(module)+"Ajax",
						'file':'Save',
						'dup_check':true,
						'record':encodeURIComponent(recordid)
					};
					params[fieldname] = encodeURIComponent(fieldvalue);
					MailManager.Request('index.php?', params, requestOnComplete);
					VtigerJS_DialogBox.unblock();
				}
			}
			// Trigger the function call
			execute();
			return deferred.promise();
		},

		Request : function(url, params, callback) {
			//var encodedParams = MailManager.getEncodedParameterString(params);
			return jQuery.ajax( {
				url  : url,
				type : 'POST',
				data : params,
				complete : function(response) {
					callback(response);
				}
			});
		},

		getEncodedParameterString : function (paramObject){
			var encodedParams = new Array();
			for(key in paramObject) {
				encodedParams.push(key+'='+ paramObject[key]);
			}
			encodedParams = encodedParams.join('&');
			return encodedParams;
		},

		clearSearchString : function() {
            jQuery('#search_txt').val("");
			jQuery('#search_type').val("");
		},

		resetLinkToDropDown : function() {
			jQuery('#_mlinktotype').val("");
		},

		resetFolderDropDown : function() {
			jQuery('#moveFolderList').val("")
		},

		toggleSelect : function(state, relCheckName) {
			var elements = jQuery('[name='+relCheckName+']');
			for(i=0; i<elements.length; i++) {
				var element = jQuery(elements[i]);
				if(state) {
					element.attr('checked',state).parent().parent().addClass('mm_lvtColDataHover').removeClass('mm_lvtColData');
				} else {
					element.attr('checked',state).parent().parent().removeClass('mm_lvtColDataHover').addClass('mm_lvtColData');
				}
			}
		},

		toggleSelectMail : function(state, element) {
			if(state) {
				jQuery(element).parent().parent().addClass('mm_lvtColDataHover').removeClass('mm_lvtColData');
			} else {
				jQuery(element).parent().parent().addClass('mm_lvtColData').removeClass('mm_lvtColDataHover');
			}

			var allChecked = false;
			if (state) {
				var allChecked = true;
				var elements = jQuery('[name="mc_box"]');
				for(var i=0; i<elements.length; i++) {
					var element = jQuery(elements[i]);
					var isChecked = jQuery(element).parent().parent().hasClass('mm_lvtColDataHover');
					if (!isChecked) {
						var allChecked = false;
						break;
					}
				}
			}
			jQuery('#parentCheckBox').attr('checked', allChecked);
		},

		highLightListMail : function(element) {
			jQuery(element).addClass('mm_lvtColDataHover').removeClass('mm_lvtColData');
		},

		unHighLightListMail : function(element) {
			jQuery(element).addClass('mm_lvtColData').removeClass('mm_lvtColDataHover');
			var state = jQuery(element).find('input:nth-child(1)')[0].checked;
			if(state){
				jQuery(element).addClass('mm_lvtColDataHover');
			}
		},

		addRequiredElements : function() {
			var option = jQuery('#search_type').val();
			var dateformat = jQuery('#jscal_dateformat').val();
			var element = jQuery('#search_txt');
			if(option == 'ON') {
				element.closest('div').addClass('date')
				element.addClass('dateField').attr('data-date-format', dateformat);
				element.after(" <span class='add-on'><i class='icon-calendar'></i></span>");
				jQuery('#search_txt').val("");
				app.registerEventForDatePickerFields(jQuery('.dateField'), true);
			} else {
				element.closest('div').removeClass('date');
				element.removeClass('dateField').removeAttr('data-date-format');
				element.unbind('focus');
				element.next().remove("span.add-on");
				jQuery('#jscal_trigger_fval').hide();
			}
		},

		getFoldersList: function() {
			var foldersList = jQuery('#foldersList').val();
			if (typeof foldersList !== 'undefined') {
				var imageEle = jQuery('.imageElement');
				if (jQuery('#foldersList').hasClass('hide')) {
					var imagePath = imageEle.data('downimage');
					jQuery('#foldersList').removeClass('hide');
				} else {
					var imagePath = imageEle.data('rightimage');
					jQuery('#foldersList').addClass('hide');
				}
				imageEle.attr('src', imagePath);
			} else {
				var progressElement = jQuery('#folders');
				progressElement.progressIndicator();

				var imageEle = jQuery('.imageElement');
				var imagePath = imageEle.data('downimage');
				imageEle.attr('src', imagePath);

				if(MailManager_QuickCreate_Js.foldersClicked == false) {
                                    AppConnector.request(MailManager._baseurl() + "_operation=folder&_operationarg=getFoldersList").then(function(response) { 
                                                response = JSON.parse(response);
						jQuery('#folders').append(response.result);
						progressElement.progressIndicator({'mode':'hide'});
						MailManager_QuickCreate_Js.foldersClicked = true;
					});
				}
			}
		},

		triggerUI5Resize: function() {
			if (parent.resizeUI5Iframe) parent.resizeUI5Iframe(self.document.body.scrollHeight);
		}
	}
}
