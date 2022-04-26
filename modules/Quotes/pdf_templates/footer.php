<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  crm-now, www.crm-now.com
* Portions created by crm-now are Copyright (C)  crm-now c/o im-netz Neue Medien GmbH.
* All Rights Reserved.
 *
 ********************************************************************************/
class MYPDF extends TCPDF 
{
	//modifiy tcpdf class footer
	public function Footer() 
	{
		//To make the function Footer() work properly
		if (!isset($this->original_lMargin)) 
		{
			$this->original_lMargin = $this->lMargin;
		}
		if (!isset($this->original_rMargin)) 
		{
			$this->original_rMargin = $this->rMargin;
		}
		global $footerradio, $pageradio;
		global $FOOTER_PAGE, $default_font, $font_size_footer, $quote_no, $NUM_FACTURE_NAME, $pdf_strings, $footer_margin, $logoradio, $userslocation;
		global $org_name, $org_address, $org_city, $org_code, $org_country, $org_irs, $org_taxid, $org_phone, $org_fax, $org_website;
		global $ORG_POSITION,$VAR_PAGE, $VAR_OF;
		//bank information - content
		global $bank_name , $bank_street , $bank_city ,$bank_zip ,$bank_country, $bank_account, $bank_routing, $bank_iban, $bank_swift;
		//bank information - labels from language files
		global $ACCOUNT_NUMBER, $ROUTING_NUMBER, $SWIFT_NUMBER, $IBAN_NUMBER;
		$this->SetFont($default_font,'',$font_size_footer);
		if ($footerradio =='true') {
			// special for MMO, no text but image
			if ($userslocation=='MMO GmbH') {
				if ($logoradio =='true') {
					if (file_exists('test/logo/MMO_Rechung_Fuss.jpg'))
						$this->Image('test/logo/'.$logo_source, $x='20', $y='265', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false);
					else {
						$this->SetXY('265','15');
						$this->Cell(20,$this->getFontSize(),$pdf_strings['MISSING_IMAGE'],0,0);
					}
				}
			}
			else {
				//text footer for Metacom
				$this->SetTextColor(120,120,120);
				//*** first column
				$this->SetFont($default_font,'',$font_size_footer);
				$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+8);
				$this->Cell($this->GetStringWidth($org_name),$this->getFontSize(),decode_html ($org_name),0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+12);
				$this->Cell($this->GetStringWidth($org_address),$this->getFontSize(),decode_html ($org_address),0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+16);
				$this->Cell($this->GetStringWidth($org_code),$this->getFontSize(),decode_html ($org_code)." ".decode_html ($org_city),0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+20);
				$this->Cell($this->GetStringWidth($org_country),$this->getFontSize(),decode_html ($org_country),0,0,'L');
				//draw line
				$x =PDF_MARGIN_LEFT+43;
				$this->SetDrawColor(120,120,120);
				$this->Line($x,$this->h - PDF_MARGIN_FOOTER+9,$x,$this->h - PDF_MARGIN_FOOTER+23);
				//*** second column
				$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+8);
				$this->Cell($this->GetStringWidth($pdf_strings['VAR_PHONE']." ".$org_phone),$this->getFontSize(),$pdf_strings['VAR_PHONE']." ".$org_phone,0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+12);
				$this->Cell($this->GetStringWidth($pdf_strings['VAR_FAX']." ".$org_fax),$this->getFontSize(),$pdf_strings['VAR_FAX']." ".$org_fax,0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+16);
				$this->Cell($this->GetStringWidth($pdf_strings['VAR_TAXID'].' '.$org_taxid),$this->getFontSize(),$pdf_strings['VAR_TAXID'].' '.$org_taxid,0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+20);
				$this->Cell($this->GetStringWidth(decode_html ($org_irs)),$this->getFontSize(),decode_html ($org_irs),0,0,'L');
				//draw line
				$x =PDF_MARGIN_LEFT+83;
				$this->Line($x,$this->h - PDF_MARGIN_FOOTER+9,$x,$this->h - PDF_MARGIN_FOOTER+23);

				//third column
				$this->SetXY(PDF_MARGIN_LEFT+85 , -PDF_MARGIN_FOOTER+8);
				$this->Cell($this->GetStringWidth(decode_html($bank_name)),$this->getFontSize(),decode_html($bank_name),0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+85 , -PDF_MARGIN_FOOTER+12);
				$this->Cell($this->GetStringWidth($pdf_strings['ACCOUNT_NUMBER']." ".$bank_account),$this->getFontSize(),$pdf_strings['ACCOUNT_NUMBER']." ".$bank_account,0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+85 , -PDF_MARGIN_FOOTER+16);
				$this->Cell($this->GetStringWidth($pdf_strings['ROUTING_NUMBER']." ".$bank_routing),$this->getFontSize(),$pdf_strings['ROUTING_NUMBER']." ".$bank_routing,0,0,'L');
				//draw line
				$x =PDF_MARGIN_LEFT+130;
				$this->Line($x,$this->h - PDF_MARGIN_FOOTER+9,$x,$this->h - PDF_MARGIN_FOOTER+23);

				//fourth column
				$this->SetXY(PDF_MARGIN_LEFT+132 , -PDF_MARGIN_FOOTER+8);
				$this->Cell($this->GetStringWidth($pdf_strings['SWIFT_NUMBER']." ".$bank_swift),$this->getFontSize(),$pdf_strings['SWIFT_NUMBER']." ".$bank_swift,0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+132 , -PDF_MARGIN_FOOTER+12);
				$this->Cell($this->GetStringWidth($pdf_strings['IBAN_NUMBER']." ".$bank_iban),$this->getFontSize(),$pdf_strings['IBAN_NUMBER']." ".$bank_iban,0,0,'L');
				$this->SetXY(PDF_MARGIN_LEFT+132 , -PDF_MARGIN_FOOTER+16);
				$this->Cell($this->GetStringWidth($org_website),$this->getFontSize(),$org_website,0,0,'L');
			}
		}
		if ($pageradio =='true') {
			//reset colors
			$this->SetTextColor(0,0,0);				
			//Print page number with quote id
			$this->SetXY(PDF_MARGIN_LEFT, -PDF_MARGIN_FOOTER+22);
			$this->Cell(0,10,$pdf_strings['NUM_FACTURE_NAME'].' '.$quote_no.', '.$pdf_strings['VAR_PAGE'].' '.$this->PageNo().' '.$pdf_strings['VAR_OF'].' '.$this->getAliasNbPages(),0,0,'C');
		}
			//reset colors
			$this->SetTextColor(0,0,0);
	}
}
?>