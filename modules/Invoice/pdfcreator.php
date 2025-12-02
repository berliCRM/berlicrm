<?php

/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  crm-now, www.crm-now.com
* Portions created by crm-now are Copyright (C)  crm-now c/o im-netz Neue Medien GmbH.
* All Rights Reserved.
 *
 ********************************************************************************/
//
// this file is included wherever a PDF is needed ... and then the function createpdffile() is called
//
use Sprain\SwissQrBill as QrBill;
use Symfony\Component\Intl\Countries;

function createpdffile($idnumber, $purpose = '', $path = __DIR__ . '/', $current_id = '')
{
    global $qr_feature; // hast to be set in config.inc.php

    // needed for e-invoice extension
    $eInvoice = false;
    if (is_dir('vendor/horstoeko')) {
        $eInvoice = true;
        $eInvoiceXmlFile = "modules/Invoice/xrechnung.xml";
        require_once('vendor/autoload.php');
        // e-invoice settings from config.inc.php
        global $default_export_e_invoice, $e_invoice_watermark_pdf;
        if (empty($default_export_e_invoice)) {
            $default_export_e_invoice = 'zugferd'; // defaults to zugferd
        }
    }
    if (!is_dir('vendor/tecnickcom/tcpdf')) {
        require_once('libraries/tcpdf/tcpdf.php');
        require_once('libraries/tcpdf/config/tcpdf_config.php');
    } else {
        define('PDF_MARGIN_FOOTER', 40);
    }
    require_once('modules/Invoice/Invoice.php');
    require_once('modules/Invoice/pdf_templates/footer.php');
    require_once('include/database/PearDatabase.php');
    require_once('include/utils/InventoryUtils.php');
    require_once('modules/Pdfsettings/helpers/PDFutils.php');

    // try to auto-set $qr_feature if not already set in config.inc.php
    if (!isset($qr_feature)) {
        /*
                                                                                                                          if (is_dir('vendor/sprain/swiss-qr-bill')) {
                                                                                                                              $qr_feature = true;
                                                                                                                          } else {
                                                                                                                              $qr_feature = false;
                                                                                                                          }
                                                                                                                          */
        $qr_feature = false;
    }

    global $FOOTER_PAGE, $default_font, $font_size_footer, $NUM_FACTURE_NAME, $pdf_strings, $quote_no, $footer_margin;

    // these vars for company details will be set as globals later on ...
    $pdfGlobals = [
        'org_management' => 'management',
        'org_irsname' => 'irsname', // Internal Revenue Service (Tax Authority)
        'org_name' => 'organizationname',
        'org_address' => 'address',
        'org_city' => 'city',
        'org_code' => 'code',
        'org_country' => 'country',
        'org_vatid' => 'vatid',
        'org_state' => 'state',
        'org_taxid' => 'tax_id',
        'org_phone' => 'phone',
        'org_website' => 'website',
        'org_fax' => 'fax',
        'logo_name' => 'logoname',
        'bank_name' => 'bankname',
        'bank_street' => 'bankstreet',
        'bank_city' => 'bankcity',
        'bank_zip' => 'bankzip',
        'bank_country' => 'bankcountry',
        'bank_account' => 'bankaccount',
        'bank_routing' => 'bankrouting',
        'bank_iban' => 'bankiban',
        'bank_swift' => 'bankswift',
    ];

    global $ORG_POSITION, $VAR_PAGE, $VAR_OF, $invoice_status;
    //bank information - labels from language files
    global $ACCOUNT_NUMBER, $ROUTING_NUMBER, $SWIFT_NUMBER, $IBAN_NUMBER;
    global $columns, $logoradio, $logo_name, $footerradio, $pageradio;
    global $adb, $app_strings, $focus, $current_user, $invoice_no, $purposefooter;
    $module = 'Invoice';

    //get the stored configuration values
    $pdf_config_details = getAllPDFDetails('Invoice');
    //set font
    $default_font = getTCPDFFontsname($pdf_config_details['fontid']);
    if ($default_font == '') {
        $default_font = 'freesans';
    }
    $font_size_header = $pdf_config_details['fontsizeheader'];
    $font_size_address = $pdf_config_details['fontsizeaddress'];
    $font_size_body = $pdf_config_details['fontsizebody'];
    $font_size_footer = $pdf_config_details['fontsizefooter'];

    //get users data
    //select language file
    include_once("modules/Invoice/language/" . $pdf_config_details['pdflang'] . ".lang.pdf.php");

    //footer: store purpose
    $purposefooter = $purpose;

    //internal number
    $id = $idnumber;

    //retreiving the Invoice  info
    $focus = new Invoice();
    $focus->retrieve_entity_info($id, "Invoice");
    // get several values from the account: account name, buyer reference, account number
    $sql = "select accountname, buyerreference, account_no from  vtiger_account where accountid= ?";
    $acc_result = $adb->pquery($sql, array($focus->column_fields['account_id']));
    $account_name = decode_html($adb->query_result($acc_result, 0, 'accountname'));
    $buyer_reference = decode_html($adb->query_result($acc_result, 0, 'buyerreference'));
    $account_no = decode_html($adb->query_result($acc_result, 0, 'account_no'));

    $invoice_no = $focus->column_fields['invoice_no'];
    //set currency format
    $sql = "select currency_symbol, currency_code from vtiger_currency_info where id= ?";
    $curr_result = $adb->pquery($sql, array($focus->column_fields['currency_id']));
    $currency_symbol = $adb->query_result($curr_result, 0, 'currency_symbol');
    $currency_code = $adb->query_result($curr_result, 0, 'currency_code');
    switch ($currency_code) {
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
            // CANADA Format
        case "CAD":
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
    if (isset($current_user->currency_decimal_separator)) {
        $decimals_separator = $current_user->currency_decimal_separator;
    }

    if (isset($current_user->currency_grouping_separator)) {
        $thousands_separator = $current_user->currency_grouping_separator;
    }

    // credit note?
    $invoice_status = $focus->column_fields["invoicestatus"];

    // get invoice date
    $invoice_date = $focus->column_fields["invoicedate"];
    $invoice_date = getValidDisplayDate($invoice_date);
    $invoice_date = str_replace("-", ".", $invoice_date);

    //number of lines after headline
    $space_headline = $pdf_config_details['space_headline'];

    //display logo?
    $logoradio = $pdf_config_details['logoradio'];

    //get PO name
    $poname = $pdf_config_details['poname'];

    // get client id
    $clientid = $pdf_config_details['clientid'];

    //display summary?
    $summaryradio = $pdf_config_details['summaryradio'];

    //display footer?
    $footerradio = $pdf_config_details['footerradio'];
    //display footer page number?
    $pageradio = $pdf_config_details['pageradio'];
    // get company, tax and bank information from settings
    $add_query = "select * from vtiger_organizationdetails";
    $result = $adb->pquery($add_query, array());
    $num_rows = $adb->num_rows($result);

    //
    // using array of pdf globals to assign company
    // values to global variables for later use
    //
    foreach ($pdfGlobals as $key => $value) {
        if ($num_rows > 0) {
            $GLOBALS[$key] = decode_html($adb->query_result($result, 0, $value));
        } else {
            // if no company information is set, set a default value
            $GLOBALS[$key] = "$value not set";
        }
        global $$key; // set var as global
    }

    // get owner information
    $recordOwnerArr = getRecordOwnerId($_REQUEST['record']);
    foreach ($recordOwnerArr as $type => $id) {
        $ownertype = $type;
        $ownerid = $id;
    }
    if ($ownertype == 'Users') {
        // get owner information for user
        $sql = "SELECT * FROM vtiger_users,vtiger_crmentity WHERE vtiger_users.id = vtiger_crmentity.smownerid AND vtiger_crmentity.crmid = ? ";
        $result = $adb->pquery($sql, array($_REQUEST['record']));
        $owner_lastname = $adb->query_result($result, 0, 'last_name');
        $owner_firstname = $adb->query_result($result, 0, 'first_name');
        $owner_id = $adb->query_result($result, 0, 'smownerid');
        $owner_phone = $adb->query_result($result, 0, 'phone_work');
        $owner_mail = $adb->query_result($result, 0, 'email1');
        $owner_title = decode_html(trim($adb->query_result($result, 0, 'title')));
    } else {
        // get owner information for Groups
        $sql = "SELECT * FROM vtiger_groups,vtiger_crmentity WHERE vtiger_groups.groupid  = vtiger_crmentity.smownerid AND vtiger_crmentity.crmid = ? ";
        $result = $adb->pquery($sql, array($_REQUEST['record']));
        $owner_lastname = '';
        $owner_firstname = $adb->query_result($result, 0, 'groupname');
        $owner_id = $adb->query_result($result, 0, 'smownerid');
        $owner_phone = $org_phone;
        $owner_title = '';
    }
    //display owner?
    $owner = $pdf_config_details['owner'];
    //display owner phone#?
    $ownerphone = $pdf_config_details['ownerphone'];
    //to display at product description based on tax type
    $gproddetailarray = array($pdf_config_details['gprodname'], $pdf_config_details['gproddes'], $pdf_config_details['gprodcom']);
    $gproddetails = 0;
    foreach ($gproddetailarray as $key => $value) {
        if ($value == 'true') {
            if ($key == 0) {
                $gproddetails = $gproddetails + 1;
            } else {
                $gproddetails = $gproddetails + $key * 2;
            }
        }
    }
    $iproddetails = 0;
    $iproddetailarray = array($pdf_config_details['iprodname'], $pdf_config_details['iproddes'], $pdf_config_details['iprodcom']);
    foreach ($iproddetailarray as $key => $value) {
        if ($value == 'true') {
            if ($key == 0) {
                $iproddetails = $iproddetails + 1;
            } else {
                $iproddetails = $iproddetails + $key * 2;
            }
        }
    }

    // SO Requisition Nummer
    $requisition_no = $focus->column_fields['vtiger_purchaseorder'];
    // CustomerMark
    $customermark = $focus->column_fields['customerno'];
    // get related Sales Order infomation (modified time)
    $salesorder_id = $focus->column_fields['salesorder_id'];

    if ($focus->column_fields["hdnTaxType"] == "individual") {
        $product_taxes = 'true';
    } else {
        $product_taxes = 'false';
    }

    // **************** BEGIN POPULATE DATA ********************
    $account_id = $focus->column_fields['account_id'];

    $valid_till = $focus->column_fields["duedate"];
    if ($valid_till != '') {
        $valid_till = getValidDisplayDate($valid_till);
    }
    $valid_till = str_replace("-", ".", $valid_till);

    $bill_street = decode_html($focus->column_fields["bill_street"]);
    $bill_city = decode_html($focus->column_fields["bill_city"]);
    $bill_state = decode_html($focus->column_fields["bill_state"]);
    $bill_code = decode_html($focus->column_fields["bill_code"]);
    $bill_country = decode_html($focus->column_fields["bill_country"]);

    //format contact name
    $contact_name = decode_html(getContactforPDF($focus->column_fields["contact_id"]));
    //get department of contact or account, contact wins
    $contact_department = '';
    //get contact department
    if (trim($focus->column_fields["contact_id"]) != '') {
        $sql = "select * from vtiger_contactdetails where contactid= ?";
        $result = $adb->pquery($sql, array($focus->column_fields["contact_id"]));
        $contact_department = decode_html(trim($adb->query_result($result, 0, "department")));
        $contact_firstname = decode_html(trim($adb->query_result($result, 0, "firstname")));
        $contact_lastname = decode_html(trim($adb->query_result($result, 0, "lastname")));
        $contact_salutation = decode_html(trim($adb->query_result($result, 0, "salutation")));

    }
    //get account department
    if ($contact_department == '' and trim($account_id) != '') {
        $sql = "select * from vtiger_account where accountid= ?";
        $result = $adb->pquery($sql, array($account_id));
        $contact_department = decode_html(trim($adb->query_result($result, 0, "tickersymbol")));
    }

    $ship_street = $focus->column_fields["ship_street"];
    $ship_city = $focus->column_fields["ship_city"];
    $ship_state = $focus->column_fields["ship_state"];
    $ship_code = $focus->column_fields["ship_code"];
    $ship_country = $focus->column_fields["ship_country"];

    // condition field for last page
    $conditions = decode_html($focus->column_fields["terms_conditions"]);
    // description field for first page
    $description = str_replace(['$contacts-salutation$', '$contacts-lastname$', '$contacts-firstname$'], [$contact_salutation, $contact_lastname, $contact_firstname], decode_html($focus->column_fields["description"]));

    // ************************ BEGIN POPULATE DATA ***************************
    //get the Associated Products for this Invoice
    $invoice_received = (float) $focus->column_fields['received'];
    $focus->id = $focus->column_fields["record_id"];
    $associated_products = getAssociatedProducts("Invoice", $focus);
    $num_products = count($associated_products);

    //This $final_details array will contain the final total, discount, Group Tax, S&H charge, S&H taxes and adjustment
    $final_details = $associated_products[1]['final_details'];

    //getting the Net Total
    $price_subtotal = $final_details["hdnSubTotal"];
    $price_subtotal_formated = number_format($price_subtotal, $decimal_precision, $decimals_separator, $thousands_separator);

    //Final discount amount/percentage
    $discount_amount = $final_details["discountTotal_final"];
    $discount_percent = $final_details["discount_percentage_final"];

    if ($discount_amount != "" and $discount_amount != "0.00") {
        $price_discount = $discount_amount;
        $price_discount_formated = number_format($price_discount, $decimal_precision, $decimals_separator, $thousands_separator);
    } elseif ($discount_percent != "" and $discount_percent != "0.00") {
        //This will be displayed near Discount label
        $final_price_discount_percent = "(" . number_format($discount_percent, $decimal_precision, $decimals_separator, $thousands_separator) . " %)";
        $price_discount = ($discount_percent * $final_details["hdnSubTotal"]) / 100;
        $price_discount_formated = number_format($price_discount, $decimal_precision, $decimals_separator, $thousands_separator);
    } else {
        $price_discount = "0.00";
    }
    // calculate netto price
    $nettoprice = $price_subtotal - $price_discount;
    $nettoprice_formated = number_format($nettoprice, $decimal_precision, $decimals_separator, $thousands_separator);
    //Adjustment
    $price_adjustment = $final_details["adjustment"];
    $price_adjustment_formated = number_format($price_adjustment, $decimal_precision, $decimals_separator, $thousands_separator);
    //Grand Total
    $price_total = $final_details["grandTotal"];
    $price_total_formated = number_format($price_total, $decimal_precision, $decimals_separator, $thousands_separator);

    //To calculate the group tax amount
    if ($final_details['taxtype'] == 'group') {
        $group_tax_total = $final_details['tax_totalamount'];
        $price_salestax = $group_tax_total;
        $price_salestax_formated = number_format($price_salestax, $decimal_precision, $decimals_separator, $thousands_separator);

        $group_total_tax_percent = '0.00';
        $group_tax_details = $final_details['taxes'];
        for ($i = 0; $i < count($group_tax_details); $i++) {
            $group_total_tax_percent = $group_total_tax_percent + $group_tax_details[$i]['percentage'];
        }
    }

    //S&H amount
    $sh_amount = $final_details['shipping_handling_charge'];
    $price_shipping_formated = number_format($sh_amount, $decimal_precision, $decimals_separator, $thousands_separator);

    //S&H taxes
    $sh_tax_details = $final_details['sh_taxes'];
    $sh_tax_percent = '0.00';
    for ($i = 0; $i < count($sh_tax_details); $i++) {
        $sh_tax_percent = $sh_tax_percent + $sh_tax_details[$i]['percentage'];
    }
    $sh_tax_amount = $final_details['shtax_totalamount'];
    $price_shipping_tax = number_format($sh_tax_amount, $decimal_precision, $decimals_separator, $thousands_separator);

    //to calculate the individuel tax amounts included we should get all available taxes and then retrieve the corresponding tax values
    $tax_details = getAllTaxes('available');
    $numer_of_tax_types = count($tax_details);
    for ($tax_count = 0; $tax_count < count($tax_details); $tax_count++) {
        $taxtype_listings['taxname' . $tax_count] = $tax_details[$tax_count]['taxname'];
        $taxtype_listings['percentage' . $tax_count] = $tax_details[$tax_count]['percentage'];
        $taxtype_listings['value' . $tax_count] = '0';
    }

    //Population of current date
    $addyear = strtotime("+0 year");
    if ($purpose == 'customerportal') {
        $dat_fmt = (($owner_id->date_format == '') ? ('dd-mm-yyyy') : ($owner_id->date_format));
    } else {
        $dat_fmt = (($current_user->date_format == '') ? ('dd-mm-yyyy') : ($current_user->date_format));
    }
    $date_issued = (($dat_fmt == 'dd-mm-yyyy') ? (date('d-m-Y', $addyear)) : (($dat_fmt == 'mm-dd-yyyy') ? (date('m-d-Y', $addyear)) : (($dat_fmt == 'yyyy-mm-dd') ? (date('Y-m-d', $addyear)) : (''))));

    //special output for shippig note
    if ($purpose == 'printsn' or $purpose == 'savesn') {
        // no summary
        $summaryradio = 'false';
        // no description
    }
    // ************************ END POPULATE DATA ***************************

    if ($eInvoice) {

        // get country ISO code from country name
        $org_country_iso = getCountryISOCode($org_country);
        // get bill country ISO code from bill country name
        $bill_country_iso = getCountryISOCode($bill_country);

        if ($default_export_e_invoice == "zugferd") {
            $eInvoiceDocument = horstoeko\zugferd\ZugferdDocumentBuilder::CreateNew(horstoeko\zugferd\ZugferdProfiles::PROFILE_EXTENDED);
        } else {
            $eInvoiceDocument = horstoeko\zugferd\ZugferdDocumentBuilder::CreateNew(horstoeko\zugferd\ZugferdProfiles::PROFILE_XRECHNUNG_3);
        }

        if (empty($valid_till)) {
            $valid_till = $invoice_date;
        }
        // set document information
        $eInvoiceDocument
            ->setDocumentInformation($invoice_no, "380", \DateTime::createFromFormat('d.m.Y', $invoice_date), "EUR")
            ->addDocumentNote($org_name . ' | ' . $org_address . ' | ' . $org_code . ' ' . $org_city . ' | ' . $org_country . ' | ' . $org_management . ' | ' . $org_taxid, null, 'REG')
            ->setDocumentSupplyChainEvent(\DateTime::createFromFormat('d.m.Y', $invoice_date))
            ->setDocumentSeller($org_name, $org_taxid)
            ->addDocumentSellerGlobalId($org_taxid, "0088") // ! should be german HRB number instead of $org_taxid
            // ->addDocumentSellerGlobalId($HRB, "9930") // ! should be german HRB number instead of $org_taxid
            ->addDocumentSellerTaxRegistration("VA", $org_taxid)
            ->addDocumentPaymentTerm($description, \DateTime::createFromFormat('d.m.Y', $valid_till))
            ->setDocumentSellerAddress($org_address, "", "", $org_code, $org_city, $org_country_iso)
            ->setDocumentSellerContact($owner_firstname . ' ' . $owner_lastname, "", $org_phone, $org_fax, $owner_mail)
            // ->setDocumentBuyer($contact_salutation . " " . $contact_firstname . " " . $contact_lastname, $account_no)
            ->setDocumentBuyer($account_name, $account_no)
            ->setDocumentBuyerReference($buyer_reference)
            ->setDocumentBuyerAddress($bill_street, "", "", $bill_code, $bill_city, $bill_country_iso)
            ->addDocumentTax("S", "VAT", $price_subtotal, ($price_total - $price_subtotal), number_format($group_total_tax_percent, 0), null, null, $price_subtotal)
            ->setDocumentSummation($price_total, $price_total - $invoice_received, $price_subtotal, 0.0, 0.0, $price_subtotal, ($price_total - $price_subtotal), null, $invoice_received)
            ->addDocumentPaymentMean(horstoeko\zugferd\codelists\ZugferdPaymentMeans::UNTDID_4461_58, null, null, null, null, null, $bank_iban, null, null, null);
        // ->addDocumentPaymentTerm($description);

    }
    //This is to get all prodcut details as row basis
    for ($i = 1, $j = $i - 1; $i <= $num_products; $i++, $j++) {
        $product_code[$i] = $associated_products[$i]['hdnProductcode' . $i];
        $product_name[$i] = decode_html($associated_products[$i]['productName' . $i]);
        $prod_description[$i] = decode_html($associated_products[$i]['productDescription' . $i]);
        $qty[$i] = $associated_products[$i]['qty' . $i];
        $qty_formated[$i] = number_format($associated_products[$i]['qty' . $i], $decimal_precision, $decimals_separator, $thousands_separator);
        $comment[$i] = decode_html($associated_products[$i]['comment' . $i]);
        $unit_price[$i] = number_format($associated_products[$i]['unitPrice' . $i], $decimal_precision, $decimals_separator, $thousands_separator);
        $list_price[$i] = number_format($associated_products[$i]['listPrice' . $i], $decimal_precision, $decimals_separator, $thousands_separator);
        $list_pricet[$i] = $associated_products[$i]['listPrice' . $i];
        $discount_total[$i] = $associated_products[$i]['discountTotal' . $i];
        $discount_totalformated[$i] = number_format($associated_products[$i]['discountTotal' . $i], $decimal_precision, $decimals_separator, $thousands_separator);
        //added by crm-now
        $usageunit[$i] = $associated_products[$i]['usageunit' . $i];
        //look whether the entry already exists, if the translated string is available then the translated string other wise original string will be returned
        $usageunit[$i] = getTranslatedString($usageunit[$i], 'Products');
        $taxable_total = $qty[$i] * $list_pricet[$i] - $discount_total[$i];
        //get subproducts if exists
        if (!empty($associated_products[$i]['subProductArray' . $i])) {
            $subProductArray[$i] = $associated_products[$i]['subProductArray' . $i];
        } else {
            $subProductArray[$i] = '';
        }
        //create a subProduct string to be added to the main product
        $subProdString = array();
        if (is_array($subProductArray[$i]) && count($subProductArray[$i]) > 0) {
            for ($subprod_count = 0; $subprod_count < count($subProductArray[$i]); $subprod_count++) {
                if ($subProductArray[$i][$subprod_count] != '') {
                    $subProdString[$i] .= "- " . $subProductArray[$i][$subprod_count] . "\n";
                }
            }
        }

        $producttotal = $taxable_total;
        $total_taxes = '0.00';


        if ($focus->column_fields["hdnTaxType"] == "individual") {
            $total_tax_percent = '0.00';
            //This loop is to get all tax percentage and then calculate the total of all taxes
            for ($tax_count = 0; $tax_count < count($associated_products[$i]['taxes']); $tax_count++) {
                $tax_percent = $associated_products[$i]['taxes'][$tax_count]['percentage'];
                $total_tax_percent = $total_tax_percent + $tax_percent;
                $tax_amount = (($taxable_total * $tax_percent) / 100);
                //calculate the tax amount for any available tax percentage
                $detected_tax = substr(array_search($total_tax_percent, $taxtype_listings), -1);
                $taxtype_listings['value' . $detected_tax] = $taxtype_listings['value' . $detected_tax] + $tax_amount;
                $total_taxes = $total_taxes + $tax_amount;
            }
            $producttotal = $taxable_total + $total_taxes;
            $product_line[$j][$pdf_strings['Tax']] = " ($total_tax_percent %) " . number_format($total_taxes, $decimal_precision, $decimals_separator, $thousands_separator);
            // combine product name, description and comment to one field based on settings
        }

        // combine product name, description and comment to one field based on settings
        if ($focus->column_fields["hdnTaxType"] == "individual") {
            $product_selection = $iproddetails;
        } else {
            $product_selection = $gproddetails;
        }
        if (!isset($subProdString[$i])) {
            $subProdString[$i] = '';
        }
        switch ($product_selection) {
            case 1:
                $product_name_long[$i] = $product_name[$i];
                break;
            case 2:
                $product_name_long[$i] = $prod_description[$i] . "\n" . $subProdString[$i];
                break;
            case 3:
                $product_name_long[$i] = $product_name[$i] . "\n" . $prod_description[$i] . "\n" . $subProdString[$i];
                break;
            case 4:
                $product_name_long[$i] = $comment[$i];
                break;
            case 5:
                $product_name_long[$i] = $product_name[$i] . "\n" . $comment[$i];
                break;
            case 6:
                if ($prod_description[$i] != '') {
                    $product_name_long[$i] = $prod_description[$i] . "\n" . $subProdString[$i] . $comment[$i];
                } else {
                    $product_name_long[$i] = $comment[$i];
                }
                break;
            case 7:
                if ($prod_description[$i] != '') {
                    $product_name_long[$i] = $product_name[$i] . "\n" . $prod_description[$i] . "\n" . $subProdString[$i] . $comment[$i];
                } else {
                    $product_name_long[$i] = $product_name[$i] . "\n" . $subProdString[$i] . $comment[$i];
                }
                break;
            default:
                if ($prod_description[$i] != '') {
                    $product_name_long[$i] = $product_name[$i] . "\n" . $prod_description[$i] . "\n" . $subProdString[$i] . $comment[$i];
                } else {
                    $product_name_long[$i] = $product_name[$i] . "\n" . $subProdString[$i] . $comment[$i];
                }
                break;
        }

        $prod_total[$i] = number_format($producttotal, $decimal_precision, $decimals_separator, $thousands_separator);

        $product_line[$j][$pdf_strings['Position']] = $j + 1;
        $product_line[$j][$pdf_strings['OrderCode']] = $product_code[$i];
        $product_line[$j][$pdf_strings['Description']] = $product_name_long[$i];
        $product_line[$j][$pdf_strings['Qty']] = $qty_formated[$i];
        $product_line[$j][$pdf_strings['Unit']] = $usageunit[$i];
        $product_line[$j][$pdf_strings['UnitPrice']] = $list_price[$i];
        $product_line[$j][$pdf_strings['Discount']] = $discount_totalformated[$i];
        $product_line[$j][$pdf_strings['LineTotal']] = $prod_total[$i];

        if ($eInvoice) {
            $eInvoiceDocument
                ->addNewPosition($i)
                ->setDocumentPositionNote($product_name_long[$i])
                ->setDocumentPositionProductDetails($product_name[$i], "", $product_code[$i])
                ->setDocumentPositionNetPrice((float) sprintf('%.2f', $associated_products[$i]['listPrice' . $i] - $associated_products[$i]['discountTotal' . $i]))
                ->setDocumentPositionQuantity((float) $qty_formated[$i], "H87")
                ->addDocumentPositionTax('S', 'VAT', number_format($group_total_tax_percent, 0))
                ->setDocumentPositionLineSummation((float) $producttotal);
            // ->setDocumentPositionLineSummation((float) $prod_total[$i]);
        }
    }

    //e-invoice export requested?
    if ($purpose == 'ExportXML') {
        // get xml
        $xml_data = file_get_contents($eInvoiceXmlFile);
        $filename = $invoice_no . '.xml';
        // redirect (download) xml
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $xml_data;
        exit;
    }

    if ($eInvoice) {
        // write xml
        $eInvoiceDocument
            ->writeFile($eInvoiceXmlFile);

        // which type? xrechnung = only xml ; zugferd = bundle
        $export_type = $default_export_e_invoice;

        if ($export_type == "xrechnung") {
            // get xml
            $xml_data = file_get_contents($eInvoiceXmlFile);
            // file_put_contents('logs/emu4812.log', $xml_data . PHP_EOL, FILE_APPEND);
            $filename = $invoice_no . '.xml';
            // redirect (download) xml
            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $xml_data;
            exit;
        }
    }





    if ($qr_feature == true) {
        //$bank_iban
        // 		//Function for generating a random IBAN
        // 		function generateUniqueIBAN() {
        // 			// Ländercode und Prüfziffer
        // 			$qriban = 'CH'; // Schweiz
        // 			$qriban .= sprintf('%02d', mt_rand(10, 99)); // Zufällige Prüfziffer

        // 			// Bankleitzahl
        // 			$qriban .= sprintf('%05d', mt_rand(30000, 31999));

        // 			// Kontonummer
        // 			$qriban .= sprintf('%010d', mt_rand(0, 9999999999));

        // 			// IBAN-Prüfziffer berechnen
        // 			$ibanWithoutChecksum = $qriban . '271500';
        // 			$checksum = 98 - bcmod($ibanWithoutChecksum, '97');
        // 			$qriban .= sprintf('%02d', $checksum);

        // 			return $qriban;
        // 		}

        // 		$pdfDataObj['qriban'] = generateUniqueIBAN();

        //available countrylist
        $countrylist = array(
            'SCHWEIZ' => 'CH',
            'Schweiz' => 'CH',
            'DEUTSCHLAND' => 'DE',
            'Deutschland' => 'DE',
            'Germany' => 'DE',
        );

        foreach ($countrylist as $country => $country_code) {
            if (strtoupper($org_country) == $country) {
                $bill_country_code = $country_code;
            }
            if (strtoupper($ship_country) == $country) {
                $ship_country_code = $country_code;
            }
        }
        if (empty($bill_country_code)) {
            $bill_country_code = 'DE';
        }
        if (empty($ship_country_code)) {
            $ship_country_code = 'DE';
        }

        // Check if the postal code contains letters
        if (preg_match('/[a-zA-Z]/', $org_code)) {
            $org_code = preg_replace('/[^0-9]/', '', $org_code);
        }

        //create QR-Code

        foreach (array('org_name', 'org_address', 'org_code', 'org_city', 'bill_country_code', 'contact_firstname', 'contact_lastname', 'ship_street', 'ship_code', 'ship_city', 'ship_country_code', ) as $key => $value) {
            if (empty($$key)) {
                $$key = "$key not set";
            }
        }


        // if (empty($org_name) || empty($org_address) || empty($org_code) || empty($org_city) || empty($bill_country_code) || empty($contact_firstname) || empty($contact_lastname) || empty($ship_street) || empty($ship_code) || empty($ship_city) || empty($ship_country_code)) {
        //     // Output of the error message
        //     var_dump('Fehler: Bitte füllen Sie alle Adressfelder für Rechnung und Lieferung bei Organisationen und Personen aus. (Adresse, Ort, Bundesland, PLZ und Land)');
        //     die();
        // } else {
        $pdfDataObj['organisation']['name'] = $org_name;
        $pdfDataObj['organisation']['hnr+street'] = $org_address;
        $pdfDataObj['organisation']['zip+state'] = $org_code . ' ' . $org_city;
        $pdfDataObj['organisation']['country'] = $bill_country_code;

        $pdfDataObj['contact']['name'] = $contact_firstname . ' ' . $contact_lastname;
        $pdfDataObj['contact']['street'] = explode(' ', $ship_street)[0];
        $pdfDataObj['contact']['hnr'] = explode(' ', $ship_street)[1];
        $pdfDataObj['contact']['zip'] = $ship_code;
        $pdfDataObj['contact']['state'] = $ship_city;
        $pdfDataObj['contact']['country'] = $ship_country_code;
        $pdfDataObj['contact']['org'] = $account_name;

        $pdfDataObj['payment']['currency'] = $currency_code;
        $pdfDataObj['payment']['amount'] = number_format($price_total, $decimal_precision, $decimals_separator, $thousands_separator);

        $pdfDataObj['qriban'] = 'CH4431999123000889012';
        // $pdfDataObj['qriban'] = $bank_iban; // ! special QR-IBAN is needed here
        // see https://github.com/sprain/php-swiss-qr-bill?tab=readme-ov-file
        $pdfDataObj['invoice_number'] = strval($focus->column_fields['invoice_no']);
        $pdfDataObj['bill_number'] = strval($idnumber);
        $pdfDataObj['description'] = strval($focus->column_fields['description']);
        // }
        if ($pdfDataObj['payment']['currency'] != " ") {
            $createpngpath = swissQrCreatepng($pdfDataObj);
        }
    }
    //************************BEGIN PDF FORMATING**************************
    // Extend the TCPDF class to create custom Header and Footer
    $page_num = '1';
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
    if ($invoice_status == 'Credit Invoice') {
        $doc_name = 'CREDIT';
    } else {
        $doc_name = 'FACTURE';
    }
    if ($purpose == 'printsn' or $purpose == 'savesn') {
        $doc_name = 'SALESNOTE';
    }
    $pdf->SetTitle($pdf_strings[$doc_name] . ": " . $account_name);
    $pdf->SetAuthor($owner_firstname . " " . $owner_lastname . ", " . $org_name);
    $pdf->SetSubject($account_name);
    $pdf->SetCreator('CRM System berliCRM: www.crm-now.de ');
    //list product names as keywords
    $productlisting = implode(", ", $product_name);
    $pdf->SetKeywords($productlisting);

    //Disable automatic page break
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);
    //set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    //set some language-dependent strings
    $pdf->setLanguageArray($pdf_config_details['pdflang']);
    //in reference to body.php -> if a new page must be added if the space available for summary is too small
    $new_page_started = false;
    $pdf->AddPage();
    $pdf->setImageScale(1.5);
    //$pdf->SetY(PDF_MARGIN_HEADER);
    include("modules/Invoice/pdf_templates/header.php");
    $pdf->SetFont($default_font, " ", $font_size_body);
    include("modules/Invoice/pdf_templates/body.php");
    //formating company name for file name
    $export_org = strtolower($account_name);
    $export_org = sanitizeFilename($export_org);

    if ($qr_feature) {
        $pdf->AddPage();
        $imagewidth = 50;
        $imageheight = 50;

        //Right Side of QR-Code
        $pdf->MultiCell(70, 0, '<h2>Rechnung</h2>', 0, 'C', 0, 0, '', '', true, 0, true);
        $pdf->Ln(6);
        $pdf->MultiCell(65, 5, '', 0, 'L', 0, 0, '', '', true);
        $pdf->MultiCell(55, 5, '<b>Zahlen an</b><br>' . $pdfDataObj['qriban'], 0, 'L', 0, 1, '', '', true, 0, true);
        $pdf->MultiCell(65, 5, '', 0, 'L', 0, 0, '', '', true);
        $pdf->MultiCell(55, 5, $pdfDataObj['organisation']['name'] . "\n" . $pdfDataObj['organisation']['hnr+street'], 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(65, 5, '', 0, 'L', 0, 0, '', '', true);
        $pdf->MultiCell(55, 5, $pdfDataObj['organisation']['zip+state'], 0, 'L', 0, 1, '', '', true);
        $pdf->Ln(2);
        $pdf->MultiCell(65, 5, '', 0, 'L', 0, 0, '', '', true);
        $pdf->MultiCell(55, 5, "<b>Referenz</b><br>" . $pdfDataObj['invoice_number'], 0, 'L', 0, 1, '', '', true, 0, true);
        $pdf->Ln(2);
        $pdf->MultiCell(65, 5, '', 0, 'L', 0, 0, '', '', true);
        $pdf->MultiCell(55, 5, "<b>Beschreibung</b><br>" . $pdfDataObj['description'], 0, 'L', 0, 1, '', '', true, 0, true);
        $pdf->Ln(2);
        $pdf->MultiCell(65, 5, '', 0, 'L', 0, 0, '', '', true);
        $pdf->MultiCell(70, 5, "<b>Zahlung von</b><br>" . $pdfDataObj['contact']['org'] . " " . $pdfDataObj['contact']['name'] . "<br>" . $pdfDataObj['contact']['street'] . ' ' . $pdfDataObj['contact']['hnr'] . "<br>" . $pdfDataObj['contact']['zip'] . ' ' . $pdfDataObj['contact']['state'], 0, 'L', 0, 1, '', '', true, 0, true);
        $pdf->Ln(5);
        $pdf->MultiCell(40, 0, '<b>Währung</b>', 0, 'C', 0, 0, '', '', true, 0, true);
        $pdf->MultiCell(15, 0, '<b>Betrag</b>', 0, 'C', 0, 1, '', '', true, 0, true);
        $pdf->MultiCell(40, 0, $pdfDataObj['payment']['currency'], 0, 'C', 0, 0, '', '', true, 0, true);
        $pdf->MultiCell(15, 0, $pdfDataObj['payment']['amount'], 0, 'C', 0, 1, '', '', true, 0, true);

        $imagepath = 'storage/temp/qr' . $pdfDataObj['bill_number'] . '.png';
        //$imageData = imagecreatefrompng($imagepath);
        //$tempimagepath = __DIR__ . '/' . $createpngpath;
        //imagejpeg($imageData, $tempimagepath, 100);
        //imagedestroy($imageData); // only needed in PHP < 8.0
        // $pdf->Image($tempimagepath, 30, 30, $imagewidth, $imageheight, 'JPEG');
        $pdf->Image($imagepath, 25, 35, $imagewidth, $imageheight, 'PNG');
        unlink($imagepath);
    }

    $createdOutputPdfName = '';
    $createdOutputPdfPath = '';
    if ($purpose == 'save' || $purpose == 'savesn') {
        // save PDF file at Invoice
        if ($purpose == 'savesn') {
            $createdOutputPdfPath = $path . $current_id . "_" . $pdf_strings['SALESNOTE'] . '_' . $date_issued . '.pdf';
            $createdOutputPdfName = $pdf_strings['SALESNOTE'] . '_' . $date_issued . '.pdf';
        } else {
            $createdOutputPdfPath = $path . $current_id . "_" . $pdf_strings['FACTURE'] . '_' . $date_issued . '.pdf';
            $createdOutputPdfName = $pdf_strings['FACTURE'] . '_' . $date_issued . '.pdf';
        }
    }
    // issue pdf
    elseif ($purpose == 'print' || $purpose == 'printsn') {
        // option: push pdf to the browser
        if ($purpose == 'printsn') {
            ob_end_clean();
            $createdOutputPdfName = sanitizeUploadFileName(
                $invoice_no . '_' . $export_org . '_' . $pdf_strings['SALESNOTE'] . '_' . $date_issued . '.pdf',
                []
            );
            $createdOutputPdfPath = sys_get_temp_dir() . '/' . $createdOutputPdfName;
        } else {
            ob_end_clean();
            $createdOutputPdfName = sanitizeUploadFileName(
                $invoice_no . '_' . $export_org . '_' . $pdf_strings['FACTURE'] . '_' . $date_issued . '.pdf',
                []
            );
            $createdOutputPdfPath = sys_get_temp_dir() . '/' . $createdOutputPdfName;
        }
    } elseif ($purpose == 'send') {
        // option: send pdf with mail
        $createdOutputPdfName = vtranslate('SINGLE_Invoice', 'Invoice') . '_' . $invoice_no . '.pdf';
        $createdOutputPdfPath = 'storage/' . $createdOutputPdfName;
    } elseif ($purpose == 'customerportal') {
        // option: use for portal
        $createdOutputPdfName = $current_id . "_Invoice.pdf";
        $createdOutputPdfPath = $path . $createdOutputPdfName;
    }

    $pdf->Output($createdOutputPdfPath, 'F'); // create PDF to file

    //
    // * do only if it is an e-invoice
    //
    if ($eInvoice) {
        //
        // create watermark if configured
        //
        if (!empty($e_invoice_watermark_pdf) && file_exists($e_invoice_watermark_pdf)) {
            $shellResult = shell_exec('/usr/bin/pdftk ' . $createdOutputPdfPath . ' background ' . $e_invoice_watermark_pdf . ' output modules/Invoice/watermarked.pdf');
            // TODO: logging and/or validating of $shellResult ...
            rename('modules/Invoice/watermarked.pdf', $createdOutputPdfPath);
            // $createdOutputPdfName = 'modules/Invoice/watermarked.pdf';
        }

        $mergeToPdf = "modules/Invoice/full.pdf";
        if (!file_exists($eInvoiceXmlFile) || !file_exists($createdOutputPdfPath)) {
            throw new \Exception("XML and/or PDF does not exist");
        }
        (new horstoeko\zugferd\ZugferdDocumentPdfMerger($eInvoiceXmlFile, $createdOutputPdfPath))->generateDocument()->saveDocument($mergeToPdf);
        rename($mergeToPdf, $createdOutputPdfPath);
    }

    if ($purpose == 'print' || $purpose == 'printsn') {
        // push to browser
        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=\"" . basename($createdOutputPdfName) . "\"");
        readfile($createdOutputPdfPath);
        unlink($createdOutputPdfPath); // delete file after download
        exit;
    } elseif ($purpose == 'save' || $purpose == 'savesn') {
        return $createdOutputPdfName;
    }
}

function getCountryISOCode(string $countryName): string
{
    $countryName = trim($countryName);

    // 1. Try match in German
    foreach (Countries::getNames('de') as $code => $nameDe) {
        if (stripos($countryName, $nameDe) !== false) {
            return $code;
        }
    }

    // 2. Try match in English
    foreach (Countries::getNames('en') as $code => $nameEn) {
        if (stripos($countryName, $nameEn) !== false) {
            return $code;
        }
    }

    // 3. Default fallback
    return 'DE';
}


function swissQrCreatepng($pdfDataObj)
{

    // This is an example how to create a typical qr bill:
    // - with reference number
    // - with known debtor
    // - with specified amount
    // - with human-readable additional information
    // - using your QR-IBAN
    //
    // Likely the most common use-case in the business world.

    // Create a new instance of QrBill, containing default headers with fixed values
    $qrBill = QrBill\QrBill::create();

    // Add creditor information
    // Who will receive the payment and to which bank account?
    $qrBill->setCreditor(
        QrBill\DataGroup\Element\CombinedAddress::create(
            $pdfDataObj['organisation']['name'],
            $pdfDataObj['organisation']['hnr+street'],
            $pdfDataObj['organisation']['zip+state'],
            $pdfDataObj['organisation']['country']
        )
    );

    $qrBill->setCreditorInformation(
        QrBill\DataGroup\Element\CreditorInformation::create(
            $pdfDataObj['qriban'] // This is a special QR-IBAN. Classic IBANs will not be valid here.
        )
    );

    // Add debtor information
    // Who has to pay the invoice? This part is optional.
    //
    // Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress.
    // They are interchangeable for creditor as well as debtor.
    $qrBill->setUltimateDebtor(
        QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
            $pdfDataObj['contact']['name'],
            $pdfDataObj['contact']['street'],
            $pdfDataObj['contact']['hnr'],
            $pdfDataObj['contact']['zip'],
            $pdfDataObj['contact']['state'],
            $pdfDataObj['contact']['country']
            //$pdfDataObj['contact']['org'],
        )
    );

    // Add payment amount information
    // What amount is to be paid?
    $qrBill->setPaymentAmountInformation(
        QrBill\DataGroup\Element\PaymentAmountInformation::create(
            $pdfDataObj['payment']['currency'],
            floatval(str_replace(",", ".", $pdfDataObj['payment']['amount']))
        )
    );

    // Add payment reference
    // This is what you will need to identify incoming payments.

    $referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
        null,  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
        //'313947143000901' // A number to match the payment with your internal data, e.g. an invoice number
        $pdfDataObj['bill_number']
    );

    $qrBill->setPaymentReference(
        QrBill\DataGroup\Element\PaymentReference::create(
            QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
            $referenceNumber
        )
    );

    // Optionally, add some human-readable information about what the bill is for.
    $qrBill->setAdditionalInformation(
        QrBill\DataGroup\Element\AdditionalInformation::create(
            $pdfDataObj['invoice_number'] . ' ' . $pdfDataObj['description']
        )
    );

    // Now get the QR code image and save it as a file.
    try {
        $filepath = 'storage/temp';
        if (!file_exists($filepath)) {
            if (!mkdir($filepath, 0777, true)) {
                var_dump('Fehler beim Erstellen des Dateipfads: ' . $filepath);
            }
        }
        $qrBill->getQrCode()->writeFile('storage/temp/qr' . $pdfDataObj['bill_number'] . '.png');
    } catch (Exception $e) {

        // $datei = fopen("test/testData.txt", "a+");
        // fwrite($datei, print_r($qrBill->getViolations(), TRUE));
        // fclose($datei);

    }
    return 'storage/temp/qr' . $pdfDataObj['bill_number'] . '.png';
}

/**
 * Sanitizes a string to create a safe filename by replacing invalid characters,
 * removing control characters, reducing multiple underscores, and handling reserved names.
 *
 * @param string $filename The input string to be sanitized.
 * @return string The sanitized filename, safe for file creation.
 */
function sanitizeFilename($filename)
{
    // List of invalid characters to be replaced or removed
    $invalidChars = ['\\', '/', ':', '*', '?', '"', '<', '>', '|', "\0"];

    // Replace invalid characters with an underscore
    $filename = str_replace($invalidChars, '_', $filename);

    // Replace multiple spaces with a single underscore
    $filename = preg_replace('/\s+/', '_', $filename);

    // Remove non-printable control characters
    $filename = preg_replace('/[[:cntrl:]]/', '', $filename);

    // Trim leading and trailing dots
    $filename = trim($filename, '.');

    // Reduce multiple underscores to a single underscore
    $filename = preg_replace('/_+/', '_', $filename);

    // Ensure the filename is not empty
    if (empty($filename)) {
        // default_filename
        $filename = 'org';
    }

    // Limit filename length to 255 characters
    $filename = substr($filename, 0, 255);

    // Check for reserved names (Windows-specific)
    $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
    if (in_array(strtoupper($filename), $reservedNames)) {
        $filename = 'file_' . $filename;
    }

    return $filename;
}
