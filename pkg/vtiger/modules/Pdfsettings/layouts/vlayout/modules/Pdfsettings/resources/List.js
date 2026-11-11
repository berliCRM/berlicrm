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
    saveLetterhead : function() {
        jQuery('.selectPdfLetterhead').off('click.pdfLetterhead').on('click.pdfLetterhead', function() {
            var button = jQuery(this);
            var targetId = button.data('target');
            var popupInstance = Vtiger_Popup_Js.getInstance();
            popupInstance.show({
                view: 'Popup',
                module: 'Documents',
                src_module: 'Pdfsettings',
                src_field: 'pdf_letterhead'
            }, function(data) {
                var records = JSON.parse(data);
                for (var documentId in records) {
                    if (!records.hasOwnProperty(documentId)) {
                        continue;
                    }
                    var record = records[documentId];
                    var label = record.name || (record.info && record.info.filename) || ('#' + documentId);
                    jQuery('#' + targetId).val(documentId);
                    jQuery('#' + button.data('label-target')).val(label);
                    jQuery('#' + button.data('preview-target'))
                        .attr('href', 'index.php?module=Documents&action=DownloadFile&mode=preview&record=' + encodeURIComponent(documentId))
                        .removeClass('hide');
                    break;
                }
            }, 'pdfLetterhead_' + targetId);
        });
        jQuery('.clearPdfLetterhead').off('click.pdfLetterhead').on('click.pdfLetterhead', function() {
            var button = jQuery(this);
            jQuery('#' + button.data('target')).val('');
            jQuery('#' + button.data('label-target')).val('');
            jQuery('#' + button.data('preview-target')).attr('href', '#').addClass('hide');
        });
        jQuery('#savePdfLetterhead').off('click.pdfLetterhead').on('click.pdfLetterhead', function() {
            var form = jQuery('#pdfLetterheadSettings');
            var data = form.serializeFormData();
            if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
                data[csrfMagicName] = csrfMagicToken;
            }
            var progress = jQuery.progressIndicator({});
            AppConnector.request({url: 'index.php', type: 'POST', data: data, dataType: 'json'}).then(
                function(result) {
                    progress.progressIndicator({'mode': 'hide'});
                    if (!result || result.success !== true) {
                        var message = result && result.error && result.error.message
                            ? result.error.message
                            : app.vtranslate('JS_ERROR');
                        Vtiger_Helper_Js.showPnotify({title: message, type: 'error'});
                        return;
                    }
                    Vtiger_Helper_Js.showPnotify({
                        title: app.vtranslate('LBL_LETTERHEAD_SUCCESS'),
                        type: 'info'
                    });
                    window.location.reload();
                },
                function(error) {
                    progress.progressIndicator({'mode': 'hide'});
                    Vtiger_Helper_Js.showPnotify({
                        title: error && error.message ? error.message : app.vtranslate('JS_ERROR'),
                        type: 'error'
                    });
                }
            );
        });
    },

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
		this.saveLetterhead();
		this.savePdfsettings();
	}
});
