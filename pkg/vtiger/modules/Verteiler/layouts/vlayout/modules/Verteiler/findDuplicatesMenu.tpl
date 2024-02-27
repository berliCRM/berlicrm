<div class="modelContainer">
<div class="modal-header contentsBackground">
<h3>{vtranslate('LBL_DUPLICATE_SEARCH',$MODULE)}</h3>
{if empty($DUPLICATECONTACTS)}
<br>
<p>{vtranslate('LBL_NO_DUPLICATES',$MODULE)}</p>
</div>
{else}
<p>{vtranslate('LBL_DUPLICATES_COMMENT',$MODULE)}</p>
</div>
<div class="modal-body" style="height:300px;overflow-y:scroll">

<form action="index.php?module=Verteiler&action=deleteDuplicates" method="POST" id="dupForm">
	<table class="table table-bordered">
		<tr>
			<th>{vtranslate('LBL_FIRSTNAME',$MODULE)}
			<th>{vtranslate('LBL_LASTNAME',$MODULE)}
			<th>{vtranslate('LBL_ORG',$MODULE)}
			<th>{vtranslate('LBL_EMAIL',$MODULE)}
			<th>{vtranslate('LBL_ADDEDBY',$MODULE)}
			<th>{vtranslate('LBL_FROM_LIST',$MODULE)}
			<th>{vtranslate('LBL_DELETE',$MODULE)}
		</tr>
		{foreach from=$DUPLICATECONTACTS item=record}
		<tr>
			<td>{$record['firstname']}
			<td>{$record['lastname']}
			<td>{$record['accountname']}
			<td>{$record['email']}
			<td>{$record['added_by_user_name']}
			<td>{$record['parent']}
			<td><input type="checkbox" name="del[{$record['crmid']}][{$record['added_by_user_id']}]">
		</tr>
		{/foreach}


	</table>
	<input type="hidden" name="autodelete" id="autodelete" value="0">
	<input type="hidden" name="record" value="{$RECORD}">
</form>
</div>
<div class="modal-footer">
    <div class="pull-right cancelLinkContainer"><a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL',$MODULE)}</a></div>
    <button class="btn btn-success" onClick='singleSubmit();'><strong>{vtranslate('LBL_MANUAL_DELETE',$MODULE)}</strong></button>
    <button class="btn btn-success" onClick='autoSubmit();'><strong>{vtranslate('LBL_AUTOMATIC_DELETE',$MODULE)}</strong></button>

</div>
{/if} 
</div>
{literal}
<script>
function singleSubmit() {
    jQuery("#dupForm").submit();
}

function autoSubmit() {
    jQuery("#autodelete").val(1);
    jQuery("#dupForm").submit();
}

{/literal}

