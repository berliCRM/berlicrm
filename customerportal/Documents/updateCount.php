<?php
/*
 * Created on Feb 10, 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 $id = $_REQUEST['file_id'];
 global $client;
 $res = $client->call('updateCount',array($id),$Server_Path,$Server_Path);
 
?>
