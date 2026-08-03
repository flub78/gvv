<?php

use PHPUnit\Framework\TestCase;

/**
 * Non-regression bridge between Vols_decouverte_looks_model's embedded
 * default layout (Lot 1) and the Vd_bon_pdf rendering engine (Lot 2) —
 * doc/plans/configuration_bons_vols_decouverte_plan.md.
 *
 * Confirms the default layout reproduces the legacy hardcoded values from
 * vols_decouverte::generate_pdf() (QR code at x=175, y=5, size=30, recto
 * only) and that the engine can render it end-to-end without a
 * DB-persisted look (fresh install, no custom configuration yet).
 *
 * @see application/libraries/Vd_bon_pdf.php
 * @see application/models/vols_decouverte_looks_model.php
 */
class VdBonPdfMySqlTest extends TestCase
{
    private $CI;
    private $looks;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('assets');
        $this->CI->load->model('vols_decouverte_looks_model');
        $this->looks = $this->CI->vols_decouverte_looks_model;

        require_once APPPATH . 'libraries/Vd_bon_pdf.php';
    }

    private function assertIsPdf($output)
    {
        $this->assertNotEmpty($output, 'Generated PDF output must not be empty');
        $this->assertStringStartsWith('%PDF', $output, 'Output must start with the PDF magic header');
    }

    public function testDefaultLayoutQrPositionMatchesLegacyHardcodedValues()
    {
        $default = $this->looks->get_default_look();
        $layout = $this->looks->get_layout($default);

        // Valeurs codées en dur dans vols_decouverte::generate_pdf() ($qrX, $qrY, $qrSize).
        $this->assertEquals(
            array('enabled' => true, 'x' => 175, 'y' => 5, 'size' => 30),
            $layout['recto']['qr_field'],
            'Default layout QR position must match the legacy hardcoded (175, 5, 30mm)'
        );
        $this->assertNull($layout['verso']['qr_field'], 'Legacy mechanism only places the QR code on the recto');
    }

    public function testDefaultLayoutExposesAllLegacyVariableFields()
    {
        $default = $this->looks->get_default_look();
        $layout = $this->looks->get_layout($default);

        $ids = array_column($layout['verso']['variable_fields'], 'id');
        foreach (array('numero', 'beneficiaire', 'occasion', 'de_la_part', 'date_validite', 'type_vol') as $expected) {
            $this->assertContains($expected, $ids, "Default layout must expose the legacy field '$expected'");
        }
    }

    public function testEngineRendersDefaultLayoutWithLegacyBackgroundFallback()
    {
        $default = $this->looks->get_default_look();
        $layout = $this->looks->get_layout($default);

        $data = array(
            'numero' => 999,
            'beneficiaire' => 'Test Bénéficiaire',
            'occasion' => 'Test occasion',
            'de_la_part' => 'Test donateur',
            'date_validite' => '31/12/2026',
            'type_vol' => 'Un vol en planeur',
            'qr_url' => 'https://example.org/vols_decouverte/action/test',
        );

        // Un look sans fond configuré (cas par défaut d'une installation neuve)
        // doit retomber sur l'image historique, comme generate_pdf() le fait
        // via configuration_model->get_file('vd.background_image').
        $fond_recto = image_dir() . 'Bon-Bapteme.png';

        $pdf = new Vd_bon_pdf();
        $pdf->generate($data, $layout, $fond_recto, null);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);

        $qr_path = sys_get_temp_dir() . '/vd_qrcode_999.png';
        $this->assertFileExists($qr_path, 'QR code image must be generated for the recto');
        unlink($qr_path);
    }

    public function testEngineRendersLookResolvedForSectionWithoutAssociation()
    {
        // Section sans association explicite (migration 153) : doit résoudre
        // sur le look par défaut, exactement comme un club qui n'a encore
        // rien configuré.
        $look = $this->looks->get_look_for_section(1);
        $layout = $this->looks->get_layout($look);

        $pdf = new Vd_bon_pdf();
        $pdf->generate(array('numero' => 1000), $layout);
        $output = $pdf->Output('vd_test.pdf', 'S');

        $this->assertIsPdf($output);
    }
}
