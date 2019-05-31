{strip}
<!DOCTYPE html>
{literal}
	<script type="text/javascript">
		function fn_submit() {
			$.mobile.loading( 'show' );
			document.form.submit();
		}
	</script>
{/literal}
<body>
<div data-role="panel" id="panelmenu" data-position="right"  data-native-menu="false" data-display="overlay">
	<div id='logoutbutton'>
		<table style="width:100%">
			<tr >
				<td style="width:10%;">
					<a href="index.php?_operation=logout" class="ui-btn ui-corner-all ui-icon-power ui-btn-icon-notext"></a>
				</td>
				<td align= "left">
					{vtranslate('LBL_LOGOUT', 'crmtogo')}
				</td>
			</tr>
 		</table>
	</div>
	<div  data-role="fieldcontain" data-mini="true">
		<form  name="form"  method="post" action="?_operation=globalsearch&module={$_MODULES[0]->name()}" target="_self">
			<input type="hidden" name="parenttab" value="{if isset($CATEGORY)}{$CATEGORY}{/if}" style="margin:0px">
			<input type="hidden" name="search_onlyin" value="{if isset($SEARCHIN)}{$SEARCHIN}{/if}" style="margin:0px">
			<table style="width:100%;padding-top:5px;">
				<tr >
					<td>
						<input type="text" data-inline="true" name="query_string" value="{if isset($QUERY_STRING)}{$QUERY_STRING}{/if}">
					</td>
					<td>
						<a href="#"  onclick="fn_submit();" target="_self"  class="ui-btn ui-btn-inline ui-icon-search ui-btn-icon-notext ui-corner-all ui-shadow"></a>
					</td>
				</tr>
 			</table>
		</form>
	</div>
	<div data-role="collapsible-set" data-mini="true">
		<ul data-role="listview" data-theme="c" id="homesortable">
		{foreach item=_MODULE from=$_MODULES}
			{if $_MODULE->active() && $_MODULE->name() neq 'Events'}
				<li id={$_MODULE->name()}>
					{if $_MODULE->name() eq 'Calendar'}
						<a href="index.php?_operation=listModuleRecords&module=Calendar" target="_self">{$_MODULE->label()}</a>
					{else}
						<a href="index.php?_operation=listModuleRecords&module={$_MODULE->name()}" target="_self">{$_MODULE->label()}</a>
					{/if}
					{if $_MODULE->name() neq 'Calendar' AND $_MODULE->name() neq 'Quotes' AND  $_MODULE->name() neq 'SalesOrder' AND  $_MODULE->name() neq 'Invoice' AND  $_MODULE->name() neq 'PurchaseOrder' AND  $_MODULE->name() neq 'Products'}
						<a href="?_operation=create&module={$_MODULE->name()}&record=''&quickcreate=1" class="ui-btn ui-icon-plus ui-btn-icon-notext" alt="{vtranslate('LBL_QUICKCREATE', 'crmtogo')}" data-transition="turn">{vtranslate('LBL_QUICKCREATE', 'crmtogo')}</a>
					{/if}
				</li>
			{/if}
		{/foreach}
		</ul>
	</div>
</div>
</body>
{/strip}