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
require_once('include/utils/utils.php');
@include("../PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '')
{
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
include("include.php");

//This is added first because when we add new comment, the comments will be added first and then Faq list will be retrieved
if($_REQUEST['fun'] == 'faq_updatecomment')
{
	include("Faq/SaveFaqComment.php");
}

$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

$params = Array(Array('id' => "$customerid", 'sessionid'=>"$sessionid"));
$result = $client->call('get_KBase_details', $params, $Server_Path, $Server_Path);

$category_array = $result[0];
$faq_array = $result[2];

if(@array_key_exists('productid',$result[1][0]) && @array_key_exists('productname',$result[1][0]))
        $product_array = $result[1];
elseif(@array_key_exists('id',$result[1][0]) && @array_key_exists('question',$result[1][0]) && @array_key_exists('answer',$result[1][0]))
        $faq_array = $result[1];

$_SESSION['product_array'] = $product_array;
$_SESSION['category_array'] = $category_array;
$_SESSION['faq_array'] = $faq_array;

$search_text = $_REQUEST['search_text'];


include("Faq/Utils.php");
include("Faq/index.html");

if($_REQUEST['fun'] == '')
{
	if(!empty($faq_array))
		echo getLatestlyCreatedFaqList();
}
elseif($_REQUEST['fun'] == 'faqs')
{
	if($_REQUEST['category_index'] != '')
	{
		echo ListFaqsPerCategory($_REQUEST['category_index']);
	}
	elseif($_REQUEST['productid'] != '')
	{
		echo ListFaqsPerProduct($_REQUEST['productid']);
	}
	else
	{
		echo 'Wrong parameters';
	}
}
elseif($_REQUEST['fun'] == 'search')
{
	$search_text = $_REQUEST['search_text'];
	$search_category = explode(":",$_REQUEST['search_category']);
	$searchlist .= getSearchResult($search_text,$search_category[1],$search_category[0]);
	echo $searchlist;
}
elseif($_REQUEST['fun'] == 'faq_detail')
{
	include("Faq/FaqDetail.php");
}
elseif($_REQUEST['fun'] == 'faq_updatecomment')
{
	?>
	<script>
		var faqid = <?php echo Zend_Json::encode($_REQUEST['faqid']); ?>;
		window.location.href = "index.php?module=Faq&action=index&fun=faq_detail&faqid="+faqid
	</script>
	<?php
}


//The following tags are opened in index.html
echo '
			</td>
		   </tr>
		</table>
	</td>
   </tr>
</table>

     ';

?>
