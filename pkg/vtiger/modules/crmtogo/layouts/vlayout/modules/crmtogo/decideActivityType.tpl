{strip}
<!DOCTYPE html>
<head>
	<!-- the following header content gets only loaded with a direct http call-->
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<meta charset="utf-8">
	<link REL="SHORTCUT ICON" HREF="../../layouts/vlayout/modules/crmtogo/resources/images/favicon.ico">	
	<script type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/jquery-1.11.2.min.js"></script>
	<script type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/jquery.mobile-1.4.5.min.js"></script>
	<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/jquery.mobile.structure-1.4.5.min.css" >
	<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/jquery.mobile.icons.min.css" >
	<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/theme.css" >
	<script type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/lang/{$LANGUAGE}.lang.js"></script>
</head>
<body>
<div class="ui-corner-bottom ui-content ui-body-c" data-theme="b" data-role="content" role="main">
	<a class="ui-btn ui-shadow ui-btn-corner-all ui-btn-up-b" data-theme="b"  data-role="button" href="?_operation=create&module=Events&record=''" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span">
		<span class="ui-btn-inner ui-btn-corner-all">
			<span class="ui-btn-text">{vtranslate('LBL_EVENT_BUTTON', 'crmtogo',{$LANGUAGE})}</span>
		</span>
	</a>
	<a class="ui-btn ui-shadow ui-btn-corner-all ui-btn-up-b" data-theme="b"  data-role="button" href="?_operation=create&module=Calendar&record=''" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span">
		<span class="ui-btn-inner ui-btn-corner-all">
			<span class="ui-btn-text">{vtranslate('LBL_TASK_BUTTON', 'crmtogo',{$LANGUAGE})}</span>
		</span>
	</a>
	<a class="ui-btn ui-shadow ui-btn-corner-all ui-btn-up-c" style="background:#ECE5AF;" data-theme="c" data-rel="back" data-role="button" href="?_operation=listModuleRecords" data-corners="true" data-shadow="true" data-iconshadow="true" data-wrapperels="span" data-transition="pop" data-direction="reverse">
		<span class="ui-btn-inner ui-btn-corner-all">
			<span class="ui-btn-text">{vtranslate('LBL_CANCEL_BUTTON', 'crmtogo',{$LANGUAGE})}</span>
		</span>
	</a>
</div>
</body>
</html>
{/strip}