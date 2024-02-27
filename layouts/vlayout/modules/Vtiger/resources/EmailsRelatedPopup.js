/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger_Popup_Js("Vtiger_EmailsRelatedModule_Popup_Js",{},{
	
	getListViewEntries: function(e){
		var thisInstance = this;
		var row  = jQuery(e.currentTarget);
		var id = row.data('id');
		var recordName = row.data('name');
		var emailFields = jQuery(row).find('.emailField');

		// we need to determine if it is a Verteiler or not
		var modulename = (document.getElementById('modulename')).value;
		if(modulename == 'Verteiler'){
			let idVerteilerIdcontactEmail = (document.getElementById('idVerteilerIdcontactEmail')).value;
			let selectedRecordDetails = {};

			if(typeof(idVerteilerIdcontactEmail)!= "undefined" && idVerteilerIdcontactEmail.trim() != ''){
				let idVerteilerIdcontactEmailArr = idVerteilerIdcontactEmail.split(';');
				for(let a=0;a<idVerteilerIdcontactEmailArr.length;a++){
					let tempArr =  idVerteilerIdcontactEmailArr[a].split(',');
					if(tempArr[0] == id){
						selectedRecordDetails[tempArr[1]] = {name : tempArr[2], 'email' : tempArr[3]};
					}
				}
			}
			else{
				var error = recordName+" "+app.vtranslate("JS_DO_NOT_HAVE_AN_EMAIL_ID");
				alert(error);
				e.preventDefault();
				return;
			}

			if(Object.keys(selectedRecordDetails).length <= 0) {
				var error = recordName+" "+app.vtranslate("JS_DO_NOT_HAVE_AN_EMAIL_ID");
				alert(error);
				e.preventDefault();
				return;
			}
			else{
				thisInstance.done(selectedRecordDetails, thisInstance.getEventName());
				e.preventDefault();
			}

		}
		else{
			var emailValue = '';
			jQuery.each(emailFields,function(i,element) {
				emailValue = jQuery(element).text();
				if(emailValue != ''){
					return false;
				}
			});
			if(emailValue == ""){
				var error = recordName+" "+app.vtranslate("JS_DO_NOT_HAVE_AN_EMAIL_ID");
				alert(error);
				e.preventDefault();
				return;
			}
			var response ={};
			response[id] = {'name' : recordName,'email' : emailValue} ;

			thisInstance.done(response, thisInstance.getEventName());
			e.preventDefault();
		}
	},
    
    registerSelectButton : function(){
		var popupPageContentsContainer = this.getPopupPageContainer();
		var thisInstance = this;
		popupPageContentsContainer.on('click','button.select', function(e){
			var tableEntriesElement = popupPageContentsContainer.find('table');
			var selectedRecordDetails = {};
			jQuery('input.entryCheckBox', tableEntriesElement).each(function(index, checkBoxElement){
				var checkBoxJqueryObject = jQuery(checkBoxElement)
				if(! checkBoxJqueryObject.is(":checked")){
					return true;
				}
				var row = checkBoxJqueryObject.closest('tr');
				var id = row.data('id');
                var name = row.data('name');

				// we need to determine if it is a Verteiler or not
				var modulename = (document.getElementById('modulename')).value;
				if(modulename == 'Verteiler'){
					let idVerteilerIdcontactEmail = (document.getElementById('idVerteilerIdcontactEmail')).value;
		
					if(typeof(idVerteilerIdcontactEmail)!= "undefined" && idVerteilerIdcontactEmail.trim() != ''){
						let idVerteilerIdcontactEmailArr = idVerteilerIdcontactEmail.split(';');
						for(let a=0;a<idVerteilerIdcontactEmailArr.length;a++){
							let tempArr =  idVerteilerIdcontactEmailArr[a].split(',');
							if(tempArr[0] == id){
								selectedRecordDetails[tempArr[1]] = {name : tempArr[2], 'email' : tempArr[3]};
							}
						}
					}
					else{
						var error = recordName+" "+app.vtranslate("JS_DO_NOT_HAVE_AN_EMAIL_ID");
						alert(error);
						e.preventDefault();
						return;
					}
				}
				else{
					var emailField = jQuery(row).find('.emailField');
					var emailValue = emailField.text();
					if(emailValue == ''){
						var error = name+" "+app.vtranslate("JS_DO_NOT_HAVE_AN_EMAIL_ID");
						alert(error);
						e.preventDefault();
						return;
					}
					
					selectedRecordDetails[id] = {name : row.data('name'), 'email' : emailValue};
				}
			});

			if(Object.keys(selectedRecordDetails).length <= 0) {
				alert(app.vtranslate('JS_PLEASE_SELECT_ONE_RECORD'));
			}else{
                thisInstance.done(selectedRecordDetails, thisInstance.getEventName());
				e.preventDefault();
			}
		});
	},
	
	registerEvents: function(){
		this._super();
	}
})