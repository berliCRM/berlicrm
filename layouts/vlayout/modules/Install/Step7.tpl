{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}

<center>{'LBL_LOADING_PLEASE_WAIT'|vtranslate}...</center>
<div style="width:600px;margin:0 auto;">
	<div class="row-fluid">
		<div class="span6">
			{vtranslate('LBL_CREATE_CONFIG', 'Install')}
		</div>
		<div class="span6" id="config">
			
		</div>
	</div>
	<div class="row-fluid">
		<div class="span6">
			{vtranslate('LBL_INIT_DB', 'Install')}
		</div>
		<div class="span6" id="database">
			
		</div>
	</div>
	<div class="row-fluid">
		<div class="span6">
			{vtranslate('LBL_CREATE_USER', 'Install')}
		</div>
		<div class="span6" id="user">
			
		</div>
	</div>
	<div class="row-fluid">
		<div class="span6">
			{vtranslate('LBL_INSTALL_MODULES', 'Install')}
		</div>
		<div class="span6" id="modules">
			
		</div>
	</div>
	<div class="row-fluid">
		<div class="span6">
			{vtranslate('LBL_FINALIZE', 'Install')}
		</div>
		<div class="span6" id="final">
			
		</div>
	</div>
</div>

<form class="form-horizontal" name="step7" method="post" action="?module=Users&action=Login">
	<input type="hidden" name="username" value="admin" />
	<input type="hidden" name="password" value="{$PASSWORD}" />
	<input type="hidden" id="svn_tag"  name="svn_tag" value="{$SVNTAG}" />
</form>
{literal}
<script type="text/javascript" src="resources/Connector.js"></script>
<script type="text/javascript">
	var imgLoadPath = '{/literal}{vimage_path("vtbusy.gif")}{literal}';
	var imgSuccessPath = '{/literal}{vimage_path("green.png")}{literal}';
	var aDeferred = jQuery.Deferred();
	//create config
	var params = {
		module : 'Install',
		action : 'ajaxInitDB',
		mode   : 'config'
	};
	jQuery('#'+params.mode).html('<img src="'+imgLoadPath+'">');
	AppConnector.request(params).then(function(response) {
		if (response.success && response.result.success) {
			jQuery('#'+params.mode).html('<img src="'+imgSuccessPath+'">');
			//fill database
			params.mode = 'database';
			jQuery('#'+params.mode).html('<img src="'+imgLoadPath+'">');
			AppConnector.request(params).then(function(response) {
				if (response.success && response.result.success) {
					jQuery('#'+params.mode).html('<img src="'+imgSuccessPath+'">');
					//create user
					params.mode = 'user';
					jQuery('#'+params.mode).html('<img src="'+imgLoadPath+'">');
					AppConnector.request(params).then(function(response) {
						if (response.success && response.result.success) {
							jQuery('#'+params.mode).html('<img src="'+imgSuccessPath+'">');
							//install modules
							params.mode = 'modules';
							jQuery('#'+params.mode).html('<img src="'+imgLoadPath+'">');
							AppConnector.request(params).then(function(response) {
								if (response.success && response.result.success) {
									jQuery('#'+params.mode).html('<img src="'+imgSuccessPath+'">');
									//final
									params.mode = 'final';
									jQuery('#'+params.mode).html('<img src="'+imgLoadPath+'">');
									AppConnector.request(params).then(function(response) {
										if (response.success && response.result.success) {
											jQuery('#'+params.mode).html('<img src="'+imgSuccessPath+'">');
											setTimeout(function() { jQuery('form[name="step7"]').submit();}, 2000);
										} else {
											handleError(response);
										}
										aDeferred.resolve();
									},
									function(error){
										handleError(error);
										aDeferred.reject();
									});
								} else {
									handleError(response);
								}
								aDeferred.resolve();
							},
							function(error){
								handleError(error);
								aDeferred.reject();
							});
						} else {
							handleError(response);
						}
						aDeferred.resolve();
					},
					function(error){
						handleError(error);
						aDeferred.reject();
					});
				} else {
					handleError(response);
				}
				aDeferred.resolve();
			},
			function(error){
				handleError(error);
				aDeferred.reject();
			});
		} else {
			handleError(response);
		}
		aDeferred.resolve();
	},
	function(error){
		handleError(error);
		aDeferred.reject();
	});
function handleError(response) {
	var imgFailPath = '{/literal}{vimage_path("Tickets.png")}{literal}';
	var errorMsg = 'Unknown error';
	if (response.error && response.error.message) {
		errorMsg = response.error.message;
	} else if (response.result.message) {
        errorMsg = response.result.message;
    }
    else if (response) {
		errorMsg = response;
	}
	jQuery('#'+params.mode).html('<img src="'+imgFailPath+'">&nbsp'+errorMsg);
}
</script>
{/literal}
