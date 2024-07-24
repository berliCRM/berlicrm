<?php 

declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

function createpng($pdfDataObj) {

require ('libraries/qrcode/autoload.php');

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
            $pdfDataObj['organisation']['country'],
        ));

$qrBill->setCreditorInformation(
        QrBill\DataGroup\Element\CreditorInformation::create(
            $pdfDataObj['qriban'], // This is a special QR-IBAN. Classic IBANs will not be valid here.
        ));

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
            $pdfDataObj['contact']['country'],
            $pdfDataObj['contact']['org'],
        ));

// Add payment amount information
// What amount is to be paid?
$qrBill->setPaymentAmountInformation(
        QrBill\DataGroup\Element\PaymentAmountInformation::create(
            $pdfDataObj['payment']['currency'],
            floatval(str_replace(",", ".",$pdfDataObj['payment']['amount'])),
        ));

// Add payment reference
// This is what you will need to identify incoming payments.
$referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
            NULL,  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
            //'313947143000901' // A number to match the payment with your internal data, e.g. an invoice number
            $pdfDataObj['bill_number'],
);

$qrBill->setPaymentReference(
        QrBill\DataGroup\Element\PaymentReference::create(
            QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
            $referenceNumber
        ));

// Optionally, add some human-readable information about what the bill is for.
$qrBill->setAdditionalInformation(
        QrBill\DataGroup\Element\AdditionalInformation::create(
            $pdfDataObj['invoice_number'].' '.$pdfDataObj['description']

        )
);

    // Now get the QR code image and save it as a file.
    try{
        $filepath = 'storage/temp';
        if (!file_exists($filepath)) {
            if (!mkdir($filepath,0777,true)) {
                var_dump('Fehler beim Erstellen des Dateipfads: '. $filepath);
            }
        }
    $qrBill->getQrCode()->writeFile('storage/temp/qr'.$pdfDataObj['bill_number'].'.png');
    } catch (Exception $e) {
        $datei = fopen("test/testData.txt","a+");
    fwrite($datei, print_r($qrBill->getViolations(), TRUE));
    fclose($datei);
}
return 'storage/temp/qr'.$pdfDataObj['bill_number'].'.png';


}
