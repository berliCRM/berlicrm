<div class="relatedContainer listViewPageDiv">
<table class="table table-bordered">
	<tr>
		{foreach from=$ENTRIES.headers item=header}
		 <th>   {$header}
		{/foreach}
	{foreach from=$ENTRIES.entries item=entry}
		<tr>
		<td>{$entry[0]}
		<td>
		{if $entry[1][1] == "Emails"}
			<a href="index.php?module=Emails&view=ComposeEmail&mode=emailPreview&record={$entry[1][0]}" target="_blank">{vtranslate('LBL_EMAILS', $MODULE)}</a>
		{else}
			<a href="index.php?module={$entry[1][1]}&view=Detail&record={$entry[1][0]}">{$entry[1][1]}</a>
		{/if}

		<td>{$entry[2]}
		</tr>
	{/foreach}
</table>
</div>