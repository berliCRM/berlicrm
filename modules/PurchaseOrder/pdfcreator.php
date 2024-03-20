<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  crm-now, www.crm-now.com
* Portions created by crm-now are Copyright (C)  crm-now c/o im-netz Neue Medien GmbH.
* All Rights Reserved.
 *
 ********************************************************************************/
function createpdffile ($idnumber,$purpose='', $path='',$current_id='') {
	require_once('libraries/tcpdf/tcpdf.php');
	require_once('libraries/tcpdf/config/tcpdf_config.php');
	require_once('modules/PurchaseOrder/PurchaseOrder.php');
	require_once('modules/PurchaseOrder/pdf_templates/footer.php');
	require_once('include/database/PearDatabase.php');
	require_once('include/utils/InventoryUtils.php');
	require_once('modules/Pdfsettings/helpers/PDFutils.php');
	global $FOOTER_PAGE, $default_font, $font_size_footer, $NUM_FACTURE_NAME, $pdf_strings, $PurchaseOrder_no, $footer_margin;
	global $org_name, $org_address, $org_city, $org_code, $org_country, $org_irs, $org_taxid, $org_phone, $org_fax, $org_website;
	global $VAR40_NAME, $VAR3_NAME, $VAR4_NAME,$ORG_POSITION,$VAR_PAGE, $VAR_OF;
	//bank information - content
	global $bank_name , $bank_street , $bank_city ,$bank_zip ,$bank_country, $bank_account, $bank_routing, $bank_iban, $bank_swift;
	//bank information - labels from language files
	global $ACCOUNT_NUMBER, $ROUTING_NUMBER, $SWIFT_NUMBER, $IBAN_NUMBER;
	global $columns, $logoradio, $logo_name, $footerradio, $pageradio;
	global $adb,$app_strings,$focus,$current_user;
	$module = 'PurchaseOrder';
	//get the stored configuration values
	$pdf_config_details = getAllPDFDetails('PurchaseOrder');
	//set font
	$default_font = getTCPDFFontsname ($pdf_config_details['fontid']);
	if ($default_font =='') $default_font = 'freesans';
	$font_size_header = $pdf_config_details['fontsizeheader'];
	$font_size_address = $pdf_config_details['fontsizeaddress'];
	$font_size_body = $pdf_config_details['fontsizebody'];
	$font_size_footer = $pdf_config_details['fontsizefooter'];

	//get users data
	//select language file
	include_once("modules/PurchaseOrder/language/".$pdf_config_details['pdflang'].".lang.pdf.php");

	//internal number
	$id = $idnumber;

	//retreiving the PO info
	$focus = new PurchaseOrder();
	$focus->retrieve_entity_info($id,'PurchaseOrder');
	$vendor_name = decode_html(getVendorName($focus->column_fields['vendor_id']));
	//get vendors No
	$sql="SELECT vendor_no FROM vtiger_vendor where vendorid = ?";
	$result = $adb->pquery($sql, array($focus->column_fields['vendor_id']));
	$vendor_id = $adb->query_result($result,0,'vendor_no');
	$carriername = $focus->column_fields['carrier'];
	$PurchaseOrder_no = $focus->column_fields['purchaseorder_no'];
	//set currency format
	$sql="select currency_symbol, currency_code from vtiger_currency_info where id= ?";
	$curr_result = $adb->pquery($sql, array($focus->column_fields['currency_id']));
	$currency_symbol = $adb->query_result($curr_result, 0, 'currency_symbol');
	$currency_code = $adb->query_result($curr_result, 0, 'currency_code');
	switch($currency_code)
      {
         //European Format
         case "EUR":
            $decimal_precision = 2;
            $decimals_separator = ',';
            $thousands_separator = '.';
         break;
         //US Format
         case "USD":
            $decimal_precision = 2;
            $decimals_separator = '.';
            $thousands_separator = ',';
         break;
          default:
            $decimal_precision = 2;
            $decimals_separator = ',';
            $thousands_separator = '.';   
         break;
      }
	if(isset($current_user->currency_decimal_separator)) {
		$decimals_separator = $current_user->currency_decimal_separator;
	}

	if(isset($current_user->currency_grouping_separator)) {
		$thousands_separator = $current_user->currency_grouping_separator;
	}
	//get the PO date set
	$date_to_display_array = array (str_replace ("-",".",getValidDisplayDate(date("Y-m-d"))));
	$date_created = getValidDisplayDate($focus->column_fields['createdtime']); 
	$date_array = explode(" ",$date_created); 
	$date_to_display_array[1] = str_replace ("-",".",$date_array[0]);
	$date_modified = getValidDisplayDate($focus->column_fields['modifiedtime']);
	$date_array = explode(" ",$date_modified); 
	$date_to_display_array[2] = str_replace ("-",".",$date_array[0]);
	$date_to_display = $date_to_display_array[$pdf_config_details['dateused']];

	//number of lines after headline
	$space_headline = $pdf_config_details['space_headline'];

	//display logo?
	$logoradio = $pdf_config_details['logoradio'];

	//display summary?
	$summaryradio = $pdf_config_details['summaryradio'];

	//display footer?
	$footerradio = $pdf_config_details['footerradio'];
	//display footer page number?
	$pageradio = $pdf_config_details['pageradio'];

	//display requisition #?
	$req = $pdf_config_details['poname'];
	
	//display vendor id?
	$carrier = $pdf_config_details['carrier'];

	//display vendor id?
	$vendor = $pdf_config_details['clientid'];
	// get company information from settings
	$add_query = "select * from vtiger_organizationdetails";
	$result = $adb->pquery($add_query, array());
	$num_rows = $adb->num_rows($result);
	if($num_rows > 0)
	{
		$org_name = $adb->query_result($result,0,"organizationname");
		$org_address = $adb->query_result($result,0,"address");
		$org_city = $adb->query_result($result,0,"city");
		$org_state = $adb->query_result($result,0,"state");
		$org_country = $adb->query_result($result,0,"country");
		$org_code = $adb->query_result($result,0,"code");
		$org_phone = $adb->query_result($result,0,"phone");
		$org_fax = $adb->query_result($result,0,"fax");
		$org_taxid = $adb->query_result($result,0,"tax_id");
		$org_irs = $adb->query_result($result,0,"irs");
		$org_website = $adb->query_result($result,0,"website");

		$logo_name = decode_html($adb->query_result($result,0,"logoname"));
		$bank_name = $adb->query_result($result,0,"bankname");
		$bank_street = $adb->query_result($result,0,"bankstreet");
		$bank_city = $adb->query_result($result,0,"bankcity");
		$bank_zip = $adb->query_result($result,0,"bankzip");
		$bank_country = $adb->query_result($result,0,"bankcountry");
		$bank_account = $adb->query_result($result,0,"bankaccount");
		$bank_routing = $adb->query_result($result,0,"bankrouting");
		$bank_iban = $adb->query_result($result,0,"bankiban");
		$bank_swift = $adb->query_result($result,0,"bankswift");
	}
	// get owner information
	$recordOwnerArr = getRecordOwnerId ($_REQUEST['record']);
	foreach($recordOwnerArr as $type=>$id)
	{
		$ownertype=$type;
		$ownerid=$id;
	}
	if($ownertype == 'Users')
	{
		// get owner information for user
		$sql="SELECT * FROM vtiger_users,vtiger_crmentity WHERE vtiger_users.id = vtiger_crmentity.smownerid AND vtiger_crmentity.crmid = ? ";
		$result = $adb->pquery($sql, array($_REQUEST['record']));
		$owner_lastname = $adb->query_result($result,0,'last_name');
		$owner_firstname = $adb->query_result($result,0,'first_name');
		$owner_id = $adb->query_result($result,0,'smownerid');
		$owner_phone = $adb->query_result($result,0,'phone_work');
		$owner_title = decode_html(trim($adb->query_result($result,0,'title')));
	}
	else {
	// get owner information for Groups
		$sql="SELECT * FROM vtiger_groups,vtiger_crmentity WHERE vtiger_groups.groupid  = vtiger_crmentity.smownerid AND vtiger_crmentity.crmid = ? ";
		$result = $adb->pquery($sql, array($_REQUEST['record']));
		$owner_lastname = '';
		$owner_firstname = $adb->query_result($result,0,'groupname');
		$owner_id = $adb->query_result($result,0,'smownerid');
		$owner_phone = $org_phone;
		$owner_title = '';
	}
	//display owner?
	$owner = $pdf_config_details['owner'];
	//display owner phone#?
	$ownerphone = $pdf_config_details['ownerphone'];
	//to display at product description based on tax type
	$gproddetailarray = array($pdf_config_details['gprodname'],$pdf_config_details['gproddes'],$pdf_config_details['gprodcom']);
	$gproddetails = 0;
	foreach($gproddetailarray as $key=>$value){
		if ($value=='true') {
			if ($key==0) $gproddetails = $gproddetails + 1;
			else $gproddetails = $gproddetails + $key*2;
		}
	}
	$iproddetails = 0;
	$iproddetailarray = array($pdf_config_details['iprodname'],$pdf_config_details['iproddes'],$pdf_config_details['iprodcom']);
	foreach($iproddetailarray as $key=>$value){
		if ($value=='true') {
			if ($key==0) $iproddetails = $iproddetails + 1;
			else $iproddetails = $iproddetails + $key*2;
		}
	}

	// PO Requisition Nummer 
	$subject = $focus->column_fields['requisition_no'];


	if($focus->column_fields["hdnTaxType"] == "individual") {
	        $product_taxes = 'true';
	} else {
	        $product_taxes = 'false';
	}

	// **************** BEGIN POPULATE DATA ********************
	$requisition_no = $focus->column_fields["requisition_no"];
	$customermark = $focus->column_fields["tracking_no"];
	
	$valid_till = $focus->column_fields["duedate"];
	$valid_till = str_replace ("-",".",getValidDisplayDate($valid_till));
	$bill_street = decode_html($focus->column_fields["bill_street"]);
	$bill_city = decode_html($focus->column_fields["bill_city"]);
	$bill_state = decode_html($focus->column_fields["bill_state"]);
	$bill_code = decode_html($focus->column_fields["bill_code"]);
	$bill_country = decode_html($focus->column_fields["bill_country"]);

	//format contact name
	$contact_name =decode_html(getContactforPDF($focus->column_fields["contact_id"]));
	//get department of contact or account, contact wins
	$contact_department = '';
	//get contact department
	if(trim($focus->column_fields["contact_id"]) != '')
	{
        	$sql = "select * from vtiger_contactdetails where contactid= ?";
        	$result = $adb->pquery($sql, array($focus->column_fields["contact_id"]));
         	$contact_department = decode_html(trim($adb->query_result($result,0,"department")));
	        $contact_firstname = decode_html(trim($adb->query_result($result,0,"firstname")));
	        $contact_lastname = decode_html(trim($adb->query_result($result,0,"lastname")));
	        $contact_salutation = decode_html(trim($adb->query_result($result,0,"salutation")));
	}
		
	$ship_street = $focus->column_fields["ship_street"];
	$ship_city = $focus->column_fields["ship_city"];
	$ship_state = $focus->column_fields["ship_state"];
	$ship_code = $focus->column_fields["ship_code"];
	$ship_country = $focus->column_fields["ship_country"];

	// condition field for last page
	$conditions = decode_html($focus->column_fields["terms_conditions"]);
	// description field for first page
	$description = decode_html($focus->column_fields["description"]);

	// ************************ BEGIN POPULATE DATA ***************************
	//get the Associated Products for this Purchase Order
	$focus->id = $focus->column_fields["record_id"];
	$associated_products = getAssociatedProducts("PurchaseOrder",$focus);
	$num_products = count($associated_products);

	//This $final_details array will contain the final total, discount, Group Tax, S&H charge, S&H taxes and adjustment
	$final_details = $associated_products[1]['final_details'];

	//getting the Net Total
	$price_subtotal = $final_details["hdnSubTotal"];
	$price_subtotal_formated = number_format($price_subtotal,$decimal_precision,$decimals_separator,$thousands_separator);

	//Final discount amount/percentage
	$discount_amount = $final_details["discount_amount_final"];
	$discount_percent = $final_details["discount_percentage_final"];

	if($discount_amount != "" AND $discount_amount != "0.00") {
		$price_discount = $discount_amount;
		$price_discount_formated = number_format($price_discount,$decimal_precision,$decimals_separator,$thousands_separator);
		}
	else if($discount_percent != "" AND $discount_percent != "0.00"){
		//This will be displayed near Discount label 
		$final_price_discount_percent = "(".number_format($discount_percent,$decimal_precision,$decimals_separator,$thousands_separator)." %)";
		$price_discount = ($discount_percent*$final_details["hdnSubTotal"])/100;
		$price_discount_formated = number_format($price_discount,$decimal_precision,$decimals_separator,$thousands_separator);
	}
	else {
		$price_discount = "0.00";
	}
	//Adjustment
	$price_adjustment = $final_details["adjustment"];
	$price_adjustment_formated = number_format($price_adjustment,$decimal_precision,$decimals_separator,$thousands_separator);
	//Grand Total
	$price_total = $final_details["grandTotal"];
	$price_total_formated = number_format($price_total,$decimal_precision,$decimals_separator,$thousands_separator);

	//To calculate the group tax amount
	if($final_details['taxtype'] == 'group')
	{
		$group_tax_total = $final_details['tax_totalamount'];
		$price_salestax = $group_tax_total;
		$price_salestax_formated = number_format($price_salestax,$decimal_precision,$decimals_separator,$thousands_separator);

		$group_total_tax_percent = '0.00';
		$group_tax_details = $final_details['taxes'];
		for($i=0;$i<count($group_tax_details);$i++)
		{
			$group_total_tax_percent = $group_total_tax_percent+$group_tax_details[$i]['percentage'];
		}
	}

	//S&H amount
	$sh_amount = $final_details['shipping_handling_charge'];
	$price_shipping_formated = number_format($sh_amount,$decimal_precision,$decimals_separator,$thousands_separator);

	//S&H taxes
	$sh_tax_details = $final_details['sh_taxes'];
	$sh_tax_percent = '0.00';
	for($i=0;$i<count($sh_tax_details);$i++)
	{
		$sh_tax_percent = $sh_tax_percent + $sh_tax_details[$i]['percentage'];
	}
	$sh_tax_amount = $final_details['shtax_totalamount'];
	$price_shipping_tax = number_format($sh_tax_amount,$decimal_precision,$decimals_separator,$thousands_separator);

	//to calculate the individuel tax amounts included we should get all available taxes and then retrieve the corresponding tax values
	$tax_details = getAllTaxes('available');
	$numer_of_tax_types = count($tax_details);
	for($tax_count=0;$tax_count<count($tax_details);$tax_count++)
	{
		$taxtype_listings['taxname'.$tax_count] = $tax_details[$tax_count]['taxname'];
		$taxtype_listings['percentage'.$tax_count] = $tax_details[$tax_count]['percentage'];
		$taxtype_listings['value'.$tax_count] = '0';
	}
	//This is to get all prodcut details as row basis
	for($i=1,$j=$i-1;$i<=$num_products;$i++,$j++)
	{
		$product_code[$i] = $associated_products[$i]['hdnProductcode'.$i];
		$product_name[$i] = decode_html($associated_products[$i]['productName'.$i]);
		$prod_description[$i] = decode_html($associated_products[$i]['productDescription'.$i]);
		$qty[$i] = $associated_products[$i]['qty'.$i];
		$qty_formated[$i] = number_format($associated_products[$i]['qty'.$i],$decimal_precision,$decimals_separator,$thousands_separator);
		$comment[$i] = decode_html($associated_products[$i]['comment'.$i]);
		$unit_price[$i] = number_format($associated_products[$i]['unitPrice'.$i],$decimal_precision,$decimals_separator,$thousands_separator);
		$list_price[$i] = number_format($associated_products[$i]['listPrice'.$i],$decimal_precision,$decimals_separator,$thousands_separator);
		$list_pricet[$i] = $associated_products[$i]['listPrice'.$i];
		$discount_total[$i] = $associated_products[$i]['discountTotal'.$i];
		$discount_totalformated[$i] = number_format($associated_products[$i]['discountTotal'.$i],$decimal_precision,$decimals_separator,$thousands_separator);
		//added by crm-now
		$usageunit[$i] = $associated_products[$i]['usageunit'.$i];
		//look whether the entry already exists, if the translated string is available then the translated string other wise original string will be returned
		$usageunit[$i] = getTranslatedString($usageunit[$i], 'Products');
		$taxable_total = $qty[$i]*$list_pricet[$i]-$discount_total[$i];
		//get subproducts if exists
		if (!empty($associated_products[$i]['subProductArray'.$i])) {
			$subProductArray[$i] = $associated_products[$i]['subProductArray'.$i];
		}
		else {
			$subProductArray[$i] = '';
		}
		//create a subProduct string to be added to the main product
		$subProdString = array();
		if (is_array ($subProductArray[$i]) && count($subProductArray[$i]) > 0) {
			for($subprod_count=0;$subprod_count<count($subProductArray[$i]);$subprod_count++) {
				if ($subProductArray[$i][$subprod_count]!='') {
					$subProdString[$i] .= "- ".$subProductArray[$i][$subprod_count]."\n";
				}
			}
		}
		$producttotal = $taxable_total;
		$total_taxes = '0.00';
		
		
		if($focus->column_fields["hdnTaxType"] == "individual")
		{
			$total_tax_percent = '0.00';
			//This loop is to get all tax percentage and then calculate the total of all taxes
			for($tax_count=0;$tax_count<count($associated_products[$i]['taxes']);$tax_count++)
			{
				$tax_percent = $associated_products[$i]['taxes'][$tax_count]['percentage'];
				$total_tax_percent = $total_tax_percent+$tax_percent;
				$tax_amount = (($taxable_total*$tax_percent)/100);
				//calculate the tax amount for any available tax percentage
				$detected_tax = substr(array_search ($total_tax_percent,$taxtype_listings), -1);
				$taxtype_listings ['value'.$detected_tax] = $taxtype_listings ['value'.$detected_tax]+$tax_amount;
				$total_taxes = $total_taxes+$tax_amount;
			}
			$producttotal = $taxable_total+$total_taxes;
			$product_line[$j][$pdf_strings['Tax']] = " ($total_tax_percent %) ".number_format($total_taxes,$decimal_precision,$decimals_separator,$thousands_separator);
		    // combine product name, description and comment to one field based on settings
		}

	    // combine product name, description and comment to one field based on settings
		if($focus->column_fields["hdnTaxType"] == "individual") {
			$product_selection = $iproddetails;
		}
		else {
			$product_selection = $gproddetails;
		}
		if (!isset ($subProdString[$i])) {
			$subProdString[$i] ='';
		}
		switch($product_selection)
			{
			    case 1:
						$product_name_long[$i] = $product_name[$i];
			    break;
			    case 2:
						$product_name_long[$i] = $prod_description[$i]."\n".$subProdString[$i];
			    break;
			    case 3:
						$product_name_long[$i] = $product_name[$i]."\n".$prod_description[$i]."\n".$subProdString[$i];
			    break;
			    case 4:
						$product_name_long[$i] = $comment[$i];
			    break;
			    case 5:
						$product_name_long[$i] = $product_name[$i]."\n".$comment[$i];
			    break;
			    case 6:
					if ($prod_description[$i]!=''){
						$product_name_long[$i] = $prod_description[$i]."\n".$subProdString[$i].$comment[$i];
						}
					else
						$product_name_long[$i] = $comment[$i];
			    break;
			    case 7:
					if ($prod_description[$i]!=''){
						$product_name_long[$i] = $product_name[$i]."\n".$prod_description[$i]."\n".$subProdString[$i].$comment[$i];
						}
					else
						$product_name_long[$i] = $product_name[$i]."\n".$subProdString[$i].$comment[$i];
			    break;
			    default:
					if ($prod_description[$i]!=''){
						$product_name_long[$i] = $product_name[$i]."\n".$prod_description[$i]."\n".$subProdString[$i].$comment[$i];
						}
					else
						$product_name_long[$i] = $product_name[$i]."\n".$subProdString[$i].$comment[$i];
			    break;
		}

		$prod_total[$i] = number_format($producttotal,$decimal_precision,$decimals_separator,$thousands_separator);

		$product_line[$j][$pdf_strings['Position']] = $j+1;
		$product_line[$j][$pdf_strings['OrderCode']] = $product_code[$i];
		$product_line[$j][$pdf_strings['Description']] = $product_name_long[$i];
		$product_line[$j][$pdf_strings['Qty']] = $qty_formated[$i];
		$product_line[$j][$pdf_strings['Unit']] = $usageunit[$i];
		$product_line[$j][$pdf_strings['UnitPrice']] = $list_price[$i];
		$product_line[$j][$pdf_strings['Discount']] = $discount_totalformated[$i];
		$product_line[$j][$pdf_strings['LineTotal']] = $prod_total[$i];

	}

	//Population of current date
	$addyear = strtotime("+0 year");
	$dat_fmt = (($current_user->date_format == '')?('dd-mm-yyyy'):($current_user->date_format));
	$date_issued = (($dat_fmt == 'dd-mm-yyyy')?(date('d-m-Y',$addyear)):(($dat_fmt == 'mm-dd-yyyy')?(date('m-d-Y',$addyear)):(($dat_fmt == 'yyyy-mm-dd')?(date('Y-m-d', $addyear)):(''))));


	// ************************ END POPULATE DATA ***************************
	//************************BEGIN PDF FORMATING**************************
	// Extend the TCPDF class to create custom Header and Footer
	$page_num='1';
	// create new PDF document
	//$pdf = new PDF( 'P', 'mm', 'A4' );
	$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true); 
	// set font
	$pdf->SetFont($default_font, " ", $font_size_body);
	$pdf->setPrintHeader(0); //header switched off permanently
	// auto break on
	//$pdf->SetAutoPageBreak(true); 
	// set footer fonts
	//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

	//set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
	// set pdf information
	$pdf-> SetTitle ($pdf_strings['FACTURE'].": ".$vendor_name);
	$pdf-> SetAuthor ($owner_firstname." ".$owner_lastname.", ".$org_name);
	$pdf-> SetSubject ($vendor_name);
	$pdf-> SetCreator ('CRM System berliCRM: www.crm-now.de ');
	//list product names as keywords
	$productlisting = implode(", ",$product_name);
	$pdf-> SetKeywords ($productlisting);
	//Disable automatic page break
	$pdf->SetAutoPageBreak(true,PDF_MARGIN_FOOTER);
	//set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 
	//set some language-dependent strings
	$pdf->setLanguageArray($pdf_config_details['pdflang']); 
	//initialize document

	//in reference to body.php -> if a new page must be added if the space available for summary is too small
	$new_page_started = false;
	$pdf->AddPage();
	$pdf-> setImageScale(1.5);
	//$pdf->SetY(PDF_MARGIN_HEADER);
	include("modules/PurchaseOrder/pdf_templates/header.php");
	$pdf->SetFont($default_font, " ", $font_size_body);
	include("modules/PurchaseOrder/pdf_templates/body.php");
	//formating company name for file name
	$export_org = utf8_decode($vendor_name);
	$export_org = decode_html(strtolower($export_org));
    $export_org = str_replace(array(" ","ö","ä","ü","ß","Ö","Ä","Ü","/","\\"),array("_","oe","ae","ue","ss","Oe","Ae","Ue","_","_"),$export_org);
	//remove not printable ascii char
	$export_org = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $export_org);

	if ($purpose=='save') {
		// save PDF file at PO
		$pdf->Output($path.$current_id."_".$export_org.'_'.$pdf_strings['FACTURE'].'_'.$date_issued.'.pdf','F'); 
		return $export_org.'_'.$pdf_strings['FACTURE'].'_'.$date_issued.'.pdf';
	}
	// issue pdf
	elseif ($purpose=='print')
	$pdf->Output($export_org.'_'.$pdf_strings['FACTURE'].'_'.$date_issued.'.pdf','D'); 
	elseif ($purpose=='send'){
		// send pdf with mail
		$translatedName = vtranslate('SINGLE_PurchaseOrder', 'PurchaseOrder');
		$pdf->Output('storage/'.$translatedName.'_'.$PurchaseOrder_no.'.pdf','F'); 
		return;
	}
	exit;
}
?>