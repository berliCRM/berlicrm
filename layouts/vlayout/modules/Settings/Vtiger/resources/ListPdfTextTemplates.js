/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("Vtiger_listpdftexttemplates_Js",{},{
    deletePdfTemplate : function() {
		jQuery('#delete_template').click(function(e){
            e.preventDefault();
			//e.stopPropagation();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
			var templtype = $( "#displaymodul option:selected" ).val();
			if (templtype == 'LETTER') {
				var frmName = document.massdelete_letter;
				var nocount =  document.getElementById('nolettercount');
				if (typeof(nocount) != 'undefined' && nocount != null) {
					data = {
							title : app.vtranslate('LBL_NO_LETTER_TEMPL'),
							animation: 'show',
							type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(data);
					return false;
				}
			}
			else {
				var frmName = document.massdelete_conclusion;
				var nocount =  document.getElementById('noconclusioncount');
				if (typeof(nocount) != 'undefined' && nocount != null) {
					data = {
							title : app.vtranslate('LBL_NO_CONCLUSION_TEMPL'),
							animation: 'show',
							type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(data);
					return false;
				}
			}
			var nocount =  document.getElementById('nocount');
			var x = frmName.selected_id.length;
			var idstring = "";
			if ( x == undefined) {
				if (frmName.selected_id.checked) {
					frmName.idlist.value=frmName.selected_id.value+';';
					var xx=1;
				}
				else {
					data = {
							title : app.vtranslate('LBL_ATLEAST_ONE'),
							animation: 'show',
							type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(data);
					return false;
				}
			}
			else {
				var xx = 0;
				for(i = 0; i < x ; i++) {
					if(frmName.selected_id[i].checked) {
						idstring = frmName.selected_id[i].value +";"+idstring
						xx++
					}
				}
				if (xx != 0) {
					frmName.idlist.value=idstring;
				}
				else {
					data = {
							title : app.vtranslate('LBL_ATLEAST_ONE'),
							animation: 'show',
							type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(data);
					return false;
				}
			}
			var message = app.vtranslate('LBL_MASS_DELETE_CONFIRMATION');
			Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(
				function(e) {
					var selectedIds = jQuery("input[name=selected_id]:checked").map(function () {return this.value;}).get().join(";");
					var deleteURL = 'index.php?parent=Settings&module=Vtiger&view=listpdftexttemplates&action=deletepdftexttemplate&texttype='+templtype.toLowerCase()+'&idlist='+selectedIds;
					var deleteMessage = app.vtranslate('JS_RECORDS_ARE_GETTING_DELETED');
					var progressIndicatorElement = jQuery.progressIndicator({
						'message' : deleteMessage,
						'position' : 'html',
						'blockInfo' : {
							'enabled' : true
						}
					});
					var actionParams = {
						"type":"POST",
						"url":deleteURL,
						"dataType":"html",
						"data" : {}
					};
					AppConnector.request(actionParams).then(
						function(data){ 
							progressIndicatorElement.progressIndicator({
								'mode' : 'hide'
							});
							var response = JSON.parse(data);
							if(response.success){
								var  params = {
									title : app.vtranslate('JS_RECORD_GETTING_DELETED'),
									text : response.message,
									delay: '2000',
									type: 'success'
								}
								Vtiger_Helper_Js.showPnotify(params);
								location.reload();
							} 
							else {
								var  params = {
										title : app.vtranslate('JS_ERROR'),
										text : response.error.message,
										type: 'error'
									}
								Vtiger_Helper_Js.showPnotify(params);
							}
						}
					);
				})
		}),
		

        jQuery('#save_template').one('submit',function(e){
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

    registerNewTemplateButton : function() {
        jQuery("#new_template").on("click",function(e){
            var ttype=jQuery("#displaymodul").val();
            window.location.href="index.php?parent=Settings&module=Vtiger&mode=create&view=createpdfstexttemplate&templatetype="+ttype;
        });
    },
    registerEvents : function(){
        this.registerNewTemplateButton();
		this.deletePdfTemplate();
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
    }

})