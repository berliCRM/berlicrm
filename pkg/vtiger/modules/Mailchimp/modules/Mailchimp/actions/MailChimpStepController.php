<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/
require_once('modules/Mailchimp/providers/MailChimp.php');
require_once('modules/Mailchimp/providers/Webhook.php');

class Mailchimp_MailChimpStepController_Action extends Vtiger_Action_Controller{

	private $db;
	private $recordid;
	private $mcgroupid;
	private $mcgroupname;
	private $crmgrouplabel;
	private $crmgroupnr;
	private $step;
	private $mc_api;

	public function process(Vtiger_Request $request) {
		global $current_user;

		$this->db = PearDatabase::getInstance();
		$this->step = (int) $request->get('step');
		$this->recordid = (int) $request->get('recordid');
		$this->mcgroupid = $request->get('mcgroupid');
		$this->mcgroupname = $request->get('mcgroupname');
		
		$verbose = $request->get('verbose') == "true" ? true:false;

		// get group name and nr.
		$result = $this->db->pquery("select mailchimpname, campaign_no from vtiger_mailchimp where vtiger_mailchimp.mailchimpid = ?", array($this->recordid));
		list($this->crmgrouplabel,$this->crmgroupnr) = $this->db->fetch_row($result);

        $response = new Vtiger_Response();
		
		if ($this->step==1) {
		
			// clear used session variables
			unset($_SESSION["mc"]);
			unset($_SESSION["mcactions"]);

			$_SESSION["mc"]["starttime"]=time();
			
			// get current global attribute fields from Mailchimp, create missing ones
			$apikey= Mailchimp_Module_Model::getApikey();
			$this->mc_api = new MailChimp($apikey);
			
            try {
                $fieldsneeded = self::initiateCustomFields();
            }
            catch (Exception $e) {
                $response->setError("API ERROR: ".$e->getMessage());
                $response->emit();
                return;
            }

			if ($fieldsneeded==0) {
				$response->setResult(array('',vtranslate('LBL_MAILCHIMP_ATTRIB_OK','Mailchimp'),2));
			}
			else {
				$response->setResult(array('',vtranslate('LBL_MAILCHIMP_ATTRIB_CREATED','Mailchimp'),1));
                sleep(3);
			}
			
			// create Mailchimp interests group
			self::initiateMcGroup($this->mcgroupid,$this->mcgroupname);
			
			
		}

		elseif ($this->step==2) {

			// load entities from auxilliary table that have been synced to this group before to identify changes
			$query = "SELECT crmid FROM `vtiger_mailchimp_synced_entities` LEFT JOIN `vtiger_crmentity` USING (crmid) WHERE mcgroupid = ? AND recordid = ? AND deleted = 0";
			$result = $this->db->pquery($query,array($this->mcgroupid,$this->recordid));
			while($row = $this->db->fetchByAssoc($result,-1,false)) {
				$crmidsyncedbefore[$row["crmid"]]=true;
			}
			$_SESSION['mc']['crmidsyncedbefore']=$crmidsyncedbefore;
			
			// load contacts from CRM database
			$Contactquery = "SELECT DISTINCT
					vtiger_crmentity.crmid,
					vtiger_contactdetails.salutation,
					lower(vtiger_contactdetails.email) as email,
					vtiger_contactdetails.firstname,
					vtiger_contactdetails.lastname,
					vtiger_contactdetails.emailoptout,
					vtiger_account.accountname
				FROM vtiger_contactdetails
					LEFT JOIN vtiger_contactaddress ON contactaddressid = vtiger_contactdetails.contactid
                    LEFT JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
					LEFT JOIN vtiger_crmentityrel as rel1 on rel1.crmid = vtiger_contactdetails.contactid
					LEFT JOIN vtiger_crmentityrel as rel2 on rel2.relcrmid = vtiger_contactdetails.contactid
					LEFT OUTER JOIN vtiger_account
						ON vtiger_contactdetails.accountid = vtiger_account.accountid
				WHERE (rel1.relcrmid = ? OR rel2.crmid = ?)
					AND vtiger_crmentity.deleted = 0";

			$crm_data = array();

			$result = $this->db->pquery($Contactquery,array($this->recordid,$this->recordid));
			while($row = $this->db->fetchByAssoc($result,-1,false)) {
				if (empty($row["email"])) {	
					$_SESSION['mc']['brokenContacts'][$row["crmid"]]=$row["firstname"]." ".$row["lastname"];
				}
				else {
					$crm_data[$row["email"]] = array(
                        'crmid'=>$row['crmid'],
                        'salutation'=>$row['salutation'],
                        'email'=>$row['email'],
                        'firstname'=>$row['firstname'], 
                        'lastname'=>$row['lastname'],
                        'accountname'=>$row['accountname'],
                        'emailoptout'=>$row['emailoptout']);
					
					unset($crmidsyncedbefore[$row["crmid"]]);
				}
			}

			// load leads
			$Leadquery = "SELECT DISTINCT
					vtiger_crmentity.crmid,
					vtiger_leaddetails.salutation,
					lower(vtiger_leaddetails.email) as email,
					vtiger_leaddetails.firstname,
					vtiger_leaddetails.lastname,
					vtiger_leaddetails.converted,
					vtiger_leaddetails.emailoptout,
					vtiger_leaddetails.company as accountname
					FROM vtiger_leaddetails
					LEFT JOIN vtiger_leadaddress on vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid
                    LEFT JOIN vtiger_leadscf ON vtiger_leaddetails.leadid = vtiger_leadscf.leadid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
					LEFT JOIN vtiger_crmentityrel as rel1 on rel1.crmid = vtiger_leaddetails.leadid
					LEFT JOIN vtiger_crmentityrel as rel2 on rel2.relcrmid = vtiger_leaddetails.leadid
                    WHERE (rel1.relcrmid = ? OR rel2.crmid = ?)
					AND vtiger_crmentity.deleted = 0"; # AND converted <> 1

			$result = $this->db->pquery($Leadquery,array($this->recordid,$this->recordid));
			while($row = $this->db->fetchByAssoc($result,-1,false)) {
				
				// leads left over in CRM sync group after conversion are removed and skipped
				if ($row["converted"]==1) {
					$this->removeFromSyncGroup($row["crmid"]);
					$this->removeFromAuxtable($row["crmid"]);
					unset($crmidsyncedbefore[$row["crmid"]]);
					continue;
				}
				
				if (empty($row["email"])) {	
					$_SESSION['mc']['brokenLeads'][$row["crmid"]]=$row["firstname"]." ".$row["lastname"];
				}
				else {
					$crm_data[$row["email"]] = array(
                        'crmid'=>$row['crmid'],
                        'salutation'=>$row['salutation'],
                        'email'=>$row['email'],
                        'firstname'=>$row['firstname'], 
                        'lastname'=>$row['lastname'],
                        'accountname'=>$row['accountname'],
                        'emailoptout'=>$row['emailoptout'],
                        'isLead' => true);
					
					unset($crmidsyncedbefore[$row["crmid"]]);
				}
			}

			// entity IDs left in $crmidsyncedbefore must have been removed locally since last sync
			if (count($crmidsyncedbefore)>0) {
				$_SESSION['mc']['removedlocally']=array_keys($crmidsyncedbefore);
			}

			// store data in session for next steps
			$_SESSION['mc']['localcontacts']=$crm_data;

			if (count($crm_data)>0) $msg[] = sprintf(getTranslatedString('LBL_GOT_ALL_MEMBERS_CRM_MAILCHIMP','Mailchimp'),decode_html($this->crmgrouplabel),$this->crmgroupnr);
			
			// load contacts from Mailchimp group (based on $this->mcgroupid) into array with email as associative index

			$_SESSION['mc']['remotecontacts']=self::getMailChimpEntries();

			if (!is_array($_SESSION['mc']['remotecontacts'])) {
				$response->setError(array(getTranslatedString('LBL_API_ERROR','Mailchimp')));
			} 
			else {
				if (count($_SESSION['mc']['remotecontacts']) > 0) {
					$msg[]= sprintf(getTranslatedString('LBL_GOT_ALL_MEMBERS_MAILCHIMP_API','Mailchimp'),decode_html($this->mcgroupname),$this->mcgroupid);
				}
				$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 2',implode("<br>",$msg),3));
			}
		}

		elseif ($this->step==3) {

			$msg = "";
			
			// data processing
			
			// find locally removed emails to remove from Mailchimp by crmid
			if (is_array($_SESSION["mc"]["removedlocally"])) {

				$q = "SELECT contactid,email FROM vtiger_contactdetails WHERE contactid IN (".implode(', ', $_SESSION["mc"]["removedlocally"]).")";
				$result = $this->db->query($q);

				while($row = $this->db->fetchByAssoc($result,-1,false)) {
					$_SESSION["mcactions"]["delete"][$row["contactid"]]=$row["email"];

					// remove from cached remotecontacts
					unset($_SESSION["mc"]["remotecontacts"][$row["email"]]);
					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_DELETE','Mailchimp'),$row["email"]);
					}

				// same for leads
				$q = "SELECT leadid,email FROM vtiger_leaddetails WHERE leadid IN (".implode(', ', $_SESSION["mc"]["removedlocally"]).")";
				$result = $this->db->query($q);

				while($row = $this->db->fetchByAssoc($result,-1,false)) {
					$_SESSION["mcactions"]["delete"][$row["leadid"]]=$row["email"];

					// remove from cached remotecontacts
					unset($_SESSION["mc"]["remotecontacts"][strtolower ($row["email"])]);
					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_DELETE','Mailchimp'),$row["email"]);
					}
			}
			
			// iterate through CRM contacts, find new entries for export to Mailchimp
			foreach ($_SESSION["mc"]["localcontacts"] as $localcontact) {

				if (!isset($_SESSION["mc"]["remotecontacts"][$localcontact["email"]])) {

					if ($localcontact["emailoptout"]>0) {
						if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_NOEXPORTONOPTOUT','Mailchimp'),$localcontact["email"]);
					}
					else {

						if ($_SESSION['mc']['crmidsyncedbefore'][$localcontact["crmid"]] == true) {

							if ($verbose) {
								$msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_DELETEDREMOTELY','Mailchimp'),$localcontact["email"]);
							}

							$_SESSION["mcactions"]["removelocally"][$localcontact["crmid"]]=$localcontact["email"];
						}
						else {
							if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_EXPORT','Mailchimp'),$localcontact["email"]);
							
							$_SESSION["mcactions"]["export"][]=$localcontact["email"];
						}
					}
				}
			}

			
			// iterate through Mailchimp contacts, find entries to either import, update, add to sync group or ignore
			foreach ($_SESSION["mc"]["remotecontacts"] as $remotecontact) {

				if (!isset($_SESSION["mc"]["localcontacts"][strtolower ($remotecontact['email_address'])])) {

					// if there's a matching local crm contact, add to sync group
					$q = "SELECT
							vtiger_crmentity.crmid,
							lower(vtiger_contactdetails.email) as email
							FROM vtiger_contactdetails
							INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
							WHERE email = ? AND vtiger_crmentity.deleted = 0 LIMIT 1";
					$result = $this->db->pquery($q,array(strtolower ($remotecontact['email_address'])));

					if ($row = $this->db->fetchByAssoc($result)) {
						// MC does not allow do delete cleaned entries by webservices
						if ($remotecontact['status'] != 'cleaned') {
							$_SESSION["mcactions"]["addtocrmgroup"]["Contacts"][$row["crmid"]]=$row["email"];
						
							if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_ADDTOCRMGROUP','Mailchimp'),$row["email"]);
						}
						continue;
					}

					// repeat for leads: if there's a match, add to sync group
					$q = "SELECT
							vtiger_crmentity.crmid,
							lower(vtiger_leaddetails.email) as email
							FROM vtiger_leaddetails
							INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
							WHERE email = ? AND converted <> 1 AND vtiger_crmentity.deleted = 0 LIMIT 1";
					$result = $this->db->pquery($q,array(strtolower($remotecontact['email_address'])));

					if ($row = $this->db->fetchByAssoc($result)) {
						// MC does not allow do delete cleaned entries by webservices
						if ($remotecontact['status'] != 'cleaned') {
							$_SESSION["mcactions"]["addtocrmgroup"]["Leads"][$row["crmid"]]=$row["email"];
							
							if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_ADDTOCRMGROUP','Mailchimp'),$row["email"]);
						}
						continue;
					}

					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_TEST4IMPORT','Mailchimp'),$remotecontact['email_address']);

					// check if all attributes are set, if so import new entry to CRM
					$imp =1;
					if ($remotecontact['status'] != 'subscribed') {
						if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_INACTIVE','Mailchimp');
						$imp=0;
					}
					if (empty($remotecontact['merge_fields']['LNAME']) || empty($remotecontact['merge_fields']['FNAME']) || 
						empty($remotecontact['merge_fields']['SALUTATION'])) {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_INCOMPLETE','Mailchimp');
							$imp=0;
					}

					if ($imp) {
						if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DOIMPORT','Mailchimp');
						$_SESSION["mcactions"]["import"][]=$remotecontact['email_address'];
					}
					else {
						if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DONTIMPORT','Mailchimp');
					}
				}
				else {
					
					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_HAVEENTRY','Mailchimp'),$remotecontact['email_address'],decode_html($this->crmgrouplabel));

					// make sure entry is in auxtable since it might have been imported from different source before
					if (!$_SESSION['mc']['crmidsyncedbefore'][$_SESSION["mc"]["localcontacts"][$remotecontact['email_address']]["crmid"]]) {
						$this->insertIntoAuxtable($_SESSION["mc"]["localcontacts"][$remotecontact['email_address']]["crmid"]);
					}

					// inactive
					if (($remotecontact['status'] == 'unsubscribed') && $_SESSION['mc']['crmidsyncedbefore'][$_SESSION["mc"]["localcontacts"][strtolower ($remotecontact['email_address'])]["crmid"]] ) {
						
						if ($verbose) {
							$convertedDate = date('Y-m-d H:i:s', strtotime($remotecontact['last_changed']));
							$convertedDate = DateTimeField::convertToUserFormat($convertedDate);
							$msg .= sprintf(getTranslatedString('LBL_VERBOSELOG_UNSUBSCRIBED','Mailchimp'),$convertedDate); 
						}
						$_SESSION["mcactions"]["optout"][]=$remotecontact['email_address'];
					}
					elseif ($remotecontact['status'] == 'cleaned') {
						if ($verbose) {
							$verbosedate = date('Y-m-d H:i:s', strtotime($remotecontact['last_changed']));
							$verbosedate = DateTimeField::convertToUserFormat($verbosedate);
							$msg .= sprintf(getTranslatedString('LBL_VERBOSELOG_BOUNCED','Mailchimp'),$verbosedate);
							# do something here? -- do not update local CRM
						}
					}
					else {
						// compare attributes, update entry on Mailchimp if changed
						$upd=0;
						if ($remotecontact['merge_fields']['FNAME'] != $_SESSION["mc"]["localcontacts"][strtolower ($remotecontact['email_address'])]["firstname"] ||
							$remotecontact['merge_fields']['LNAME'] != $_SESSION["mc"]["localcontacts"][strtolower ($remotecontact['email_address'])]["lastname"] ||
							$remotecontact['merge_fields']['SALUTATION'] != $_SESSION["mc"]["localcontacts"][strtolower ($remotecontact['email_address'])]["salutation"] ||
							$remotecontact['merge_fields']['COMPANY'] != $_SESSION["mc"]["localcontacts"][strtolower ($remotecontact['email_address'])]["accountname"]) {
						
							if ($verbose) {
								$msg .= getTranslatedString('LBL_VERBOSELOG_ATTRIBCHANGED','Mailchimp');
							}
							$upd=1;
						}
						
						if ($remotecontact['status'] == 'subscribed' && $_SESSION["mc"]["localcontacts"][$remotecontact['email_address']]["emailoptout"] > 0 ) {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_OPTOUT','Mailchimp');
							$upd=1;
						}

						if ($upd) {
							if ($verbose) {
								$msg .= getTranslatedString('LBL_VERBOSELOG_DOUPDATE','Mailchimp');
							}
							//use the key
							$mc_key = '';
							foreach ($_SESSION["mc"]["remotecontacts"] as $key => $contactinfo) {
								if(strtolower($remotecontact['email_address'])== strtolower($contactinfo['email_address'])) {
									$mc_key = $key ;
									break; 
								}
							}
							$_SESSION["mcactions"]["update"][]=$mc_key;
						}
						else {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DONTUPDATE','Mailchimp');
						}
					}
				}
			}
			// end of iteration through Mailchimp contacts
			
			if ($verbose) $msg .= "<hr>";
			
			$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 3',$msg,4));
		}
		
		/***************************************************************************************
		*	Step 4: Perform actions collected in session arrays
		*   MUST expect to be called multiple times, so unset array entries if they're done
		*/
		
		elseif ($this->step==4) {
			$apikey= Mailchimp_Module_Model::getApikey();
			$this->mc_api = new MailChimp($apikey);
		
			// existing entities to add to crm group, fast
			if (is_array($_SESSION["mcactions"]["addtocrmgroup"])) {
				
				foreach ($_SESSION["mcactions"]["addtocrmgroup"] as $entitytype => $entities) {
					
					if ($entitytype == "Leads") {
						$_SESSION['mc']['summary'] .= "<h4>".getTranslatedString('LBL_EXISTING_LEADS_ADDED','Mailchimp')."</h4><p style='color:#880'>".implode(", ",$entities)."</p>";
					}
					else {
						$_SESSION['mc']['summary'] .= "<h4>".getTranslatedString('LBL_EXISTING_CONTACTS_ADDED','Mailchimp')."</h4><p style='color:#880'>".implode(", ",$entities)."</p>";
					}
					
					foreach ($entities as $crmid => $email) {
				
						$q = "INSERT INTO `vtiger_crmentityrel` (`crmid` ,`module` ,`relcrmid` ,`relmodule`) VALUES (?, ?, ?, 'Mailchimp')";
						$this->db->pquery($q,array($crmid,$entitytype,$this->recordid));

						$this->insertIntoAuxtable($crmid);
					}
				}
				unset ($_SESSION["mcactions"]["addtocrmgroup"]); //action performed, unset array
			}

		
			// transfer new receivers to Mailchimp, slowish
			if (is_array($_SESSION["mcactions"]["export"])) {

				// number of entries to begin with
				if (empty($_SESSION["mc"]["progressstartcount"])) {
					$_SESSION["mc"]["progressstartcount"]=count($_SESSION["mcactions"]["export"]);
				}	
				
				$batchsize=10;
				
				$newreceivers=array();
				// we need to set the default for the related group (interests) by using the proper id
				// to do: find a better way to get the interestgroupid
				// get all interest-categories of this list
				$interest_cat = $this->mc_api->get('lists/'.$this->mcgroupid.'/interest-categories/');
				$categories_arr = $interest_cat['categories'];
				$interestgroupid = '';
				// get all interest of the interest-categories related to this list and group
				if (is_array($categories_arr) ) {
					foreach ($categories_arr as $key => $category) {
						if ($category['title'] == $this->crmgrouplabel) {
							$interests_arr =$this->mc_api->get('lists/'.$this->mcgroupid.'/interest-categories/'.$category['id'].'/interests');
							if ($this->mcgroupid == $interests_arr['interests'][0]['list_id'] and $category['id'] == $interests_arr['interests'][0]['category_id'] ) {
								$interestgroupid = $interests_arr['interests'][0]['id'];
							}
						}
					}
				}

				foreach ($_SESSION["mcactions"]["export"] as $key => $email) {
				
				$newreceivers[] = array(
						'email_address' => $email ,
						'status'        => 'subscribed',
						'merge_fields'  => Array (
									'SALUTATION' => $_SESSION["mc"]["localcontacts"][$email]["salutation"],
									'FNAME' => $_SESSION["mc"]["localcontacts"][$email]["firstname"] ,
									'LNAME' => $_SESSION["mc"]["localcontacts"][$email]["lastname"],
									'COMPANY' => $_SESSION["mc"]["localcontacts"][$email]["accountname"],
									"LETTERSALU"=>$_SESSION["mc"]["localcontacts"][$email]["salutation"]
								) ,
						'interests' 	=> Array (
									$interestgroupid => true
								),
					); 
				
					$this->insertIntoAuxtable($_SESSION["mc"]["localcontacts"][$email]["crmid"]);
					
					$_SESSION["mc"]["exported"][]=$email;
					unset($_SESSION["mcactions"]["export"][$key]);
						
					// post batches of 10s, and remainder
					if (count($newreceivers) >= $batchsize || (count($_SESSION["mcactions"]["export"])==0 && count($newreceivers)>0) ) {
						
						foreach ($newreceivers as $mcreceiver) {
							$this->mc_api->post("lists/".$this->mcgroupid."/members",$mcreceiver);
							if ($this->mc_api->success()) {					
								// do something in GUI
							}
							else {
								//error handling
							}
						
						}
					//	$rest->post("/groups/{$this->mcgroupid}/receivers",$newreceivers);
						
						$entriesleft = $_SESSION["mc"]["progressstartcount"]-count($_SESSION["mcactions"]["export"]);
						$msg = sprintf(getTranslatedString('LBL_EXPORTPROGRESS','Mailchimp'),$entriesleft,$_SESSION["mc"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 4',$msg,4,"clvpr1"));
						$response->emit();
						return;
					}
					
				}
				
				$_SESSION['mc']['summary'] .= "<h4>".vtranslate('LBL_NEW_LOCAL_ENTRIES_TO_EXPORT','Mailchimp')."</h4><p style='color:#080'>".implode(", ",$_SESSION["mc"]["exported"])."</p>";

				unset ($_SESSION["mcactions"]["export"]); //action performed, unset array
				unset ($_SESSION["mc"]["progressstartcount"]);
			}


			// update (locally) changed entries on Mailchimp
			// potentially very slow, so show some progress indication
			
			if (is_array($_SESSION["mcactions"]["update"])) {

				// number of entries to begin with
				if (empty($_SESSION["mc"]["progressstartcount"])) {
					$_SESSION["mc"]["progressstartcount"]=count($_SESSION["mcactions"]["update"]);
				}	
				
				$batchsize=15;
			
				foreach ($_SESSION["mcactions"]["update"] as $key => $email) {
					// MC does not accept Null
					$SALUTATION ='';
					if (isset ($_SESSION["mc"]["localcontacts"][$email]["salutation"]) AND $_SESSION["mc"]["localcontacts"][$email]["salutation"] != Null) {
						$SALUTATION = $_SESSION["mc"]["localcontacts"][$email]["salutation"];
					}
					$FNAME ='';
					if (isset ($_SESSION["mc"]["localcontacts"][$email]["firstname"]) AND $_SESSION["mc"]["localcontacts"][$email]["firstname"] != Null) {
						$FNAME = $_SESSION["mc"]["localcontacts"][$email]["firstname"];
					}
					$LNAME ='';
					if (isset ($_SESSION["mc"]["localcontacts"][$email]["lastname"]) AND $_SESSION["mc"]["localcontacts"][$email]["lastname"] != Null) {
						$LNAME = $_SESSION["mc"]["localcontacts"][$email]["lastname"];
					}
					$COMPANY ='';
					if (isset ($_SESSION["mc"]["localcontacts"][$email]["accountname"]) AND $_SESSION["mc"]["localcontacts"][$email]["accountname"] != Null) {
						$COMPANY = $_SESSION["mc"]["localcontacts"][$email]["accountname"];
					}
					$LETTERSALU ='';
					if (isset ($_SESSION["mc"]["localcontacts"][$email]["salutation"]) AND $_SESSION["mc"]["localcontacts"][$email]["salutation"] != Null) {
						$LETTERSALU = $_SESSION["mc"]["localcontacts"][$email]["salutation"];
					}
					$updreceiver = array(
						'merge_fields'  => Array (
							'SALUTATION' => $SALUTATION,
							'FNAME' => $_SESSION["mc"]["localcontacts"][$email]["firstname"] ,
							'LNAME' => $_SESSION["mc"]["localcontacts"][$email]["lastname"],
							'COMPANY' => $_SESSION["mc"]["localcontacts"][$email]["accountname"],
							'LETTERSALU' => $_SESSION["mc"]["localcontacts"][$email]["salutation"]
						)
					); 

					$subscriber_hash = $this->mc_api->subscriberHash($email);
					$this->mc_api->patch("lists/".$this->mcgroupid."/members/".$subscriber_hash, $updreceiver);
					
					// explicitly calling "setinactive" since updating "active" attribute seems buggy/unsupported ("active"=>$_SESSION["mc"]["localcontacts"][$email]["emailoptout"]==1?"false":"true",)
					if ($_SESSION["mc"]["localcontacts"][$email]["emailoptout"]>0 && $_SESSION["mc"]["remotecontacts"][$email]['status'] == 'subscribed') {
						// $rest->put("/groups/{$this->mcgroupid}/receivers/".urlencode($email)."/setinactive");
					}
				
					$batchsize--;
					
					$_SESSION["mc"]["updated"][]=$email;
					unset($_SESSION["mcactions"]["update"][$key]);
					
					if ($batchsize < 1 || ( count($_SESSION["mcactions"]["update"]) == 0 && count($_SESSION["mc"]["updated"]) > $batchsize )) {
                    
						$entriesleft = $_SESSION["mc"]["progressstartcount"]-count($_SESSION["mcactions"]["update"]);
						$msg = sprintf(getTranslatedString('LBL_UPDATEPROGRESS','Mailchimp'),$entriesleft,$_SESSION["mc"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 4',$msg,4,"clvpr2"));
						$response->emit();
						return;
					}
				}
				
				$_SESSION['mc']['summary'] .= "<h4>".vtranslate('LBL_UPDATED_ENTRIES','Mailchimp')."</h4><p style='color:#00f'>".implode(", ",$_SESSION["mc"]["updated"])."</p>";;

				unset ($_SESSION["mcactions"]["update"]); //action performed, unset array
				unset ($_SESSION["mc"]["progressstartcount"]);
			}

			// import new Mailchimp entries to CRM as Lead or Contact according to setting
			
			if (is_array($_SESSION["mcactions"]["import"])) {

				$subscribertype = Mailchimp_Module_Model::getSubscriberType();

				// number of entries to begin with
				if (empty($_SESSION["mc"]["progressstartcount"])) {
					$_SESSION["mc"]["progressstartcount"]=count($_SESSION["mcactions"]["import"]);
				}	
				
				// update progress after this many entries
				$batchsize=25;
				
				foreach ($_SESSION["mcactions"]["import"] as $key => $email) {
					if ($subscribertype == "contact") {

						$company = trim($_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['COMPANY']);

						// search for $company in vtiger_account
						$q = "SELECT accountname,accountid from vtiger_account 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_account.accountid
							WHERE vtiger_crmentity.deleted=0 AND vtiger_account.accountname=?";
						
						$result = $this->db->pquery($q,array($company));
						$vtacc = $this->db->fetchByAssoc($result);

						if (is_array($vtacc)) {
							$accountid = $vtacc["accountid"];
						}
						else {
							// create if not found
							require_once('modules/Accounts/Accounts.php');
							$account = new Accounts();
							$account->column_fields[accountname] = $company;
							$account->column_fields[assigned_user_id]=$current_user->id;
							$account->save("Accounts");
							$accountid = $account->id;
						}

						$contact = new Contacts();
						$contact->column_fields['email']=$email;
						$contact->column_fields['salutationtype']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['SALUTATION'];
						$contact->column_fields['firstname']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['FNAME'];
						$contact->column_fields['lastname']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['LNAME'];
						$contact->column_fields['account_id']=$accountid;
						$contact->save("Contacts");
						$id = $contact->id;

						// put new contact in current CRM group...
						$this->addToSyncGroup($id,'Contacts');

						// ... and insert into auxilliary table
						$this->insertIntoAuxtable($id);

					}
					else {
						require_once('modules/Leads/Leads.php');
						$lead = new Leads();
						$lead->column_fields['email']=$email;
						$lead->column_fields['salutationtype']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['SALUTATION'];
						$lead->column_fields['firstname']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['FNAME'];
						$lead->column_fields['lastname']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['LNAME'];
						$lead->column_fields['company']=$_SESSION["mc"]["remotecontacts"][$email]['merge_fields']['COMPANY'];
						$lead->save("Leads");
						$id = $lead->id;

						// put new lead in current vtiger group...
						$this->addToSyncGroup($id,'Leads');
						
						// ... and insert into auxilliary table
						$this->insertIntoAuxtable($id);
					}
					
					$batchsize--;
					
					$_SESSION["mc"]["imported"][]=$email;
					unset($_SESSION["mcactions"]["import"][$key]);
					
					if ($batchsize < 1 || ( count($_SESSION["mcactions"]["import"]) == 0 && count($_SESSION["mc"]["imported"]) > $batchsize )) {
                    
						$entriesleft = $_SESSION["mc"]["progressstartcount"]-count($_SESSION["mcactions"]["import"]);
						$msg = sprintf(getTranslatedString('LBL_IMPORTPROGRESS','Mailchimp'),$entriesleft,$_SESSION["mc"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 4',$msg,4,"clvpr4"));
						$response->emit();
						return;
					}
				}
				unset ($_SESSION["mcactions"]["import"]);
				unset ($_SESSION["mc"]["progressstartcount"]);
				
				if ($subscribertype == "contact") {
					$_SESSION['mc']['summary'] .= "<h4>".vtranslate('LBL_NEW_CONTACTS_IMPORTED','Mailchimp')."</h4><p style='color:#080'>".implode(", ",$_SESSION["mc"]["imported"])."</p>";
				}
				else {
					$_SESSION['mc']['summary'] .= "<h4>".vtranslate('LBL_NEW_LEADS_IMPORTED','Mailchimp')."</h4><p style='color:#080'>".implode(", ",$_SESSION["mc"]["imported"])."</p>";
				}
			}
			
            // optionally: set local emailoptout if receiver unsubscribed
            // if (is_array($_SESSION["mcactions"]["optout"])) {
                // foreach ($_SESSION["mcactions"]["optout"] as $key => $email) {
                    // if ($_SESSION["mc"]["localcontacts"][$email]["isLead"]) {
                        // $q = "UPDATE vtiger_leaddetails SET emailoptout = 1 WHERE leadid=?";
                    // }
                    // else {
                        // $q = "UPDATE vtiger_contactdetails SET emailoptout = 1 WHERE contactid=?";
                    // }
                    // $this->db->pquery($q,array($_SESSION["mc"]["localcontacts"][$email]['crmid']));
                // }
            // }
            // unset ($_SESSION["mcactions"]["optout"]);

			// remove receivers from sync group
			if (is_array($_SESSION["mcactions"]["removelocally"])) {
				
				$_SESSION['mc']['summary'] .= "<h4>".vtranslate('LBL_REMOVE_ENTITYS_FROM_CRM','Mailchimp')."</h4><p>".implode(", ",$_SESSION["mcactions"]["removelocally"])."</p>";
				
				foreach ($_SESSION["mcactions"]["removelocally"] as $crmid => $email) {
					
					// remove from sync list
					$this->removeFromSyncGroup($crmid);

					// delete from aux table
					$this->removeFromAuxtable($crmid);
				}
				unset ($_SESSION["mcactions"]["removelocally"]);
			}
			
			// delete receivers from Mailchimp, very slow!
			if (is_array($_SESSION["mcactions"]["delete"])) {
				
				// number of entries to begin with
				if (empty($_SESSION["mc"]["progressstartcount"])) {
					$_SESSION["mc"]["progressstartcount"]=count($_SESSION["mcactions"]["delete"]);
				}	

				$batchsize=10;
				
				foreach ($_SESSION["mcactions"]["delete"] as $crmid => $email) {
					try {
						$subscriber_hash = $this->mc_api->subscriberHash($email);
						$this->mc_api->delete("lists/".$this->mcgroupid."/members/".$subscriber_hash);
					} 
					catch (Exception $e) {
						# better ignore errors
					}

					// remove entry from aux table
					$this->removeFromAuxtable($crmid);
					
					$batchsize--;
					
					$_SESSION["mc"]["deleted"][]=$email;
					unset($_SESSION["mcactions"]["delete"][$crmid]);
					
					if ($batchsize < 1 || ( count($_SESSION["mcactions"]["delete"]) == 0 && count($_SESSION["mc"]["deleted"]) > $batchsize )) {
                    
						$entriesleft = $_SESSION["mc"]["progressstartcount"]-count($_SESSION["mcactions"]["delete"]);
						$msg = sprintf(getTranslatedString('LBL_DELETEPROGRESS','Mailchimp'),$entriesleft,$_SESSION["mc"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 4',$msg,4,"clvpr4"));
						$response->emit();
						return;
					}
				}
				
				unset ($_SESSION["mcactions"]["delete"]);
				$_SESSION['mc']['summary'] .= "<h4>".vtranslate('LBL_REMOVE_FROM_MAILCHIMP','Mailchimp')."</h4><p style='color:#800'>".implode(", ",$_SESSION["mc"]["deleted"])."</p>";
				unset($_SESSION["mc"]["deleted"]);
			}

			$msg = "<hr>".$_SESSION['mc']['summary'];
			
			if (is_array($_SESSION["mc"]["brokenContacts"])) $msg .= vtranslate('LBL_BROKEN_CONTACTS','Mailchimp') . implode(", ",$_SESSION["mc"]["brokenContacts"]);
			if (is_array($_SESSION["mc"]["brokenLeads"])) $msg .= vtranslate('LBL_BROKEN_LEADS','Mailchimp') . implode(", ",$_SESSION["mc"]["brokenLeads"]);
			
			if (!is_array($_SESSION["mcactions"])) $msg .= "<br><h4>".vtranslate('LBL_NO_CHANGES_TO_SYNC','Mailchimp')."</h4>";
			
			// update lastsynchronization
			$currentdate = date("Y-m-d H:i:s");
			$q = "UPDATE vtiger_mailchimp SET lastsynchronization  = ? WHERE mailchimpid = ?";
			$this->db->pquery($q,array($currentdate,$this->recordid));

			$exec_time = time()-$_SESSION["mc"]["starttime"];
			
			$msg .= "<br>".sprintf(vtranslate('LBL_FINISHED_AFTER','Mailchimp'),$exec_time);
			
			// clear used session variables
			unset($_SESSION["mc"]);
			unset($_SESSION["mcactions"]);
			
			$response->setResult(array(getTranslatedString('LBL_STEP','Mailchimp').' 4',$msg,0));
		}
		else {
			$response->setError(array('Error: step parameter out of bounds'));
		}

        $response->emit();
    }
	
	private function initiateMcGroup($list_id,$group) {
		//start --- create additional CRM group in Mailchimp if it does not exist
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$groupinfo = self::listInterestGroupings($list_id);
		$group_exists = false;
		$mc_groupname = self::getGroupName();
		if (is_array($groupinfo)) {
			foreach ($groupinfo as $groupis => $groupname) {
				if ($groupname==$mc_groupname) {
					$group_exists = true;
				}
			}
		}
		if ($group_exists == false) {
			//create new interests group at Mailchimp
			$new_group = self::addInterestGroupings($list_id,$mc_groupname, 'checkboxes');
		}
		//stop --- create additional CRM group in Mailchimp if it does not exist
	}
	
	/**
	* get all groups 
	* returns array([name]=>[tag])
	*/
	private function listInterestGroupings($list_id){
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$interest_cat = $mc_api->get('lists/'.$list_id.'/interest-categories/');
		$categories_arr = $interest_cat['categories'];
		if (is_array($categories_arr)) {
			foreach ($categories_arr as $key => $category) {
				$groups[$category ['id']] =  $category ['title'];
			}
			return $groups;
		}
		else {
			return 'ERROR';
		}
	}
	
	/**
	* add a tags (internal MC field names) to merge information
	* <name> as displayed in MC list
	* <type> supported: text, number, address, phone, email, date, url, imageurl, radio, dropdown, birthday, zip
	* <tag> one word in capital letters
	*/
	private function addInterestGroupings($list_id, $title, $type){
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$new_interests = $mc_api->post('lists/'.$list_id.'/interest-categories/', [
							 'title' 	=> $title ,
							 'type' 	=> $type ,
													
		]);
		if ($mc_api->success()) {
			//add interests group name
			$new_interests_id = $new_interests['id'];
			$new_groupincat = $mc_api->post('lists/'.$list_id.'/interest-categories/'.$new_interests_id.'/interests', array (
						 'name' 	=> 'default' ,
			 ));
			return;
		}
		else {
			//error handling
		}
	}
	
	private function initiateCustomFields() {
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$mc_customfields = array ('Contacts'=>Array('salutation'=>'SALUTATION','account_id'=>'COMPANY','letter_salutation'=>'LETTERSALU'));
		$fieldsadded = false;
		foreach($mc_customfields as $crmmodule=>$field_array){
			foreach($field_array as $crm_field=>$mc_field){
				//start --- create additional list fields in MailChimp if they do not exist
				$fieldfound = false;
				$mcVars = self::listMergeVars();
				foreach ($mcVars as $merge_name =>$merge_tag) {
					if ($merge_tag == $mc_field) {
						$fieldfound = true;
					}
				}
				//create key
				if ($fieldfound == false) {
					//add key
					$result = self::addMergeVars($mc_field,$mc_field, 'text');
					$fieldsadded = true;
				}
			}
		}
		if ($fieldsadded == true) {
			return true;
		}
		else {
			return false;
		}
	}
	/**
	* get all tags (internal MC field names) from merge information
	* returns array([name]=>[tag])
	*/
	private function listMergeVars(){
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$result = $mc_api->get("lists/".$this->mcgroupid."/merge-fields");
		$merge_field_list = $result['merge_fields'] ;
		foreach ($merge_field_list as $key => $mergefield) {
			$fieldtag[$mergefield ['name']] =  $mergefield ['tag'];
		}

		return $fieldtag;
	}
	/**
	* add a tags (internal MC field names) to merge information
	* <name> as displayed in MC list
	* <type> supported: text, number, address, phone, email, date, url, imageurl, radio, dropdown, birthday, zip
	* <tag> one word in capital letters
	*/
	private function addMergeVars($new_merge_field, $tagname, $fieldtype){
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$RESULT= $mc_api->post("lists/".$this->mcgroupid."/merge-fields", [
					'name' 	=> $new_merge_field ,
					'tag' 	=> $tagname ,
					'type'	=> $fieldtype,
											
		]);
		if ($mc_api->success()) {
			return;
		}
		else {
			return $mc_api->getLastError();
		}
	}
	
	private function getMailChimpEntries(){
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		// there is a limit for large data sets, the number of results to return - defaults to 10(!), upper limit set at 15000 - therefore we have to loop through the data
		// first get the total (limit 1) to decide the batch size for speed optimization
		$MCtotal = self::getNumberOfMailchimpEntries($this->mcgroupid);
		if ($MCtotal > 100) {
			$numberPerBatch = 100;
		}
		else if ($MCtotal > 50){
			$numberPerBatch = 50;
		}
		else {
			$numberPerBatch = 10;
		}
		$offset = 0;
		$actualMCdata = array ();
		// infinite loop interrupted using a break
        while (true) {
			set_time_limit(0);
			$MCbatchinfoLoop = $mc_api->get('lists/'.$this->mcgroupid.'/members?offset='.$offset.'&count='.$numberPerBatch.'');
			if ($mc_api->success()) {
				$actualMCdata =array_merge($actualMCdata, $MCbatchinfoLoop['members']);
			}
			else {
				return $mc_api->getLastError();
			}
			if (count ($MCbatchinfoLoop['members']) < $numberPerBatch) {
                break;
            }
			$offset = $offset + $numberPerBatch;
		}
		if(count($actualMCdata) > 0) {
			// use email address as key
			foreach ($actualMCdata as $key =>$mcdata) {
				$newarr[strtolower ($mcdata['email_address'])] = $actualMCdata[$key];
			}
			$actualMCdata=$newarr;
			unset($newarr);
		}
		return $actualMCdata;
	}
	
	private function getNumberOfMailchimpEntries($list_id){
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		//provides the number of 'subscribed' entries and does not consider others like unsubscribed, cleaned, ....
		$MCbatchinfo = $mc_api->get("lists/{$list_id}");
		$member_count = $MCbatchinfo['stats']['member_count'];
		if ($mc_api->success()) {
			return	$member_count;
		}
		else {
			return $mc_api->getLastError();
		}
	}
	
	/**
	* Get the Mail Campaign name because it is used to match the Mail Campaign to the MailChimp list 
	*/
	private function getGroupName(){
		$db = PearDatabase::getInstance();
		$result = $db->pquery("select mailchimpname from vtiger_mailchimp where vtiger_mailchimp.mailchimpid = ?", array($this->recordid));
		$donnee = $db->fetch_row($result);
		return $donnee['mailchimpname'];
	}

	private function insertIntoAuxtable($crmid) {
		$q = "INSERT INTO vtiger_mailchimp_synced_entities (`crmid`,`mcgroupid`,`recordid`) VALUES (?,?,?)";
		$this->db -> pquery($q,array($crmid,$this->mcgroupid,$this->recordid));
	}
	
	private function removeFromAuxtable($crmid) {
		$q = "DELETE from vtiger_mailchimp_synced_entities WHERE crmid = ? AND mcgroupid = ? AND recordid = ?";
		$this->db -> pquery($q,array($crmid,$this->mcgroupid,$this->recordid));
	}

	private function removeFromSyncGroup($crmid) {
		$q = "DELETE from vtiger_crmentityrel WHERE (crmid = ? AND relcrmid = ?) OR (crmid = ? AND relcrmid = ?)";
		$this->db->pquery($q,array($crmid, $this->recordid, $this->recordid, $crmid));
	}
	
	private function addToSyncGroup($crmid,$type) {
		$query = "INSERT INTO vtiger_crmentityrel VALUES (?, ?, ?, 'Mailchimp')";
		$this->db -> pquery($query,array($crmid,$type,$this->recordid));
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
}

?>
