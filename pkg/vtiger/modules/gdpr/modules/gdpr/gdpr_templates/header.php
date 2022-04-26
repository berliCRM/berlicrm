<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  crm-now, www.crm-now.com
* Portions created by crm-now are Copyright (C)  crm-now c/o im-netz Neue Medien GmbH.
* All Rights Reserved.
 *
 ********************************************************************************/
// hole and fold marks
$pdf->SetDrawColor(120,120,120);
$pdf->line(5, 99, 4, 99);
$pdf->line(5, 148.5, 4, 148.5);
$pdf->line(5, 198, 4, 198);
$pdf->SetDrawColor(0,0,0);
// ************** Begin company information *****************
//company logo
$pdf-> setImageScale(1.5);
if ($logoradio =='true') {
	if (file_exists('test/logo/'.$logo_name)) {
		$pdf->Image('test/logo/'.$logo_name, $x='125', $y='10', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false);
	}
}
//set location
$xmargin = '20';
$ymargin = '20';
$xdistance = '163';
$pdf->SetXY($xmargin,$ymargin);
// define standards
$pdf->SetFont($default_font,'',$font_size_header);
//company address
$pdf->SetX($xmargin);
$pdf->Cell($pdf->GetStringWidth($org_name),$pdf->getFontSize(),$org_name,0,1);
$pdf->Cell($pdf->GetStringWidth($org_address),$pdf->getFontSize(),$org_address,0,1);
$pdf->Cell($pdf->GetStringWidth($org_code),$pdf->getFontSize(),$org_code,0,0);
$pdf->Cell($pdf->GetStringWidth(' '.$org_city),$pdf->getFontSize(),' '.$org_city,0,1);
// ************** End company information *****************
///current date
$pdf->Ln(10);
$pdf->SetX($xmargin+$xdistance);
$pdf->Cell($pdf->GetStringWidth($date_issued),$pdf->getFontSize(),$date_issued,0,1);

/// used to define the y location for the body
$ylocation_after = $pdf->GetY();

?>