/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger_List_Js("Pdfsettings_List_Js",{
    
    savePdfsettingsParameter : function() {
        jQuery('#pdfsettings').one('submit',function(e){
            e.preventDefault();
			//e.stopPropagation();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
            var form = jQuery(e.currentTarget);
            var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.Quotes_gprodname_qc != 'on') & (inputdata.Quotes_gproddes_qc != 'on') & (inputdata.Quotes_gprodcom_qc != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_GROUP_REQUIRED_QUOTES'),
                    animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.Quotes_iprodname_qc != 'on') & (inputdata.Quotes_iproddes_qc != 'on') & (inputdata.Quotes_iprodcom_qc != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_INDIVIDUAL_REQUIRED_QUOTES'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.Invoice_gprodname_ic != 'on') & (inputdata.Invoice_gproddes_ic != 'on') & (inputdata.Invoice_gprodcom_ic != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_GROUP_REQUIRED_INV'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.Invoice_iprodname_ic != 'on') & (inputdata.Invoice_iproddes_ic != 'on') & (inputdata.Invoice_iprodcom_ic != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_INDIVIDUAL_REQUIRED_INV'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.SalesOrder_gprodname_sc != 'on') & (inputdata.SalesOrder_gproddes_sc != 'on') & (inputdata.SalesOrder_gprodcom_sc != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_GROUP_REQUIRED_SO'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.SalesOrder_iprodname_sc != 'on') & (inputdata.SalesOrder_iproddes_sc != 'on') & (inputdata.SalesOrder_iprodcom_sc != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_INDIVIDUAL_REQUIRED_SO'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.PurchaseOrder_gprodname_pc != 'on') & (inputdata.PurchaseOrder_gproddes_pc != 'on') & (inputdata.PurchaseOrder_gprodcom_pc != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_GROUP_REQUIRED_PO'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			if( (inputdata.PurchaseOrder_iprodname_pc != 'on') & (inputdata.PurchaseOrder_iproddes_pc != 'on') & (inputdata.PurchaseOrder_iprodcom_pc != 'on')) {
                data = {
                    title : app.vtranslate('ITEM_INDIVIDUAL_REQUIRED_PO'),
                    animation: 'show',
                    type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }

			var params = 'index.php?module=Pdfsettings&view=List&action=UpdatePDFSettings&inputdata='+jQuery.param(inputdata);
			AppConnector.request(params).then(
				function(result) {
					if(result=='OK'){
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_SUCCESS'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					} 
				}
			);
			disableFields(pdfsettings);
			return true;
        });
    },
 
	getDefaultParams : function() {
		var params = {
			'module': app.getModuleName(),
			'parent': app.getParentModuleName(),
			'dataType' : 'url',
			'url' : 'index.php?module=Pdfsettings&view=List&action=UpdatePDFSettings',
			'view' : 'List'
		}
        return params;
    },
   
},
{
    savePdfsettings : function() {
		jQuery("#saveg").unbind().click(function(event){
            Pdfsettings_List_Js.savePdfsettingsParameter();
		});
		jQuery("#savei").unbind().click(function(event){
            Pdfsettings_List_Js.savePdfsettingsParameter();
		});
		jQuery("#saveso").unbind().click(function(event){
            Pdfsettings_List_Js.savePdfsettingsParameter();
		});
		jQuery("#savepo").unbind().click(function(event){
            Pdfsettings_List_Js.savePdfsettingsParameter();
		});
		
		
    },

    registerEvents : function(){
		this._super();
        var container = this.getListViewContainer();
		this.savePdfsettings();
	}
});