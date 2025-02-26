/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_List_Js("Settings_LoginHistory_List_Js",{},{
    
	registerFilterChangeEvent : function() {
		var thisInstance = this;
		jQuery('#usersFilter').on('change',function(e){
			jQuery('#pageNumber').val("1");
			jQuery('#pageToJump').val('1');
			jQuery('#orderBy').val('');
			jQuery("#sortOrder").val('');
			var params = {
				module : app.getModuleName(),
				parent : app.getParentModuleName(),
				'search_key' : 'user_name',
				'search_value' : jQuery(e.currentTarget).val(),
				'page' : 1,
                'user_name' :this.options[this.selectedIndex].getAttribute("name")
			}
			//Make total number of pages as empty
			jQuery('#totalPageCount').text("");
			thisInstance.getListViewRecords(params).then(
				function(data){
					thisInstance.updatePagination();
				}
			);
		});
	},
	
	getDefaultParams : function() {
		var pageNumber = jQuery('#pageNumber').val();
		var module = app.getModuleName();
		var parent = app.getParentModuleName();
		var params = {
			'module': module,
			'parent' : parent,
			'page' : pageNumber,
			'view' : "List",
			'user_name' : jQuery('select[id=usersFilter] option:selected').attr('name'),
			'search_key' : 'user_name',
			'search_value' : jQuery('#usersFilter').val()
		}

		return params;
	},
	
	/**
	 * Function to get Page Jump Params
	 */
	getPageJumpParams : function(){
		var module = app.getModuleName();
		var parent = app.getParentModuleName();
		var pageJumpParams = {
			'module' : module,
			'parent' : parent,
			'action' : "ListAjax",
			'mode' : "getPageCount",
			'search_value' : jQuery('#usersFilter').val(),
			'search_key' : 'user_name'
		}
		return pageJumpParams;
	},
	
	/**
	 * Function for Export Buttons
	 */
	registerButtonsForLoginHistory : function(){
		let offset = 0;
		let buttonCSVsortTime = jQuery("#exportUserByTime");
		let buttonCSVsortName = jQuery("#exportUserByName");
		let usersFilter = jQuery("#usersFilter");
        let filetyp = "csv"; 

		buttonCSVsortTime.click(function(e) {
			buttonCSVsortTime.prop('disabled',true);
			buttonCSVsortName.prop('disabled',true);
			let selecteduser = usersFilter.val();
			exportCsvFile('exportUserByTime', offset ,selecteduser );
		});

		buttonCSVsortName.click(function(e) {
			buttonCSVsortTime.prop('disabled',true);
			buttonCSVsortName.prop('disabled',true);
			let selecteduser = usersFilter.val();
			exportCsvFile('exportUserByName', offset ,selecteduser);
		});

		function exportCsvFile(mode, offset, selecteduser) {

			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
   
			var params = 'index.php?module=LoginHistory&view=List&parent=Settings&action=exportLoginHistory&mode='+mode+'&offset='+offset+'&selecteduser='+selecteduser+"&filetyp="+filetyp;

			AppConnector.request(params).then(
				function(response) {
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					if(response) {
						var exportedData = jQuery.parseJSON(response);

						var startednow = exportedData.result.startednow;
						var offset = exportedData.result.recordnum;
						var wiederholen = exportedData.result.wiederholen;

						if (startednow ==  1 && wiederholen == 1)  {
							// we have start and have not reacht the end. On start we have set the Labels, but now it will be set in php.
							exportCsvFile(mode, offset, selecteduser);
						}
						else if (wiederholen == 1){
							//the start was allready and we have now many repetitions, until we reach the end.
							exportCsvFile(mode, offset, selecteduser);
						}
						else{
							// the end is reached, and we need to download the file. 
							downloadfile(filetyp, mode, selecteduser, offset);
						}	
					}
					else {
						var  params = {
							title : app.vtranslate('JS_MESSAGE'),
							text: app.vtranslate('JS_EXPORT_FAILURE'),
							animation: 'show',
							type: 'error'
						}
						Vtiger_Helper_Js.showPnotify(params);
					}
				},
				function(error){
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					alert ('internal CRM problem');
				}
			)
            
		}

		function downloadfile(filetyp, mode, selecteduser, offset) {
			// here download the file, and then delete it."loaddel"
			let turn  = "loaddel";
			let params = "index.php?module=LoginHistory&view=List&parent=Settings&action=DownloadFile"+"&filetyp="+filetyp+"&mode="+mode+"&selecteduser="+selecteduser+"&offset="+offset+"&turn="+turn ;            
            window.location.href = params;

			// and set the buttons enabled.
			buttonCSVsortTime.prop('disabled',false);
            buttonCSVsortName.prop('disabled',false);
		}

	},

	
	
	registerEvents : function() {
		this.registerFilterChangeEvent();
		this.registerPageNavigationEvents();
		this.registerEventForTotalRecordsCount();
		this.registerButtonsForLoginHistory();
	}
});