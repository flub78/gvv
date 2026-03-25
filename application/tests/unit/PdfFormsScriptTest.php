<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for bin/pdf_forms.py (Lot 1).
 */
class PdfFormsScriptTest extends TestCase
{
    private $pdf_fixture;
    private $script;
    private $project_root;

    protected function setUp(): void
    {
        $this->project_root = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
        $this->pdf_fixture = $this->project_root . 'doc/design_notes/documents/134iFormlic.pdf';
        $this->script = $this->project_root . 'bin/pdf_forms.py';

        if (!file_exists($this->pdf_fixture)) {
            $this->markTestSkipped('PDF fixture not found: ' . $this->pdf_fixture);
        }
        if (!file_exists($this->script)) {
            $this->markTestSkipped('Script not found: ' . $this->script);
        }
    }

    public function testExtractCommandReturnsJsonFields()
    {
        $cmd = 'python3 ' . escapeshellarg($this->script)
            . ' extract --pdf ' . escapeshellarg($this->pdf_fixture)
            . ' 2>&1';
        $output = shell_exec($cmd);

        $this->assertNotEmpty($output, 'Extract output should not be empty');

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded, 'Extract output should be valid JSON array');
        $this->assertNotEmpty($decoded, 'Extract should return at least one field');

        $first = $decoded[0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('type', $first);
    }

    public function testFillCommandSupportsUtf8AndProducesReadablePdf()
    {
        $tmp_output = sys_get_temp_dir() . '/pdf_forms_test_' . uniqid() . '.pdf';
        $tmp_data = sys_get_temp_dir() . '/pdf_forms_data_' . uniqid() . '.json';

        // Use real field names from 134iFormlic
        $payload = array(
            'fields' => array(
                'CopieScan rectoverso de la pièce didentité' => true,
                'Nom de famille 1' => 'ÉLÈVE TEST',
                'Prénoms' => 'André'
            )
        );

        file_put_contents($tmp_data, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $fill_cmd = 'python3 ' . escapeshellarg($this->script)
            . ' fill --pdf ' . escapeshellarg($this->pdf_fixture)
            . ' --json_data ' . escapeshellarg($tmp_data)
            . ' --output ' . escapeshellarg($tmp_output)
            . ' 2>&1';

        $fill_output = shell_exec($fill_cmd);
        $this->assertTrue($fill_output === '' || $fill_output === null, 'stdout should be empty in exclusive file mode for fill');

        $this->assertFileExists($tmp_output, 'Filled PDF should be created');
        $this->assertGreaterThan(0, filesize($tmp_output), 'Filled PDF should not be empty');

        $extract_cmd = 'python3 ' . escapeshellarg($this->script)
            . ' extract --pdf ' . escapeshellarg($tmp_output)
            . ' 2>&1';
        $extract_output = shell_exec($extract_cmd);
        $decoded = json_decode($extract_output, true);

        $this->assertIsArray($decoded, 'Extracted output from filled PDF should be valid JSON');

        $by_name = array();
        foreach ($decoded as $field) {
            if (isset($field['name'])) {
                $by_name[$field['name']] = $field;
            }
        }

        $this->assertEquals('ÉLÈVE TEST', $by_name['Nom de famille 1']['value']);
        $this->assertEquals('André', $by_name['Prénoms']['value']);
        $this->assertArrayHasKey('value', $by_name['CopieScan rectoverso de la pièce didentité']);
        $this->assertNotEquals('/Off', $by_name['CopieScan rectoverso de la pièce didentité']['value']);

        @unlink($tmp_output);
        @unlink($tmp_data);
    }

    public function testExtractWithJsonFieldsUsesExclusiveFileOutput()
    {
        $tmp_fields = sys_get_temp_dir() . '/pdf_forms_fields_' . uniqid() . '.json';

        $cmd = 'python3 ' . escapeshellarg($this->script)
            . ' extract --pdf ' . escapeshellarg($this->pdf_fixture)
            . ' --json_fields ' . escapeshellarg($tmp_fields)
            . ' 2>&1';

        $output = shell_exec($cmd);

        $this->assertTrue($output === '' || $output === null, 'stdout should be empty in exclusive file mode');
        $this->assertFileExists($tmp_fields, 'json_fields output file should be created');

        $decoded = json_decode((string) file_get_contents($tmp_fields), true);
        $this->assertIsArray($decoded, 'json_fields output should be valid JSON array');
        $this->assertNotEmpty($decoded, 'json_fields output should contain fields');

        @unlink($tmp_fields);
    }

    public function testFillCommandSupportsImageOverlay()
    {
        $tmp_output = sys_get_temp_dir() . '/pdf_forms_img_test_' . uniqid() . '.pdf';
        $tmp_data = sys_get_temp_dir() . '/pdf_forms_img_data_' . uniqid() . '.json';
        $tmp_overlay_pdf = sys_get_temp_dir() . '/pdf_forms_overlay_' . uniqid() . '.pdf';
        copy($this->pdf_fixture, $tmp_overlay_pdf);

        $payload = array(
            'fields' => array(
                'Nom de famille 1' => 'IMAGE TEST',
            ),
            'images' => array(
                array(
                    'pdf' => $tmp_overlay_pdf,
                    'page' => 0,
                    'x' => 36,
                    'y' => 36,
                    'width' => 120,
                    'height' => 40,
                )
            )
        );

        file_put_contents($tmp_data, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $fill_cmd = 'python3 ' . escapeshellarg($this->script)
            . ' fill --pdf ' . escapeshellarg($this->pdf_fixture)
            . ' --json_data ' . escapeshellarg($tmp_data)
            . ' --output ' . escapeshellarg($tmp_output)
            . ' 2>&1';

        $fill_output = shell_exec($fill_cmd);
        $this->assertTrue($fill_output === '' || $fill_output === null, 'stdout should be empty in fill mode with image overlay');

        $this->assertFileExists($tmp_output, 'Filled PDF with image should be created');
        $this->assertGreaterThan(0, filesize($tmp_output), 'Filled PDF with image should not be empty');

        $extract_cmd = 'python3 ' . escapeshellarg($this->script)
            . ' extract --pdf ' . escapeshellarg($tmp_output)
            . ' 2>&1';
        $extract_output = shell_exec($extract_cmd);
        $decoded = json_decode($extract_output, true);
        $this->assertIsArray($decoded, 'Extracted output from image-filled PDF should be valid JSON');

        $by_name = array();
        foreach ($decoded as $field) {
            if (isset($field['name'])) {
                $by_name[$field['name']] = $field;
            }
        }
        $this->assertEquals('IMAGE TEST', $by_name['Nom de famille 1']['value']);

        @unlink($tmp_output);
        @unlink($tmp_data);
        @unlink($tmp_overlay_pdf);
    }
}
