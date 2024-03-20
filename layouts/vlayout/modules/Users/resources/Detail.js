/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("Users_Detail_Js",{
	
	triggerChangePassword : function (CHPWActionUrl, module){
		AppConnector.request(CHPWActionUrl).then(
			function(data) {
				if(data) {
					var callback = function(data) {
						var params = app.validationEngineOptions;
						params.onValidationComplete = function(form, valid){
							if(valid){
								Users_Detail_Js.savePassword(form)
							}
							return false;
						}
						jQuery('#changePassword').validationEngine(app.validationEngineOptions);
					}
					app.showModalWindow(data, function(data){
						if(typeof callback == 'function'){
							callback(data);
						}
					});
				}
			}
		);
	},
	
	savePassword : function(form){
		var new_password  = form.find('[name="new_password"]');
		var confirm_password = form.find('[name="confirm_password"]');
		var old_password  = form.find('[name="old_password"]');
		var userid = form.find('[name="userid"]').val();
		//Check Password
		var passwordOK = passwordChecker(new_password.val());
		//Complex Password is ok
		if(passwordOK) {
			if(new_password.val() == confirm_password.val()){
				var params = {
					'module': app.getModuleName(),
					'action' : "SaveAjax",
					'mode' : 'savePassword',
					'old_password' : old_password.val(),
					'new_password' : new_password.val(),
					'userid' : userid
				}
				AppConnector.request(params).then(
					function(data) {
						if(data.success){
							app.hideModalWindow();
							var  params1 = {
										title : app.vtranslate('JS_PASSWORD_CHANGE'),
										text : app.vtranslate(data.result.message),
										delay: '2000',
										type: 'success'
									}
							Vtiger_Helper_Js.showPnotify(params1);
						}else{
							old_password.validationEngine('showPrompt', app.vtranslate(data.error.message) , 'error','topLeft',true);
							return false;
						}
					}
				);
			} 
			else {
				new_password.validationEngine('showPrompt', app.vtranslate('JS_REENTER_PASSWORDS') , 'error','topLeft',true);
				return false;
			}
		}
		else {
			var  params2 = {
				title : app.vtranslate('JS_PASSWORD_CHANGE'),
				text : app.vtranslate('JS_INCORRECTPW'),
				delay: '2000',
				type: 'error'
			}
			Vtiger_Helper_Js.showPnotify(params2);
			return false;
		}
	},
	
	/*
	 * function to trigger delete record action
	 * @params: delete record url.
	 */
    triggerDeleteUser : function(deleteUserUrl) {
		var message = app.vtranslate('LBL_DELETE_USER_CONFIRMATION');
		Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(function(data) {
				AppConnector.request(deleteUserUrl).then(
				function(data){
					if(data){
						var callback = function(data) {
							var params = app.validationEngineOptions;
							params.onValidationComplete = function(form, valid){
								if(valid){
									Users_Detail_Js.deleteUser(form)
								}
								return false;
							}
							jQuery('#deleteUser').validationEngine(app.validationEngineOptions);
						}
						app.showModalWindow(data, function(data){
							if(typeof callback == 'function'){
								callback(data);
							}
						});
					}
				});
			},
			function(error, err){
			}
		);
	},
	
	deleteUser: function (form){
		var userid = form.find('[name="userid"]').val();
		var transferUserId = form.find('[name="tranfer_owner_id"]').val();
		
		var params = {
				'module': app.getModuleName(),
				'action' : "DeleteAjax",
				'mode' : 'deleteUser',
				'transfer_user_id' : transferUserId,
				'userid' : userid
			}
		AppConnector.request(params).then(
			function(data) {
				if(data.success){
					app.hideModalWindow();
					Vtiger_Helper_Js.showPnotify(app.vtranslate(data.result.status.message));
					var url = data.result.listViewUrl;
					window.location.href=url;
				}
			}
		);
	},
	
	triggerTransferOwner : function(transferOwnerUrl){
		var message = app.vtranslate('LBL_TRANSFEROWNER_CONFIRMATION');
		Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(function(data) {
				AppConnector.request(transferOwnerUrl).then(
				function(data){
					if(data){
						var callback = function(data) {
							var params = app.validationEngineOptions;
							params.onValidationComplete = function(form, valid){
								if(valid){
									Users_Detail_Js.transferOwner(form)
								}
								return false;
							}
							jQuery('#transferOwner').validationEngine(app.validationEngineOptions);
						}
						app.showModalWindow(data, function(data){
							if(typeof callback == 'function'){
								callback(data);
							}
						});
					}
				});
			},
			function(error, err){
			}
		);
	},
	
	transferOwner : function(form){
		var userid = form.find('[name="userid"]').val();
		var transferUserId = form.find('[name="tranfer_owner_id"]').val();
		
		var params = {
				'module': app.getModuleName(),
				'action' : "SaveAjax",
				'mode' : 'transferOwner',
				'transfer_user_id' : transferUserId,
				'userid' : userid
			}
		AppConnector.request(params).then(
			function(data) {
				if(data.success){
					app.hideModalWindow();
					Vtiger_Helper_Js.showPnotify(app.vtranslate(data.result.message));
					var url = data.result.listViewUrl;
					window.location.href=url;
				}
			}
		);
	},
	
	triggerChangeAccessKey: function (url) {
		var title = app.vtranslate('JS_NEW_ACCESS_KEY_REQUESTED');
		var message = app.vtranslate('JS_CHANGE_ACCESS_KEY_CONFIRMATION');
		Vtiger_Helper_Js.showConfirmationBox({'title': title,'message': message}).then(function (data) {
			AppConnector.request(url).then(function(data) {
				var params = {};
				if(data['success']) {
					data = data.result;
					params['type'] = 'success';
					message = app.vtranslate(data.message);
					var accessKeyEle = jQuery('#Users_detailView_fieldValue_accesskey');
					if (accessKeyEle.length) {
						accessKeyEle.find('.value').html(data.accessKey);
					}
				} else {
					message = app.vtranslate(data['error']['message']);
				}
				params['text'] = message;
				Vtiger_Helper_Js.showPnotify(params);
			});
		});
	},
	
},{
	
	usersEditInstance : false,
	
	updateStartHourElement : function(form) {
		this.usersEditInstance.triggerHourFormatChangeEvent(form);
		this.updateStartHourElementValue();
	},
	hourFormatUpdateEvent  : function() {
		var thisInstance = this;
		this.getForm().on(this.fieldUpdatedEvent, '[name="hour_format"]', function(e, params){
			thisInstance.updateStartHourElementValue();
		});
	},
	
	updateStartHourElementValue : function() {
		var form = this.getForm();
		var startHourSelectElement = jQuery('select[name="start_hour"]',form);
		var selectedElementValue = startHourSelectElement.find('option:selected').text();
		startHourSelectElement.closest('td').find('span.value').text(selectedElementValue);
	},
	
	startHourUpdateEvent : function(form) {
		var thisInstance = this;
		form.on(this.fieldUpdatedEvent, '[name="start_hour"]', function(e, params){
			thisInstance.updateStartHourElement(form);
		});
	},
	
	registerEvents : function() {
        this._super();
		var form = this.getForm();
		this.usersEditInstance = Vtiger_Edit_Js.getInstance();
		this.updateStartHourElement(form);
		this.hourFormatUpdateEvent();
		this.startHourUpdateEvent(form);
		Users_PreferenceEdit_Js.registerChangeEventForCurrencySeperator();
	}
	
});
function passwordChecker(passwordValue) {
	//Length Password
	var passwordLength = (passwordValue.length);
	//alert("Length: " + passwordLength);
	//Capital?
	var containsCapital = checkCapital(passwordValue);
	//alert("Capital: " + containsCapital);
	//Lower?
	var containsLower = checkLower(passwordValue);
	//alert("Lower: " + containsLower);
	//Number?
	var containsNumber = checkNumber(passwordValue);
	//alert("number: " + containsNumber);
	//Special Char?
	var containsSpecialChar = checkSpecialChar(passwordValue);
	//alert("Special Char:" + containsSpecialChar);

	//COMPLEX PASSWORD: Minimum 8 characters, and three of the four conditions needs to be ok --> Capital, Lowercase, Special Character, Number
	if(passwordLength < 10) {
		return false;
	}
	else {
		//Combination Match All
		if((containsNumber == true)&&(containsCapital == true)&&(containsLower == true)&&(containsSpecialChar == true)) {
			return true;
		}
		else {
			//Combination 1 
			if((containsNumber == true)&&(containsCapital == true)&&(containsLower == true) ) {
				return true;
			}
			else {
				//Combination 2
				if((containsCapital == true)&&(containsLower == true)&&(containsSpecialChar == true)) {
					return true;
				}
				else {
					//Combination 3
					if((containsLower == true)&&(containsSpecialChar == true)&&(containsNumber == true)) {
						return true;
					}
					else {	
						//Combination 4
						if((containsNumber == true)&&(containsCapital == true)&&(containsSpecialChar == true)) {
							return true;
						}
						else {
							return false;
						}
					}
				}
			}
		}
	}
}
//Check for special character
function checkSpecialChar(passwordValue) {
	var i=0;
	var ch='';
	while (i <= passwordValue.length){
    	character = passwordValue.charAt(i);
    	if ((character == ".")||(character =="!")||(character =="?")||(character ==",")||(character ==";")||(character =="-")||(character =="@")||(character =="#")){
			return true;
    	}
		i++;
	}
	return false;
}
//check for number
function checkNumber(passwordValue) {
	var i=0;
	var ch='';
	while (i <= passwordValue.length-1){
    	character = passwordValue.charAt(i);
    	if (!isNaN(character)){
		return true;
    	}
		i++;
	}
	return false;
}
//Check for lowercase character
function checkLower(passwordValue) {
	var i=0;
	var ch='';
	while (i <= passwordValue.length){
    	character = passwordValue.charAt(i);
		var patt1=/[a-zA-Z]/g
		var match = character.match(patt1);
		if(!(match == null)) {
    		if (!isNaN(character * 1)){
			//number
    		}
			else {
    			if (character == character.toUpperCase()) {
    			}
    			if (character == character.toLowerCase()){
				return true;
    			}
    		}
		}
    	i++;
	}
	return false;
}
//Check for capital
function checkCapital(passwordValue) {
	var i=0;
	var ch='';
	while (i <= passwordValue.length){
    	character = passwordValue.charAt(i);
		var patt1=/[a-zA-Z]/g
		var match = character.match(patt1);
		if(!(match == null)) {
    		if (!isNaN(character * 1)){
			//number
    		}
			else {
    			if (character == character.toUpperCase()) {
    				return true;
    			}
    			if (character == character.toLowerCase()){
    			}
    		}
		}
    	i++;
	}
	return false;
}
