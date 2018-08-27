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
require_once('modules/berliCleverReach/providers/cleverreach.php');

class berliCleverReach_berliCleverReachStepController_Action extends Vtiger_Action_Controller{

	private $db;
	private $recordid;
	private $crgroupid;
	private $crgroupname;
	private $crmgrouplabel;
	private $crmgroupnr;
	private $step;

	public function process(Vtiger_Request $request) {
		global $current_user;

		$this->db = PearDatabase::getInstance();
		$this->step = (int) $request->get('step');
		$this->recordid = (int) $request->get('recordid');
		$this->crgroupid = (int) $request->get('crgroupid');
		$this->crgroupname = $request->get('crgroupname');
		
		$verbose = $request->get('verbose') == "true" ? true:false;

		// get group name and nr.
		$q = "SELECT cleverreachname, bcr_campaign_no FROM `vtiger_berlicleverreach` WHERE cleverreachid = ?";
		$result = $this->db->pquery($q,array($this->recordid));
		$row = $this->db->fetchByAssoc($result,-1,false);
        $this->crmgrouplabel = $row['cleverreachname'];
        $this->crmgroupnr = $row['bcr_campaign_no'];
		
        $response = new Vtiger_Response();
		
		if ($this->step==1) {
		
			// clear used session variables
			unset($_SESSION["clvrreach"]);
			unset($_SESSION["clvractions"]);

			$_SESSION["clvrreach"]["starttime"]=time();
			
			// get current global attribute fields from cleverreach, create missing ones
			$clvr = new cleverreachAPI();
			$rest = $clvr->getrest();

            try {
                $fieldsneeded = $clvr->createCleverReachAttributes();
            }
            catch (Exception $e) {
                $response->setError("API ERROR: ".$e->getMessage());
                $response->emit();
                return;
            }

			if ($fieldsneeded==0) {
				$response->setResult(array('',vtranslate('LBL_CLEVERREACH_ATTRIB_OK','berliCleverReach'),2));
			}
			else {
				$response->setResult(array('',vtranslate('LBL_CLEVERREACH_ATTRIB_CREATED','berliCleverReach'),1));
                sleep(3);
			}
		}

		elseif ($this->step==2) {

			// load entities from auxilliary table that have been synced to this group before to identify changes
			$q = "SELECT crmid FROM `vtiger_berlicleverreach_synced_entities` LEFT JOIN `vtiger_crmentity` USING (crmid) WHERE crgroupid = ? AND recordid = ? AND deleted = 0";
			$result = $this->db->pquery($q,array($this->crgroupid,$this->recordid));
			while($row = $this->db->fetchByAssoc($result,-1,false)) {
				$crmidsyncedbefore[$row["crmid"]]=true;
			}
			$_SESSION['clvrreach']['crmidsyncedbefore']=$crmidsyncedbefore;
			
			// load contacts from vTiger database
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
					$_SESSION['clvrreach']['brokenContacts'][$row["crmid"]]=$row["firstname"]." ".$row["lastname"];
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
					$_SESSION['clvrreach']['brokenLeads'][$row["crmid"]]=$row["firstname"]." ".$row["lastname"];
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
				$_SESSION['clvrreach']['removedlocally']=array_keys($crmidsyncedbefore);
			}

			// store data in session for next steps
			$_SESSION['clvrreach']['localcontacts']=$crm_data;

			if (count($crm_data)>0) $msg[] = sprintf(getTranslatedString('LBL_GOT_ALL_MEMBERS_VTIGER_CLEVERREACH','berliCleverReach'),htmlspecialchars($this->crmgrouplabel),$this->crmgroupnr);
			
			// load contacts from cleverreach group $this->crgroupid into array with email as associative index
			$clvr = new cleverreachAPI();
			$rest = $clvr->getrest();

			$_SESSION['clvrreach']['remotecontacts']=$clvr->fetchCleverReachGroupByID($this->crgroupid);

			if (!is_array($_SESSION['clvrreach']['remotecontacts'])) {
				$response->setError(array(getTranslatedString('LBL_API_ERROR','berliCleverReach')));
			} 
			else {
				
				if (count($_SESSION['clvrreach']['remotecontacts']) > 0) $msg[]= sprintf(getTranslatedString('LBL_GOT_ALL_MEMBERS_CLEVERREACH_API','berliCleverReach'),htmlspecialchars($this->crgroupname),$this->crgroupid);
				
				$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 2',implode("<br>",$msg),3));
			}
		}

		elseif ($this->step==3) {

			$msg = "";
			
			// data processing
			
			// find locally removed emails to remove from CleverReach by crmid
			if (is_array($_SESSION["clvrreach"]["removedlocally"])) {

				$q = "SELECT contactid,email FROM vtiger_contactdetails WHERE contactid IN (".implode(', ', $_SESSION["clvrreach"]["removedlocally"]).")";
				$result = $this->db->query($q);

				while($row = $this->db->fetchByAssoc($result,-1,false)) {
					$_SESSION["clvractions"]["delete"][$row["contactid"]]=$row["email"];

					// remove from cached remotecontacts
					unset($_SESSION["clvrreach"]["remotecontacts"][$row["email"]]);
					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_DELETE','berliCleverReach'),$row["email"]);
					}

				// same for leads
				$q = "SELECT leadid,email FROM vtiger_leaddetails WHERE leadid IN (".implode(', ', $_SESSION["clvrreach"]["removedlocally"]).")";
				$result = $this->db->query($q);

				while($row = $this->db->fetchByAssoc($result,-1,false)) {
					$_SESSION["clvractions"]["delete"][$row["leadid"]]=$row["email"];

					// remove from cached remotecontacts
					unset($_SESSION["clvrreach"]["remotecontacts"][$row["email"]]);
					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_DELETE','berliCleverReach'),$row["email"]);
					}
			}

			
			// iterate through CRM contacts, find new entries for export to CleverReach
			foreach ($_SESSION["clvrreach"]["localcontacts"] as $localcontact) {

				if (!isset($_SESSION["clvrreach"]["remotecontacts"][$localcontact["email"]])) {

					if ($localcontact["emailoptout"]>0) {
						if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_NOEXPORTONOPTOUT','berliCleverReach'),$localcontact["email"]);
					}
					else {

						if ($_SESSION['clvrreach']['crmidsyncedbefore'][$localcontact["crmid"]] == true) {

							if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_DELETEDREMOTELY','berliCleverReach'),$localcontact["email"]);

							$_SESSION["clvractions"]["removelocally"][$localcontact["crmid"]]=$localcontact["email"];
						}
						else {
							if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_EXPORT','berliCleverReach'),$localcontact["email"]);
							
							$_SESSION["clvractions"]["export"][]=$localcontact["email"];
						}
					}
				}
			}

			
			// iterate through CleverReach contacts, find entries to either import, update, add to sync group or ignore
			foreach ($_SESSION["clvrreach"]["remotecontacts"] as $remotecontact) {
			
				if (!isset($_SESSION["clvrreach"]["localcontacts"][$remotecontact->email])) {

					// if there's a matching local crm contact, add to sync group
					$q = "SELECT
							vtiger_crmentity.crmid,
							lower(vtiger_contactdetails.email) as email
							FROM vtiger_contactdetails
							INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
							WHERE email = ? AND vtiger_crmentity.deleted = 0 LIMIT 1";
					$result = $this->db->pquery($q,array($remotecontact->email));

					if ($row = $this->db->fetchByAssoc($result)) {
					
						$_SESSION["clvractions"]["addtocrmgroup"]["Contacts"][$row["crmid"]]=$row["email"];
						
						if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_ADDTOCRMGROUP','berliCleverReach'),$row["email"]);
						continue;
					}

					// repeat for leads: if there's a match, add to sync group
					$q = "SELECT
							vtiger_crmentity.crmid,
							lower(vtiger_leaddetails.email) as email
							FROM vtiger_leaddetails
							INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
							WHERE email = ? AND converted <> 1 AND vtiger_crmentity.deleted = 0 LIMIT 1";
					$result = $this->db->pquery($q,array($remotecontact->email));

					if ($row = $this->db->fetchByAssoc($result)) {

						$_SESSION["clvractions"]["addtocrmgroup"]["Leads"][$row["crmid"]]=$row["email"];
						
						if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_ADDTOCRMGROUP','berliCleverReach'),$row["email"]);
						continue;
					}

					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_TEST4IMPORT','berliCleverReach'),$remotecontact->email);

					// check if all attributes are set, if so import new entry to CRM
					$imp =1;
					if ($remotecontact->active == false) {
						if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_INACTIVE','berliCleverReach');
						$imp=0;
					}
					if (empty($remotecontact->global_attributes->lname) || empty($remotecontact->global_attributes->fname) || 
						empty($remotecontact->global_attributes->salutation)) {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_INCOMPLETE','berliCleverReach');
							$imp=0;
					}

					if ($imp) {
						if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DOIMPORT','berliCleverReach');
						$_SESSION["clvractions"]["import"][]=$remotecontact->email;
					}
					else {
						if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DONTIMPORT','berliCleverReach');
					}

				}
				else {
					
					if ($verbose) $msg .= "<br>".sprintf(getTranslatedString('LBL_VERBOSELOG_HAVEENTRY','berliCleverReach'),$remotecontact->email,htmlspecialchars($this->crmgrouplabel));

					// make sure entry is in auxtable since it might have been imported from different source before
					if (!$_SESSION['clvrreach']['crmidsyncedbefore'][$_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["crmid"]]) {
						$this->insertIntoAuxtable($_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["crmid"]);
					}
				
					// inactive
					if ( $remotecontact->active == false && $remotecontact->deactivated > 0 && $_SESSION['clvrreach']['crmidsyncedbefore'][$_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["crmid"]] ) {
						
						if ($verbose) $msg .= sprintf(getTranslatedString('LBL_VERBOSELOG_UNSUBSCRIBED','berliCleverReach'),date("Y-m-d",$remotecontact->deactivated)); 
						$_SESSION["clvractions"]["optout"][]=$remotecontact->email;
					}
					elseif ($remotecontact->active == false && $remotecontact->bounced > 0) {
						if ($verbose) $msg .= sprintf(getTranslatedString('LBL_VERBOSELOG_BOUNCED','berliCleverReach'),date ("Y-m-d",$remotecontact->bounced));
						# do something here?
					}
					else {
						// compare attributes, update entry on CleverReach if changed
						$upd=0;
						if ($remotecontact->global_attributes->fname != $_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["firstname"] ||
							$remotecontact->global_attributes->lname != $_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["lastname"] ||
							$remotecontact->global_attributes->salutation != $_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["salutation"] ||
							$remotecontact->global_attributes->company != $_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["accountname"]) {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_ATTRIBCHANGED','berliCleverReach');
							$upd=1;
						}
						
						if ($remotecontact->active == true && $_SESSION["clvrreach"]["localcontacts"][$remotecontact->email]["emailoptout"] > 0 ) {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_OPTOUT','berliCleverReach');
							$upd=1;
						}

						if ($upd) {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DOUPDATE','berliCleverReach');
							$_SESSION["clvractions"]["update"][]=$remotecontact->email;
						}
						else {
							if ($verbose) $msg .= getTranslatedString('LBL_VERBOSELOG_DONTUPDATE','berliCleverReach');
						}
					}
				}
			}
			// end of iteration through CleverReach contacts
			
			if ($verbose) $msg .= "<hr>";
			
			$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 3',$msg,4));
		}
		
		/***************************************************************************************
		*	Step 4: Perform actions collected in session arrays
		*   MUST expect to be called multiple times, so unset array entries if they're done
		*/
		
		elseif ($this->step==4) {
			
			$clvr = new cleverreachAPI();
			$rest = $clvr->getrest();
			
			// existing entities to add to crm group, fast
			if (is_array($_SESSION["clvractions"]["addtocrmgroup"])) {
				
				foreach ($_SESSION["clvractions"]["addtocrmgroup"] as $entitytype => $entities) {
					
					if ($entitytype == "Leads") {
						$_SESSION['clvrreach']['summary'] .= "<h4>".getTranslatedString('LBL_EXISTING_LEADS_ADDED','berliCleverReach')."</h4><p style='color:#880'>".implode(", ",$entities)."</p>";
					}
					else {
						$_SESSION['clvrreach']['summary'] .= "<h4>".getTranslatedString('LBL_EXISTING_CONTACTS_ADDED','berliCleverReach')."</h4><p style='color:#880'>".implode(", ",$entities)."</p>";
					}
					
					foreach ($entities as $crmid => $email) {
				
						$q = "INSERT INTO `vtiger_crmentityrel` (`crmid` ,`module` ,`relcrmid` ,`relmodule`) VALUES (?, ?, ?, 'berliCleverReach')";
						$this->db->pquery($q,array($crmid,$entitytype,$this->recordid));

						$this->insertIntoAuxtable($crmid);
					}
				}
				unset ($_SESSION["clvractions"]["addtocrmgroup"]); //action performed, unset array
			}

			
			// transfer new receivers to cleverreach, slowish
			if (is_array($_SESSION["clvractions"]["export"])) {

				// number of entries to begin with
				if (empty($_SESSION["clvrreach"]["progressstartcount"])) {
					$_SESSION["clvrreach"]["progressstartcount"]=count($_SESSION["clvractions"]["export"]);
				}	
				
				$batchsize=50;
				
				$newreceivers=array();

				foreach ($_SESSION["clvractions"]["export"] as $key => $email) {

					$newreceivers[] = array("email"=>$email,
											"global_attributes"=>array(	"fname"=>$_SESSION["clvrreach"]["localcontacts"][$email]["firstname"],
																		"lname"=>$_SESSION["clvrreach"]["localcontacts"][$email]["lastname"],
																		"salutation"=>$_SESSION["clvrreach"]["localcontacts"][$email]["salutation"],
																		"company"=>$_SESSION["clvrreach"]["localcontacts"][$email]["accountname"],
																		"letter_salutation"=>$_SESSION["clvrreach"]["localcontacts"][$email]["salutation"]
																		)
										);

					$this->insertIntoAuxtable($_SESSION["clvrreach"]["localcontacts"][$email]["crmid"]);
					
					$_SESSION["clvrreach"]["exported"][]=$email;
					unset($_SESSION["clvractions"]["export"][$key]);
						
					// post batches of 100s, and remainder
					if (count($newreceivers) >= $batchsize || (count($_SESSION["clvractions"]["export"])==0 && count($newreceivers)>0) ) {
						
						$rest->post("/groups/{$this->crgroupid}/receivers",$newreceivers);
						
						$entriesleft = $_SESSION["clvrreach"]["progressstartcount"]-count($_SESSION["clvractions"]["export"]);
						$msg = sprintf(getTranslatedString('LBL_EXPORTPROGRESS','berliCleverReach'),$entriesleft,$_SESSION["clvrreach"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 4',$msg,4,"clvpr1"));
						$response->emit();
						return;
					}
					
				}
				
				$_SESSION['clvrreach']['summary'] .= "<h4>".vtranslate('LBL_NEW_LOCAL_ENTRIES_TO_EXPORT','berliCleverReach')."</h4><p style='color:#080'>".implode(", ",$_SESSION["clvrreach"]["exported"])."</p>";

				unset ($_SESSION["clvractions"]["export"]); //action performed, unset array
				unset ($_SESSION["clvrreach"]["progressstartcount"]);
			}


			// update (locally) changed entries on cleverreach
			// potentially very slow, so show some progress indication
			
			if (is_array($_SESSION["clvractions"]["update"])) {

				// number of entries to begin with
				if (empty($_SESSION["clvrreach"]["progressstartcount"])) {
					$_SESSION["clvrreach"]["progressstartcount"]=count($_SESSION["clvractions"]["update"]);
				}	
				
				$batchsize=15;
			
				foreach ($_SESSION["clvractions"]["update"] as $key => $email) {

					$updreceiver = array("email"=>$email,
										"global_attributes"=>array(	"fname"=>$_SESSION["clvrreach"]["localcontacts"][$email]["firstname"],
																	"lname"=>$_SESSION["clvrreach"]["localcontacts"][$email]["lastname"],
																	"salutation"=>$_SESSION["clvrreach"]["localcontacts"][$email]["salutation"],
																	"company"=>$_SESSION["clvrreach"]["localcontacts"][$email]["accountname"],
																	"letter_salutation"=>$_SESSION["clvrreach"]["localcontacts"][$email]["salutation"]
																	)
										);

					$updateurl = "/groups/{$this->crgroupid}/receivers/".urlencode($email);

					$rest->put($updateurl,$updreceiver);

					// explicitly calling "setinactive" since updating "active" attribute seems buggy/unsupported ("active"=>$_SESSION["clvrreach"]["localcontacts"][$email]["emailoptout"]==1?"false":"true",)
					if ($_SESSION["clvrreach"]["localcontacts"][$email]["emailoptout"]>0 && $_SESSION["clvrreach"]["remotecontacts"][$email]->active == true) {
						$rest->put("/groups/{$this->crgroupid}/receivers/".urlencode($email)."/setinactive");
					}
				
					$batchsize--;
					
					$_SESSION["clvrreach"]["updated"][]=$email;
					unset($_SESSION["clvractions"]["update"][$key]);
					
					if ($batchsize < 1 || ( count($_SESSION["clvractions"]["update"]) == 0 && count($_SESSION["clvrreach"]["updated"]) > $batchsize )) {
                    
						$entriesleft = $_SESSION["clvrreach"]["progressstartcount"]-count($_SESSION["clvractions"]["update"]);
						$msg = sprintf(getTranslatedString('LBL_UPDATEPROGRESS','berliCleverReach'),$entriesleft,$_SESSION["clvrreach"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 4',$msg,4,"clvpr2"));
						$response->emit();
						return;
					}
				}
				
				$_SESSION['clvrreach']['summary'] .= "<h4>".vtranslate('LBL_UPDATED_ENTRIES','berliCleverReach')."</h4><p style='color:#00f'>".implode(", ",$_SESSION["clvrreach"]["updated"])."</p>";;

				unset ($_SESSION["clvractions"]["update"]); //action performed, unset array
				unset ($_SESSION["clvrreach"]["progressstartcount"]);
			}

			// import new cleverreach entries to crm as lead or contact according to setting
			
			if (is_array($_SESSION["clvractions"]["import"])) {

				$subscribertype = berliCleverReach_Module_Model::getSubscriberType();

				// number of entries to begin with
				if (empty($_SESSION["clvrreach"]["progressstartcount"])) {
					$_SESSION["clvrreach"]["progressstartcount"]=count($_SESSION["clvractions"]["import"]);
				}	
				
				// update progress after this many entries
				$batchsize=25;
				
				foreach ($_SESSION["clvractions"]["import"] as $key => $email)
				{
					if ($subscribertype == "contact") {

						$company = trim($_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->company);

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
						$contact->column_fields['salutationtype']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->salutation;
						$contact->column_fields['firstname']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->fname;
						$contact->column_fields['lastname']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->lname;
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
						$lead->column_fields['salutationtype']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->salutation;
						$lead->column_fields['firstname']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->fname;
						$lead->column_fields['lastname']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->lname;
						$lead->column_fields['company']=$_SESSION["clvrreach"]["remotecontacts"][$email]->global_attributes->company;
						$lead->save("Leads");
						$id = $lead->id;

						// put new lead in current vtiger group...
						$this->addToSyncGroup($id,'Leads');
						
						// ... and insert into auxilliary table
						$this->insertIntoAuxtable($id);
					}
					
					$batchsize--;
					
					$_SESSION["clvrreach"]["imported"][]=$email;
					unset($_SESSION["clvractions"]["import"][$key]);
					
					if ($batchsize < 1 || ( count($_SESSION["clvractions"]["import"]) == 0 && count($_SESSION["clvrreach"]["imported"]) > $batchsize )) {
                    
						$entriesleft = $_SESSION["clvrreach"]["progressstartcount"]-count($_SESSION["clvractions"]["import"]);
						$msg = sprintf(getTranslatedString('LBL_IMPORTPROGRESS','berliCleverReach'),$entriesleft,$_SESSION["clvrreach"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 4',$msg,4,"clvpr4"));
						$response->emit();
						return;
					}
				}
				unset ($_SESSION["clvractions"]["import"]);
				unset ($_SESSION["clvrreach"]["progressstartcount"]);
				
				if ($subscribertype == "contact") {
					$_SESSION['clvrreach']['summary'] .= "<h4>".vtranslate('LBL_NEW_CONTACTS_IMPORTED','berliCleverReach')."</h4><p style='color:#080'>".implode(", ",$_SESSION["clvrreach"]["imported"])."</p>";
				}
				else {
					$_SESSION['clvrreach']['summary'] .= "<h4>".vtranslate('LBL_NEW_LEADS_IMPORTED','berliCleverReach')."</h4><p style='color:#080'>".implode(", ",$_SESSION["clvrreach"]["imported"])."</p>";
				}
			}
			
            // optionally: set local emailoptout if receiver unsubscribed
            // if (is_array($_SESSION["clvractions"]["optout"])) {
                // foreach ($_SESSION["clvractions"]["optout"] as $key => $email) {
                    // if ($_SESSION["clvrreach"]["localcontacts"][$email]["isLead"]) {
                        // $q = "UPDATE vtiger_leaddetails SET emailoptout = 1 WHERE leadid=?";
                    // }
                    // else {
                        // $q = "UPDATE vtiger_contactdetails SET emailoptout = 1 WHERE contactid=?";
                    // }
                    // $this->db->pquery($q,array($_SESSION["clvrreach"]["localcontacts"][$email]['crmid']));
                // }
            // }
            // unset ($_SESSION["clvractions"]["optout"]);

			// remove receivers from sync group
			if (is_array($_SESSION["clvractions"]["removelocally"])) {
				
				$_SESSION['clvrreach']['summary'] .= "<h4>".vtranslate('LBL_REMOVE_ENTITYS_FROM_VTIGER','berliCleverReach')."</h4><p>".implode(", ",$_SESSION["clvractions"]["removelocally"])."</p>";
				
				foreach ($_SESSION["clvractions"]["removelocally"] as $crmid => $email) {
					
					// remove from sync list
					$this->removeFromSyncGroup($crmid);

					// delete from aux table
					$this->removeFromAuxtable($crmid);
				}
				unset ($_SESSION["clvractions"]["removelocally"]);
			}
			
			// delete receivers from cleverreach, very slow!
			if (is_array($_SESSION["clvractions"]["delete"])) {
				
				// number of entries to begin with
				if (empty($_SESSION["clvrreach"]["progressstartcount"])) {
					$_SESSION["clvrreach"]["progressstartcount"]=count($_SESSION["clvractions"]["delete"]);
				}	

				$batchsize=10;
				
				foreach ($_SESSION["clvractions"]["delete"] as $crmid => $email) {

					try {
						$rest->delete("/groups/{$this->crgroupid}/receivers/{$email}");
					} catch (Exception $e) {
						# better ignore errors
					}

					// remove entry from aux table
					$this->removeFromAuxtable($crmid);
					
					$batchsize--;
					
					$_SESSION["clvrreach"]["deleted"][]=$email;
					unset($_SESSION["clvractions"]["delete"][$crmid]);
					
					if ($batchsize < 1 || ( count($_SESSION["clvractions"]["delete"]) == 0 && count($_SESSION["clvrreach"]["deleted"]) > $batchsize )) {
                    
						$entriesleft = $_SESSION["clvrreach"]["progressstartcount"]-count($_SESSION["clvractions"]["delete"]);
						$msg = sprintf(getTranslatedString('LBL_DELETEPROGRESS','berliCleverReach'),$entriesleft,$_SESSION["clvrreach"]["progressstartcount"]);
						$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 4',$msg,4,"clvpr4"));
						$response->emit();
						return;
					}
				}
				
				unset ($_SESSION["clvractions"]["delete"]);
				$_SESSION['clvrreach']['summary'] .= "<h4>".vtranslate('LBL_REMOVE_FROM_CLEVERREACH','berliCleverReach')."</h4><p style='color:#800'>".implode(", ",$_SESSION["clvrreach"]["deleted"])."</p>";
				unset($_SESSION["clvrreach"]["deleted"]);
			}

			$msg = "<hr>".$_SESSION['clvrreach']['summary'];
			
			if (is_array($_SESSION["clvrreach"]["brokenContacts"])) $msg .= vtranslate('LBL_BROKEN_CONTACTS','berliCleverReach') . implode(", ",$_SESSION["clvrreach"]["brokenContacts"]);
			if (is_array($_SESSION["clvrreach"]["brokenLeads"])) $msg .= vtranslate('LBL_BROKEN_LEADS','berliCleverReach') . implode(", ",$_SESSION["clvrreach"]["brokenLeads"]);
			
			if (!is_array($_SESSION["clvractions"])) $msg .= "<br><h4>".vtranslate('LBL_NO_CHANGES_TO_SYNC','berliCleverReach')."</h4>";
			
			// update lastsynchronization
			$currentdate = date("Y-m-d H:i:s");
			$q = "UPDATE vtiger_berlicleverreach SET lastsynchronization = ? WHERE cleverreachid = ?";
			$this->db->pquery($q,array($currentdate,$this->recordid));

			$exec_time = time()-$_SESSION["clvrreach"]["starttime"];
			
			$msg .= "<br>".sprintf(vtranslate('LBL_FINISHED_AFTER','berliCleverReach'),$exec_time);
			
			// clear used session variables
			unset($_SESSION["clvrreach"]);
			unset($_SESSION["clvractions"]);
			
			$response->setResult(array(getTranslatedString('LBL_STEP','berliCleverReach').' 4',$msg,0));
		}
		else {
			$response->setError(array('Error: step parameter out of bounds'));
		}

        $response->emit();
    }

	private function insertIntoAuxtable($crmid) {
		$q = "INSERT INTO vtiger_berlicleverreach_synced_entities (`crmid`,`crgroupid`,`recordid`) VALUES (?,?,?)";
		$this->db -> pquery($q,array($crmid,$this->crgroupid,$this->recordid));
	}
	
	private function removeFromAuxtable($crmid) {
		$q = "DELETE from vtiger_berlicleverreach_synced_entities WHERE crmid = ? AND crgroupid = ? AND recordid = ?";
		$this->db -> pquery($q,array($crmid,$this->crgroupid,$this->recordid));
	}

	private function removeFromSyncGroup($crmid) {
		$q = "DELETE from vtiger_crmentityrel WHERE (crmid = ? AND relcrmid = ?) OR (crmid = ? AND relcrmid = ?)";
		$this->db->pquery($q,array($crmid, $this->recordid, $this->recordid, $crmid));
	}
	
	private function addToSyncGroup($crmid,$type) {
		$query = "INSERT INTO vtiger_crmentityrel VALUES (?, ?, ?, 'berliCleverReach')";
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
