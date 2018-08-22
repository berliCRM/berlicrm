<?php

global $client;

$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid"));
$result = $client->call('get_combo_values', $params, $Server_Path, $Server_Path);

$_SESSION['combolist'] = $result;
$combolist = $_SESSION['combolist'];
for($i=0;$i<count($result);$i++)
{
	if($result[$i]['productid'] != '')
	{
		$productslist[0] = $result[$i]['productid'];
	}
	if($result[$i]['productname'] != '')
	{
		$productslist[1] = $result[$i]['productname'];
	}
	if($result[$i]['ticketpriorities'] != '')
	{
		$ticketpriorities = $result[$i]['ticketpriorities'];
	}
	if($result[$i]['ticketseverities'] != '')
	{
		$ticketseverities = $result[$i]['ticketseverities'];
	}
	if($result[$i]['ticketcategories'] != '')
	{
		$ticketcategories = $result[$i]['ticketcategories'];
	}
	if($result[$i]['servicename'] != ''){
		$servicename = $result[$i]['servicename'];
	}
	if($result[$i]['serviceid'] != ''){
		$serviceid= $result[$i]['serviceid'];
	}
}

if($productslist[0] != '#MODULE INACTIVE#'){
	$noofrows = count($productslist[0]);
	
	for($i=0;$i<$noofrows;$i++)
	{
		if($i > 0)
			$productarray .= ',';
		$productarray .= "'".$productslist[1][$i]."'";
	}
}
if($servicename == '#MODULE INACTIVE#' || $serviceid == '#MODULE INACTIVE#'){
	unset($servicename); 
	unset($serviceid);
}

?>

<aside class="right-side">
	<section class="content-header">
		<h1><?PHP echo getTranslatedString('LBL_NEW_TICKET');?></h1>
	</section>
	<section class="content">
		<div class="row">
			<form name="Save" method="post" action="index.php" role="form">
	   			<input type="hidden" name="module" value="HelpDesk">
	   			<input type="hidden" name="action" value="index">
	   			<input type="hidden" name="fun" value="saveticket">
	   			<input type="hidden" name="projectid" value="<?php echo $_REQUEST['projectid'] ?>" />
	        	<div class="col-md-12">
					<div class="box box-primary">
						<div class="box-body">
						
							<div class="form-group">
								<label><font color="red">*</font><?PHP echo getTranslatedString('TICKET_TITLE');?></label>								
								<input type="text" name="title" class = "form-control" placeholder="<?PHP echo getTranslatedString('TICKET_TITLE');?>">
							</div>
							
							<div class="form-group">
								<label><?PHP echo getTranslatedString('LBL_PRODUCT_NAME');?></label>
								<style>@import url( css/dropdown.css );</style>
								<script src="js/modomt.js"></script>
								<script src="js/getobject2.js"></script>
								<script src="js/acdropdown.js"></script>
								<script language="javascript">
									var products = new Array(<?php echo $productarray; ?>);
								</script>
								<input class="form-control" autocomplete="off" name="productid" id="inputer2"  acdropdown="true" autocomplete_list="array:products" autocomplete_list_sort="false" autocomplete_matchsubstring="true" placeholder="<?PHP echo getTranslatedString('LBL_PRODUCT_NAME');?>">
							</div>
							<div class="form-group">
								<label><?PHP echo getTranslatedString('LBL_SERVICE_CONTRACTS');?></label>
								<?php
									$list = '<select name=servicename size="1" class="form-control">';
									$list .= '<OPTION value="">'.getTranslatedString('NONE').'</OPTION>';
									for($i=0;$i<count($servicename);$i++){
									$list .= '<OPTION value="'.$serviceid[$i].'" >'.$servicename[$i].'</OPTION>';
									}
									$list .= '</select>';
									echo $list;
								?>
							</div>
							<div class="form-group">
								<label><?PHP echo getTranslatedString('LBL_TICKET_PRIORITY');?></label>
								<?php echo getComboList('priority',$ticketpriorities); ?>
							</div>
							<div class="form-group">
								<label><?PHP echo getTranslatedString('LBL_TICKET_CATEGORY');?></label>
								<?php echo getComboList('category',$ticketcategories);?>
							</div>
							<div class="form-group">
								<label><?PHP echo getTranslatedString('LBL_TICKET_SEVERITY');?></label>
								<?php echo getComboList('severity',$ticketseverities); ?>
							</div>
							<div class="form-group">
								<label><?PHP echo getTranslatedString('LBL_DESCRIPTION');?></label>
								<textarea name="description" cols="55" rows="5" class="form-control"></textarea>
							</div>
							<div class="box-footer">
	                            <button title="<?PHP echo getTranslatedString('LBL_SAVE_ALT');?>" accessKey="S" class="btn btn-primary" value="<?PHP echo getTranslatedString('LBL_SAVE');?>" onclick="return formvalidate(this.form)" type="submit" name="button">
	                            	<?PHP echo getTranslatedString('LBL_SAVE');?>
	                            </button>
	                            <button title="<?PHP echo getTranslatedString('LBL_CANCEL_ALT');?>" accessKey="X" class="btn btn-primary" onclick="window.history.back()" type="button" name="button"  value="<?PHP echo getTranslatedString('LBL_CANCEL');?>">
	                            	<?PHP echo getTranslatedString('LBL_CANCEL');?>
	                           	</button>
							</div>
						</div>
	   				</div>
	   			</div>
	   		</div>
		</form>
		</div>
	</section>
</aside>
<script>
function formvalidate(form)
{
	if(trim(form.title.value) == '')
	{
		alert("Ticket Title is empty");
		return false;
	}
	return true;
}
function trim(s) 
{
	while (s.substring(0,1) == " ")
	{
		s = s.substring(1, s.length);
	}
	while (s.substring(s.length-1, s.length) == ' ')
	{
		s = s.substring(0,s.length-1);
	}

	return s;
}
</script>
<?php

?>
