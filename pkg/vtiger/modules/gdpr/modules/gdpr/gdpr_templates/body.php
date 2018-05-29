<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  crm-now, www.crm-now.com
* Portions created by crm-now are Copyright (C)  crm-now c/o im-netz Neue Medien GmbH.
* All Rights Reserved.
 *
 ********************************************************************************/
//definitions
// get y location info from header
$pdf->SetXY( PDF_MARGIN_LEFT, $ylocation_after);
//color for text
$pdf->SetTextColor(0,0,0);
$pdf->SetFillColor(255,255,255);
$pdf->SetDrawColor(255,255,255);
$pdf->SetFont( $default_font, "", $font_size_body);
$pdf->SetLineWidth(.3); 


/////////////////////////////////////////////
//  START block personal information
/////////////////////////////////////////////
$pdf->Ln(10);
if (!empty($salutation)) {
	$pdf->Cell($pdf->GetStringWidth($org_name),$pdf->getFontSize(),$salutation.' '.$lastname.',',0,1);
}
$pdf->MultiRow('', getTranslatedString('LBL_GDPR_INQUIRY')."\n");
$pdf->SetX( PDF_MARGIN_LEFT);
$pdf->MultiRow('', getTranslatedString('LBL_GDPR_DATA_USAGE1')."\n");
$pdf->MultiRow('', getTranslatedString('LBL_GDPR_DATA_USAGE2')."\n");
$pdf->MultiRow('', getTranslatedString('LBL_GDPR_DATA_USAGE3')."\n");
$pdf->Ln(2);

// head line
$pdf->SetFillColor(180, 180, 180);
$pdf->SetTextColor(255,255,255); 
$pdf->SetDrawColor(180, 180, 180);
$pdf->Cell('180',$pdf->getFontSize(),getTranslatedString('LBL_CONTACT_RELATED_DATA'),1,1,'L',1);

//styling for normal none header cells
$pdf->SetTextColor(0,0,0);
$pdf->SetFillColor(255,255,255);

// personal data
//create & populate table cells
$pdf->SetX( PDF_MARGIN_LEFT);
foreach ($print_record as $label => $contents) {
	$pdf->MultiRow($label.':', $contents);
}
//insert empty line 
$pdf->Ln();

if (!empty($other_information)) {
	/////////////////////////////////////////////
	//  START block other information
	/////////////////////////////////////////////
	$pdf->SetFillColor(180, 180, 180);
	$pdf->SetTextColor(255,255,255); 
	$pdf->SetDrawColor(180, 180, 180);
	$pdf->Cell('180',$pdf->getFontSize(),getTranslatedString('LBL_GDPR_INQUIRY_OTHER_MODULE'),1,1,'L',1);

	//styling for normal none header cells
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFillColor(255,255,255);

	//create & populate table cells
	$pdf->SetX( PDF_MARGIN_LEFT);
	foreach ($other_information as $label => $contents) {
		$pdf->MultiRow($label.':', $contents);
	}
}

?>