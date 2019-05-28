/*********************************************************************************
 * The contents of this file are copyright to Target Integration Ltd and are governed
 * by the license provided with the application. You may not use this file except in 
 * compliance with the License.
 * For support please visit www.targetintegration.com 
 * or email support@targetintegration.com
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *********************************************************************************/
var Settings_Mailchimp_Js = {

	regiserSelectGroupEvent : function(data) {
		data.find('[name="mcgrouplist"]').on('change',function(e) {
			var recordId = jQuery( "#recordId" ).val();
			Settings_Mailchimp_Js.getGroups (recordId);
			jQuery('#syncButton').prop('disabled', false);
		});
	},
	getGroups :  function(id) {
		var listeid = document.getElementById('mcgrouplist').value;
		document.getElementById('groups').disabled = false;
		var groups_name ="";
		jQuery('button[name=saveButton]').prop("disabled",true);
		jQuery('#loading').show();
		var params = 'index.php?module=Mailchimp&action=LoadSyncValues&get=getGroupInfos&id='+id+'&listeid='+listeid;
		AppConnector.request(params).then(
			function(result) {
				jQuery('button[name=saveButton]').prop("disabled",false);
				jQuery('#loading').hide();
				if(result.result =='nogroupfound') {
					//no group found
					groups_name= 'default';
					document.getElementById('newgroupname_row').style.display ="";
					document.getElementById('groupname').style.display ="none";
					document.getElementById('new_groupname').style.display ="block";
					document.getElementById('groups_row').style.display ="none";
					document.getElementById('newGroupName').value="default";
					//document.getElementById('newGroupName').disabled = false;
				}
				else {
					//group found
					groups_name=result.result;
					document.getElementById('newgroupname_row').style.display ="none";
					document.getElementById('groupname').style.display ="block";
					document.getElementById('new_groupname').style.display ="none";
					document.getElementById('groups_row').style.display ="";
					document.getElementById('groups').value = groups_name;
					document.getElementById('groups').disabled ="disabled";
				}
			}
		);
	}


}
function loadMailchimpList(type,id) {
	var element = type+"_cv_list";
	var value = document.getElementById(element).value;        

	var filter = jQuery(element)[jQuery(element).selectedIndex].value	;
	if(filter=='None')return false;
	if(value != '') {
		jQuery("status").style.display="inline";
		new Ajax.Request(
			'index.php',
			{queue: {position: 'end', scope: 'command'},
				method: 'post',
				postBody: 'module=Mailchimp&action=MailchimpAjax&file=LoadList&ajax=true&return_action=DetailView&return_id='+id+'&list_type='+type+'&cvid='+value,
				onComplete: function(response) {
					jQuery("status").style.display="none";
					jQuery("RLContents").update(response.responseText);
				}
			}
		);
	}
}



MailChimpCommon = {
	OVERLAYID: '__MailChimpCommonOverlay__',
	
	initOverlay : function() {
		var overlaynode = document.createElement('div');
		overlaynode.id = MailChimpCommon.OVERLAYID;
		overlaynode.style.width = '550px';
		overlaynode.style.display = 'block';		
		overlaynode.style.zIndex = '1000000';
		overlaynode.style.backgroundColor = '#333';

		document.body.appendChild(overlaynode);
	},
	showlist: function(record) {
		if (!jQuery( "#mailchimplog" ).length ) {
			alert(app.vtranslate('JSLBL_GOTO_DETAIL_VIEW'));
		}
		else {
			MailChimpCommon.initOverlay();
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
				'enabled' : true
				}
			});

			var params = 'index.php?module=Mailchimp&view=showGroupOverlay&record='+encodeURIComponent(record);
			AppConnector.request(params).then(
				function(result) {
					progressIndicatorElement.progressIndicator({'mode':'hide'});
					var callBackFunction = function(result) {
						jQuery('[name="addItemForm"]',result).validationEngine();
						Settings_Mailchimp_Js.regiserSelectGroupEvent(result);
					}
					app.showModalWindow(result, function(data) {
						if(typeof callBackFunction == 'function') {
							callBackFunction(data);
						}
					});
				},
				function(error,err){
					progressIndicatorElement.progressIndicator({'mode':'hide'});
					alert(app.vtranslate('JS_SERVER_ERROR')+': '+error+' '+err)
				}

			);
		}
	},
	

	checklist : function() {
		var e = document.getElementById('mcgrouplist');
		if ( e.options[e.selectedIndex].value =='') {
			alert (alert_arr.LBL_NO_DATA_SELECTED);
			return false;
		}
		return true;
	},
	
	sync : function(recordid) {
		var mcgroupid =  jQuery("#mcgrouplist").val();
		var mcgroupname = jQuery("#mcgrouplist option:selected").text();
		
		jQuery('#mailchimplog').children().remove();
		
		var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
				'enabled' : true
				}
			});
		jQuery('#mailchimplog').append('<div>'+app.vtranslate('MC_WAIT')+'</div>');	
		MailChimpCommon.syncstart("Mailchimp", recordid, 1, mcgroupid, mcgroupname, progressIndicatorElement);
		
	},
	
	syncstart : function getStep(module, recordid, step, mcgroupid, mcgroupname, progressIndicatorElement) { 
		var params = {
				'step' : step,
				'module' : 'Mailchimp',
				'action' : 'MailChimpStepController',
				'recordid' : recordid,
				'mcgroupid' : mcgroupid,
				'mcgroupname' : mcgroupname,
				'verbose' : document.getElementById("verbose").checked
		}

		AppConnector.request(params).then(
			function(responseData){					
				if(responseData.success){
					
					var nextstep = responseData.result[2];
		
					// "divid" set, update progress indicator
					if (responseData.result[3]!= null) {
					
						var divid = responseData.result[3];
						
						if (!jQuery('#'+divid).length) {
							jQuery('#mailchimplog').append('<div id="'+divid+'" style="font-size:125%;padding-top:10px"></div>');
						}
						
						jQuery('#'+divid).animate({color: '#aab'}).animate({color: '#000'});
						jQuery('#'+divid).html(responseData.result[1]);
						jQuery('#mailchimplog').scrollTop($('#mailchimplog')[0].scrollHeight);
						MailChimpCommon.syncstart("Mailchimp", recordid, nextstep, mcgroupid, mcgroupname, progressIndicatorElement);
						return;
					}
					
					// append response message to synclog and scroll into view
					jQuery('#mailchimplog').append('<div>'+responseData.result[1]+"</div>");
					jQuery('#mailchimplog').scrollTop($('#mailchimplog')[0].scrollHeight);
					
					if (nextstep > 0 && nextstep < 5) {
				
						MailChimpCommon.syncstart("Mailchimp", recordid, nextstep, mcgroupid, mcgroupname, progressIndicatorElement);
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
		jQuery('#mailchimplog').children().remove();
	},
	/**
	 * Function to hide overlay
	 */
	hide : function() {
		app.hideModalWindow();
	},
	
	
}
