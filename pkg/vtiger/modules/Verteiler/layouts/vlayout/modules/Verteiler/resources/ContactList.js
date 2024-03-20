    /*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("Verteiler_ContactList_Js",{
    
    listViewAddToVerteiler : function() {
        var listInstance = Vtiger_List_Js.getInstance();
		var selectedIds = listInstance.readSelectedIds();
        
        if(selectedIds.length < 1){
			listInstance.noRecordSelectedAlert();
            return false;
		}
        
        var params = 'index.php?module=Verteiler&view=VerteilerListOverlay';
        AppConnector.request(params).then(
            function(result) {
                app.showModalWindow(result);
                Verteiler_ContactList_Js.registerEvents();
            });
    },
    
    changeLoadVerteilerElementView : function() {
		var filterSelectElement = jQuery('#verteilerlist');
		if(filterSelectElement.length > 0){
			app.showSelect2ElementView(filterSelectElement);
		}
	},
    
    registerVerteilerChange : function() {
        jQuery('#verteilerlist').on("change",function() {
            // enable submit button if valid entry from dropdown list has been selected
			jQuery('#addContacs').prop('disabled', jQuery("#verteilerlist").val()=="");
        });
    },
    
    registerSubmit : function() {
        jQuery('#addContacs').on('click',function() {
            var listInstance = Vtiger_List_Js.getInstance();
            var selectedIds = listInstance.readSelectedIds();
            var verteilerId = jQuery('#verteilerlist').val();
            
            if (selectedIds == "all") {
                var cvId = listInstance.getCurrentCvId();
                var params = {
                    'sourceRecord' : verteilerId,
                    'relatedModule' :'Contacts',
                    'viewId' : cvId,
                    'module' : 'Verteiler',
                    'action': "RelationAjax",
                    'mode' : 'addRelationsFromRelatedModuleViewId'
                }
                
                var progressIndicatorElement = jQuery.progressIndicator({
                    'position' : 'html',
                    'blockInfo' : {'enabled' : true}
                });
                AppConnector.request(params).then(
                    function(responseData){
                        progressIndicatorElement.progressIndicator({
                            'mode' : 'hide'
                        })
                        if(responseData.result == 0){
                            var message = app.vtranslate('JS_LBL_NO_RECORDS');
                            var params = {
                                text: message,
                                type: 'info'
                            };
                            Vtiger_Helper_Js.showMessage(params);
                        } else {
                            var message = app.vtranslate('JS_CONTACTS_ADDED');
                            var params = {
                                text: message,
                                type: 'info'
                            };
                            Vtiger_Helper_Js.showMessage(params);
                        }
                    },

                    function(textStatus, errorThrown){
                        progressIndicatorElement.progressIndicator({
                            'mode' : 'hide'
                        })
                    }
                );
            }
            else {
                var params = {
                    'src_record' : verteilerId,
                    'related_module' : 'Contacts',
                    'module' : 'Verteiler',
                    'action': "RelationAjax",
                    'mode' : 'addRelation',
                    'related_record_list' : JSON.stringify(selectedIds)
                };
                var progressIndicatorElement = jQuery.progressIndicator({
                    'position' : 'html',
                    'blockInfo' : {'enabled' : true}
                });
                AppConnector.request(params).then(
                    function(responseData){
                        progressIndicatorElement.progressIndicator({
                            'mode' : 'hide'
                        })
                        if(responseData.result){
                            var message = app.vtranslate('JS_CONTACTS_ADDED');
                            var params = {
                                text: message,
                                type: 'info'
                            };
                            Vtiger_Helper_Js.showMessage(params);
                        }
                    },

                    function(textStatus, errorThrown){
                        progressIndicatorElement.progressIndicator({
                            'mode' : 'hide'
                        })
                    }
                );
            }
        });
    },
    
    registerEvents : function() {
        Verteiler_ContactList_Js.changeLoadVerteilerElementView();
        Verteiler_ContactList_Js.registerVerteilerChange();
        Verteiler_ContactList_Js.registerSubmit();
    }
},
{
})