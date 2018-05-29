<?php

	global $result;
	global $client;
	
	echo '<aside class="right-side">';
	echo '<section class="content-header" style="box-shadow:none;"><div class="row-pad">';
	echo '<div class="col-sm-10"><b style="font-size:20px;">'.getTranslatedString("LBL_ASSET_INFORMATION").'</b></div>';
	echo '</div></section>';
	
	echo '<section class="content" style="overflow: visible;"><div class="row">';
	echo '<div class="col-xs-12">';
	echo '<div class="box-body table-responsive no-padding"><table class="table table-hover">';
	
	$block = 'Assets';	      
	$customerid = $_SESSION['customer_id'];
	$username = $_SESSION['customer_name'];
	$sessionid = $_SESSION['customer_sessionid'];
	
	if ($customerid != '' ) {
		$params = array('id' => "$customerid", 'block'=>"$block",'sessionid'=>$sessionid);
		$result = $client->call('get_list_values', $params, $Server_Path, $Server_Path);
		echo getblock_fieldlistview($result,$block);
	}
?>
