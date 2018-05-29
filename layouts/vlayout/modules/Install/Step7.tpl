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

<form class="form-horizontal" name="step7" method="post" action="?module=Users&action=Login">
	<input type=hidden name="username" value="admin" >
	<input type=hidden name="password" value="{$PASSWORD}" >
	<input type="hidden" id="svn_tag"  name="svn_tag" value="{$SVNTAG}" />
</form>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('form[name="step7"]').submit();
	});
</script>
