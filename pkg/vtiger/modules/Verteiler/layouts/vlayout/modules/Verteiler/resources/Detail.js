/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("Verteiler_Detail_Js",{
	selectedexport : '',
	sendemail : function(recordid) {
		var params = {};
		params['sourceModule'] = "Verteiler";
		params['sourceRecord'] = recordid;
		params['selected_ids'] = "all";

        var actionParams = {
            "type":"POST",
            "url" :'index.php?module=Contacts&view=MassActionAjax&mode=showComposeEmailForm&step=step1',
            "dataType":"html",
            "data" : params
        };

		var url = 'index.php?module=Verteiler&view=showCheckVerteilerEmails&recordid='+recordid;
		AppConnector.request(url).then(
			function(data){
				if(data) {
					if (data == 'ok') {
						//call email popup
						Vtiger_Index_Js.showComposeEmailPopup(actionParams);
					}
					else {
							//call modalwindow
							var callbackFunction = function(data) {											
							}
							app.showModalWindow(data, function(data){ 
								if(typeof callbackFunction == 'function'){
									callbackFunction(data);
								}
							});
					}
				}
				else {
					alert (app.vtranslate('JS_INTERNAL_ERROR_MESSAGE2'));
				}
				
			},
			function(error){
				alert (app.vtranslate('JS_INTERNAL_ERROR_MESSAGE2'));
			}
		);
    },
    
    exportexcel : function(recordid) {
        window.location = "index.php?module=Verteiler&action=ExcelExport&record="+recordid;
    },
	
	/**
	 * Function to show an export list
	 */
	showExportOptions : function(recordid){
		var url = 'index.php?module=Verteiler&view=showExportOptions&recordid='+recordid;
		AppConnector.request(url).then(
			function(data){
				if(data.indexOf("NODESTINATION") > -1 ) {
					var params = {
						title: app.vtranslate('JS_LBL_MODULE_ERROR'),
						text: app.vtranslate('JS_LBL_NO_EXPORT'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
					return false;
				}
				else {
					var callbackFunction = function(data) {
						// app.showScrollBar(jQuery('#transferPopupScroll'), {
							// height: '180px',
							// railVisible: true,
							// size: '6px'
						// }),
						jQuery(".chosen").chosen();
						jQuery('#exportdestination').change(function(e){
						e.preventDefault();
						var selectedValue = '';
						var selectedModule = '';
						var test = jQuery('#exportdestination').val();
							if(jQuery('#exportdestination').val() == 'Campaigns'){
								jQuery('#result_Campaigns').show();
								jQuery('#result_Mailchimp').hide();
								jQuery('#result_berliCleverReach').hide();
							} 
							else if(jQuery('#exportdestination').val() == 'Mailchimp'){
								jQuery('#result_Mailchimp').show();
								jQuery('#result_Campaigns').hide();
								jQuery('#result_berliCleverReach').hide();
							} 
							else if(jQuery('#exportdestination').val() == 'berliCleverReach'){
								jQuery('#result_berliCleverReach').show();
								jQuery('#result_Mailchimp').hide();
								jQuery('#result_Campaigns').hide();
							} 
							else {
								jQuery('#result_Campaigns').hide();
								jQuery('#result_Mailchimp').hide();
								jQuery('#result_berliCleverReach').hide();
								jQuery("#exportbutton" ).prop("disabled", true);
							}
						});
						jQuery("#campaignlist").change(function(e){
							selectedValue = $(this).children("option:selected").val();
							selectedModule = 'Campaigns';
							if (selectedValue =='') {
								jQuery("#exportbutton" ).prop("disabled", true);
							}
							else {
								jQuery("#exportbutton" ).prop("disabled", false);
							}
							Verteiler_Detail_Js.selectedexport =  selectedModule+'_'+selectedValue;
						});
						jQuery("#maichimplist").change(function(e){
							selectedValue = $(this).children("option:selected").val();
							selectedModule = 'Mailchimp';
							if (selectedValue =='') {
								jQuery("#exportbutton" ).prop("disabled", true);
							}
							else {
								jQuery("#exportbutton" ).prop("disabled", false);
							}
							Verteiler_Detail_Js.selectedexport =  selectedModule+'_'+selectedValue;
						});
						jQuery("#cleverreachlist").change(function(e){
							selectedValue = $(this).children("option:selected").val();
							selectedModule = 'berliCleverReach';
							if (selectedValue =='') {
								jQuery("#exportbutton" ).prop("disabled", true);
							}
							else {
								jQuery("#exportbutton" ).prop("disabled", false);
							}
							Verteiler_Detail_Js.selectedexport = selectedModule+'_'+selectedValue;
						});
						jQuery("#exportbutton" ).on( "click", function() {
							if(typeof Verteiler_Detail_Js.selectedexport == 'undefined' || Verteiler_Detail_Js.selectedexport =='') {
								var params = {
									title: app.vtranslate('JS_LBL_MODULE_ERROR'),
									text: app.vtranslate('JS_LBL_NO_EXPORT_SELECTION'),
									width: '35%'
								};
								Vtiger_Helper_Js.showPnotify(params);
								return false;
							}
							else {
								var params = {
									module: 'Verteiler',
									action: 'exportDataToOtherModules',
									mode: 'export',
									record: recordid,
									target: Verteiler_Detail_Js.selectedexport
								}
								var progressInstance = jQuery.progressIndicator();
								AppConnector.request(params).then(
									function(data) {
										progressInstance.hide();
										if(data) {
											if(typeof data.result !== 'undefined' &&  data.result['success'] == true){
												var sparams = {
													title : app.vtranslate('JS_LBL_EXPORT_TITLE'),
													text: app.vtranslate('JS_LBL_EXPORT_FINISHED'),
													animation: 'show',
													type: 'info'
												};
												Vtiger_Helper_Js.showPnotify(sparams);
											}
											else if(typeof data.success !== 'undefined' &&  data.success == false){
												var eparam = {
													text:data.error.message
												};
												Vtiger_Helper_Js.showPnotify(eparam);
											}
											else{
												var eparam = {
													text:data.result['error']
												};
												Vtiger_Helper_Js.showPnotify(eparam);
											}
										}
										else {
											var sparams = {
													title : app.vtranslate('JS_INTERNAL_ERROR'),
													text: app.vtranslate('JS_INTERNAL_ERROR_MESSAGE1'),
													animation: 'show',
													type: 'error'
												};
											Vtiger_Helper_Js.showPnotify(sparams);
										}
									},
									function(error,err){
										progressInstance.hide();
										alert (app.vtranslate('JS_INTERNAL_ERROR_MESSAGE2'));
									}
								);
							}
						});				
					}
					app.showModalWindow(data, function(data){
						if(typeof callbackFunction == 'function'){
							callbackFunction(data);
						}
					});
				} 
			},
			function(error){
				alert (app.vtranslate('JS_INTERNAL_ERROR_MESSAGE2'));
			}
		);
		
	},

	 /**
	 * Function to show a contact list
	 */ 
	/*	 showEmailCheckResults : function(recordid){
			var url = 'index.php?module=Verteiler&view=showCheckVerteilerEmails&recordid='+recordid;
			AppConnector.request(url).then(
				function(data){
					if(data.indexOf("NODESTINATION") > -1 ) {
						var params = {
							title: app.vtranslate('JS_LBL_EMAIL_ERROR'),
							text: app.vtranslate('JS_LBL_NO_EMAIL'),
							width: '35%'
						};
						Vtiger_Helper_Js.showPnotify(params);	
						return false; 
					}
					else {
						var callbackFunction = function(data) {											
						}
						app.showModalWindow(data, function(data){
							if(typeof callbackFunction == 'function'){
								callbackFunction(data);
							}
						});
					} 
				},
				function(error){
					alert (app.vtranslate('JS_INTERNAL_ERROR_MESSAGE2'));
				}
			);
			
		}, */
},

{
    
	loadRelatedList : function(pageNumber){
		var relatedListInstance = new Verteiler_RelatedList_Js(this.getRecordId(), app.getModuleName(), this.getSelectedTab(), this.getRelatedModuleName());
		var params = {'page':pageNumber};
		this.clearSelectedRecords();
		relatedListInstance.loadRelatedList(params);
	},
	
	/**
	 * Function to clear selected records
	 */
	clearSelectedRecords : function() {
		jQuery('[name="selectedIds"]').data('selectedIds',"");
		jQuery('[name="excludedIds"]').data('excludedIds',"");
	},
	
	
	/**
	 * Function to register Event for Sorting
	 */
	registerEventForRelatedList : function(){
		var thisInstance = this;
		var detailContentsHolder = this.getContentHolder();
		detailContentsHolder.on('click','.relatedListHeaderValues',function(e){
			var element = jQuery(e.currentTarget);
			var selectedTabElement = thisInstance.getSelectedTab();
			var relatedModuleName = thisInstance.getRelatedModuleName();
			var relatedController = new Verteiler_RelatedList_Js(thisInstance.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);
			relatedController.sortHandler(element).then(function(data){
				var emailEnabledModule = jQuery(data).find('[name="emailEnabledModules"]').val();
				if(emailEnabledModule){
					thisInstance.registerEmailEnabledActions();
				}
			});
		});
		
		detailContentsHolder.on('click', '#addEntries', function(e){
			var selectedTabElement = thisInstance.getSelectedTab();
			var relatedModuleName = thisInstance.getRelatedModuleName();
			var relatedController = new Verteiler_RelatedList_Js(thisInstance.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);
			relatedController.showSelectRelationPopup().then(function(data){
				var emailEnabledModule = jQuery(data).find('[name="emailEnabledModules"]').val();
				if(emailEnabledModule){
					thisInstance.registerEmailEnabledActions();
				}
			});
		});

        detailContentsHolder.on('click', 'a.relationDelete', function(e){
			e.stopImmediatePropagation();
			var element = jQuery(e.currentTarget);
			var instance = Vtiger_Detail_Js.getInstance();
			var key = instance.getDeleteMessageKey();
			var message = app.vtranslate(key);
			Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(
				function(e) {
					var row = element.closest('tr');
					var relatedRecordid = row.data('id');
					var selectedTabElement = thisInstance.getSelectedTab();
					var relatedModuleName = thisInstance.getRelatedModuleName();
                    var entryAddedBy = row.find(".addedByUser").data("id");
					var relatedController = new Verteiler_RelatedList_Js(thisInstance.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);
                    
                    var params = {};
                    params['action'] = 'RelationAjax';
                    params['mode'] = 'deleteRelation';
                    params['module'] = 'Verteiler';
                    params['related_module'] = 'Contacts';
                    params['src_record'] = thisInstance.getRecordId();
                    params['related_record_list'] = JSON.stringify([relatedRecordid]);
                    params['added_by_user_id'] = entryAddedBy;
                    
                    AppConnector.request(params).then(
                        function(responseData) {
                            if(!responseData.success) {
						var params = {
							text: responseData.error.message,
							type: 'error'
						};
						Vtiger_Helper_Js.showMessage(params);
                        }
                        else {
                            relatedController.loadRelatedList();
                        }
                    });
				},
				function(error, err){
				}
			);
		});
	},
	
	/**
	 * Function to register event for adding related record for module
	 */
	registerEventForAddingRelatedRecord : function(){
		var thisInstance = this;
		var detailContentsHolder = this.getContentHolder();
		detailContentsHolder.on('click','[name="addButton"]',function(e){
			var element = jQuery(e.currentTarget);
			var selectedTabElement = thisInstance.getSelectedTab();
			var relatedModuleName = thisInstance.getRelatedModuleName();
            var quickCreateNode = jQuery('#quickCreateModules').find('[data-name="'+ relatedModuleName +'"]');
            if(quickCreateNode.length <= 0) {
                window.location.href = element.data('url');
                return;
            }
            
			var relatedController = new Vtiger_RelatedList_Js(thisInstance.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);
			relatedController.addRelatedRecord(element).then(function(data){
				var emailEnabledModule = jQuery(data).find('[name="emailEnabledModules"]').val();
				if(emailEnabledModule){
					thisInstance.registerEmailEnabledActions();
				}
			});
		})
	},
	
	/**
	 * Function to register email enabled actions
	 */
	registerEmailEnabledActions : function() {
		var moduleName = app.getModuleName();
		var className = moduleName+"_List_Js";
		var listInstance = new window[className]();
		listInstance.registerEvents();
		listInstance.markSelectedRecords();
		this.registerRelatedListEvents();
	},
	
	registerEventForRelatedTabClick : function(){
		var thisInstance = this;
		var detailContentsHolder = thisInstance.getContentHolder();
		var detailContainer = detailContentsHolder.closest('div.detailViewInfo');
		jQuery('.related', detailContainer).on('click', 'li', function(e, urlAttributes){
			var tabElement = jQuery(e.currentTarget);
			var element = jQuery('<div></div>');
			element.progressIndicator({
				'position':'html',
				'blockInfo' : {
					'enabled' : true,
					'elementToBlock' : detailContainer
				}
			});
			var url = tabElement.data('url');
			if(typeof urlAttributes != 'undefined'){
				var callBack = urlAttributes.callback;
				delete urlAttributes.callback;
			}
			thisInstance.loadContents(url,urlAttributes).then(
				function(data){
					thisInstance.deSelectAllrelatedTabs();
					thisInstance.markTabAsSelected(tabElement);
					element.progressIndicator({'mode': 'hide'});
					var emailEnabledModule = jQuery(data).find('[name="emailEnabledModules"]').val();
					if(emailEnabledModule){
						var listInstance = new Verteiler_List_Js();
						listInstance.registerEvents();
						thisInstance.registerRelatedListEvents();
					}
					if(typeof callBack == 'function'){ 
						callBack(data);
					}
					//Summary tab is clicked
					if(tabElement.data('linkKey') == thisInstance.detailViewSummaryTabLabel) {
						thisInstance.loadWidgets();
						thisInstance.registerSummaryViewContainerEvents(detailContentsHolder);
					}
				},
				function (){
					//TODO : handle error
					element.progressIndicator({'mode': 'hide'});
				}
			);
		});
	},
	
	/**
	 * Function to register related list events
	 */
	registerRelatedListEvents : function(){
		var selectedTabElement = this.getSelectedTab();
		var relatedModuleName = this.getRelatedModuleName();
		var relatedController = new Verteiler_RelatedList_Js(this.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);
		relatedController.registerEvents();
	},
	
    registerRelatedListFilterEvents : function(){
        var thisInstance = this;
        var detailContentsHolder = this.getContentHolder();
        detailContentsHolder.on('change','#relatedFilter',function(e){
            var selectedTabElement = thisInstance.getSelectedTab();
            var relatedModuleName = thisInstance.getRelatedModuleName();
            var relatedController = new Verteiler_RelatedList_Js(thisInstance.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);
            var params = relatedController.getCompleteParams();
            relatedController.loadRelatedList(params);
        });
        // workaround for dropdown menu popping up when pressing enter on text input
        detailContentsHolder.on('keypress','#relatedFilter',function(e){
            if (e.keyCode == 13) {
                e.preventDefault();
                jQuery("#relatedFilter").trigger("change");
            }
        
        })
    },
	
  
	registerEvents : function(){
		this.registerRelatedListEvents();
		this.registerRelatedListFilterEvents();
		this._super();
		//Calling registerevents of campaigns list to handle checkboxs click of related records
		var listInstance = Vtiger_List_Js.getInstance();
		listInstance.registerEvents();
	}
})