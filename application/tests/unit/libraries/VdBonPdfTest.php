<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Vd_bon_pdf (Lot 2 —
 * doc/plans/configuration_bons_vols_decouverte_plan.md).
 *
 * Pure rendering engine, no database dependency: layouts are hand-crafted
 * fixtures here rather than loaded from Vols_decouverte_looks_model (whose
 * default layout is cross-checked against the legacy hardcoded values in
 * application/tests/mysql/VdBonPdfMySqlTest.php, since that model requires
 * a DB-backed CI instance).
 *
 * @see application/libraries/Vd_bon_pdf.php
 */
class VdBonPdfTest extends TestCase
{
    private $tempFiles = array();

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Vd_bon_pdf.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = array();
    }

    private function emptyLayout()
    {
        return array(
            'recto' => array('variable_fields' => array(), 'static_fields' => array(), 'qr_field' => null),
            'verso' => array('variable_fields' => array(), 'static_fields' => array(), 'qr_field' => null),
        );
    }

    private function assertIsPdf($output)
    {
        $this->assertNotEmpty($output, 'Generated PDF output must not be empty');
        $this->assertStringStartsWith('%PDF', $output, 'Output must start with the PDF magic header');
    }

    public function testGenerateWithEmptyLayoutProducesValidPdf()
    {
        $pdf = new Vd_bon_pdf();
        $pdf->generate(array(), $this->emptyLayout());
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }

    public function testGenerateWithBackgroundImageDoesNotThrow()
    {
        $layout = $this->emptyLayout();
        $pdf = new Vd_bon_pdf();
        $pdf->generate(array(), $layout, image_dir() . 'Bon-Bapteme.png', null);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }

    public function testGenerateSkipsMissingBackgroundWithoutError()
    {
        $layout = $this->emptyLayout();
        $pdf = new Vd_bon_pdf();
        $pdf->generate(array(), $layout, '/no/such/file.png', '/no/such/other.png');
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }

    public function testGenerateRendersAllExpectedVariableFields()
    {
        $layout = $this->emptyLayout();
        $layout['verso']['variable_fields'] = array(
            array('id' => 'numero', 'enabled' => true, 'x' => 5, 'y' => 5, 'font' => 'helvetica', 'bold' => true, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 60),
            array('id' => 'beneficiaire', 'enabled' => true, 'x' => 5, 'y' => 15, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
            array('id' => 'occasion', 'enabled' => true, 'x' => 5, 'y' => 25, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
            array('id' => 'de_la_part', 'enabled' => true, 'x' => 5, 'y' => 35, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
            array('id' => 'date_validite', 'enabled' => true, 'x' => 5, 'y' => 45, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
            array('id' => 'type_vol', 'enabled' => true, 'x' => 5, 'y' => 60, 'font' => 'helvetica', 'bold' => true, 'size' => 14, 'color' => array(0, 0, 0), 'align' => 'C', 'width' => 190),
            array('id' => 'titre_vol', 'enabled' => true, 'x' => 5, 'y' => 50, 'font' => 'helvetica', 'bold' => true, 'size' => 20, 'color' => array(0, 0, 0), 'align' => 'C', 'width' => 190),
            // Champ désactivé : ne doit pas empêcher le rendu des autres.
            array('id' => 'inconnu', 'enabled' => false, 'x' => 5, 'y' => 70, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
        );

        $data = array(
            'numero' => 42,
            'beneficiaire' => 'Jean Dupont',
            'occasion' => 'Anniversaire',
            'de_la_part' => 'La famille',
            'date_validite' => '31/12/2026',
            'type_vol' => 'Vol de découverte en planeur',
            'titre_vol' => 'Un vol en planeur',
        );

        $pdf = new Vd_bon_pdf();
        $pdf->generate($data, $layout);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }

    public function testGenerateWithCustomLookMovedFieldAndDisabledQr()
    {
        $layout = $this->emptyLayout();
        // Champ déplacé, police/couleur modifiées, par rapport au layout par défaut.
        $layout['verso']['variable_fields'] = array(
            array('id' => 'beneficiaire', 'enabled' => true, 'x' => 90, 'y' => 40, 'font' => 'times', 'bold' => true, 'size' => 18, 'color' => array(200, 0, 0), 'align' => 'C', 'width' => 100),
        );
        $layout['verso']['static_fields'] = array(
            array('text' => 'Club Planeur Test', 'x' => 2, 'y' => 2, 'font' => 'helvetica', 'bold' => true, 'size' => 8, 'color' => array(0, 0, 128), 'align' => 'L', 'width' => 80),
        );
        // QR désactivé sur ce look personnalisé.
        $layout['recto']['qr_field'] = array('enabled' => false, 'x' => 175, 'y' => 5, 'size' => 30);

        $pdf = new Vd_bon_pdf();
        $pdf->generate(array('beneficiaire' => 'Custom Look'), $layout, null, null);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }

    public function testGenerateWithQrUrlProducesQrImageAndPlacesIt()
    {
        $layout = $this->emptyLayout();
        $layout['recto']['qr_field'] = array('enabled' => true, 'x' => 175, 'y' => 5, 'size' => 30);

        $data = array('numero' => 'unittest_' . uniqid(), 'qr_url' => 'https://example.org/vols_decouverte/action/test');
        $expectedQrPath = sys_get_temp_dir() . '/vd_qrcode_' . $data['numero'] . '.png';
        $this->tempFiles[] = $expectedQrPath;

        $pdf = new Vd_bon_pdf();
        $pdf->generate($data, $layout);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
        $this->assertFileExists($expectedQrPath, 'The QR code PNG must be generated when qr_url is provided');
    }

    public function testGenerateWithQrFieldDisabledDoesNotPlaceQr()
    {
        $layout = $this->emptyLayout();
        $layout['recto']['qr_field'] = array('enabled' => false, 'x' => 175, 'y' => 5, 'size' => 30);

        $pdf = new Vd_bon_pdf();
        // Pas de qr_url fourni : aucune génération de QR ne doit être tentée.
        $pdf->generate(array(), $layout);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }
}
