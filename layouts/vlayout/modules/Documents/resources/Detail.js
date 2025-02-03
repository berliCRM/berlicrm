/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("Documents_Detail_Js", {
	
	document: addEventListener('DOMContentLoaded', function () {
		const previewBox = document.createElement('div');
		previewBox.id = 'pdf-preview-box';
		previewBox.style.position = 'absolute';
		previewBox.style.display = 'none';
		previewBox.style.border = '1px solid #ccc';
		previewBox.style.background = '#fff';
		previewBox.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
		previewBox.style.padding = '10px';
		previewBox.style.zIndex = '1000';
		previewBox.style.overflow = 'auto';
		document.body.appendChild(previewBox);

		const closePreviewButton = document.createElement('button');
		closePreviewButton.textContent = '×';
		closePreviewButton.style.position = 'absolute';
		closePreviewButton.style.top = '8px';
		closePreviewButton.style.right = '8px';
		closePreviewButton.style.background = 'white';
		closePreviewButton.style.border = 'none';
		closePreviewButton.style.fontSize = '25px';
		closePreviewButton.style.cursor = 'pointer';
		closePreviewButton.style.color = '#333';
		const linkElement = $('[id$="_filename"]');

		// Button zur Box hinzufügen
		previewBox.appendChild(closePreviewButton);
		document.body.appendChild(previewBox);
	
		// Event-Listener für Schließen-Button
		closePreviewButton.addEventListener('click', function () {
			previewBox.style.display = 'none';
		});

		linkElement.on('mouseenter', function (e) {

			const pdfUrl = this.dataset.pdfPreview;
			console.log(linkElement.find('a'));

			previewBox.innerHTML = `<iframe id="preview" src="`+linkElement.find('a').attr('href')+`" width="300" height="400" frameborder="0"></iframe>`;
			previewBox.appendChild(closePreviewButton);
			previewBox.style.display = 'block';
			const linkRect = this.getBoundingClientRect();
			previewBox.style.left = `${linkRect.right + 10}px`; // Rechts neben dem Link
			previewBox.style.top = `${linkRect.top}px`;
		});

		linkElement.on('click', function (e) {
			e.preventDefault();
			const downloadLink = document.createElement('a');
			downloadLink.href = this.href;
			downloadLink.download = '';
			document.body.appendChild(downloadLink);
			downloadLink.click(); 
			document.body.removeChild(downloadLink);
		});
	}),

	//It stores the CheckFileIntegrity response data
	checkFileIntegrityResponseCache : {},
	
	/*
	 * function to trigger CheckFileIntegrity action
	 * @param: CheckFileIntegrity url.
	 */
	checkFileIntegrity : function(checkFileIntegrityUrl) {
		Documents_Detail_Js.getFileIntegrityResponse(checkFileIntegrityUrl).then(
			function(data){
				Documents_Detail_Js.displayCheckFileIntegrityResponse(data);
			}
		);
	},
	
	/*
	 * function to get the CheckFileIntegrity response data
	 */
	getFileIntegrityResponse : function(params){
		var aDeferred = jQuery.Deferred();
		
		//Check in the cache 
		if(!(jQuery.isEmptyObject(Documents_Detail_Js.checkFileIntegrityResponseCache))) {
			aDeferred.resolve(Documents_Detail_Js.checkFileIntegrityResponseCache);
		}
		else{
			AppConnector.request(params).then(
				function(data) {
					//store it in the cache, so that we dont do multiple request
					Documents_Detail_Js.checkFileIntegrityResponseCache = data;
					aDeferred.resolve(Documents_Detail_Js.checkFileIntegrityResponseCache);
				}
			);
		}
		return aDeferred.promise();
	},
	
	/*
	 * function to display the CheckFileIntegrity message
	 */
	displayCheckFileIntegrityResponse : function(data) {
		var result = data['result'];
		var success = result['success'];
		var message = result['message'];
		var params = {};
		if(success) {
			params = {
				text: message,
				type: 'success'
			}
		} else {
			params = {
				text: message,
				type: 'error'
			}
		}
		Documents_Detail_Js.showNotify(params);
	},
	
	//This will show the messages of CheckFileIntegrity using pnotify
	showNotify : function(customParams) {
		var params = {
			title: app.vtranslate('JS_CHECK_FILE_INTEGRITY'),
			text: customParams.text,
			type: customParams.type,
			width: '30%',
			delay: '2000'
		};
		Vtiger_Helper_Js.showPnotify(params);
	},

	triggerSendEmail : function(recordIds) {
		var params = {
			"module" : "Documents",
			"view" : "ComposeEmail",
			"documentIds" : recordIds
		};
		var emailEditInstance = new Emails_MassEdit_Js();
		emailEditInstance.showComposeEmailForm(params);
	}
	
},{});