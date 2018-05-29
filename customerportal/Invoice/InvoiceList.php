<?php
	
	global $result;
	global $client;
	
	$onlymine=$_REQUEST['onlymine'];
	if($onlymine == 'true') {
	    $mine_selected = 'selected';
	    $all_selected = '';
	} else {
	    $mine_selected = '';
	    $all_selected = 'selected';
	}
	$params = array();
	echo '<aside class="right-side">';
	echo '<section class="content-header" style="box-shadow:none;"><div class="row-pad">';
	echo '<div class="col-sm-11"><b style="font-size:20px;">'.getTranslatedString("LBL_INVOICE_INFORMATION").'</b></div>';    
	$allow_all = $client->call('show_all',array('module'=>'Invoice'),$Server_Path, $Server_Path);
	if($allow_all == 'true'){
	    	
	    echo '<div class="col-sm-1 search-form"><div class="btn-group">
	    	<button type="button" class="btn btn-default dropdown-toggle invoice_owner_btn" data-toggle="dropdown">
	    		'.getTranslatedString('SHOW').'<span class="caret"></span> 
	    	</button>
	    	<ul class="dropdown-menu invoice_owner">
	 		<li><a href="#">'.getTranslatedString('MINE').'</a></li>
			<li><a herf="#">'.getTranslatedString('ALL').'</a></li>
			</ul></div></div></div><section>';
	}
	      	
	echo '<section class="content"><div class="row">';
	echo '<div class="col-xs-12">';
	echo '<div class="box-body table-responsive no-padding"><table class="table table-hover">';
	    					
	if ($customerid != '' )
	{
		$block = "Invoice";
		$params = array('id' => "$customerid", 'block'=>"$block",'sessionid'=>$sessionid,'onlymine'=>$onlymine);
		$result = $client->call('get_list_values', $params, $Server_Path, $Server_Path);
		echo getblock_fieldlistview($result,$block);
	}
	

?>

