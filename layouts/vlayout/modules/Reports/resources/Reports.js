callCampaignList = {
	//It stores the list response data
	listResponseCache : {},

	showlist: function(reportid, modulename) {
		//callMailChimpList.initOverlay();
		var aDeferred = jQuery.Deferred();
		
		var url = 'index.php?module=Reports&view=showCampaignList&reportid='+encodeURIComponent(reportid)+'&modulename='+modulename;
		AppConnector.request(url).then(
			
			function(data){
				if(data.indexOf("NOCAMPAIGN") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_CAMP_NOT_ACTIVE'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
					return false;
				}
				else {
					app.showScrollBar(jQuery('#transferPopupScroll'), {
						height: '300px',
						railVisible: true,
						size: '6px'
					});
					callMailChimpList.listResponseCache = data;
					aDeferred.resolve(callMailChimpList.listResponseCache);
					var callbackFunction = function(data) {
						app.showScrollBar(jQuery('#transferPopupScroll'), {
							height: '300px',
							railVisible: true,
							size: '6px'
						});
					}
					app.showModalWindow(data, function(data){
						if(typeof callbackFunction == 'function' && jQuery('#transferPopupScroll').height() > 300){
							callbackFunction(data);
						}
					});
				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	},
	
	create : function(reportid, modulename) {
		var campaignlist = document.getElementById('campaignlist').value;
		var id_list = document.getElementById('id_list').value;
		var url = 'index.php?module=Reports&action=addContactsOrLeadsfromReportstoCampaign&ajax=true&reportid='+reportid+'&campaignlist='+encodeURIComponent(campaignlist)+'&contactids='+id_list+'&modulename='+modulename;

		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
			'enabled' : true
			}
		});
		
		AppConnector.request(url).then(
			function(data){
				
				progressIndicatorElement.progressIndicator({'mode' : 'hide'});
				
				if(data.result.indexOf("FAILURE") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_NO_TRANSFER'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
					return false;
				}
				else {
					var params = {
						title: app.vtranslate('JS_ALERT'),
						text: data.result,
						type : 'info',
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);

				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	}
	
}

callMailChimpList = {
	//It stores the list response data
	listResponseCache : {},

	showlist: function(reportid, modulename) {
		//callMailChimpList.initOverlay();
		var aDeferred = jQuery.Deferred();
		
		var url = 'index.php?module=Reports&view=showMailChimpList&reportid='+encodeURIComponent(reportid)+'&modulename='+modulename;
		AppConnector.request(url).then(
			
			function(data){
				if(data.indexOf("NOMAILCHIMP") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_NOT_ACTIVE'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
					return false;
				}
				else {
					app.showScrollBar(jQuery('#transferPopupScroll'), {
						height: '300px',
						railVisible: true,
						size: '6px'
					});
					callMailChimpList.listResponseCache = data;
					aDeferred.resolve(callMailChimpList.listResponseCache);
					var callbackFunction = function(data) {
						app.showScrollBar(jQuery('#transferPopupScroll'), {
							height: '300px',
							railVisible: true,
							size: '6px'
						});
					}
					app.showModalWindow(data, function(data){
						if(typeof callbackFunction == 'function' && jQuery('#transferPopupScroll').height() > 300){
							callbackFunction(data);
						}
					});
				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	},
	
	create : function(reportid, modulename) {
		var mailchimplistid = document.getElementById('mailchimplist').value;
		var id_list = document.getElementById('id_list').value;
		var url = 'index.php?module=Reports&action=addContactsOrLeadsfromReportstoMailchimp&ajax=true&reportid='+reportid+'&mailchimplistid='+encodeURIComponent(mailchimplistid)+'&contactids='+id_list+'&modulename='+modulename;
		
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
			'enabled' : true
			}
		});
		
		AppConnector.request(url).then(
			
			function(data){
				
				progressIndicatorElement.progressIndicator({'mode' : 'hide'});
				
				if(data.result.indexOf("FAILURE") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_NO_TRANSFER'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
				}
				else {
					var params = {
						title: app.vtranslate('JS_ALERT'),
						text: data.result,
						type : 'info',
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);

				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	}
	
}

callCleverReachList = {
	//It stores the list response data
	listResponseCache : {},

	showlist: function(reportid, modulename) {
		//callCleverReachList.initOverlay();
		var aDeferred = jQuery.Deferred();
		
		var url = 'index.php?module=Reports&view=showCleverReachList&reportid='+encodeURIComponent(reportid)+'&modulename='+modulename;
		AppConnector.request(url).then(
			
			function(data){
				if(data.indexOf("NOCLEVERREACH") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_CR_NOT_ACTIVE'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
					return false;
				}
				else {
					app.showScrollBar(jQuery('#transferPopupScroll'), {
						height: '300px',
						railVisible: true,
						size: '6px'
					});
					callCleverReachList.listResponseCache = data;
					aDeferred.resolve(callCleverReachList.listResponseCache);
					var callbackFunction = function(data) {
						app.showScrollBar(jQuery('#transferPopupScroll'), {
							height: '300px',
							railVisible: true,
							size: '6px'
						});
					}
					app.showModalWindow(data, function(data){
						if(typeof callbackFunction == 'function' && jQuery('#transferPopupScroll').height() > 300){
							callbackFunction(data);
						}
					});
				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	},
	
	create : function(reportid, modulename) {
		var cleverreachlist = document.getElementById('cleverreachlist').value;
		var id_list = document.getElementById('id_list').value;
		var url = 'index.php?module=Reports&action=addContactsOrLeadsfromReportstoCleverReach&ajax=true&reportid='+reportid+'&cleverreachid='+encodeURIComponent(cleverreachlist)+'&contactids='+id_list+'&modulename='+modulename;
		
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
			'enabled' : true
			}
		});
	
		AppConnector.request(url).then(
			
			function(data){
				
				progressIndicatorElement.progressIndicator({'mode' : 'hide'});
				
				if(data.result.indexOf("FAILURE") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_NO_TRANSFER'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
				}
				else {
					var params = {
						title: app.vtranslate('JS_ALERT'),
						text: data.result,
						type : 'info',
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);

				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	}
	
}

callVerteilerList = {
	//It stores the list response data
	listResponseCache : {},

	showlist: function(reportid, modulename) {
		var aDeferred = jQuery.Deferred();
		
		var url = 'index.php?module=Reports&view=showVerteilerList&reportid='+reportid+'&modulename='+modulename;
		AppConnector.request(url).then(
			
			function(data){
				if(data.indexOf("NOCLEVERREACH") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_VERTEILER_NOT_ACTIVE'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
					return false;
				}
				else {
					app.showScrollBar(jQuery('#transferPopupScroll'), {
						height: '300px',
						railVisible: true,
						size: '6px'
					});
					callVerteilerList.listResponseCache = data;
					aDeferred.resolve(callVerteilerList.listResponseCache);
					var callbackFunction = function(data) {
						app.showScrollBar(jQuery('#transferPopupScroll'), {
							height: '300px',
							railVisible: true,
							size: '6px'
						});
					}
					app.showModalWindow(data, function(data){
						if(typeof callbackFunction == 'function' && jQuery('#transferPopupScroll').height() > 300){
							callbackFunction(data);
						}
					});
				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	},
	
	create : function(reportid, modulename) {
		var verteilerlist = document.getElementById('verteilerlist').value;
		var id_list = document.getElementById('id_list').value;
		var url = 'index.php?module=Reports&action=addContactsfromReportstoVerteiler&ajax=true&reportid='+reportid+'&verteilerid='+verteilerlist+'&contactids='+id_list+'&modulename='+modulename;
		
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
			'enabled' : true
			}
		});
	
		AppConnector.request(url).then(
			
			function(data){
				
				progressIndicatorElement.progressIndicator({'mode' : 'hide'});
				
				if(data.result.indexOf("FAILURE") > -1 ) {
					var params = {
						title: app.vtranslate('JS_ERROR'),
						text: app.vtranslate('JS_NO_TRANSFER'),
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);
				}
				else {
					var params = {
						title: app.vtranslate('JS_ALERT'),
						text: data.result,
						type : 'info',
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);

				} 
			},
			function(error){
				//aDeferred.reject();
			}
		);
	}
	
}

startCron = {
	
	exercise : function() {
		var aDeferred = jQuery.Deferred();
		var url = 'index.php?module=Reports&action=resetReportsCron&ajax=true';
		
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
			'enabled' : true
			}
		});
	
		AppConnector.request(url).then(
			function(data){
				progressIndicatorElement.progressIndicator({'mode' : 'hide'});
				aDeferred.resolve(data);
				if(data.result.success == true ) {
					if(typeof data.result.listViewUrl != 'undefined') {
						var path = data.result.listViewUrl;
						window.location.assign(path);
					}
				}
				else {
					var params = {
						title: data.result.error,
						text: data.result.message,
						type : 'info',
						width: '35%'
					};
					Vtiger_Helper_Js.showPnotify(params);

				}

			},
			function(error){
				//aDeferred.reject();
			}
		);
		return aDeferred.promise();
	}

}
