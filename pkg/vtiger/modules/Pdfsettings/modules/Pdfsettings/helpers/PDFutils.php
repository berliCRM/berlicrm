<?php
/*This function returns the pdf_strings for the current language (crm-now extension)
*/
function return_module_language_pdf($language, $module) {
	global $log;
	$log->debug("Entering return_module_language_pdf(".$language.",". $module.") method ...");
	global $default_language, $log;

	@include("modules/$module/language/$language.lang.pdf.php");
	if(!isset($pdf_strings)) {
		$log->warn("Unable to find the module language file for language: ".$language." and module: ".$module);
		require("modules/$module/language/$default_language.lang.pdf.php");
		$language_used = $default_language;
	}
	$language_used = $language;
	foreach($pdf_strings as $entry_key=>$entry_value){
		$pdf_strings[$entry_key] = $entry_value;
	}

	$log->debug("Exiting return_module_language_pdf method ...");
	return $pdf_strings;
}
/*This function returns the pdf_strings for the current language related to a specific module
*/
function return_specific_language_pdf($language, $module) {
	global $log;
	$log->debug("Entering return_specific_language_pdf(".$language.",". $module.") method ...");
	global $default_language, $log;
	@include("modules/Pdfsettings/languages/$language/$language.$module.lang.pdf.php");
	if(!isset($pdf_setting_strings)) {
		$log->warn("Unable to find the module language file for language: ".$language." and module: ".$module);
		require("modules/Pdfsettings/languages/$default_language/$default_language.$module.lang.pdf.php");
		$language_used = $default_language;
	}
	return $pdf_setting_strings;
}

/**	Function used to get the list of PDF configuration settings as a array
 *	@param string $module - module for which the PDF settings is requested
 *	return array $pdfsettings - return all settings as a array
 *       crm-now extension
 */
function getAllPDFDetails ($module)
{
	global $adb, $log;
	$log->debug("Entering into the function getAllPDFDetails($module)");
	$pdfsettings = Array();
	$pdfsettings_result=$adb->pquery("select * from berli_pdfconfiguration where pdfmodul=?",array($module));
	$pdfsettings['pdfid'] = $adb->query_result($pdfsettings_result,0,'pdfid');
	$pdfsettings['pdfmodul'] = $adb->query_result($pdfsettings_result,0,'pdfmodul');
	$pdfsettings['fontid'] = $adb->query_result($pdfsettings_result,0,'fontid');
	$pdfsettings['fontsizebody'] = $adb->query_result($pdfsettings_result,0,'fontsizebody');
	$pdfsettings['fontsizeheader'] = $adb->query_result($pdfsettings_result,0,'fontsizeheader');
	$pdfsettings['fontsizefooter'] = $adb->query_result($pdfsettings_result,0,'fontsizefooter');
	$pdfsettings['fontsizeaddress'] = $adb->query_result($pdfsettings_result,0,'fontsizeaddress');
	$pdfsettings['dateused'] = $adb->query_result($pdfsettings_result,0,'dateused');
	$pdfsettings['space_headline'] = $adb->query_result($pdfsettings_result,0,'spaceheadline');
	$pdfsettings['summaryradio'] = $adb->query_result($pdfsettings_result,0,'summaryradio');
	$pdfsettings['gprodname'] = $adb->query_result($pdfsettings_result,0,'gprodname');
	$pdfsettings['gproddes'] = $adb->query_result($pdfsettings_result,0,'gproddes');
	$pdfsettings['gprodcom'] = $adb->query_result($pdfsettings_result,0,'gprodcom');
	$pdfsettings['iprodname'] = $adb->query_result($pdfsettings_result,0,'iprodname');
	$pdfsettings['iproddes'] = $adb->query_result($pdfsettings_result,0,'iproddes');
	$pdfsettings['iprodcom'] = $adb->query_result($pdfsettings_result,0,'iprodcom');
	$pdfsettings['pdflang'] = $adb->query_result($pdfsettings_result,0,'pdflang');
	$pdfsettings['footerradio'] = $adb->query_result($pdfsettings_result,0,'footerradio');
	$pdfsettings['logoradio'] = $adb->query_result($pdfsettings_result,0,'logoradio');
	$pdfsettings['pageradio'] = $adb->query_result($pdfsettings_result,0,'pageradio');
	$pdfsettings['owner'] = $adb->query_result($pdfsettings_result,0,'owner');
	$pdfsettings['ownerphone'] = $adb->query_result($pdfsettings_result,0,'ownerphone');
	$pdfsettings['poname'] = $adb->query_result($pdfsettings_result,0,'poname');
	$pdfsettings['clientid'] = $adb->query_result($pdfsettings_result,0,'clientid');
	$pdfsettings['carrier'] = $adb->query_result($pdfsettings_result,0,'carrier');
	$pdfsettings['paperf'] = $adb->query_result($pdfsettings_result,0,'paperf');
	$log->debug("Exit from the function getAllPDFDetails($module)");
	return $pdfsettings;
}

/**	Function used to get the list of PDF colums settings as a array
 *	@param string $module - module for which the PDF settings is requested
 *	return array $pdfcolumnsettings - return all column settings as a array
 *       crm-now extension
*/
function getAllPDFColums ($module)
{
	global $adb, $log;
	$log->debug("Entering into the function getAllPDFColums($module)");
	$pdfcolumnsettings = Array();
	for($i=0;$i<2;$i++) {
	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.position AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'Position'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'taxmode') =='group') $queryfieldname= 'group_enabled';
		else $queryfieldname= 'individual_enabled';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'position') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['Position'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

		$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.ordercode AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'OrderCode'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'ordercode') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';

		$pdfcolumnsettings[$i]['OrderCode'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

		$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.description AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'Description'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'description') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['Description'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.qty AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'Qty'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'qty') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['Qty'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.unit AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'Unit'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'unit') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['Unit'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.unitprice AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'UnitPrice'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'unitprice') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';

		$pdfcolumnsettings[$i]['UnitPrice'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.discount AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'Discount'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'discount') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['Discount'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.tax AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'Tax'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'tax') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['Tax'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);

	$pdfcolumnsettings_result=$adb->pquery("
SELECT `berli_pdf_fields`.pdffieldname AS name, `berli_pdfcolums_sel`.pdftaxmode as taxmode, `berli_pdfcolums_sel`.*, `berli_pdfcolums_active`.linetotal AS active, berli_pdf_fields.quotes_g_enabled AS group_enabled,  berli_pdf_fields.quotes_i_enabled AS individual_enabled
FROM `berli_pdfcolums_sel`
INNER JOIN berli_pdfcolums_active ON (berli_pdfcolums_active.pdfmodulname = `berli_pdfcolums_sel`.pdfmodul)
INNER JOIN berli_pdf_fields on berli_pdfcolums_active.pdftaxmode= `berli_pdfcolums_sel`.pdftaxmode
WHERE pdfmodul =? and `berli_pdf_fields`.pdffieldname =?",array($module,'LineTotal'));
		if ($adb->query_result($pdfcolumnsettings_result,$i,'active') =='disabled') $queryactivefield= 'disabled="disabled"';
		else $queryactivefield= '';
		if ($adb->query_result($pdfcolumnsettings_result,$i,'linetotal') =='checked') $queryselectfield= 'checked="checked"';
		else $queryselectfield= '';
		$pdfcolumnsettings[$i]['LineTotal'] = array(
		'taxtype' => $adb->query_result($pdfcolumnsettings_result,$i,'taxmode'),
		'enabled' => $adb->query_result($pdfcolumnsettings_result,$i,$queryfieldname),
		'active' => $queryactivefield,
		'selected' => $queryselectfield
		);
	}
	$log->debug("Exit from the function getAllPDFColums($module)");
	return $pdfcolumnsettings;
}

/**	Function used to get the list of fonts available for PDF creation 
 *	@param string $module - module for which the PDF settings is requested
 *	return array $pdffonts - return all fonts as a array
 *       crm-now extension
 */
function getAllPDFFonts ()
{
	global $adb, $log;
	$log->debug("Entering into the function getAllPDFFonts");
	$pdffonts = Array();
	$pdffonts_result=$adb->pquery("select * from berli_pdffonts",'');
	$noofrows = $adb->num_rows($pdffonts_result);
	for($i=0;$i<$noofrows;$i++)
	{
		$pdffonts[$i]['fontid'] = $adb->query_result($pdffonts_result,$i,'fontid');
		$pdffonts[$i]['tcpdfname'] = $adb->query_result($pdffonts_result,$i,'tcpdfname');
		$pdffonts[$i]['namedisplay'] = $adb->query_result($pdffonts_result,$i,'namedisplay');
	}
	$log->debug("Exit from the function getAllPDFFonts ($pdffonts)");
	return $pdffonts;
}

/**	Function used to get the list of fonts available for PDF creation 
 *	@param string $module - module for which the PDF settings is requested
 *	return array $pdffonts - return all fonts as a array
 *      crm-now extension
 */
function getTCPDFFontsname ($fontsid)
{
	global $adb, $log;
	$log->debug("Entering into the function getTCPDFFontsname ($fontsid)");
	$pdffonts = Array();
	$pdffonts_name=$adb->pquery("SELECT * FROM `berli_pdffonts` WHERE `fontid` =?",array($fontsid));
	$fontsname = $adb->query_result($pdffonts_name,0,'tcpdfname');
	$log->debug("Exit from the function getAllPDFFonts ($fontsname)");
	return $fontsname;
}

/**	Function used to get the list of languages available for PDF creation based on existing language files
 *	@param string $module - module for which the PDF settings is requested
 *	return array $pdflanguages - return all languages as a array
 *       crm-now extension
 */
function getAllPDFlanguages ($module)
{
	global $adb, $log;
	$log->debug("Entering into the function getAllPDFlanguages");
	$pdflanguages = Array();
	$pdflangdir = "modules/".$module."/language/";
	if ( is_dir ( $pdflangdir )) {
	    if ( $handle = opendir($pdflangdir) ) {
	        while (($file = readdir($handle)) !== false) {
				//find the language files for pdf
				if(strpos($file,"pdf")!==false) {
					$string_pices = explode(".",$file);
					//get language key and name as array
					include($pdflangdir."$file");
					$pdflanguages[$string_pices[0]]= $pdf_strings['LANGUAGENAME'];
				}
			   $pdf_strings=0;
	        }
	        closedir($handle);
	    }
	}
	$log->debug("Exit from the function getAllPDFlanguages");
	return $pdflanguages;
}
/**
 * Function to get the Contact Name for PDF Output when a contact id is given 
 * Takes the input as $contact_id - contact id
 * returns the Contact <salutation> <firstname><lastname> in string format.
 */

function getContactforPDF($contact_id)
{
	global $log;
	$log->debug("Entering getContactName(".$contact_id.") method ...");
	$log->info("in getContactName ".$contact_id);

    global $adb;
	$contact_name = '';
	if($contact_id != '')
	{
        	$sql = "select * from vtiger_contactdetails where contactid=".$contact_id;
        	$result = $adb->query($sql);
        	$firstname = $adb->query_result($result,0,"firstname");
        	$lastname = $adb->query_result($result,0,"lastname");
        	$salutation = $adb->query_result($result,0,"salutation");
			//special action if salutation contains Dr. or Prof.
			if (trim($firstname)!=''){
				if (substr_count($salutation,"Dr."))
				 $contact_name = 'Dr. '.$firstname.' '.$lastname;
				elseif (substr_count($salutation,"Prof."))
				 $contact_name = 'Prof. '.$firstname.' '.$lastname;
				else
				$contact_name = $firstname.' '.$lastname;
			}
			else {
				if (strpos($salutation, 'Herr') !== false) $contact_salutation ='zu Hd. Herrn ';
				elseif (strpos($salutation, 'Frau')) $contact_salutation ='zu Hd. Frau ';
				else $contact_salutation ='';
				if (substr_count($salutation,'Dr.'))
				 $contact_name = $contact_salutation.'Dr. '.$lastname;
				elseif (substr_count($salutation,'Prof.'))
				 $contact_name = $contact_salutation.'Prof. '.$lastname;
				else
				$contact_name = $contact_salutation.' '.$lastname;
			}
	}
	$log->debug("Exiting getContactName method ...");
    return $contact_name;
}
/** Function to get the field label/permission array to construct the default orgnization field UI for the specified profile 
  * @param $fieldListResult -- mysql query result that contains the field label and uitype:: Type array
  * @param $lang_strings -- i18n language mod strings array:: Type array
  * @param $profileid -- profile id:: Type integer
  * @returns $standCustFld -- field label/permission array :: Type varchar
  *
 */	

function getPDFFieldList($module)
{
	global $adb, $log;
	$log->debug("Entering into the function getPDFFieldList($module)");
	$tabid=getTabid($module);
	$pdfsettings = Array();
	$pdfsettings_query="select * from berli_pdfsettings where pdfmodul='".$module."'";
 	$pdfsettings = $adb->pquery($pdfsettings_query,array());
	$noofpickrows = $adb->num_rows($pdfsettings);
	for($j = 0; $j < $noofpickrows; $j++) {
		$pdffieldlist[$adb->query_result($pdfsettings,$j,'pdffieldname')]= $adb->query_result($pdfsettings,$j,'pdfeditable');
	}
	return $pdffieldlist;
}

?>