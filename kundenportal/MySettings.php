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

session_start();
require_once 'csrf-protect.php';
$errormsg = '';
require_once("PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '') {
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
require_once("include/utils/utils.php");
$default_language = getPortalCurrentLanguage();
require_once("language/".$default_language.".lang.php");
global $default_charset;
header('Content-Type: text/html; charset='.$default_charset);
if($_REQUEST['fun'] != '' && $_REQUEST['fun'] == 'savepassword' && requestValidateWriteAccess())
{
	include("include.php");
	require_once("HelpDesk/Utils.php");
	include("version.php");
	global $version;
	$errormsg = SavePassword($version);
}

if($_REQUEST['last_login'] != '')
{
	$last_login = portal_purify(stripslashes($_REQUEST['last_login']));
	$_SESSION['last_login'] = $last_login;
}
elseif($_SESSION['last_login'] != '')
{
	$last_login = $_SESSION['last_login'];
}

if($_REQUEST['support_start_date'] != '')
	$_SESSION['support_start_date'] = $support_start_date = portal_purify(stripslashes(
		$_REQUEST['support_start_date']));
elseif($_SESSION['support_start_date'] != '')
	$support_start_date = $_SESSION['support_start_date'];

if($_REQUEST['support_end_date'] != '')
	$_SESSION['support_end_date'] = $support_end_date = portal_purify(stripslashes(
		$_REQUEST['support_end_date']));
elseif($_SESSION['support_end_date'] != '')
	$support_end_date = $_SESSION['support_end_date'];

?>

<?php
	include("header.html");
?>		

<form name="savepassword" action="MySettings.php" method="post">

	<input type="hidden" name="fun" value="savepassword">
	
	<aside class="right-side">
		<section class="content-header">
			<ul class="breadcrumb">
							<li>
								<i class="icon-home"></i>
                                                                        <a href=index.php?fun=home><b>
                                                                    <?php echo getTranslatedString('LBL_PORTAL_HOME');  ?>
                                                                </b></a>
								<span class="divider">&raquo;</span>
							 </li>
			</ul>
			<h1 id="main-heading">
							<?php
								echo getTranslatedString('LBL_MY_SETTINGS');
							?>
			</h1>
		</section>
		<section class="content">
			
			<div id="main-content" >
				<div class="row" style="text-align:center">
				
					<div class="col-md-2"> &nbsp;
					</div>
					
					<div class="col-md-6">
						<div class="box box-primary">
						
							<div class="box-body">
						
								<div class="form-group">
									<h1><?php echo getTranslatedString('LBL_MY_DETAILS');?></h1>							
								</div>
								<div class="form-group">
									<label><?php echo getTranslatedString('LBL_LAST_LOGIN'); ?></label>								
									<input type="text" name="title" class="form-control" readonly value = "<?php echo $last_login; ?>"/>
								</div>
								
								<div class="form-group">
									<label><?php echo getTranslatedString('LBL_SUPPORT_START_DATE'); ?></label>
									<input type="text" name="title" class="form-control" readonly value = "<?php echo $support_start_date;?>"></input>
								</div>
								<div class="form-group">
									<label><?php echo getTranslatedString('LBL_SUPPORT_END_DATE'); ?></label>
									<input type="text" name="title" class="form-control" readonly value = "<?php echo $support_end_date; ?>"></input>
								</div>
								
								<div class="form-group">
									<?php echo $errormsg; ?>
								</div>
								
								<div class="form-group">
									<h1><?php echo getTranslatedString('LBL_CHANGE_PASSWORD'); ?></h1>
								</div>
						   
						   		<div class="form-group">
									<label><?php echo getTranslatedString('LBL_OLD_PASSWORD'); ?></label>
									<input type="password" name="old_password" class="form-control" 
										value="" autocomplete="off">
								</div>
							
								<div class="form-group">
									<label><?php echo getTranslatedString('LBL_NEW_PASSWORD'); ?></label>
									<input type="password" name="new_password" class="form-control" 
										value="" autocomplete="off">
								</div>
						   	
						   		<div class="form-group">
									<label><?php echo getTranslatedString('LBL_CONFIRM_PASSWORD'); ?></label>
									<input type="password" name="confirm_password" class="form-control"  value="" autocomplete="off">
								</div>
							
							
								<div class="box-footer">
		                            <button title="Save[Alt+S]" accesskey="S" class="btn btn-primary" value="Save" onclick="return verify_data(this.form)" type="submit" name="button">
		                            	Submit
		                            </button>
		                            
								</div>
						
							</div>
						
						</div>
					</div>
					
					<div class="col-md-2"> &nbsp;
					</div>
					
					
					
				</div>
			</div>
		</section>
	</aside>
			
</form>
	


	<script>
		function verify_data(form)
		{
		        oldpw = trim(form.old_password.value);
		        newpw = trim(form.new_password.value);
		        confirmpw = trim(form.confirm_password.value);
		        if(oldpw == '')
		        {
				alert("Enter Old Password");
		                return false;
		        }
		        else if(newpw == '')
		        {
				alert("Enter New Password");
		                return false;
		        }
		        else if(confirmpw == '')
		        {
				alert("Confirm the New Password");
		                return false;
		        }
		        else
		        {
		                return true;
		        }
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
	include("footer.html");
?>


