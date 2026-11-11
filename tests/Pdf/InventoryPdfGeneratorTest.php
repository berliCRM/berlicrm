<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/include/InventoryPdfGenerator.php';

class InventoryPdfGeneratorProbeDocument extends InventoryPdfDocument
{
    public $templatePages = array();

    public function useTemplate($template, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false)
    {
        $this->templatePages[] = $this->PageNo();
        return parent::useTemplate($template, $x, $y, $width, $height, $adjustPageSize);
    }
}
final class InventoryPdfGeneratorTest extends TestCase
{
    private $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/berlicrm-pdf-' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') ?: array() as $file) {
            unlink($file);
        }
        rmdir($this->temporaryDirectory);
    }

    public function testProductDetailMaskPreservesLegacyBitValues(): void
    {
        $settings = array('gprodname' => 'true', 'gproddes' => 'false', 'gprodcom' => 'true');
        $this->assertSame(5, InventoryPdfGenerator::productDetailMask($settings, 'gprod'));
    }

    public function testFirstAndContinuationTemplatesAreAppliedToExpectedPages(): void
    {
        $first = $this->createLetterhead('first.pdf', 'FIRST');
        $continuation = $this->createLetterhead('continuation.pdf', 'CONTINUATION');
        $pdf = new InventoryPdfGeneratorProbeDocument('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->configureLetterheads(array(
            'first' => array('path' => $first),
            'continuation' => array('path' => $continuation),
        ));

        for ($page = 1; $page <= 3; ++$page) {
            $pdf->AddPage();
            $pdf->Text(20, 20, 'CONTENT ' . $page);
        }

        $this->assertSame(array(1, 2, 3), $pdf->templatePages);
        $this->assertSame(3, $pdf->getNumPages());
        $content = $pdf->Output('inventory.pdf', 'S');
        $this->assertSame('%PDF-', substr($content, 0, 5));
    }

    public function testMissingContinuationTemplateLeavesLaterPagesPlain(): void
    {
        $pdf = new InventoryPdfGeneratorProbeDocument('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->configureLetterheads(array('first' => array('path' => $this->createLetterhead('first.pdf', 'FIRST'))));
        $pdf->AddPage();
        $pdf->AddPage();

        $this->assertSame(array(1), $pdf->templatePages);
    }

    public function testEmptyConfigurationAddsNoBackground(): void
    {
        $pdf = new InventoryPdfGeneratorProbeDocument('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->configureLetterheads(array());
        $pdf->AddPage();

        $this->assertSame(array(), $pdf->templatePages);
        $this->assertSame('%PDF-', substr($pdf->Output('inventory.pdf', 'S'), 0, 5));
    }

    public function testMultiPageLetterheadIsRejectedWithoutBlockingOutput(): void
    {
        $pdf = new InventoryPdfGeneratorProbeDocument('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->configureLetterheads(array(
            'first' => array('path' => $this->createLetterhead('two-pages.pdf', 'LETTER', 'P', 2)),
        ));
        $pdf->AddPage();

        $this->assertSame(array(), $pdf->templatePages);
        $this->assertSame('%PDF-', substr($pdf->Output('inventory.pdf', 'S'), 0, 5));
    }

    public function testMismatchedPageSizeFailsOpen(): void
    {
        $pdf = new InventoryPdfGeneratorProbeDocument('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->configureLetterheads(array('first' => array('path' => $this->createLetterhead('letter.pdf', 'LETTER', 'L'))));
        $pdf->AddPage();
        $pdf->Text(20, 20, 'CONTENT');

        $this->assertSame(array(), $pdf->templatePages);
        $this->assertSame('%PDF-', substr($pdf->Output('inventory.pdf', 'S'), 0, 5));
    }

    private function createLetterhead($filename, $text, $orientation = 'P', $pages = 1)
    {
        $path = $this->temporaryDirectory . '/' . $filename;
        $pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        for ($page = 1; $page <= $pages; ++$page) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Text(10, 10, $text . ' ' . $page);
        }
        $pdf->Output($path, 'F');
        return $path;
    }
}
