<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/

require_once('include/Zend/Json.php');
	
if($_REQUEST['faqid'] != ''){
	$faqid = Zend_Json::decode($_REQUEST['faqid']);
   
}	

$faq_array = $_SESSION['faq_array'];
$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];



for($i=0;$i<count($faq_array);$i++)
{

	if($faqid == $faq_array[$i]['id'])
	{
		$faq_id = $faq_array[$i]['id'];
		$faq_module_no = $faq_array[$i]['faqno'];
		$faq_createdtime = $faq_array[$i]['faqcreatedtime'];
		$faq_modifiedtime = $faq_array[$i]['faqmodifiedtime'];
		$faq_productid = $faq_array[$i]['product_id'];
		$faq_category = $faq_array[$i]['category'];

		$comments_array = $_SESSION['faq_array'][$i]['comments'];
		$createdtime_array = $_SESSION['faq_array'][$i]['createdtime'];

		$comments_count = count($comments_array);

		$list .= '<div style = "clear:both;"></div>
					<div class = "widget-box">';
		$list .= '<div class = "widget-header"><h5 class = "widget-title">'.getTranslatedString('LBL_FAQ_TITLE').'</h5></div>';
								
		$list .= '<div class = "widget-body">
					<div class="widget-main no-padding single-entity-view">
						<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
		//$list .= '<h5  align="right" class="widget-title">
			//  <span id="faq" class="lnkHdr" onMouseOver="fnShow(this)" onMouseOut="fnHideDiv(\'faqDetail\')">'.getTranslatedString('LBL_FAQ_DETAIL').'</span></h5>';
		$list .= '<div class="row">
					<div class="form-group col-sm-6">
						<div class="col-sm-9 dvtCellInfo">'.$faq_array[$i]['question'].'</div></div></div>';
		$list .= '<div class="row"><div class="form-group col-sm-6">
				<label class="col-sm-3 control-label no-padding-right">'.getTranslatedString('LBL_ANSWER').'</label>
				<div class="col-sm-9 dvtCellInfo" align="left" valign="top">'.$faq_array[$i]['answer'].'</div>
			    </div></div>';
		$list .= '</div></div></div></div>';

		$list .= '<div class = "widget-box">';
		$list .= '<div class = "widget-header"><h5 class = "widget-title">'.getTranslatedString('LBL_COMMENTS').'</h5></div>';

		$list .= '<div class = "widget-body">
					<div class="widget-main no-padding single-entity-view">
						<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';

		for($j=0;$j<$comments_count;$j++)
		{
			$list .= '<div class="row">
						<div class="form-group col-sm-12">
					   <label class="col-sm-1 control-label no-padding-right">'.($comments_count-$j).' </label>
						<div class="col-sm-11 dvtCellInfo" align="left" valign="top">
							'.$comments_array[$j];

			if ($createdtime_array[$j]!="0000-00-00 00:00:00")
				$list .= '<br><span class="hdr">'.getTranslatedString('LBL_ADDED_ON').$createdtime_array[$j].'</span>';

			$list .= '</div></div></div>';
		}
		$list .= '</div></div></div></div>';
		$list .= '<div class = "widget-box">';
		$list .= '<div class = "widget-header"><h5 class = "widget-title">'.getTranslatedString('LBL_DOCUMENTS').'</h5></div>';
		$list .= '<div class = "widget-body">
					<div class="widget-main no-padding single-entity-view">
						<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
		
		$module = 'Documents';
		$params = array('id' => "$faqid",'module'=>"$module", 'contactid'=>"$customerid",'sessionid'=>"$sessionid");
		$result = $client->call('get_documents', $params, $Server_Path, $Server_Path);
		$list .=  getblock_fieldlistview($result,$module);	
		$list .= '</div></div></div></div>';   	
	   	$list .= '<form name="comments" method="POST" action="index.php">
				<input type="hidden" name="module">
				<input type="hidden" name="action">
				<input type="hidden" name="fun">
				<input type=hidden name=faqid value="'.$faqid.'">
			   	<div class = "widget-box">';
		$list .= '<div class = "widget-header"><h5 class = "widget-title">'.getTranslatedString('LBL_ADD_COMMENT').'</h5></div>
				<div class = "widget-body">
					<div class="widget-main no-padding single-entity-view">
						<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">
							<div class="row">
								<div class="form-group col-sm-12">
									<textarea name="comments" style = "width:100%;">&nbsp;</textarea>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-sm-6">
								<input title="'.getTranslatedString('LBL_SAVE_ALT').'" accesskey="S" class="btn btn-primary"  name="submit" value="'.getTranslatedString('LBL_SUBMIT').'" style="width: 70px;" type="submit" onclick="this.form.module.value=\'Faq\';this.form.action.value=\'index\';this.form.fun.value=\'faq_updatecomment\'; if(trim(this.form.comments.value) != \'\') return true; else return false;"/>
								</div>
							</div>
						</div>
					</div>
				</div>
			   </div></form>';
		$list .= '<div class="widget-box">
					<div class = "widget-body">
						<div class="widget-main no-padding single-entity-view">
							<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">
								<div class="row">'.getPageOption().'</div>
			   				</div>
			   			</div>
			   		</div>
			   	</div>';
	}
}

$list .= '		</table>';

//This is added to get the FAQ details as a Popup on Mouse over
$list .= getArticleIdTime($faq_module_no,$faq_productid,$faq_category,$faq_createdtime,$faq_modifiedtime);

echo $list;





?>
<style>
	.fagView {display:none;}
</style>
