<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
chdir (dirname(__FILE__) . '/..');
include_once 'vtigerversion.php';
include_once 'data/CRMEntity.php';

@session_start();

if(isset($_REQUEST['username']) && isset($_REQUEST['password'])){
	global $root_directory, $log;
	$userName = $_REQUEST['username'];
	$password = $_REQUEST['password'];

	$user = CRMEntity::getInstance('Users');
	$user->column_fields['user_name'] = $userName;
	if ($user->doLogin($password)) {
		$zip = new ZipArchive();
		$fileName = 'vtiger6.zip';
		if ($zip->open($fileName)) {
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$log->fatal('Filename: ' . $zip->getNameIndex($i) . '<br />');
			}
			if ($zip->extractTo($root_directory)) {
				$zip->close();
				
				$userid = $user->retrieve_user_id($userName);
				$_SESSION['authenticated_user_id'] = $userid;

				header('Location: ../index.php?module=Migration&view=Index&mode=step1');
			} else {
				$errorMessage = '<p>ERROR EXTRACTING MIGRATION ZIP FILE!</p>';
				header('Location: index.php?error='.$errorMessage);
			}
		} else {
			$errorMessage = 'ERROR READING MIGRATION ZIP FILE!';
			header('Location: index.php?error='.$errorMessage);
		}
	} else {
		$errorMessage = 'INVALID CREDENTIALS';
		header('Location: index.php?error='.$errorMessage);
	}
}
?>
<html>
    <head>
		<title>Vtiger CRM Setup</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<script type="text/javascript" src="resources/js/jquery-min.js"></script>
		<link href="resources/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="resources/css/mkCheckbox.css" rel="stylesheet">
		<link href="resources/css/style.css" rel="stylesheet">
    </head>
    <body>
		<div class="container-fluid page-container">
			<div class="row-fluid">
				<div class="span6">
					<div class="logo">
						<img src="resources/images/vt1.png" alt="Vtiger Logo"/>
					</div>
				</div>
				<div class="span6">
					<div class="head pull-right">
						<h3>Migration Wizard</h3>
					</div>
				</div>
			</div>
			<div class="row-fluid main-container">
				<div class="span12 inner-container">
					<div class="row-fluid">
						<div class="span10">
							<h4 class=""> Welcome </h4>
						</div>
						<div class="span2">
							<a href="https://wiki.vtiger.com/vtiger6/" target="_blank" class="pull-right">
								<img src="resources/images/help40.png" alt="Help-Icon"/>
							</a>
						</div>
					</div>
					<hr>
					<div class="row-fluid">
						<div class="span4 welcome-image">
							<img src="resources/images/migration_screen.png" alt="Vtiger Logo"/>
						</div>
						<div class="span8">
							<?php $currentVersion = explode('.', $vtiger_current_version);
							if($currentVersion[0] >= 6 && $currentVersion[1] >= 0){?>
							<div>
								<h3> Welcome to Vtiger Migration </h3>
								<?php
								if(isset($_REQUEST['error'])) {
									echo '<span><font color="red"><b>'.filter_var($_REQUEST['error'], FILTER_SANITIZE_STRING).'</b></font></span><br><br>';
								}?>
								<p>We have detected that you have <strong>Vtiger <?php echo $vtiger_current_version?> </strong>installation. <br> <br> </p>
								<p>
									<strong> Warning: </strong>Please note that it is not possible to revert back to <?php echo $vtiger_current_version?> after the upgrade to vtiger 6 <br>
									So, it is important to take a backup of the <?php echo $vtiger_current_version?> installation, including the source files
									and database.</p><br>
								<form action="index.php" method="POST">
									<div><input type="checkbox" id="checkBox1" name="checkBox1"/> <div class="chkbox"></div> I have taken the backup of database <a href="http://community.vtiger.com/help/vtigercrm/administrators/backup.html" target="_blank" >(how to?)</a> </div><br>
									<div><input type="checkbox" id="checkBox2" name="checkBox2"/> <div class="chkbox"></div> I have taken the backup of source folder <a href="http://community.vtiger.com/help/vtigercrm/administrators/backup.html" target="_blank" >(how to?)</a></div><br>
									<br><div>
										<span id="error"></span>
										User Name <span class="no">&nbsp;</span>
										<input type="text" value="" name="username" id="username" />&nbsp;&nbsp;
										Password <span class="no">&nbsp;</span>
										<input type="password" value="" name="password" id="password" />&nbsp;&nbsp;
									</div>
									<br><br><br>
									<div class="button-container">
										<input type="submit" class="btn btn-large btn-primary" id="startMigration" name="startMigration" value="Start Migration" />
									</div>
								</form>
							</div>
							<?php } else if($currentVersion[0] < 6){?>
							<div><br><br><br><br><br>
								<h3><font color='red'>WARNING : Cannot continue with Migration </font></h3>
								<p>
									We detected that this installation is running <strong>Vtiger CRM </strong><?php if($vtiger_current_version < 6 ) { echo '<b>'.$vtiger_current_version.'</b>'; } ?>.
									Please upgrade to <strong>5.4.0</strong> first before continuing with this wizard.
								</p>
								<br><br><br><br>
								<div class="button-container">
										<input type="button" onclick="window.location.href='index.php'" class="btn btn-large btn-primary" value="Finish"/>
								</div>
							</div>
							<?php } else {?><br><br><br><br>
								<h3><font color='red'>WARNING : Cannot continue with Migration </font></h3>
								<p>
									<strong>We detected that this source is upgraded latest version.</strong>
								</p>
								<br><br><br><br>
								<div class="button-container">
										<input type="button" onclick="window.location.href='index.php'" class="btn btn-large btn-primary" value="Finish"/>
								</div>
							<?php }?>
						</div>
					</div>
				</div>
			</div>
			<script>
				$(document).ready(function(){
					$('input[name="startMigration"]').click(function(){
						if($("#checkBox1").is(':checked') == false || $("#checkBox2").is(':checked') == false){
							alert('Before starting migration, please take your database and source backup');
							return false;
						}
						if($('#username').val() == '' || $('#password').val() == ''){
							alert('Please enter Admin credentials to start Migration');
							return false;
						}
						return true;
					});
				});
			</script>
    </body>
</html>

