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
{strip}
	{assign var="MODULE_NAME" value=$MODULE_MODEL->get('name')}

					</div>
				</form>
			</div>
			<div class="noprint related span2 marginLeftZero" data-module-name="{$MODULE_NAME}" data-record-id="{$RECORD->getId()}">
				<div class="">
					<ul class="nav nav-stacked nav-pills">
						{foreach item=RELATED_LINK from=$DETAILVIEW_LINKS['DETAILVIEWTAB']}
							{assign var="DETAILVIEWRELATEDLINKCOUNT" value=$RELATED_LINK->getRecordCount()}
							{assign var="COUNT_KIND" value=$RELATED_LINK->get('countKind')}
							<li class="{if $RELATED_LINK->getLabel()==$SELECTED_TAB_LABEL}active{/if}" data-url="{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}" data-label-key="{$RELATED_LINK->getLabel()}" data-link-key="{$RELATED_LINK->get('linkKey')}" >
								<a href="javascript:void(0);" class="textOverflowEllipsis" style="width:auto" title="{vtranslate($RELATED_LINK->getLabel(),{$MODULE_NAME})}"><strong>{vtranslate($RELATED_LINK->getLabel(),{$MODULE_NAME})}{if $COUNT_KIND}<span class="detailViewRelatedCount" data-count-kind="{$COUNT_KIND}" data-count-pending="{if $RELATED_LINK->get('countDeferred')}1{else}0{/if}">{if $DETAILVIEWRELATEDLINKCOUNT neq '0' AND $DETAILVIEWRELATEDLINKCOUNT neq ''}&nbsp;({$DETAILVIEWRELATEDLINKCOUNT}){/if}</span>{elseif $DETAILVIEWRELATEDLINKCOUNT neq '0' AND $DETAILVIEWRELATEDLINKCOUNT neq ''}&nbsp;({$DETAILVIEWRELATEDLINKCOUNT}){/if}</strong></a>
							</li>
						{/foreach}
						{foreach item=RELATED_LINK from=$DETAILVIEW_LINKS['DETAILVIEWRELATED']}
							<li class="{if $RELATED_LINK->getLabel()==$SELECTED_TAB_LABEL}active{/if}" data-url="{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}" data-label-key="{$RELATED_LINK->getLabel()}" >
								{* Assuming most of the related link label would be module name - we perform dual translation *}
								{assign var="DETAILVIEWRELATEDLINKLBL" value=vtranslate($RELATED_LINK->getLabel(), $RELATED_LINK->getRelatedModuleName())}
								{assign var="DETAILVIEWRELATEDLINKCOUNT" value=$RELATED_LINK->getRecordCount()}
								<a href="javascript:void(0);" class="textOverflowEllipsis" style="width:auto" title="{$DETAILVIEWRELATEDLINKLBL}"><strong>{$DETAILVIEWRELATEDLINKLBL}<span class="detailViewRelatedCount" data-count-kind="relation" data-relation-id="{$RELATED_LINK->get('relationId')}" data-count-pending="{if $RELATED_LINK->get('countDeferred')}1{else}0{/if}">{if $DETAILVIEWRELATEDLINKCOUNT neq '0' AND $DETAILVIEWRELATEDLINKCOUNT neq ''}&nbsp;({$DETAILVIEWRELATEDLINKCOUNT}){/if}</span></strong></a>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>
		</div>
	</div>
	</div>
</div>
</div>
<script type="text/javascript" defer src="{vresource_url('layouts/vlayout/modules/Vtiger/resources/DetailViewCounts.js')}"></script>
{/strip}
