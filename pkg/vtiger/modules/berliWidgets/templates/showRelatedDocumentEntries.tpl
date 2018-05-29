{strip}
<div class="relatedEntrisContainer" id="updates">
	<div>
		{if !empty($RELATED_ENTRIES)}
			<ul class="unstyled">
				{foreach item=RELATED_ENTRY from=$RELATED_ENTRIES}
					<li>
						<div>
							<span><a data-id={$RELATED_ENTRY->getId()} href="{$RELATED_ENTRY->getDetailViewUrl()|escape}" title="{decode_html($RELATED_ENTRY->getName())}">{decode_html($RELATED_ENTRY->getName())}</a></span>
						</div>
					</li>
				{/foreach}
			</ul>
		{else}
			<div class="summaryWidgetContainer">
				<p class="textAlignCenter">{vtranslate('LBL_NO_RECORDS', 'berliWidgets')}</p>
			</div>
		{/if}
	</div>
	<span class="clearfix"></span>
</div>
{/strip}
