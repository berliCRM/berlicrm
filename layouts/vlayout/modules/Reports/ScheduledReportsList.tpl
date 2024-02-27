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
<div class="recordNamesList">
	<div class="row-fluid">
		<div class="">
			<ul class="nav nav-list">
				{foreach item=scheduledreport key=reportid from=$SCHEDULEDREPORTS}
				<li>
					{vtranslate('LBL_CARRYING_OUT', $MODULE)}: {decode_html($scheduledreport.next_time)}<a href="index.php?module=Reports&view=Detail&record={$reportid}" target="blank"  title="{decode_html($scheduledreport.reportname)}">{decode_html($scheduledreport.reportname)}</a>
				</li>
				{foreachelse}
					<li style="text-align:center">{vtranslate('LBL_NOTHING_SCHEDULED', $MODULE)}
					</li>
				{/foreach}
			</ul>
			<hr>
			<ul class="nav nav-list">
				<li>
					<b>{vtranslate('LBL_CRON_STATUS', $MODULE)}:</b>&nbsp;{$CRONSTATUS}
				</li>
			</ul>
			<ul class="nav nav-list">
				<li>
					0:&nbsp;{vtranslate('LBL_CRON_STATUS_0', $MODULE)}
				</li>
				<li>
					1:&nbsp;{vtranslate('LBL_CRON_STATUS_1', $MODULE)}
				</li>
				<li>
					2:&nbsp;{vtranslate('LBL_CRON_STATUS_2', $MODULE)}
				</li>
				<li>
					&nbsp;
				</li>
			</ul>
			{if $CRONSTATUS eq 2}
				<hr>
				<ul class="nav nav-list">
					<li>
						<i>{vtranslate('LBL_CRON_HEADLINE', $MODULE)}</i>
					</li>
					<li>
						{vtranslate('LBL_LAST_TIME', $MODULE)}:&nbsp;{$LASTTIME}
					</li>
					{if $NEXTTIME eq ''}
					<li>
						{vtranslate('LBL_RUNNING_SINCE', $MODULE)}:&nbsp;{$RUNNINGTIME}
					</li>
					{else}
					<li>
						{vtranslate('LBL_NEXT_TIME', $MODULE)}:&nbsp;{$NEXTTIME}
					</li>
					{/if}
				</ul>
				{if $ISADMIN eq 'on' && $SHOWBUTTON eq true}
					<ul class="nav nav-list">
						<li>
							&nbsp;
						</li>
						<li>
							<button type="button" id="start_cron" class="btn btn-danger backStep" onclick="if(startCron.exercise());else return false;"><strong>{vtranslate('LBL_CRON_ACTIVATION',$MODULE)} </strong></button>
						</li>
						<li>
							&nbsp;
						</li>
						<li>
							{vtranslate('LBL_CRON_COMMENT_1', $MODULE)}{$CRONFREQUENCYLIMIT}{vtranslate('LBL_CRON_COMMENT_2', $MODULE)}
						</li>
						<li>
							&nbsp;
						</li>
						<li>
							{vtranslate('LBL_CRON_COMMENT_3', $MODULE)}
						</li>
						<li>
							&nbsp;
						</li>
						<li>
							{vtranslate('LBL_CRON_COMMENT_6', $MODULE)}
						</li>
						
					</ul>
				{else if $ISADMIN neq 'on'}
					<ul class="nav nav-list">
						<li>
							&nbsp;
						</li>
						<li>
							{vtranslate('LBL_CRON_COMMENT_4', $MODULE)}{$CRONFREQUENCYLIMIT}{vtranslate('LBL_CRON_COMMENT_5', $MODULE)}
						</li>
					</ul>
				{/if}
			{else}
				<hr>
				<ul class="nav nav-list">
					<li>
						<i>{vtranslate('LBL_CRON_HEADLINE', $MODULE)}</i>
					</li>
					<li>
						{vtranslate('LBL_LAST_TIME', $MODULE)}:&nbsp;{$LASTTIME}
					</li>
					<li>
						{vtranslate('LBL_NEXT_TIME', $MODULE)}:&nbsp;{$NEXTTIME}
					</li>
				</ul>
			
			{/if}
		</div>
	</div>
</div>
{/strip}