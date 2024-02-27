/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Email_Validator_Js("Vtiger_To_Email_Validator_Js", {

	/**
	 *Function which invokes field validation
	 *@param accepts field element as parameter
	 * @return error if validation fails true on success
	 */
	invokeValidation: function(field, rules, i, options){
		var toEmailInstance = new Vtiger_To_Email_Validator_Js();
		toEmailInstance.setElement(field);
		return toEmailInstance.validate();
	}
},{

	/**
	 * Function to validate the email field data
	 */
	validate: function(){
		var fieldValue = this.getFieldValue();
		var fieldValuesList = fieldValue.split(',');

		if(fieldValue == "" || fieldValue == undefined || fieldValue == null){
			fieldValuesList = new Array();
			let toemailinfoField = document.getElementsByName('toemailinfo');
			let toemailinfoObj =  JSON.parse(toemailinfoField[0].value);
			for (let key in toemailinfoObj){
				fieldValuesList.push( toemailinfoObj[key][0]); 
			}
		}

		let filteredArray = fieldValuesList.filter(function(ele , pos){
			return fieldValuesList.indexOf(ele) == pos;
		});
		fieldValuesList = filteredArray;


		for (var i in fieldValuesList) {
			var splittedFieldValue = fieldValuesList[i];
			var emailInstance = new Vtiger_Email_Validator_Js();
			var response = emailInstance.validateValue(splittedFieldValue);
			if(response != true){
				return emailInstance.getError();
			}
		}
	}

});

jQuery.Class("Emails_MassEdit_Js",{},{

	ckEditorInstance : false,
	massEmailForm : false,
	saved : "SAVED",
	sent : "SENT",
	attachmentsFileSize : 0,
	documentsFileSize : 0,
	
	/**
	 * Function to get ckEditorInstance
	 */
	getckEditorInstance : function(){
		if(this.ckEditorInstance == false){
			this.ckEditorInstance = new Vtiger_CkEditor_Js();
		}
		return this.ckEditorInstance;
	},

	/**
	 * function to display the email form
	 * return UI
	 */
	showComposeEmailForm : function(params,cb,windowName){
		app.hideModalWindow();
		var popupInstance = Vtiger_Popup_Js.getInstance();
		return popupInstance.show(params,cb,windowName);

	},

	/*
	 * Function to get the Mass Email Form
	 */
	getMassEmailForm : function(){
		if(this.massEmailForm == false){
			this.massEmailForm = jQuery("#massEmailForm");
		}
		return this.massEmailForm;
	},

	/**
	 * function to call the registerevents of send Email step1
	 */
	registerEmailFieldSelectionEvent : function(replySubject, replyBody, ccc){
		var thisInstance = this;
		var selectEmailForm = jQuery("#SendEmailFormStep1");
		selectEmailForm.on('submit',function(e){
			var form = jQuery(e.currentTarget);
			var params = form.serializeFormData();	
			params.subject = replySubject;
			params.description = replyBody;
			params.cc = ccc;
			thisInstance.showComposeEmailForm(params,"","composeEmail");
			e.preventDefault();
		});
	},

	/*
	* Function to register the event of send email
	*/
	registerSendEmailEvent : function(){
		this.getMassEmailForm().on('submit',function(e){
			//TODO close the window once the mail has sent
			var formElement = jQuery(e.currentTarget);
			var invalidFields = formElement.data('jqv').InvalidFields;
			var progressElement = formElement.find('[name="progressIndicator"]');
			if(invalidFields.length == 0){
				jQuery('#sendEmail').attr('disabled',"disabled");
				jQuery('#saveDraft').attr('disabled',"disabled");
				progressElement.progressIndicator();
				return true;
			}
			return false;
		}).on('keypress',function(e){
			if(e.which == 13){
				e.preventDefault();
			}
		});
	},
	setAttachmentsFileSizeByElement : function(element){
			this.attachmentsFileSize += element.get(0).files[0].size;
	},
	
	setAttachmentsFileSizeBySize : function(fileSize){
		this.attachmentsFileSize += parseFloat(fileSize);
	},

	removeAttachmentFileSizeByElement : function(element){
			this.attachmentsFileSize -= element.get(0).files[0].size;
	},
	
	removeAttachmentFileSizeBySize : function(fileSize){
		this.attachmentsFileSize -= parseFloat(fileSize);
	},

	getAttachmentsFileSize : function(){
		return this.attachmentsFileSize;
	},
	setDocumentsFileSize : function(documentSize){
		this.documentsFileSize += parseFloat(documentSize);
	},
	getDocumentsFileSize : function(){
		return this.documentsFileSize;
	},

	getTotalAttachmentsSize : function(){
		return parseFloat(this.getAttachmentsFileSize())+parseFloat(this.getDocumentsFileSize());
	},

	getMaxUploadSize : function(){
		return jQuery('#maxUploadSize').val();
	},

	removeDocumentsFileSize : function(documentSize){
		this.documentsFileSize -= parseFloat(documentSize);
	},

	removeAttachmentsFileSize : function(){
		//TODO  update the attachment file size when you delete any attachment from the list
	},

	fileAfterSelectHandler : function(element, value, master_element){
		var thisInstance = this;
		var mode = jQuery('[name="emailMode"]').val();
		var existingAttachment = JSON.parse(jQuery('[name="attachments"]').val());
		element = jQuery(element);
		thisInstance.setAttachmentsFileSizeByElement(element);
		var totalAttachmentsSize = thisInstance.getTotalAttachmentsSize();
		var maxUploadSize = thisInstance.getMaxUploadSize();
		if(totalAttachmentsSize > maxUploadSize){
			Vtiger_Helper_Js.showPnotify(app.vtranslate('JS_MAX_FILE_UPLOAD_EXCEEDS'));
			this.removeAttachmentFileSizeByElement(jQuery(element));
			master_element.list.find('.MultiFile-label:last').find('.MultiFile-remove').trigger('click');
		}else if((mode != "") && (existingAttachment != "")){
			fileuploaded = value.split('\\').pop().split('/').pop();
			jQuery.each(existingAttachment,function(key,value){
				if((value['attachment'] == fileuploaded) && !(value.hasOwnProperty( "docid"))){
					var errorMsg = app.vtranslate("JS_THIS_FILE_HAS_ALREADY_BEEN_SELECTED")+" "+fileuploaded;
					Vtiger_Helper_Js.showPnotify(app.vtranslate(errorMsg));
					thisInstance.removeAttachmentFileSizeByElement(jQuery(element),value);
					master_element.list.find('.MultiFile-label:last').find('.MultiFile-remove').trigger('click');
					return false;
				}
			})
		}
		return true;
	},
	/*
	 * Function to register the events for getting the values
	 */
	registerEventsToGetFlagValue : function(){
		var thisInstance = this;
		jQuery('#saveDraft').on('click',function(e){
			jQuery('#flag').val(thisInstance.saved);
		});
		jQuery('#sendEmail').on('click',function(e){
			jQuery('#flag').val(thisInstance.sent);
		});
	},
	
	checkHiddenStatusofCcandBcc : function(){
		var ccLink = jQuery('#ccLink');
		var bccLink = jQuery('#bccLink');
		if(ccLink.is(':hidden') && bccLink.is(':hidden')){
			ccLink.closest('div.row-fluid').addClass('hide');
		}
	},

	/*
	 * Function to register the events for bcc and cc links
	 */
	registerCcAndBccEvents : function(){
		var thisInstance = this;
		jQuery('#ccLink').on('click',function(e){
			jQuery('#ccContainer').show();
			jQuery(e.currentTarget).hide();
			thisInstance.checkHiddenStatusofCcandBcc();
		});
		jQuery('#bccLink').on('click',function(e){
			jQuery('#bccContainer').show();
			jQuery(e.currentTarget).hide();
			thisInstance.checkHiddenStatusofCcandBcc();
		});
	},

	/*
	 * Function to register the send email template event
	 */
	registerSendEmailTemplateEvent : function(){
		var thisInstance = this;
		jQuery('#selectEmailTemplate').on('click',function(e){
			var url = jQuery(e.currentTarget).data('url');
			var popupInstance = Vtiger_Popup_Js.getInstance();
			popupInstance.show(url,function(data){
				var responseData = JSON.parse(data);
				for(var id in responseData){
					var selectedName = responseData[id].name;
					var selectedTemplateBody = responseData[id].info;
				}
				var ckEditorInstance = thisInstance.getckEditorInstance();
				// Wenn "E-Mail Vorlage wahlen" dann signaturetext dazu.
				let signaturetextElement = document.getElementsByName("signaturetext");
				if (typeof signaturetextElement !== 'undefined' && typeof signaturetextElement[0] !== 'undefined' && signaturetextElement[0].value !== 'undefined' ) {
					let signaturetext = (signaturetextElement[0].value);
					selectedTemplateBody = selectedTemplateBody +'<p></p>'+ signaturetext;
				}
				ckEditorInstance.loadContentsInCkeditor(selectedTemplateBody);
				jQuery('#subject').val(selectedName);
			},'tempalteWindow');
		});
	},
	getDocumentAttachmentElement : function(selectedFileName,id,selectedFileSize){
		return '<div class="MultiFile-label"><a class="removeAttachment cursorPointer" data-id='+id+' data-file-size='+selectedFileSize+'>x </a><span>'+selectedFileName+'</span></div>';
	},
	registerBrowseCrmEvent : function(){
		var thisInstance = this;
		jQuery('#browseCrm').on('click',function(e){
			var selectedDocumentId;
			var url = jQuery(e.currentTarget).data('url');
			var popupInstance = Vtiger_Popup_Js.getInstance();
			popupInstance.show(url,function(data){
				var responseData = JSON.parse(data);
				for(var id in responseData){
					selectedDocumentId = id;
					var selectedFileName = responseData[id].info['filename'];
					var selectedFileSize = responseData[id].info['filesize'];
					var response = thisInstance.writeDocumentIds(selectedDocumentId)
					if(response){
						var attachmentElement = thisInstance.getDocumentAttachmentElement(selectedFileName,id,selectedFileSize);
						//TODO handle the validation if the size exceeds 5mb before appending.
						jQuery(attachmentElement).appendTo(jQuery('#attachments'));
						jQuery('.MultiFile-applied,.MultiFile').addClass('removeNoFileChosen');
						thisInstance.setDocumentsFileSize(selectedFileSize);
					}
				}
				
			},'browseCrmWindow');
		});
	},
	/**
	 * Function to check whether selected document 
	 * is already an existing attachment
	 * @param expects document id to check
	 * @return true if present false if not present
	 */
	checkIfExisitingAttachment : function(selectedDocumentId){
		var documentExist;
		var documentPresent;
		var mode = jQuery('[name="emailMode"]').val();
		var selectedDocumentIds = jQuery('#documentIds').val();
		var existingAttachment = JSON.parse(jQuery('[name="attachments"]').val());
		if((mode != "") && (existingAttachment != "")){
			jQuery.each(existingAttachment,function(key,value){
				if(value.hasOwnProperty( "docid")){
					if(value['docid'] == selectedDocumentId){
						documentExist = 1;
						return false;
					} 
				}
			})
			if(selectedDocumentIds != ""){
				selectedDocumentIds = JSON.parse(selectedDocumentIds);
			}
			if((documentExist == 1) || (jQuery.inArray(selectedDocumentId,selectedDocumentIds) != '-1')){
				documentPresent = 1;
			} else {
				documentPresent = 0;
			}
		} else if(selectedDocumentIds != ""){
			selectedDocumentIds = JSON.parse(selectedDocumentIds);
			if((jQuery.inArray(selectedDocumentId,selectedDocumentIds) != '-1')){
				documentPresent = 1;
			} else {
				documentPresent = 0;
			}
		}
		if(documentPresent == 1){
			var errorMsg = app.vtranslate("JS_THIS_DOCUMENT_HAS_ALREADY_BEEN_SELECTED");
			Vtiger_Helper_Js.showPnotify(app.vtranslate(errorMsg));
			return true;
		} else {
			return false;
		}
	},

	writeDocumentIds :function(selectedDocumentId){
		var thisInstance = this;
		var newAttachment;
		var selectedDocumentIds = jQuery('#documentIds').val();
		if(selectedDocumentIds != ""){
			selectedDocumentIds = JSON.parse(selectedDocumentIds);
			var existingAttachment = thisInstance.checkIfExisitingAttachment(selectedDocumentId);
			if(!existingAttachment){
				newAttachment = 1;
			} else {
				newAttachment = 0;
			}
		} else {
			var existingAttachment = thisInstance.checkIfExisitingAttachment(selectedDocumentId);
			if(!existingAttachment){
				newAttachment = 1;
				var selectedDocumentIds = new Array();
			}
		}
		if(newAttachment == 1){
			selectedDocumentIds.push(selectedDocumentId);
			jQuery('#documentIds').val(JSON.stringify(selectedDocumentIds));
			return true;
		} else {
			return false;
		}
	},
	
	removeDocumentIds : function(removedDocumentId){
		var documentIdsContainer = jQuery('#documentIds');
		var documentIdsArray = JSON.parse(documentIdsContainer.val());
		documentIdsArray.splice( jQuery.inArray('"'+removedDocumentId+'"', documentIdsArray), 1);
		documentIdsContainer.val(JSON.stringify(documentIdsArray));
	},
	
	registerRemoveAttachmentEvent : function(){
		var thisInstance = this;
		this.getMassEmailForm().on('click','.removeAttachment',function(e){
			var currentTarget = jQuery(e.currentTarget);
			var id = currentTarget.data('id');
			var fileSize = currentTarget.data('fileSize');
			currentTarget.closest('.MultiFile-label').remove();
			thisInstance.removeDocumentsFileSize(fileSize);
			thisInstance.removeDocumentIds(id);
			if (jQuery('#attachments').is(':empty')){
				jQuery('.MultiFile,.MultiFile-applied').removeClass('removeNoFileChosen');
			}
		});
	},
	
	/**
	 * Function to register event for to field in compose email popup
	 */
	registerEventsForToField : function(){
		var thisInstance = this;
		this.getMassEmailForm().on('click','.selectEmail',function(e){
			var moduleSelected = jQuery('.emailModulesList').val();
			var parentElem = jQuery(e.target).closest('.toEmailField');
			var sourceModule = jQuery('[name=module]').val();
			var params = {
				'module' : moduleSelected,
				'src_module' : sourceModule,
				'view': 'EmailsRelatedModulePopup'
			}
			var popupInstance =Vtiger_Popup_Js.getInstance();
			popupInstance.show(params, function(data){
				var responseData = JSON.parse(data);
				const numberLimitOfEmails = 100;

				let responseDataLength = (Object.keys(responseData).length);

				// Only if it is to many emails, we need to change the 'show' of input fields. 
				let elementToEmail = document.getElementById('toEmailViewId');
				let elementToEmailCount = document.getElementById('toEmailCount');

				if(moduleSelected == "Verteiler"){
					elementToEmailCount.value = '';
				}

				if(responseDataLength > numberLimitOfEmails){
					// The user input of email will be not displayed, and
					if(elementToEmail != null ){
						elementToEmail.setAttribute('type','hidden');
						elementToEmail.style.display = "none"; 
					}
					// the another, readonly input with Count of emails will displayed now. 
					elementToEmailCount.setAttribute('type','text');
					elementToEmailCount.value = responseDataLength + '' + (elementToEmailCount.value).slice((elementToEmailCount.value).indexOf(' '));
					elementToEmailCount.style.display = "block"; 
				}
				else{
					if(elementToEmail != null ){
						// user input of email will be displayed
						elementToEmail.setAttribute('type','text');
						elementToEmail.style.display = "block"; 
					}
					// the Count will not displayed. 
					elementToEmailCount.setAttribute('type','hidden');
					elementToEmailCount.style.display = "none"; 
					elementToEmailCount.value = '';
				}

				// only if import from "Verteiler", we need first set the allready loaded emails to empty, and the load from new "Verteiler".
				if(moduleSelected == "Verteiler"){
					let preloadData = Array();
					thisInstance.setPreloadData(preloadData);
					
					let toemailinfoField = document.getElementsByName('toemailinfo');
					let existingToMailInfo = {};
					toemailinfoField[0].value = (JSON.stringify(existingToMailInfo));
	
					let selectedIdElement = document.getElementsByName('selected_ids');
					let emptyIdsValue = new Array();
					selectedIdElement[0].value = (JSON.stringify(emptyIdsValue));
	
					let toEmails = document.getElementsByName('to');
					let emptyToValue = new Array();
					toEmails[0].value = (JSON.stringify(emptyToValue));
				}
				
				for(var id in responseData){
					var data = {
						'name' : responseData[id].name,
						'id' : id,
						'emailid' : responseData[id].email
					}
					// the setReferenceFieldValue cost to many time (bottleneck), so set it only if we have not many emails.
					if(responseDataLength <= numberLimitOfEmails){

						thisInstance.setReferenceFieldValue(parentElem, data);
					}
					thisInstance.addToEmailAddressData(data);
					thisInstance.appendToSelectedIds(id);
					thisInstance.addToEmails(data);
				}
			},'relatedEmailModules');
		});
		
		this.getMassEmailForm().on('click','[name="clearToEmailField"]',function(e){
			var element = jQuery(e.currentTarget);
			element.closest('div.toEmailField').find('#toEmail').val('');
			thisInstance.getMassEmailForm().find('[name="toemailinfo"]').val(JSON.stringify(new Array()));
			thisInstance.getMassEmailForm().find('[name="selected_ids"]').val(JSON.stringify(new Array()));
			thisInstance.getMassEmailForm().find('[name="to"]').val(JSON.stringify(new Array()));

			var preloadData = [];
			thisInstance.setPreloadData(preloadData);
			thisInstance.getMassEmailForm().find('#toEmail').select2('data', preloadData);
		});
		
		
	},
	
	setReferenceFieldValue : function(container,object){
		var thisInstance = this;
		var preloadData = thisInstance.getPreloadData();

		var emailInfo = {
			'recordId' : object.id,
			'id' : object.emailid,
			'text' : object.name+' <b>('+object.emailid+')</b>'
		}

		let isNew = thisInstance.preloadDataAddNewEmail(preloadData, emailInfo);
		if(isNew){
			preloadData.push(emailInfo);
			thisInstance.setPreloadData(preloadData);
		}

		container.find('#toEmail').select2('data', preloadData);

		var toEmailField = container.find('#toEmail');
		var toEmailFieldExistingValue = toEmailField.val();
		var toEmailFieldNewValue;

		// check here vor double emails, add only new
		if(toEmailFieldExistingValue != "" || toEmailFieldExistingValue != null){
			let arrFields = toEmailFieldExistingValue.split(",");
			let newHere = true;
			for(let i =0; i < arrFields.length; i++){
				if(((arrFields[i]).trim()).toLowerCase() == ((object.emailid).trim()).toLowerCase()){
					newHere = false;
					break;
				}
			}
			if(newHere){
				toEmailFieldNewValue = toEmailFieldExistingValue+","+object.emailid;
			}
		} 
		else {
			toEmailFieldNewValue = object.emailid;
		}
		toEmailField.val(toEmailFieldNewValue);
	},

	addToEmailAddressData : function(mailInfo) {
		var mailInfoElement = this.getMassEmailForm().find('[name="toemailinfo"]');
		var existingToMailInfo = JSON.parse(mailInfoElement.val());
		 if(typeof existingToMailInfo.length != 'undefined') {
			existingToMailInfo = {};
		} 
		// If same record having two different email id's then it should be appended to existing email id
		if(existingToMailInfo.hasOwnProperty(mailInfo.id) === true){
			var existingValues = existingToMailInfo[mailInfo.id];
			var newValue = new Array(mailInfo.emailid);
			// If it was the same email, so we need not to add it.
			let newHere = true;
			for(let i =0; i < existingValues.length; i++){
				if(((existingValues[i]).trim()).toLowerCase() == ((mailInfo.emailid).trim()).toLowerCase()){
					newHere = false;
					break;
				}
			}
			if(newHere){
				// only if it is another email, we need add it. 
				existingToMailInfo[mailInfo.id] = jQuery.merge(existingValues,newValue);
			}
		} else {
			existingToMailInfo[mailInfo.id] = new Array(mailInfo.emailid);
		}
		mailInfoElement.val(JSON.stringify(existingToMailInfo));
	},

	appendToSelectedIds : function(selectedId) {
		var selectedIdElement = this.getMassEmailForm().find('[name="selected_ids"]');
		var previousValue = '';
		if(JSON.parse(selectedIdElement.val()) != '' && JSON.parse(selectedIdElement.val()) != 'all' && JSON.parse(selectedIdElement.val()) != '"all"') {
			previousValue = JSON.parse(selectedIdElement.val());

			if(jQuery.inArray(selectedId,previousValue) === -1){
				previousValue.push(selectedId);
			}
		} 
		else {
			previousValue = new Array(selectedId);
		}
		selectedIdElement.val(JSON.stringify(previousValue));

	},

	addToEmails : function(mailInfo){
		var toEmails = this.getMassEmailForm().find('[name="to"]');
		var value = JSON.parse(toEmails.val());
		if(value == ""){
			value = new Array();
		}
		let pushIt = true;
		for(let i = 0; i < value.length; i++){
			if( ((mailInfo.emailid).trim()).toLowerCase() == ((value[i]).trim()).toLowerCase()){
				pushIt = false;
				break;
			}
		}
		if(pushIt){
			value.push(mailInfo.emailid);
			toEmails.val(JSON.stringify(value));
		}
		
		let toEmailField = document.getElementById('toEmail');
		let toEmailFieldValues = toEmailField.value;
		let toEmailValuesArr = toEmailFieldValues.split(',');

		let toEmailValuesArrUpdated = Array();

		let pushIt2 = true;
		for(let i = 0; i < toEmailValuesArr.length; i++){
			if(((mailInfo.emailid).trim()).toLowerCase() == ((toEmailValuesArr[i]).trim()).toLowerCase() 
			|| ((toEmailValuesArr[i]).trim()).toLowerCase() == "" || ((toEmailValuesArr[i]).trim()).toLowerCase() == null){
				pushIt = false;
				break;
			}
		}
		if(pushIt2){
			toEmailValuesArrUpdated.push(mailInfo.emailid);
			toEmailValuesStr = toEmailValuesArrUpdated.join(",");
			toEmailField.value = toEmailValuesStr;
		}
	},

	/**
	 * Function to remove attachments that are added in 
	 * edit view of email in compose email form
	 */
	registerEventForRemoveCustomAttachments : function(){
		var thisInstance = this;
		var composeEmailForm = this.getMassEmailForm();
		jQuery('[name="removeAttachment"]').on('click',function(e){
			var attachmentsContainer = composeEmailForm.find('[ name="attachments"]');
			var attachmentsInfo = JSON.parse(attachmentsContainer.val());
			var element = jQuery(e.currentTarget);
			var imageContainer = element.closest('div.MultiFile-label');
			var imageContainerData = imageContainer.data();
			var fileType = imageContainerData['fileType'];
			var fileSize = imageContainerData['fileSize'];
			var fileId = imageContainerData['fileId'];
			if(fileType == "document"){
				thisInstance.removeDocumentsFileSize(fileSize);
			} else if(fileType == "file"){
				thisInstance.removeAttachmentFileSizeBySize(fileSize);
			}
			jQuery.each(attachmentsInfo,function(index,attachmentObject){
				if((typeof attachmentObject != "undefined") && (attachmentObject.fileid == fileId)){
					attachmentsInfo.splice(index,1);
				}
			})
			attachmentsContainer.val(JSON.stringify(attachmentsInfo));
			imageContainer.remove();
		})
	},
	
	/**
	 * Function to calculate upload file size
	 */
	calculateUploadFileSize : function(){
		var thisInstance = this;
		var composeEmailForm = this.getMassEmailForm();
		var attachmentsList = composeEmailForm.find('#attachments');
		var attachments = attachmentsList.find('.customAttachment');
		jQuery.each(attachments,function(){
			var element = jQuery(this);
			var fileSize = element.data('fileSize');
			var fileType = element.data('fileType');
			var documentId = element.data('documentId');
			if(fileType == "file"){
				thisInstance.setAttachmentsFileSizeBySize(fileSize);
			} else if(fileType == "document"){
				jQuery('#documentIds').val(documentId);
				thisInstance.setDocumentsFileSize(fileSize);
			}
		})
	},
	
	/**
	 * Function to register event for saved or sent mail
	 * getting back to preview
	 */
	registerEventForGoToPreview : function(){
		jQuery('#gotoPreview').on('click',function(e){
			var recordId = jQuery('[name="parent_id"]').val();
			var parentRecordId = jQuery('[name="parent_record_id"]').val();
			var params = {};
			params['module'] = "Emails";
			params['view'] = "ComposeEmail";
			params['mode'] = "emailPreview";
			params['record'] = recordId;
			params['parentId'] = parentRecordId;
			var urlString = (typeof params == 'string') ? params : jQuery.param(params);
			var url = 'index.php?' + urlString;
			self.location.href = url;
		})
	},

	preloadData : new Array(),

	getPreloadData : function(){
		return this.preloadData;
	},

	setPreloadData : function(dataInfo){
		this.preloadData = dataInfo;
		return this;
	},

	searchEmails : function(params){
		var aDeferred = jQuery.Deferred();

		if(typeof params.module == 'undefined'){
			params.module = app.getModuleName();
		}

		if(typeof params.action == 'undefined'){
			params.action = 'BasicAjax';
		}
		AppConnector.request(params).then(
			function(data){
				aDeferred.resolve(data);
			},
			function(error){
				aDeferred.reject();
			}
		)
		return aDeferred.promise();
	},

	/**
	 * Function which will handle the reference auto complete event registrations
	 * @params - container <jQuery> - element in which auto complete fields needs to be searched
	 */
	registerAutoCompleteFields : function(container){
		var thisInstance = this;

		container.find('.emailField').select2({
			minimumInputLength: 3,
			closeOnSelect : false,

			tags : [],
			tokenSeparators: [","],

			createSearchChoice : function(term){
				return {id: term, text: term};
			},

			ajax : {
				'url' : 'index.php?module=Emails&action=BasicAjax',
				'dataType' : 'json',
				'data' : function(term,page){
					 var data = {};
					 data['searchValue'] = term;
					 return data;
				},
				'results' : function(data){
					var finalResult = [];
					var results = data.result;
					var resultData = new Array();
					for(var moduleName in results){
						var moduleResult = [];
						moduleResult.text = moduleName;

						var children = new Array();
						for(var recordId in data.result[moduleName]){
							var emailInfo = data.result[moduleName][recordId];
							for (var i in emailInfo){
								var childrenInfo = [];
								childrenInfo.recordId = recordId;
								childrenInfo.id = emailInfo[i].value;
								childrenInfo.text = emailInfo[i].label;
								children.push(childrenInfo);
							}
						}
						moduleResult.children = children;
						resultData.push(moduleResult);
					}
					finalResult.results = resultData;
					return finalResult;
				},
				transport : function(params){
					return jQuery.ajax(params);
				}
			}

		}).on("change", function (selectedData){
			var addedElement = selectedData.added;
			var removedData = selectedData.removed;
			var currentElementName = jQuery(selectedData.currentTarget).attr('name');
			if(currentElementName == 'cc' || currentElementName == 'bcc'){
				var fieldName = 'ccInfo';
				if(currentElementName == 'bcc'){
					fieldName = 'bccInfo';
				}

				var emailData = [];
				var fieldData = jQuery('[name="'+fieldName+'"]').val();
				if(typeof(fieldData) != 'undefined' && fieldData.length){
					emailData = JSON.parse(fieldData);
					emailData = jQuery.map(emailData, function(value, index){
						return [value];
					});
				}

				if(typeof addedElement != 'undefined'){
					var data = {
						'id' : addedElement.recordId,
						'name' : addedElement.text,
						'emailid' : addedElement.id
					}

					let isNew = thisInstance.preloadDataAddNewEmail(emailData, addedElement, true);
					if(isNew){
						emailData.push(data);
					}

				} 
				else if(typeof removedData != 'undefined') {
					for(var i in emailData) {
						if(removedData.recordId != undefined || removedData.recordId != '' || removedData.recordId != null){
							if(emailData[i].id == removedData.recordId){
								emailData.splice(i, 1);
							}
						}
						else{
							if(emailData[i].emailid == removedData.id){
								emailData.splice(i, 1);
							}
						}
					}
				}
				jQuery('[name="'+fieldName+'"]').val(JSON.stringify(emailData));
			}

			if(typeof addedElement != 'undefined'){
				var data = {
					'id' : addedElement.recordId,
					'name' : addedElement.text,
					'emailid' : addedElement.id
				}
				//crm-now: cc and bcc shouldn't recieve extra emails
				if(currentElementName != 'cc' && currentElementName != 'bcc'){
					thisInstance.addToEmails(data);
				}
				if(typeof addedElement.recordId != 'undefined'){
					//crm-now: cc and bcc shouldn't recieve extra emails
					if(currentElementName != 'cc' && currentElementName != 'bcc'){
						thisInstance.addToEmailAddressData(data);
					}
					thisInstance.appendToSelectedIds(addedElement.recordId);
				}

				var preloadData = thisInstance.getPreloadData();
				var emailInfo = {
					'id' : addedElement.id
				}
				if(typeof addedElement.recordId != 'undefined'){
					emailInfo['text'] = addedElement.text;
					emailInfo['recordId'] = addedElement.recordId;
				}
				else {
					emailInfo['text'] = addedElement.id;
				}

				let isNew = thisInstance.preloadDataAddNewEmail(preloadData, emailInfo);

				if(isNew){
					preloadData.push(emailInfo);
					thisInstance.setPreloadData(preloadData);
				}
			}

			var removedElement = selectedData.removed;

			if (typeof removedElement != 'undefined'){
				var data = {
					'id' : removedElement.recordId,
					'name' : (removedElement.text).trim(),
					'emailid' : (removedElement.id).trim()
				}
				thisInstance.removeFromEmails(data);
				if (typeof removedElement.recordId != 'undefined'){
					thisInstance.removeFromEmailAddressData(data);
					thisInstance.removeFromSelectedIds(removedElement.recordId);
				}

				var preloadData = thisInstance.getPreloadData();

				for(var i in preloadData){
					if( removedElement.recordId != undefined || removedElement.recordId != '' || removedElement.recordId != null ){
						if(preloadData[i].recordId == removedElement.recordId){
							preloadData.splice(i, 1);
						}
					}
					else{
						if(preloadData[i].id == removedElement.id){
							preloadData.splice(i, 1);
						}
					}
				}
				thisInstance.setPreloadData(preloadData);
			}
		});

		container.find('.emailField').select2("container").find("ul.select2-choices").sortable({
			containment: 'parent',
			start: function(){
				container.find('.emailField').select2("onSortStart");
			},
			update: function(){
				container.find('.emailField').select2("onSortEnd");
			}
		});

		var toEmailNamesList = JSON.parse(container.find('[name="toMailNamesList"]').val());
		var toEmailInfo = JSON.parse(container.find('[name="toemailinfo"]').val());
		var toEmails = container.find('[name="toEmail"]').val();
		var toFieldValues = Array();
		if(toEmails.length > 0){
			toFieldValues = toEmails.split(',');
		}

		var preloadData = thisInstance.getPreloadData();
		if (typeof toEmailInfo != 'undefined'){
			for(var key in toEmailInfo) {
				if(toEmailNamesList.hasOwnProperty(key)){
					for(var i in toEmailNamesList[key]){
						var emailInfo = [];
						var emailId = toEmailNamesList[key][i].value;
						var emailInfo = {
							'recordId' : key,
							'id' : emailId,
							'text' : toEmailNamesList[key][i].label+' <b>('+emailId+')</b>'
						}
						preloadData.push(emailInfo);
						if(jQuery.inArray(emailId, toFieldValues) != -1){
							var index = toFieldValues.indexOf(emailId);
							if(index !== -1) {
								toFieldValues.splice(index, 1);
							}
						}
					}
				}
			}
		}

		if(typeof toFieldValues != 'undefined'){
			for(var i in toFieldValues){
				var emailId = toFieldValues[i];
				var emailInfo = {
					'id' : emailId,
					'text' : emailId
				}
				preloadData.push(emailInfo);
			}
		}
		
		if(typeof preloadData != 'undefined'){
			thisInstance.setPreloadData(preloadData);
			container.find('.emailField[name="toEmail"]').select2('data', preloadData);
			
			let selected_idsElement = this.getMassEmailForm().find('[name="selected_ids"]'); 
			let previousValue = JSON.parse(selected_idsElement.val());
			
			if(previousValue == "all" || previousValue == '"all"' || previousValue == '"[]"' || previousValue == '' || previousValue == null || previousValue == undefined){
				previousValue = [];
				for(let index = preloadData.length-1; index >=0; index--){
					let reId = (preloadData[index]).recordId;
					previousValue.push(reId);
				}
				selected_idsElement.val(JSON.stringify(previousValue));
			}
		}

		var ccValues = container.find('[name="cc"]').val();
		if(ccValues.length > 0 && ccValues != "null"){
			let ccValParse0 = JSON.parse(ccValues.val());
			let ccValParse = [];
			if(ccValParse0.length == 1){
				ccValParse = (ccValParse0[0].split(',')).map(function(item){ return (item.trim()).toLowerCase(); });
			}
			else if(ccValParse0.length > 1){
				ccValParse = ccValParse0;
			}

			var emailData = [];
			for(var i in ccValParse){
				var ccValue = ((ccValParse[i]).trim()).toLowerCase();
				// if(ccValue.id) {
					// emailData.push({'id' : ccValue.emailid, 'text' : ccValue.name, 'recordId' : ccValue.id});
				// } else if(ccValue.emailid) {
					// emailData.push({'id' : ccValue.emailid, 'text' : ccValue.name});
				// } else {
					emailData.push({'id' : ccValue, 'text' : ccValue});
				// }
			}
			container.find('.emailField[name="cc"]').select2('data', emailData);
		}

		var bccValues = container.find('[name="bcc"]').val();
		if(bccValues){
			bccValues = bccValues.split(",");
			var bemailData = [];
			for(var i in bccValues){
				var bccValue = bccValues[i];
				// if(bccValue.id) {
					// bemailData.push({'id' : bccValue.emailid, 'text' : bccValue.name, 'recordId' : bccValue.id});
				// } else if(bccValue.emailid) {
					// bemailData.push({'id' : bccValue.emailid, 'text' : bccValue.name});
				// } else {
					bemailData.push({'id' : bccValue.trim(), 'text' : bccValue.trim()});
				// }
			}
			container.find('.emailField[name="bcc"]').select2('data', bemailData);
		}
	},

	removeFromEmailAddressData : function(mailInfo){
		var mailInfoElement = this.getMassEmailForm().find('[name="toemailinfo"]');
		var previousValue = JSON.parse(mailInfoElement.val());
		if(previousValue[mailInfo.id] != undefined ){
			var elementSize = previousValue[mailInfo.id].length;
			var emailAddress = (mailInfo.emailid).trim();
			var selectedId = (mailInfo.id).trim();
			//If element length is not more than two delete existing record.
			if(elementSize < 2){
				delete previousValue[selectedId];
			} 
			else{
				// Update toemailinfo hidden element value
				var newValue;
				var reserveValue = previousValue[selectedId];
				delete previousValue[selectedId];
				//Remove value from an array and return the resultant array
				newValue = jQuery.grep(reserveValue, function(value){
					return value != emailAddress;
				});
				previousValue[selectedId] = newValue;
				//update toemailnameslist hidden element value
			}
			mailInfoElement.val(JSON.stringify(previousValue));
		}
	},

	removeFromSelectedIds : function(selectedId){
		var selectedIdElement = this.getMassEmailForm().find('[name="selected_ids"]');
		var previousValue = JSON.parse(selectedIdElement.val());

		if(previousValue == "all" || previousValue == '"all"'){
			previousValue = [];
		}

		var mailInfoElement = this.getMassEmailForm().find('[name="toemailinfo"]');
		var mailAddress = JSON.parse(mailInfoElement.val());
		var elements = mailAddress[selectedId];
		
		//Don't remove id from selected_ids if element is having more than two email id's
		if(typeof(elements) == 'undefined' || elements.length < 2){
			var updatedValue = [];
			for (var i in previousValue){
				var id = previousValue[i];
				var skip = false;
				if(id == selectedId){
					skip = true;
				}
				if(skip == false){
					updatedValue.push(id);
				}
			}
			selectedIdElement.val(JSON.stringify(updatedValue));
		}
	},
	removeFromEmails : function(mailInfo){
		let toEmails = this.getMassEmailForm().find('[name="to"]'); 
		let previousValue0 = JSON.parse(toEmails.val());
		let previousValue = [];
		if(previousValue0.length == 1){
			previousValue = (previousValue0[0].split(',')).map(function(item){ return item.trim(); });
		}
		else if(previousValue0.length > 1){
			previousValue = previousValue0;
		}
		let updatedValue = [];
		for(let i in previousValue){
			let email = previousValue[i];
			let skip = false;
			if((email.trim()).toLowerCase() == ((mailInfo.emailid).trim()).toLowerCase()){
				skip = true;
			}
			if(skip == false){
				updatedValue.push(email);
			}
		}
		toEmails.val(JSON.stringify(updatedValue));

		// and remove from toEmails
		let toEmailField = document.getElementById('toEmail');
		let toEmailFieldValues = toEmailField.value;
		let toEmailValuesArr = toEmailFieldValues.split(',');
		let updatedValue2 = [];
		for(let i in toEmailValuesArr){
			let email = toEmailValuesArr[i];
			let skip = false;
			if((email.trim()).toLowerCase() == ((mailInfo.emailid).trim()).toLowerCase()){
				skip = true;
			}
			if(skip == false){
				updatedValue2.push(email);
			}
		}
		updatedValue2Str = updatedValue2.join(",");
		toEmailField.value = updatedValue2Str;
	},
	
	// function that previews an email with filled substitute fields
	registerEventEmailPreview : function(){
		var thisInstance = this;
		jQuery('#previewEmail').on('click',function(e){
			// added for email preview
			//get email receivers
			var form = jQuery("#massEmailForm");
			var receivers = form.find('[name="toemailinfo"]').val();
			if(receivers.length < 3){
				alert ( app.vtranslate('JS_LBL_NO_RECEIVERS'));
				return;
			}
			var oCKeditor = CKEDITOR.instances.description.getData();
			
			var editor_val = CKEDITOR.instances.description.document.getBody().getChild(0).getText();
			if(editor_val == ''){
				alert ( app.vtranslate('JS_LBL_NO_CONTENT'));
				return false ;
			}
			
			var params = {
				module: 'Emails',
				view: 'showEmailContent',
				receivers : receivers,
				mode : 'previewEmail',
				emailbody: oCKeditor
			}
			var aDeferred = jQuery.Deferred();
			thisInstance.getMenuActionResponseData(params).then(
				function(data){
					thisInstance.displayTplMenueResponseData(data);
				}
			);
		});
	},
	
	/*
	 * Function which will register module change event
	 */
	registerChangeEventForModule : function(){
		var thisInstance = this;
		var filterContainer = jQuery('#modulename');
		filterContainer.on('change', function(e){
			thisInstance.loadFields();
		});
	},
	
	/*
	 * Function to load condition list for the selected field
	 * @params : fieldSelect - select element which will represents field list
	 * @return : select element which will represent the condition element
	 */
	loadFields : function(){
		var moduleName = jQuery('#modulename').val();
		var allFields = jQuery('#moduleFields').data('value');
		var fieldSelectElement = jQuery('#templateFields');
		var options = '';
		for(var key in allFields){
			//IE Browser consider the prototype properties also, it should consider has own properties only.
			if(allFields.hasOwnProperty(key) && key == moduleName){
				var moduleSpecificFields = allFields[key];
				var len = moduleSpecificFields.length;
				for(var i = 0; i < len; i++){
					var fieldName = moduleSpecificFields[i][0].split(':');
					options += '<option value="'+moduleSpecificFields[i][1]+'"';
					if(fieldName[0] == moduleName){
						options += '>'+fieldName[1]+'</option>';
					}
					else{
						options += '>'+moduleSpecificFields[i][0]+'</option>';
					}
				}
			}
		}
		
		if(options == ''){
			options = '<option value="">NONE</option>';
		}
		fieldSelectElement.empty().html(options).trigger("liszt:updated");
		return fieldSelectElement;
		
	},

	registerFillTemplateContentEvent : function(){
		jQuery('#templateFields').change(function(e){
			var textarea = CKEDITOR.instances.description;
			var value = jQuery(e.currentTarget).val();
			textarea.insertHtml(value);
		});
	},
	
	/*
	 * function to get the URL response data
	 */
	//cache for response data
	use_cache: false,
	getMenuActionResponseDataCache : {},
	getMenuActionResponseData : function(params){
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
				'enabled' : false
			}
		});
		var aDeferred = jQuery.Deferred();
		// check cache
		if(!(jQuery.isEmptyObject(this.getMenuActionResponseDataCache)) && this.use_cache == true){
			aDeferred.resolve(this.getMenuActionResponseDataCache);
		} 
		else{
			AppConnector.request(params).then(
				function(data){
					//store it in the cache, so that we do no multiple request
					this.getMenuActionResponseDataCache = data;
					aDeferred.resolve(this.getMenuActionResponseDataCache);
				}
			);
		}
		progressIndicatorElement.progressIndicator({
			'mode' : 'hide'
		});
		return aDeferred.promise();
	},
	/*
	 * function to display the response data (tpl)
	 */
	displayTplMenueResponseData : function(data){
        var callbackFunction = function(data){
            app.showScrollBar(jQuery('#menueScroll'),{
                height: '450px',
                railVisible: true,
                size: '6px'
            });
        }
        app.showModalWindow(data, function(data){
            if(typeof callbackFunction == 'function' && jQuery('#menueScroll').height() > 300){
                callbackFunction(data);
            }
        });
	},

	registerEvents : function(){
		var thisInstance = this;
		var composeEmailForm = this.getMassEmailForm();
		if(composeEmailForm.length > 0){
			jQuery("#multiFile").MultiFile({
				list: '#attachments',
				'afterFileSelect' : function(element, value, master_element){
					var masterElement = master_element;
					var newElement = jQuery(masterElement.current);
					newElement.addClass('removeNoFileChosen');
					thisInstance.fileAfterSelectHandler(element, value, master_element);
				},
				'afterFileRemove' : function(element, value, master_element){
					if (jQuery('#attachments').is(':empty')){
						jQuery('.MultiFile,.MultiFile-applied').removeClass('removeNoFileChosen');
					}
					thisInstance.removeAttachmentFileSizeByElement(jQuery(element));
				}
			});
			this.getMassEmailForm().validationEngine(app.validationEngineOptions);
			this.registerSendEmailEvent();
			var textAreaElement = jQuery('#description');
			
			// wenn er normal geladen wird, zB "Sende E-Mail" dann addiert signaturetext dazu.
			let signaturetextElement = document.getElementsByName("signaturetext");
			if (typeof signaturetextElement !== 'undefined' && typeof signaturetextElement[0] !== 'undefined' && signaturetextElement[0].value !== 'undefined') {
				let signaturetext = (signaturetextElement[0].value);
				textAreaElement[0].defaultValue = textAreaElement[0].defaultValue + signaturetext;
			}

			var ckEditorInstance = this.getckEditorInstance(textAreaElement);
			ckEditorInstance.loadCkEditor(textAreaElement);
			this.registerAutoCompleteFields(this.getMassEmailForm());
			this.registerRemoveAttachmentEvent();
			this.registerEventsToGetFlagValue();
			this.registerCcAndBccEvents();
			this.registerSendEmailTemplateEvent();
			this.registerBrowseCrmEvent();
			this.registerEventsForToField();
			this.registerEventForRemoveCustomAttachments();
			this.calculateUploadFileSize();
			this.registerEventForGoToPreview();
			this.registerEventEmailPreview();
			this.registerChangeEventForModule();
			this.registerFillTemplateContentEvent();
		}
	},

	/**
	 * to search and find out, if we have a new email-adress or it is allready in preloadData.
	 * @param {*} preloadDataOld 
	 * @param {*} objectNew 
	 * @param {*} emailidHere 
	 * //objectNew: it can be 1.: 'recordId','id','text'  OR  2.:'id','emailid','name'. If emailid = true, so it is 2option.
	 * @returns true, if it is a new emailAdress and needed added to the others
	 */
	preloadDataAddNewEmail : function(preloadDataOld, objectNew, emailidHere = false){
		let isNew = true;
		for(let index = (preloadDataOld.length - 1); index >= 0; index--){
			let reId = (preloadDataOld[index]).recordId;
			let emailAdress = (preloadDataOld[index]).id;
			let VornameNachname = (preloadDataOld[index]).text;
			if(emailidHere){
				reId = (preloadDataOld[index]).id;
				emailAdress = (preloadDataOld[index]).name;
				VornameNachname = (preloadDataOld[index]).emailid;
			}
			if(objectNew.recordId != '' || objectNew.recordId != null || objectNew.recordId != undefined){
				if(reId == objectNew.recordId){
					isNew = false;
					break;
				}
			}
			else{
				if(emailAdress == objectNew.id){
					isNew = false;
					break;
				}
			}
		}
		return isNew;
	}

});
//On Page Load
jQuery(document).ready(function(){
	var emailMassEditInstance = new Emails_MassEdit_Js();
	emailMassEditInstance.registerEvents();
});
