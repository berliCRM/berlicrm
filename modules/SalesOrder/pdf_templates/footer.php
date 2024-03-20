<?php
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
		global $FOOTER_PAGE, $default_font, $font_size_footer, $SalesOrder_no, $NUM_FACTURE_NAME, $pdf_strings, $footer_margin;
		global $org_name, $org_address, $org_city, $org_code, $org_country, $org_irs, $org_taxid, $org_phone, $org_fax, $org_website, $requisition_no;
		global $VAR40_NAME, $VAR3_NAME, $VAR4_NAME,$ORG_POSITION,$VAR_PAGE, $VAR_OF;
		//bank information - content
		global $bank_name , $bank_street , $bank_city ,$bank_zip ,$bank_country, $bank_account, $bank_routing, $bank_iban, $bank_swift;
		//bank information - labels from language files
		global $ACCOUNT_NUMBER, $ROUTING_NUMBER, $SWIFT_NUMBER, $IBAN_NUMBER;
		$this->SetFont($default_font,'',$font_size_footer);
		if ($footerradio =='true') {
			$this->SetTextColor(120,120,120,true);
			//*** first column
			$this->SetFont($default_font,'',$font_size_footer);
			$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+8);
			$this->Cell(20,4,decode_html($org_name),0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+12);
			$this->Cell(20,4,decode_html($org_address),0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+16);
			$this->Cell(20,4,$org_code." ".decode_html($org_city),0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT , -PDF_MARGIN_FOOTER+20);
			$this->Cell(20,4,decode_html($org_country),0,0,'L');
			//draw line
			$x =PDF_MARGIN_LEFT+43;
			$this->SetDrawColor(120,120,120);
			$this->Line($x,$this->h - PDF_MARGIN_FOOTER+9,$x,$this->h - PDF_MARGIN_FOOTER+23);
			//*** second column
			$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+8);
			$this->Cell(20,4,$pdf_strings['VAR_PHONE']." ".$org_phone,0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+12);
			$this->Cell(20,4,$pdf_strings['VAR_FAX']." ".$org_fax,0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+16);
			$this->Cell(20,4,$pdf_strings['VAR_TAXID'].' '.$org_taxid,0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+45 , -PDF_MARGIN_FOOTER+20);
			$this->Cell(20,4,decode_html($org_irs),0,0,'L');
			//draw line
			$x =PDF_MARGIN_LEFT+83;
			$this->Line($x,$this->h - PDF_MARGIN_FOOTER+9,$x,$this->h - PDF_MARGIN_FOOTER+23);

			//third column
			$this->SetXY(PDF_MARGIN_LEFT+85 , -PDF_MARGIN_FOOTER+8);
			$this->Cell(20,4,decode_html($bank_name),0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+85 , -PDF_MARGIN_FOOTER+12);
			$this->Cell(20,4,$pdf_strings['ACCOUNT_NUMBER']." ".$bank_account,0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+85 , -PDF_MARGIN_FOOTER+16);
			$this->Cell(20,4,$pdf_strings['ROUTING_NUMBER']." ".$bank_routing,0,0,'L');
			//draw line
			$x =PDF_MARGIN_LEFT+130;
			$this->Line($x,$this->h - PDF_MARGIN_FOOTER+9,$x,$this->h - PDF_MARGIN_FOOTER+23);

			//fourth column
			$this->SetXY(PDF_MARGIN_LEFT+132 , -PDF_MARGIN_FOOTER+8);
			$this->Cell(20,4,$pdf_strings['SWIFT_NUMBER']." ".$bank_swift,0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+132 , -PDF_MARGIN_FOOTER+12);
			$this->Cell(20,4,$pdf_strings['IBAN_NUMBER']." ".$bank_iban,0,0,'L');
			$this->SetXY(PDF_MARGIN_LEFT+132 , -PDF_MARGIN_FOOTER+16);
			$this->Cell(20,4,$org_website,0,0,'L');
		}
		if ($pageradio =='true') {
			//reset colors
			$this->SetTextColor(0,0,0,true);				
			//Print page number with soid
			$this->SetXY(PDF_MARGIN_LEFT, -PDF_MARGIN_FOOTER+22);
			if (trim($requisition_no) != '') $SOID = $requisition_no;
			else $SOID = $SalesOrder_no;
			$this->Cell(0,10,$pdf_strings['NUM_FACTURE_NAME'].' '.$SOID.', '.$pdf_strings['VAR_PAGE'].' '.$this->PageNo().' '.$pdf_strings['VAR_OF'].' '.$this->getAliasNbPages(),0,0,'C');
		}
		//reset colors
		$this->SetTextColor(0,0,0,true);
	}
}		
?>