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
require_once('include/utils/utils.php');

function getNoofFaqsPerCategory($category_name)
{
	$faq_array = $_SESSION['faq_array'];
	$count = 0;
	for($i=0;$i<count($faq_array);$i++)
	{
		if($category_name == $faq_array[$i]['category'])
			$count++;
	}
	return $count;
}
function getNoofFaqsPerProduct($productid)
{
	$faq_array = $_SESSION['faq_array'];
	$count = 0;
	for($i=0;$i<count($faq_array);$i++)
	{
		if($productid == $faq_array[$i]['product_id'])
			$count++;
	}
	return $count;
}
function getLatestlyCreatedFaqList()
{
	$list = '';
	$product_array = $_SESSION['product_array'];
	$faq_array = $_SESSION['faq_array'];
	$list = '<div class="widget-header"><h5><b>'.getTranslatedString('LBL_RECENTLY_CREATED').'</b></h5></div>';
	$list .= '<div class="table-responsive">
				<table width="100%" border="0" cellspacing="1" cellpadding="3" class="lvt table table-striped table-bordered table-hover">';
	
	for($i=0;$i<count($faq_array);$i++)
	{
		$record_exist = true;
		$list .= '<tr>
					<td>
						<img src="images/faq.gif" valign="absmiddle">&nbsp;
						<a class="faqQues" href=index.php?module=Faq&action=index&fun=faq_detail&faqid='.$faq_array[$i]['id'].'>'.$faq_array[$i]['question'].'</a>
					</td>
			   	  </tr>
			   	  <tr>
					<td class="small" style="padding-left:35px;" >'.$faq_array[$i]['answer'].'</td>
	    		   	</tr>';
	}
	if(!$record_exist)
		$list .= getTranslatedString('LBL_NO_FAQ');

	$list .= '</table></div></div>';
	return $list; 
}
function ListFaqsPerCategory($category_index)
{
	$list = '';
	$category_array = $_SESSION['category_array'];
	$faq_array = $_SESSION['faq_array'];
	$category = $category_array[$category_index];
	$list = '<div class="widget-header"><h5><b>'.getTranslatedString('LNK_CATEGORY').': '.portal_purify($category).'</b><h5></div>';
	$list .= '<div class="table-responsive"><table width="100%" border="0" cellspacing="0" cellpadding="0" class="lvt table table-striped table-bordered table-hover">';

	for($i=0;$i<count($faq_array);$i++)
	{
		if($category == $faq_array[$i]['category'])
		{
			$flag = true;
			$list .= '
				   <tr>
					<td><img src="images/faq.gif" valign="absmiddle">&nbsp;
						<a class="faqQues" href=index.php?module=Faq&action=index&fun=faq_detail&faqid='.$faq_array[$i]['id'].'>'.$faq_array[$i]['question'].'</a></td>
				   </tr>
				   <tr>
					<td class="small">'.$faq_array[$i]['answer'].'</td></tr><tr><td height="10"></td>
				   </tr>';
		}
	}
	if(!$flag)
		$list .= getTranslatedString('LBL_NO_FAQ_IN_THIS_CATEGORY');
	$list .= '</table>';
	return $list; 
}
function ListFaqsPerProduct($productid)
{
	$list = '';
	$product_array = $_SESSION['product_array'];
	$faq_array = $_SESSION['faq_array'];
	$list = '<div class="widget-header"><h5><b>'.getTranslatedString('LBL_PRODUCT').': '.getProductname(portal_purify($productid)).'</b><h5></div>';
	$list .= '<div class="table-responsive"><table width="100%" border="0" cellspacing="0" cellpadding="0" class="lvt table table-striped table-bordered table-hover">';
	
	for($i=0;$i<count($faq_array);$i++)
	{
		if($productid == $faq_array[$i]['product_id'])
		{
			$flag = true;
			$list .= '
				   <tr>
					<td><img src="images/faq.gif" valign="absmiddle">&nbsp;
						<a class="faqQues" href=index.php?module=Faq&action=index&fun=faq_detail&faqid='.$faq_array[$i]['id'].'>'.$faq_array[$i]['question'].'</a></td>
				   </tr>
				   <tr>
					<td class="small">'.$faq_array[$i]['answer'].'</td>
				   </tr>
				   <tr>
					<td height="10"></td>
				   </tr>';
		}
	}
	if(!$flag) 
		$list .= getTranslatedString('LBL_NO_FAQ_IN_THIS_PRODUCT');
	$list .= '</table>';
	return $list; 
}

function getArticleIdTime($faqid,$product_id,$faqcategory,$faqcreatedtime,$faqmodifiedtime)
{
	$list .='<div id="faqDetail" onMouseOver="fnShowDiv(\'faqDetail\')" onMouseOut="fnHideDiv(\'faqDetail\')">
		 <table class="fagView" cellpadding="0" cellspacing="0">
		   <tr>
			<td align="right"><b>'.getTranslatedString('LBL_FAQ_ID').': </b></td><td align="left"><b>'.$faqid.'</b></td>
		   </tr>
		   <tr>
			<td align="right">'.getTranslatedString('LBL_PRODUCT').': </td><td align="left">'.getProductName($product_id).'</td>
		   </tr>
		   <tr>
			<td align="right">'.getTranslatedString('LBL_CATEGORY').': </td><td align="left">'.$faqcategory.'</td>
		   </tr>
		   <tr>
			<td align="right">'.getTranslatedString('LBL_CREATED_DATE').': </td><td align="left">'.substr($faqcreatedtime,0,10).'</td>
		   </tr>
		   <tr>
			<td align="right" nowrap>'.getTranslatedString('LBL_MODIFIED_DATE').': </td><td align="left">'.substr($faqmodifiedtime,0,10).'</td>
		   </tr>
		</table>
		</div>';

	return $list;
}
function getPageOption()
{
	$list .= '
			<table width="100%" border="0" cellspacing="3" cellpadding="3">
		   	   <tr>
				<td width="18" align="center"><img src="images/print.gif" valign="absmiddle"></td><td><a href="javascript:printPage()">'.getTranslatedString('LBL_PRINT_THIS_PAGE').'</a></td>
				<td width="18" align="center"><img src="images/email.gif" valign="absmiddle"></td><td><a href="javascript:sendAsEmail();">'.getTranslatedString('LBL_EMAIL_THIS_PAGE').'</a></td>
				<td width="18" align="center"><img src="images/favorite.gif" valign="absmiddle"></td><td><a href="javascript:addToFavorite();">'.getTranslatedString('LBL_ADD_TO_FAVORITES').'</a></td>
			   </tr>
			</table>
		';
	$list .= '<script language="JavaScript">
				function printPage() {
					window.print()
				}
				function sendAsEmail() {
					var emailBody=escape("'.getTranslatedString('LBL_ARTICLE_INTERESTED').'"+String.fromCharCode(13)+String.fromCharCode(13)+"URL: "+document.location.href)
					document.location.href = "mailto:?body="+emailBody;
				}
				function addToFavorite() {
					if (document.all) {
						window.external.addFavorite(document.location.href,document.title);
					} else {
						alert("'.getTranslatedString('LBL_PRESS_CNTR_D').'")
					}
				}
			</script>';
	
	return $list;
}
function getProductName($productid)
{
	$product_array = $_SESSION['product_array'];
	$productname = '';
	for($i=0;$i<count($product_array);$i++)
	{
		if($productid == $product_array[$i]['productid'])
			$productname = $product_array[$i]['productname'];
	}
	return $productname;
}
function getSearchCombo()
{
	$category_array = $_SESSION['category_array'];
	$product_array = $_SESSION['product_array'];
	$comboarray = '<select name="search_category">';
	$comboarray .= '<OPTION value="all:All">All</OPTION>';
	$comboarray .= '<OPTGROUP label="Categories">';
	for($i=0;$i<count($category_array);$i++)
	{
		$selected = '';
		$search_category = explode(":",$_REQUEST['search_category']);
		if($category_array[$i] == $search_category[1])
			$selected = 'selected';
		$comboarray .= '<OPTION value="category:'.$category_array[$i].'"'.$selected.'>'.$category_array[$i].'</OPTION>';
	}
	$comboarray .= '</OPTGROUP>';
	$comboarray .= '<OPTGROUP label="Products">';
        for($i=0;$i<count($product_array);$i++)
        {
                $selected = '';
		$search_category = explode(":",$_REQUEST['search_category']);
                if($product_array[$i]['productname'] == $search_category[1])
                        $selected = 'selected';
                $comboarray .= '<OPTION value="products:'.$product_array[$i]['productname'].'"'.$selected.'>'.$product_array[$i]['productname'].'</OPTION>';
        }
        $comboarray .= '</OPTGROUP>';
	$comboarray .= '</select>';
	return $comboarray;
}
function getSearchResult($search_text,$search_value,$search_by)
{
	$faq_array = $_SESSION['faq_array'];
	
	$list = '<div class="addimage">'.getTranslatedString('LBL_SEARCH_RESULT').'</div>';
	$list .= '<br><table class="dummy" width="100%" border=0 cellspacing=0 cellpadding=0>';

	if($search_value == 'All')
        {
                for($i=0;$i<count($faq_array);$i++)
                {
			if($search_text != '')
	                        $flag = @stristr($faq_array[$i]['question'],$search_text);
			else
				$flag = true;

                        if($flag)
                        {
				$record_exist = true;
                                $list .= ' <tr>
						<td><img src="images/faq.gif" valign="absmiddle">&nbsp;
			                                <a class="faqQues" href=index.php?module=Faq&action=index&fun=faq_detail&faqid='.$faq_array[$i]['id'].'>'.$faq_array[$i]['question'].'</a></td>
					   </tr>
					   <tr>
						<td class="small">'.$faq_array[$i]['answer'].'</td>
					   </tr>
					   <tr>
						<td height="18" class="kbFAQInfo">'.getTranslatedString('LBL_CATEGORY').': '.$faq_array[$i]['category'].'</td>
					   </tr>
					   <tr>
						<td height="15"></td>
					   </tr>';
                        }
                }
		if(!$record_exist)
                        $list .=  getTranslatedString('LBL_NO_FAQ_IN_THIS_SEARCH_CRITERIA');
        }
        elseif($search_by == 'category')
        {
                for($i=0;$i<count($faq_array);$i++)
                {
			if($search_text != '')
	                        $flag = @stristr($faq_array[$i]['question'],$search_text);
			else
				$flag = true;
                        if($flag && $faq_array[$i]['category'] == $search_value)
                        {
				$record_exist = true;
                                $list .= '
					   <tr>
						<td><img src="images/faq.gif" valign="absmiddle">&nbsp;
							<a class="faqQues" href=index.php?module=Faq&action=index&fun=faq_detail&faqid='.$faq_array[$i]['id'].'>'.$faq_array[$i]['question'].'</a></td>
					   </tr>
					   <tr>
						<td class="small">'.$faq_array[$i]['answer'].'</td>
					   </tr>';
                        }
                }
		if(!$record_exist)
			$list .=  getTranslatedString('LBL_NO_FAQ_IN_THIS_SEARCH_CRITERIA');
        }
	elseif($search_by == 'products')
	{
		$product_array = $_SESSION['product_array'];
		$faq_array = $_SESSION['faq_array'];
		for($i=0;$i<count($product_array);$i++)
		{
			if($product_array[$i]['productname'] == $search_value)
			{
				for($j=0;$j<count($faq_array);$j++)
       				{
					if($search_text != '')
		                                $flag = @stristr($faq_array[$j]['question'],$search_text);
                		        else
                                		$flag = true;
			        	if($flag && ($product_array[$i]['productid'] == $faq_array[$j]['product_id']))
			                {
                        			$record_exist = true;
			                        $list .= '
							   <tr>
								<td><img src="images/faq.gif" valign="absmiddle">
									<a class="faqQues" href=index.php?module=Faq&action=index&fun=faq_detail&faqid='.$faq_array[$j]['id'].'>'.$faq_array[$j]['question'].'</a></td>
							   </tr>
							   <tr>
								<td class="small">'.$faq_array[$j]['answer'].'</td>
							   </tr>
							   <tr>
								<td height="10"></td>
							   </tr>';
			                }
			        }
			}
		}
		if(!$record_exist)
                        $list .=  getTranslatedString('LBL_NO_FAQ_IN_THIS_SEARCH_CRITERIA');
	}

	$list .= '</table>';
	return $list;
}

function text_length($str){
	$length = strlen($str);
	if($length > 25){
		$str = substr($str,0,25)."..";
		return $str;
	}
	return $str;
}
?>
