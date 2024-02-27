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
$ymargin__below_header = $ylocation_after;
//color for text
$pdf->SetTextColor(0,0,0);
//color for lines
$pdf->SetDrawColor(180,180,180);
// distance between lines and product rows
$line_distance = 2;
$line_distance_products = 4;
//at first page include headline with description 
//write the headline
$pdf->SetXY( PDF_MARGIN_LEFT, $ymargin__below_header);
$pdf->SetFont( $default_font, "B", $font_size_body+2);
$pdf->Cell($pdf->GetStringWidth($pdf_strings['FACTURE']),$pdf->getFontSize(), $pdf_strings['FACTURE'],0,1);
$pdf->SetFont( $default_font, "", $font_size_body);
//insert empty line below headline
for($l=0;$l< $space_headline;$l++) {
	$pdf->Ln($pdf->getFontSize());
}
//write the contents of the description field
$description = str_replace('$contacts-salutation$',decode_html($contact_salutation),$description);
$description = str_replace('$contacts-firstname$',decode_html($contact_firstname),$description);
$description = str_replace('$contacts-lastname$',decode_html($contact_lastname),$description);
$description = str_replace('$users-firstname$',decode_html($owner_firstname),$description);
$description = str_replace('$users-lastname$',decode_html($owner_lastname),$description);
$description = str_replace('$users-title$',decode_html($owner_title),$description);
$pdf->MultiCell('180',$line_distance, $description,0,'L',0);
$current_y_location = $pdf->GetY();
//insert empty line below description
$pdf->Ln($pdf->getFontSize());
/* ************ Begin Table Setup ********************** */
// Each of these arrays needs to have matching keys
// "key" => "Length"
// space for the total of columns depends on the x_margin
// for x_margin= 20 (DIN 5008 = 2cm left margin ) total of columns needs to be 180 in order to fit the table correctly
//get colums settings from DB
// *** enabled=1 means that this column is part of a possible selection
// *** seleced = checked menas that the colums is part of the selection if enabled=1
$pdfcolumnsettings = getAllPDFColums ($module);
$column_body_content_group_sel= $pdfcolumnsettings[0];
$column_body_content_individual_sel= $pdfcolumnsettings[1];
$columnline_positions = array('Position'=>'R','OrderCode'=>'L','Description'=>'L','Qty'=>'R','Unit'=>'R','UnitPrice'=>'R','Discount'=>'R','Tax'=>'R','LineTotal'=>'R');
if($focus->column_fields["hdnTaxType"] == "individual") $column_body_content = $pdfcolumnsettings[1];
else $column_body_content = $pdfcolumnsettings[0];
// the total width of all columns must be 180
//the width for each column if all columns are selected is predefined, if a column gets unchecked the space is added to the description column 
$columnwidth_taken =0;
//define the column positions and sizes
foreach ($column_body_content as $key => $value) {
	if ($value['selected'] =='checked="checked"' and $value['enabled'] =='1')
	{
		$colsAlign[$pdf_strings[$key]] = $columnline_positions[$key] ;
		If ($focus->column_fields["hdnTaxType"] =='individual') $defined_columnsizes = array('Position'=>'10','OrderCode'=>'20','Description'=>'21','Qty'=>'15','Unit'=>'20','UnitPrice'=>'22','Discount'=>'15','Tax'=>'32','LineTotal'=>'25');
		else $defined_columnsizes = array('Position'=>'10','OrderCode'=>'20','Description'=>'53','Qty'=>'15','Unit'=>'20','UnitPrice'=>'22','Discount'=>'15', 'LineTotal'=>'25');
		$current_columnwidht = $defined_columnsizes[$key];
		$cols[$pdf_strings[$key]] = $current_columnwidht;
		$columnwidth_taken = $columnwidth_taken + $current_columnwidht;
	}
}
$cols[$pdf_strings['Description']] = $cols[$pdf_strings['Description']]+ (180 - $columnwidth_taken);

//***********begin product list table header ******************/
$x_value     = PDF_MARGIN_LEFT;
foreach ($cols as $lib =>$pos)
{
	$text = $lib;
	$pdf->SetX($x_value-2);
	$pdf->Cell($pos,$pdf->getFontSize(),$text,0,0,$colsAlign[$lib],0,0);
	$x_value += $pos;
}
$pdf->Ln($pdf->getFontSize());
$line_y_location= $pdf->GetY();
//add line below headline
$pdf->Line(PDF_MARGIN_LEFT,$line_y_location+2, "200", $line_y_location+2);
$pdf->SetXY(PDF_MARGIN_LEFT,$line_y_location+4);
//***********end product list table header ******************/
/* ************* Begin Product Population *************** */
// start for y
$actual_y_position = $pdf->GetY();
// list products
for($i=0;$i<$num_products;$i++)
{
	$x_value     = PDF_MARGIN_LEFT;
	$counter = 0;
	$newpage= false;
	$total_length = 1;
	$formated_text_array=array();
	//print all lines --> $k is the number of lines,  $total_length set by the length of the description field
	for($k=0;$k< $total_length;$k++)
	{
		foreach ($cols as $lib =>$pos)
		{
			$longCell = $pos -2;
			//get text to issue
			$text    = decode_html($product_line[$i][$lib]);
			//WordWrapText Class formats the text to fit into the given space
			//returns formatted $text --> stream 5 only product name and position only
			$pdf->WordWrapText($text, $longCell);
			$formated_text_array= explode ("\n",decode_html($text));
			//Number of lines in formated text
			$row_count_orig_formated_text = count($formated_text_array);
			if ($lib == $pdf_strings['Description']) $total_length = $row_count_orig_formated_text;
			//decide whether position is left, center or right
			$formText  = $colsAlign[$lib];
			$current_y_location = $actual_y_position;
			//write  Cell 
			$pdf->SetXY($x_value, $current_y_location);
			$current_y_location  = $pdf->GetY() + $line_distance_products;
			$newpage= $pdf->CheckPageBreakPDF($current_y_location, PDF_MARGIN_FOOTER);
			if ($newpage==true) 
			{
				// reset parameters for new page
				$pdf->SetY(PDF_MARGIN_HEADER);
				$actual_y_position = $pdf->GetY();
				//print line header on top of the new page
				$new_x_value     = PDF_MARGIN_LEFT;
				$pos_bevor_header = $pos;
				foreach ($cols as $lib =>$pos)
				{
					$text = $lib;
					$pdf->SetX($new_x_value-2);
					$pdf->Cell($pos,$pdf->getFontSize(),$text,0,0,$colsAlign[$lib],0,0);
					$new_x_value += $pos;
				}
				$pdf->Ln($pdf->getFontSize());
				$line_y_location= $pdf->GetY();
				$pos = $pos_bevor_header;
				//add line below headline
				$pdf->Line(PDF_MARGIN_LEFT,$line_y_location+2, "200", $line_y_location+2);
				$pdf->SetXY( PDF_MARGIN_LEFT, $line_y_location+4);
				$actual_y_position = $pdf->GetY();
				$current_y_location = $actual_y_position;
			}
			if (!empty($formated_text_array[$k])) {
				$pdf->Cell($longCell, $pdf->getFontSize(), $formated_text_array [$k],0,1,$formText,0,0);
			}
			$x_value += $pos;
		}
		$actual_y_position   = $actual_y_position  + $line_distance_products;
		$x_value     = PDF_MARGIN_LEFT;
	}
	//include space after first line
	$actual_y_position = $pdf->GetY()+2;
	//print the additional lines if more than 1 line needs to be printed
	$x_value     = PDF_MARGIN_LEFT;
	//set new y_position after product line is finished
	$actual_y_position   = $actual_y_position+$line_distance_products;
	$pdf->SetXY( $x_value, $actual_y_position);
	//add line below product
	$y_line_storage = $pdf->GetY();
	if ($newpage==false)
		$pdf->Line(PDF_MARGIN_LEFT,$actual_y_position-2, "200", $actual_y_position-2);
	$pdf->SetXY( PDF_MARGIN_LEFT, $y_line_storage+2);
}
/* ******************* End product population ********* */

/* ************* Begin Totals ************************** */
$line_y_location = $pdf->GetY();
$pdf->SetXY(PDF_MARGIN_LEFT, $line_y_location);
If ($summaryradio == 'true') { 
	$currency_symbol=decode_html ($currency_symbol);
	//check whether the space available at this page for total is sufficiant
	//the space is not calculated but estimated, we want to keep the summary as a block not divided by a page break
	$hight=25;
	//Issue a page break if the space available is not enough to print the summary
	$newpage= $pdf->CheckPageBreakSummary($hight);
	//set y-margin if new page
	if ($newpage == true) $pdf->SetXY(PDF_MARGIN_LEFT , PDF_MARGIN_HEADER);
	else $pdf->SetXY(PDF_MARGIN_LEFT , $line_y_location+5);
	$line_y_location = $pdf->GetY();
	if($focus->column_fields["hdnTaxType"] == "individual")
	//************** populate VAT listing for individual tax only **************************************************
	{
		$line_y_tax = $line_y_location;
		for($tax_count=0;$tax_count<count($tax_details);$tax_count++)
		{	
			$VATLISTING = number_format($taxtype_listings ['percentage'.$tax_count],$decimal_precision,$decimals_separator,$thousands_separator).' '.$pdf_strings['Tax_NAME'].' '.number_format($taxtype_listings ['value'.$tax_count],$decimal_precision,$decimals_separator,$thousands_separator).' '.$currency_symbol.' '.$pdf_strings['INCLUDE_NAME'];
			if ($taxtype_listings ['value'.$tax_count] != "0.00")
			{
				$pdf->SetXY(PDF_MARGIN_LEFT , $line_y_tax);
				$pdf->SetFont($default_font, "", $font_size_body);
				$pdf->Cell($pdf->GetStringWidth($VATLISTING), $pdf->getFontSize(), decode_html($VATLISTING),0,0,'L');
				$line_y_tax = $line_y_tax +4;
			}
		}	
	}
	//************** populate summary **************************************************
	//if taxtype is not individual ie., group tax
	//set standards
	$pdf->SetFont( $default_font, "", $font_size_body);
	$line_distance = $pdf->getFontSize()+2;
	
	if($focus->column_fields["hdnTaxType"] != "individual")
	{
		//line & text $price_subtotal
		//line & text $price_subtotal
		$data= $pdf_strings['VAR_SUBTOTAL'].":";
		$pdf->SetXY( 105 , $line_y_location);
		$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0); 

		//value $price_subtotal
		$pdf->SetXY( 144 , $line_y_location );
		$pdf->Cell(54, $line_distance, decode_html($price_subtotal_formated),0,1,'R',0,1);
		$line_y_location = $pdf->GetY();
		
		//line & text $price_discount
		if ($price_discount != '0.00')
		{
			$pdf->Line("105",$line_y_location, "200", $line_y_location);
			if(isset($final_price_discount_percent) && $final_price_discount_percent != '')
				$data= $pdf_strings['Discount']."   $final_price_discount_percent:";
			else
				$data= $pdf_strings['Discount'].":";
			$pdf->SetXY( 105 , $line_y_location );
			$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
			//value $price_discount
			$pdf->SetXY( 144 , $line_y_location );
			$pdf->Cell(54, $line_distance, decode_html($price_discount_formated),0,1,'R',0,1);
			$line_y_location = $pdf->GetY();
		}

		//line & text $price_salestax
		$pdf->Line("105",$line_y_location, "200", $line_y_location);
		$data= $pdf_strings['Tax']."  ($group_total_tax_percent %):";
		$pdf->SetXY( 105 , $line_y_location );
		$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
		//value $price_salestax
		$pdf->SetXY( 144 , $line_y_location );
		$pdf->Cell(54,$line_distance, decode_html($price_salestax_formated),0,1,'R',0,1);
		$line_y_location = $pdf->GetY();

		//line & text $price_shipping
		if ($sh_amount != '0.00')
		{
			$pdf->Line("105",$line_y_location, "200", $line_y_location);
			$data = $pdf_strings['VAR_SHIPCOST'].":";
			$pdf->SetXY( 105 , $line_y_location );
			$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
			//value $price_shipping
			$pdf->SetXY( 144 , $line_y_location );
			$pdf->Cell(54, $line_distance, decode_html($price_shipping_formated),0,1,'R',0,1);
			$line_y_location = $pdf->GetY();
		}
	}
	else
	{
		//line & text $price_subtotal
		$data= $pdf_strings['VAR_SUBTOTAL'].":";
		$pdf->SetXY( 105 , $line_y_location);
		$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
		//value $price_subtotal
		$pdf->SetXY( 144 , $line_y_location );
		$pdf->Cell(54, $line_distance, decode_html($price_subtotal_formated),0,1,'R',0,1);
		$line_y_location = $pdf->GetY();

		//line & text $price_discount
		if ($price_discount != '0.00')
		{
			if(isset ($final_price_discount_percent) && $final_price_discount_percent != '')
				$data= $pdf_strings['Discount']."   $final_price_discount_percent:";
			else
				$data= $pdf_strings['Discount'].":";
			$pdf->SetXY( 105 , $line_y_location );
			$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
			//value $price_discount
			$pdf->SetXY( 144 , $line_y_location );
			$pdf->SetFont( $default_font, "", $font_size_body);
			$pdf->Cell(54, $line_distance, decode_html($price_discount_formated),0,1,'R',0,1);
			$line_y_location = $pdf->GetY();
			$pdf->Line("105",$line_y_location, "200", $line_y_location);
		}
		//line & text $price_shipping
		if ($sh_amount != '0.00')
		{
			$pdf->Line("105",$line_y_location, "200", $line_y_location);
			$data = $pdf_strings['VAR_SHIPCOST'].":";
			$pdf->SetXY( 105 , $line_y_location );
			$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
			//value $price_shipping
			$pdf->SetXY( 144 , $line_y_location );
			$pdf->Cell(54, $line_distance, decode_html($price_shipping_formated),0,1,'R',0,1);
			$line_y_location = $pdf->GetY();
		}
	}

	//Set the x and y positions to place the S&H Tax, Adjustment and Grand Total
	//line & text $price_shipping_tax
	if ($sh_amount != '0.00')
	{
		$pdf->Line("105",$line_y_location, "200", $line_y_location);
		$data = $pdf_strings['VAR_TAX_SHIP']."  ($sh_tax_percent %):";
		$pdf->SetXY( 105 , $line_y_location );
		$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
		//value $price_shipping_tax
		$pdf->SetXY( 144 , $line_y_location );
		$pdf->Cell(54, $line_distance, decode_html($price_shipping_tax),0,1,'R',0,1);
		$line_y_location = $pdf->GetY();
	}
	//line & text $price_adjustment
	if ($price_adjustment != '0.00')
	{
		$pdf->Line("105",$line_y_location, "200", $line_y_location);
		$data = $pdf_strings['VAR_ADJUSTMENT'].":";
		$pdf->SetXY( 105 , $line_y_location );
		$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
		//value $price_adjustment
		$pdf->SetXY( 144 , $line_y_location );
		$pdf->Cell(54, $line_distance, decode_html($price_adjustment_formated),0,1,'R',0,1);
		$line_y_location = $pdf->GetY();
		$pdf->Line("105",$line_y_location, "200", $line_y_location);
	}
	//line & text $price_total
	$line_y_location_remeber= $line_y_location;
	$data = $pdf_strings['VAR_TOTAL']." ($currency_symbol):";
	$pdf->SetXY( 105 , $line_y_location );
	$pdf->SetFont( $default_font, "B", $font_size_body);
	$pdf->Cell(110, $line_distance, decode_html($data),0,0,'L',0,0);
	//value $price_total
	$pdf->SetXY( 144 , $line_y_location );
	$pdf->Cell(54, $line_distance, decode_html($price_total_formated),0,1,'R',0,1);
	$line_y_location = $pdf->GetY();
	//double line for total
	$pdf->Line("105",$line_y_location, "200", $line_y_location);
	$line_y_location = $line_y_location +1;
	$pdf->SetY($line_y_location);
	$pdf->Line("105",$line_y_location, "200", $line_y_location);
}
/* ************** End Totals *********************** */

/******** start populating conditions ****************/
//assuming for condition width = 180 (full page width) 
$pdf->SetFont( $default_font, "", $font_size_body);
$conditions = str_replace('$contacts-salutation$',decode_html($contact_salutation),$conditions);
$conditions = str_replace('$contacts-firstname$',decode_html($contact_firstname),$conditions);
$conditions = str_replace('$contacts-lastname$',decode_html($contact_lastname),$conditions);
$conditions = str_replace('$users-firstname$',decode_html($owner_firstname),$conditions);
$conditions = str_replace('$users-lastname$',decode_html($owner_lastname),$conditions);
$conditions = str_replace('$users-title$',decode_html($owner_title),$conditions);
//set distance to summary
$current_y_location  = $line_y_location + 3*$line_distance;
$pdf->SetXY(PDF_MARGIN_LEFT, $current_y_location);
$pdf->MultiCell('180',$line_distance, $conditions,0,'L',0);
?>