<?php

require_once("PortalConfig.php");
require_once("include/utils/utils.php");
include("language/$default_language.lang.php");

function GetForgotPasswordUI($mail_send_message='')
{
	$list .= '<html class="bg-gray"><head>';
	$list .= '<link rel="stylesheet" type="text/css" href="css/style.css">';
	$list .= '<meta name="viewport" content="width=device-width,initial-scale=1" />';
	$list .= '<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/font-awesome.min.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/ionicons.min.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/morris/morris.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/jvectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/datepicker/datepicker3.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/daterangepicker/daterangepicker-bs3.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css" rel="stylesheet" type="text/css" />' ;
    $list .= '<link href="css/AdminLTE.css" rel="stylesheet" type="text/css" /></head>' ;

    $list .= '<body class="bg-gray">';
    $list .= '<div class="form-box" id="login-box">';
	$list .= '<div class="header" style="font-weight: 400;">'.getTranslatedString('LBL_FORGOT_LOGIN').'</div>';
    $list .= '<form name="forgot_password" action="index.php" method="post">';
    $list .= '<input type="hidden" name="email_id">';
    $list .= '<input type="hidden" name="param" value="forgot_password">';
		$list .= '<div class="body bg-gray">';
		$list .= '<div class="form-group">';
		if($mail_send_message != ''){
			$list .= "<span style = 'color:red;'>".$mail_send_message . "</span>";
		}
		$list .= '<input class="form-control" type="text" PLACEHOLDER="' .getTranslatedString('LBL_YOUR_EMAIL').'" name="email_id" VALUE=""/></div>';
        $list .= '</div>';
		$list .= '<div class="footer"><button class="btn bg-light-blue btn-block" type="submit" value="" style="font-size:16px;font=weight:500;">'.getTranslatedString('LBL_SEND_PASSWORD').'</button><br/><a href = "login.php">Back To Login</a>';
        $list .= '</div></form></div></body></html>';

	return $list;
}
if($_REQUEST['mail_send_message'] != '')
{
	$mail_send_message = explode("@@@",$_REQUEST['mail_send_message']);

	if($mail_send_message[0] == 'true')
	{
		$list = '<link rel="stylesheet" type="text/css" href="css/style.css">';
		$list .= '<table width="100%" border="0" cellspacing="2" cellpadding="2" align="center">';
		$list .= '<tr><td class="detailedViewHeader" nowrap colspan=2 align="center"><b>';
		$list .= getTranslatedString('LBL_FORGOT_LOGIN').'</b></td></tr>';
		$list .= '<br><tr><td>&nbsp;</td></tr>';
		$list .= '<tr><td class="dvtCellInfo">Mail has been sent to your mail id with the customer portal login details</td></tr>';
		$list .= '<br><tr><td>&nbsp;</td></tr>';
		$list .= '<tr><td align="right"><a href="login.php?close_window=true"> '.getTranslatedString('LBL_CLOSE').'</a>';
		$list .= '</td></tr></table>';

		echo $list; die;
	}
	elseif($mail_send_message[0] == 'false')
	{
		$list = GetForgotPasswordUI("Invalid Email Address");
		echo $list;
	}
}
elseif($_REQUEST['param'] == 'forgot_password')
{
	$list = GetForgotPasswordUI();
        echo $list;
}
elseif($_REQUEST['param'] == 'sign_up')
{
	echo 'Sign Up..........';
}




?>
