/*********************************************************************************
 * The contents of this file are copyright to Target Integration Ltd and are governed
 * by the license provided with the application. You may not use this file except in 
 * compliance with the License.
 * For support please visit www.targetintegration.com 
 * or email support@targetintegration.com
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 * 27.7. bb			renamed mcgrouplist to crgrouplist
 * 28.7.			improved form validation
 *********************************************************************************/
var Settings_berliCleverReach_Js = {

	registerSelectGroupEvent : function(data) {
		data.find('[name="crgrouplist"]').on('change',function(e) {

			// enable submit button if valid entry from dropdown list has been selected
			jQuery('#syncButton').prop('disabled', jQuery("#crgrouplist").prop("selectedIndex")<1);
		});
	}
};

berliCleverReachCommon = {
	
	showlist: function(record) {
		// check if we're in Detail View
		if (!jQuery( "#berliCleverReachlog" ).length ) {
			alert(app.vtranslate('JSLBL_GOTO_DETAIL_VIEW'));
		}
		else {
			//berliCleverReachCommon.initOverlay();
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
				'enabled' : true
				}
			});
		// show groups from cleverreach to sync to
			var params = 'index.php?module=berliCleverReach&view=showGroupOverlay&record='+encodeURIComponent(record);
			AppConnector.request(params).then(
				function(result) {
					progressIndicatorElement.progressIndicator({'mode':'hide'});
					var callBackFunction = function(result) {
						jQuery('[name="addItemForm"]',result).validationEngine();
						Settings_berliCleverReach_Js.registerSelectGroupEvent(result);
					}
					app.showModalWindow(result, function(data) {
						if(typeof callBackFunction == 'function') {
							callBackFunction(data);
						}
					});
				}
			);
		}
	},
	
	sync : function(recordid) {
		// start sync with local record recordid

		// cleverreach group id
		var crgroupid =  jQuery("#crgrouplist").val();
		var crgroupname = jQuery("#crgrouplist option:selected").text();
		
		jQuery('#berliCleverReachlog').children().remove();
		
		var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
				'enabled' : true
				}
			});
			
		berliCleverReachCommon.syncstart("berliCleverReach", recordid, 1, crgroupid, crgroupname, progressIndicatorElement);
	},
	
 
	syncstart : function getStep(module, recordid, step, crgroupid, crgroupname, progressIndicatorElement) { 
		
		var params = {
				'step' : step,
				'module' : 'berliCleverReach',
				'action' : 'berliCleverReachStepController',
				'recordid' : recordid,
				'crgroupid' : crgroupid,
				'crgroupname' : crgroupname,
				'verbose' : document.getElementById("verbose").checked
		}

		if (step==1) {
			jQuery('#berliCleverReachlog').append('<div>'+app.vtranslate('MC_WAIT')+'</div>');
		}
		
		AppConnector.request(params).then(
				function(responseData){					
					if(responseData.success){
												
						var nextstep = responseData.result[2];
						
						// "divid" set, update progress indicator
						if (responseData.result[3]!= null) {
						
							var divid = responseData.result[3];
							
							if (!jQuery('#'+divid).length) {
								jQuery('#berliCleverReachlog').append('<div id="'+divid+'" style="font-size:125%;padding-top:10px"></div>');
							}
							
							jQuery('#'+divid).animate({color: '#aab'}).animate({color: '#000'});
							jQuery('#'+divid).html(responseData.result[1]);
							jQuery('#berliCleverReachlog').scrollTop($('#berliCleverReachlog')[0].scrollHeight);
							berliCleverReachCommon.syncstart("berliCleverReach", recordid, nextstep, crgroupid, crgroupname, progressIndicatorElement);
							return;
						}
						
						// append response message to synclog and scroll into view
						jQuery('#berliCleverReachlog').append('<div>'+responseData.result[1]+"</div>");
						jQuery('#berliCleverReachlog').scrollTop($('#berliCleverReachlog')[0].scrollHeight);
						
						if (nextstep > 1 && nextstep < 5) {
					
							berliCleverReachCommon.syncstart("berliCleverReach", recordid, nextstep, crgroupid, crgroupname, progressIndicatorElement);
						}
						else {
							progressIndicatorElement.progressIndicator({'mode' : 'hide'});
						}
						
					}
					else {
						var mparams = {
							title : app.vtranslate('RESPONSE_TIME_OUT'),
							text: responseData.error.message,
							animation: 'show',
							type: 'error',
                            delay: 8000
						};
						Vtiger_Helper_Js.showPnotify(mparams);
						progressIndicatorElement.progressIndicator({'mode' : 'hide'});
						return false;
					}
				},

				function(textStatus, errorThrown){
					var mparams = {
						title : textStatus,
						text: errorThrown,
						animation: 'show',
						type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(mparams);
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					return false;
				}
			);
	},
	/**
	 * Function to empty the log field entries
	 */
	emptyLog : function() {
		if (!jQuery( "#berliCleverReachlog" ).length ) {
			alert(app.vtranslate('LBL_GOTO_DETAIL_VIEW'));
		}
		else {
			jQuery('#berliCleverReachlog').children().remove();
		}
	},
	/**
	 * Function to hide overlay
	 */
	hide : function() {
		app.hideModalWindow();
	},
	
	
}
