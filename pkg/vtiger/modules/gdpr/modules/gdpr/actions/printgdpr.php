<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('libraries/tcpdf/tcpdf.php');
require_once('libraries/tcpdf/config/tcpdf_config.php');

class gdpr_printgdpr_Action {
	
	public function validateRequest(Vtiger_Request $request) { 
            $request->validateReadAccess(); 
	}
	public function loginRequired() {
		return true;
	}
	public function checkPermission() { }
	
	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}
	
	function process(Vtiger_Request $request) {
		$src_record = vtlib_purify($request->get('recordid'));
		$src_module = vtlib_purify($request->get('scr_module'));
		if(!empty($src_record)) {
			$this->pdfexport($src_module, $src_record);
		}
	}
	
	static function out($message, $delimiter="\n") {
		echo $message . $delimiter;
	}
	
	
	static function pdfexport($module,$recordid) {
		global $current_user;
		
		try {
			self::createpdffile($module,$recordid);
			
		} catch(Exception $e) {
			self::out("ERROR: " . $e->getMessage());
		}		
	}

	static function createpdffile ($module, $id) {
		require_once('include/database/PearDatabase.php');
		require_once('modules/Pdfsettings/helpers/PDFutils.php');
		global $currentModule;
		// global needed for ListView operations
		$currentModule = $module;
		$db = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		//get the stored PDF configuration values from Quotes
		$pdf_config_details = getAllPDFDetails('Quotes');
		//set font
		$default_font = getTCPDFFontsname ($pdf_config_details['fontid']);
		if ($default_font =='') {
			$default_font = 'freesans';
		}
		$font_size_header = $pdf_config_details['fontsizeheader'];
		$font_size_address = $pdf_config_details['fontsizeaddress'];
		$font_size_body = $pdf_config_details['fontsizebody'];
		$font_size_footer = $pdf_config_details['fontsizefooter'];
	
		//number of lines after headline
		$space_headline = $pdf_config_details['space_headline'];

		//display logo?
		$logoradio = $pdf_config_details['logoradio'];

		// ************************ BEGIN POPULATE DATA ***************************
		// current date for top
		$date_issued = Vtiger_Date_UIType::getDisplayDateValue(date('Y-m-d'));
		$date_issued = str_replace ("-",".",$date_issued);

		// get gdpr settings
		// get company information from settings
		$add_query = "select * from vtiger_organizationdetails";
		$result = $db->query($add_query);
		$num_rows = $db->num_rows($result);
		if($num_rows > 0) {
			$org_name = decode_html($db->query_result($result,0,"organizationname"));
			$org_address = decode_html($db->query_result($result,0,"address"));
			$org_city = decode_html($db->query_result($result,0,"city"));
			$org_state = decode_html($db->query_result($result,0,"state"));
			$org_country = decode_html($db->query_result($result,0,"country"));
			$org_code = $db->query_result($result,0,"code");
			$org_phone = $db->query_result($result,0,"phone");
			$org_fax = $db->query_result($result,0,"fax");
			$org_website = $db->query_result($result,0,"website");
			$logo_name = $db->query_result($result,0,"logoname");
		}
		include_once 'modules/Settings/gdpr/models/Record.php';
		include_once 'modules/Settings/gdpr/models/Module.php';
		$gdpr_recordModel = Settings_gdpr_Record_Model::getInstance();
		$gdpr_parameter_model = $gdpr_recordModel->getGlobalSettingsParameters();
        $moduleSettings = $gdpr_parameter_model->getModuleSettings(); 

		$current_tabid = getTabid($module);
        $allModules = Settings_ModuleManager_Module_Model::getEntityModules();
		
		$parentModuleModel =  $allModules[$current_tabid];
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($id, $module);
		
		// get fields and contents
		$fieldModelList = $parentModuleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $parentRecordModel->get($fieldName);
			$fieldDataType = $fieldModel->getFieldDataType();
            if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
            }
			elseif ($fieldDataType == 'date') {
				$fieldValue = decode_html($fieldModel->getDisplayValue($fieldValue, true));
				$fieldValue = str_replace ("-",".",$fieldValue);
			}
			elseif ($fieldDataType != 'email' AND $fieldDataType != 'url' AND $fieldDataType != 'skype') {
				$fieldValue = decode_html($fieldModel->getDisplayValue($fieldValue, true));
			}
			$RecordDetails[$fieldName] = $fieldValue;
		}

		// used for PDF salutation in body
		$salutation = $RecordDetails['salutationtype'];
		$lastname = $RecordDetails['lastname'];
		
		// if special fields are selected do not provide all data
		$fields_to_consider =  $moduleSettings[$current_tabid]['fields'];
		if (!empty($fields_to_consider)) {
			foreach ($fields_to_consider as $fieldid) {
				$column_name[] = array_search($fieldid, array_column($fieldModelList, 'id', 'name'));
			}
			$RecordDetails = array_intersect_key($RecordDetails, array_flip($column_name));
		}

		// CRM internal fields are excluded
		// Contacts
		$excluded_fields_contacts = array ('createdtime','modifiedtime','modifiedby','support_start_date','support_end_date','isconvertedfromlead','imagename','portal','notify_owner','reference','assigned_user_id','emailoptout','donotcall','contact_no','salutationtype','account_id','description');
		// Leads
		$excluded_fields_leads = array ('createdtime','modifiedtime','modifiedby','assigned_user_id','lead_no','salutationtype','leadsource','industry','annualrevenue','rating','noofemployees','emailoptout','description','leadstatus');
		$excluded_fields = array_merge($excluded_fields_contacts, $excluded_fields_leads);
		
		foreach ($RecordDetails as $fieldname => $fieldcontents) {
			if (!empty(trim($fieldcontents)) AND !in_array($fieldname, $excluded_fields)){
				//get field label
				$fieldlabel = array_search($fieldname, array_column($fieldModelList, 'name', 'label'));
				$translated_fieldname = getTranslatedString($fieldlabel,$module);
				$print_record[$translated_fieldname] = $fieldcontents;
				
			}
		}
		
		// get related entries
		// get module with personalized information from settings
		$other_module = array ();
		if (is_array ($moduleSettings ) ) {
			foreach($moduleSettings as $tabid => $settingsfields) {
				if ($tabid != $current_tabid) {
					if (!empty($settingsfields['fields'])) {
						$other_module[] = vtlib_getModuleNameById($tabid);
					}
				}
			}
		}
		
		$relations = $parentModuleModel->getRelations();
		foreach($relations as $relation) {
			$relatedModuleName = $relation->getRelationModuleModel()->getName();
			if (in_array($relatedModuleName, $other_module)) {
				$currentModule = $module;
				$listViewModel = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName);
				$record_count = $listViewModel->getRelatedEntriesCount();
				if ($record_count>0) {
					$other_information[getTranslatedString($relatedModuleName)] = $record_count;
				}
			}
		}
		
		// ************************ END POPULATE DATA ***************************
		//************************BEGIN PDF FORMATING****************************
		$page_num='1';
		// create new PDF document
		//$pdf = new PDF( 'P', 'mm', 'A4' );
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true); 
		// set font
		$pdf->SetFont($default_font, " ", $font_size_body);
		$pdf->setPrintHeader(0);

		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		// set pdf information
		$recordName = $RecordDetails['lastname'].' '.$RecordDetails['firstname'];
		$pdf-> SetTitle ($recordName);
		$pdf-> SetAuthor ($org_name);
		$pdf-> SetSubject ($recordName);
		$pdf-> SetCreator ('CRM System berliCRM: www.crm-now.de ');
		$pdf-> SetKeywords ('gdpr information');
		// disable footer
		$pdf->setPrintFooter(false);
		//Disable automatic page break
		$pdf->SetAutoPageBreak(true,PDF_MARGIN_FOOTER);
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 
		//initialize document

		$new_page_started = false;
		$pdf->AddPage();
		include("modules/gdpr/gdpr_templates/header.php");
		include("modules/gdpr/gdpr_templates/body.php");
		//formatting name for file name
		$exportname = utf8_decode($recordName);
		$exportname = decode_html($exportname);
		$exportname = strtoupper(str_replace(array("ö","ä","ü","ß"),array("oe","ae","ue","ss"),$exportname));
		//remove not printable ascii char
		$exportname = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $exportname);

		// issue pdf
		$pdf->Output(getTranslatedString('LBL_DSGVO_NAME').'_'.$exportname.'_'.$date_issued.'.pdf','D');
		exit;
	}
}

// extend TCPF with custom function for Multicell
class MYPDF extends TCPDF {

    public function MultiRow($left, $right) {
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)
        $page_start = $this->getPage();
        $y_start = $this->GetY();

        // write the left cell
        $this->MultiCell(40, 0, $left, 0, 'L', 0, 2, '', '', true, 0);
		
		if (empty($left)) {
			//reset margin
			$this->SetX( PDF_MARGIN_LEFT);
		}

        $page_end_1 = $this->getPage();
        $y_end_1 = $this->GetY();

        $this->setPage($page_start);

        // write the right cell
        $this->MultiCell(0, 0, $right, 0, 'L', 0, 1, $this->GetX() ,$y_start, true, 0);

        $page_end_2 = $this->getPage();
        $y_end_2 = $this->GetY();

        // calculation of MultiCell height: set the new row position by case
        if (max($page_end_1,$page_end_2) == $page_start) {
            $ynew = max($y_end_1, $y_end_2);
        } 
		elseif ($page_end_1 == $page_end_2) {
            $ynew = max($y_end_1, $y_end_2);
        } 
		elseif ($page_end_1 > $page_end_2) {
            $ynew = $y_end_1;
        } 
		else {
            $ynew = $y_end_2;
        }
        $this->setPage(max($page_end_1,$page_end_2));
        $this->SetXY($this->GetX(),$ynew);
    }
}

