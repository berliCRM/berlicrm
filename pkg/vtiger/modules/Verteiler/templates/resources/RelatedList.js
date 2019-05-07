/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_RelatedList_Js("Verteiler_RelatedList_Js",{
	
	/*
	 * function to trigger send Email
	 * @params: send email url , module name.
	 */
	triggerSendEmail : function(massActionUrl, module){
		var params = {"relatedLoad" : true};
		//To get the current module
		params['sourceModule'] = app.getModuleName();
		//to get current campaign id 
		params['sourceRecord'] = jQuery('#recordId').val();
		Vtiger_List_Js.triggerSendEmail(massActionUrl, module, params);
	}
},{
	
	loadRelatedList : function(params){
		var aDeferred = jQuery.Deferred();
		var thisInstance = this;
		this._super(params).then(function(data){
			thisInstance.registerEvents();
			var moduleName = app.getModuleName();
			var className = moduleName+"_List_Js";
			var listInstance = new window[className]();
			listInstance.registerEvents();
			aDeferred.resolve(data);
		});
		return aDeferred.promise();
	},
	
	getCompleteParams : function(){
		var params = {};
		params['view'] = "Detail";
		params['module'] = this.parentModuleName;
		params['record'] = this.getParentId(),
		params['relatedModule'] = this.relatedModulename,
		params['sortorder'] =  this.getSortOrder(),
		params['orderby'] =  this.getOrderBy(),
		params['page'] = this.getCurrentPageNum();
		params['mode'] = "showRelatedList",
		params['selectedIds'] = jQuery('#selectedIds').data('selectedIds');
		params['excludedIds'] = jQuery('#excludedIds').data('excludedIds');
		params['filter'] = jQuery('#relatedFilter').val();
		return params;
	},
	
	changeCustomFilterElementView : function() {
		var filterSelectElement = jQuery('#recordsFilter');
		if(filterSelectElement.length > 0){
			app.showSelect2ElementView(filterSelectElement);
		}
	},
    
    changeLoadVerteilerElementView : function() {
		var filterSelectElement = jQuery('#loadVerteiler');
		if(filterSelectElement.length > 0){
			app.showSelect2ElementView(filterSelectElement);
		}
	},
	/**
	 * Function to register change event for custom filter
	 */
	
	registerChangeCustomFilterEvent : function(){
		var filterSelectElement = jQuery('#recordsFilter');
		filterSelectElement.off();  // make sure it isn't attached twice after reloading list        
		filterSelectElement.change(function(e){
             var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_ADD_THIS_FILTER');
             Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(     
             	function() {
			var element = jQuery(e.currentTarget);
			var cvId = element.find('option:selected').data('id');
			var relatedModuleName = jQuery('.relatedModuleName').val();
			var params = {
				'sourceRecord' : jQuery('#recordId').val(),
				'relatedModule' :relatedModuleName,
				'viewId' : cvId,
				'module' : app.getModuleName(),
				'action': "RelationAjax",
				'mode' : 'addRelationsFromRelatedModuleViewId'
			}
			
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(params).then(
				function(responseData){
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
                    if(!responseData.success) {
						var params = {
							text: responseData.error.message,
							type: 'error'
						};
						Vtiger_Helper_Js.showMessage(params);
                    }
					else if(responseData.result == 0){
						var message = app.vtranslate('JS_LBL_NO_RECORDS');
						var params = {
							text: message,
							type: 'info'
						};
						Vtiger_Helper_Js.showMessage(params);
					} else {
						Vtiger_Detail_Js.reloadRelatedList();
					}
				},

				function(textStatus, errorThrown){
				}
			);
            },
           function(error, err){
                       }
       );
		});
		
	},
 
	/**
	 * Function to register change event for load verteiler
	 */
	
	registerLoadVerteilerEvent : function(){
		var filterSelectElement = jQuery('#loadVerteiler');
		filterSelectElement.off();  // make sure it isn't attached twice after reloading list        
		filterSelectElement.change(function(e){
             var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_ADD_THIS_VERTEILER');
             Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(     
             	function() {
			var params = {
				'sourceRecord' : jQuery('#recordId').val(),
				'verteilerId' : filterSelectElement.val(),
				'module' : app.getModuleName(),
				'action': "RelationAjax",
				'mode' : 'addRelationsFromOtherVerteiler'
			}
			
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(params).then(
				function(responseData){
					progressIndicatorElement.progressIndicator({
						'mode' : 'hide'
					})
                    if(!responseData.success) {
						var params = {
							text: responseData.error.message,
							type: 'error'
						};
						Vtiger_Helper_Js.showMessage(params);
                    }
					else if(responseData.result == 0){
						var message = app.vtranslate('JS_LBL_NO_RECORDS');
						var params = {
							text: message,
							type: 'info'
						};
						Vtiger_Helper_Js.showMessage(params);
					} else {
						Vtiger_Detail_Js.reloadRelatedList();
					}
				},

				function(textStatus, errorThrown){
				}
			);
            },
           function(error, err){
                       }
       );
		});
		
	},
    
    registerRemoveEntriesEvent : function() {
        jQuery('#removeEntries').off();
        jQuery('#removeEntries').on("click",function() {
            var entries = [];
            // collect selected entries and their respective userid
            jQuery('.listViewEntriesCheckBox:checked').each(function() {
                var row = jQuery(this).closest('tr');
                var entry = [jQuery(this).val(),row.find(".addedByUser").data("id")];
                entries.push(entry);
            });     
            if (entries.length == 0) {
                alert(app.vtranslate('JS_LBL_PLEASE_SELECT_ENTRIES'));
                return;
            }

            var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_REMOVE_ENTRIES');
             Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(     
             	function() {
                    var params = {};
                    params['action'] = 'RelationAjax';
                    params['mode'] = 'massDeleteRelation';
                    params['module'] = 'Verteiler';
                    params['related_module'] = 'Contacts';
                    params['src_record'] = jQuery('#recordId').val();
                    params['related_records'] = JSON.stringify(entries);
                    AppConnector.request(params).then(function(responseData) {
                        if(!responseData.success) {
						var params = {
							text: responseData.error.message,
							type: 'error'
						};
						Vtiger_Helper_Js.showMessage(params);
                        }
                        else 
                        Vtiger_Detail_Js.reloadRelatedList()
                    });
                });
        });
    },
	
    registerFindDuplicatesEvent : function() {
        jQuery('#findDuplicates').off();
        jQuery('#findDuplicates').on("click",function() {
            var url = "index.php?module=Verteiler&view=findDuplicatesMenuAjax&record=" + jQuery('#recordId').val();
            AppConnector.request(url).then(
            function(data){
                app.showModalWindow(data, function(data){
                    if(typeof callbackFunction == 'function' && jQuery('#transferPopupScroll').height() > 400){
                        callbackFunction(data);
                    }
                });
            }
        );
        });
    },
    
	registerEvents : function(){
		this.changeCustomFilterElementView();
		this.changeLoadVerteilerElementView();
		this.registerChangeCustomFilterEvent();
		this.registerLoadVerteilerEvent();
		this.registerRemoveEntriesEvent();
		this.registerFindDuplicatesEvent();
	}
})