{strip}
<body>
<div class="detailViewContainer">
    <br>
    <div class="row-fluid textarea redColor">
		{if GOOGLEKEY_ERROR eq true}	
			{vtranslate('GOOGLEKEY_ERROR', $MODULE_NAME)}
		{else}
			{vtranslate('GOOGLEINAVTIVE_ERROR', $MODULE_NAME)}
		{/if}
	</div>	
</div>		
</body>
{/strip}