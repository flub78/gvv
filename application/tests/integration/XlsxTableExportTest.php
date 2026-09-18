<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for GVVMetadata::xlsx_table() and the "xlsx" mode of
 * array_field().
 *
 * See doc/design_notes/exports_feuille_de_calcul_design.md — unlike the
 * existing csv/pdf export, xlsx mode must keep values typed (float, int)
 * instead of pre-formatted display strings, so the operator can compute on
 * the exported data directly in the spreadsheet.
 */
class XlsxTableExportTest extends TestCase
{
    private $gvvmetadata;

    public function setUp(): void
    {
        $CI = get_instance();
        $CI->load->helper('form');
        $CI->load->helper('download');

        if (!class_exists('GVVMetadata')) {
            require_once APPPATH . 'libraries/MetaData.php';
            require_once APPPATH . 'libraries/Gvvmetadata.php';
        }

        $this->gvvmetadata = new GVVMetadata();
    }

    private function callArrayField($table, $field, $value, &$row, $mode)
    {
        $method = new ReflectionMethod('GVVMetadata', 'array_field');
        $method->setAccessible(true);
        return $method->invokeArgs($this->gvvmetadata, array($table, $field, $value, &$row, $mode));
    }

    /**
     * A 'currency' field must come back as a real float in xlsx mode (not the
     * "12,50" formatted string csv mode produces), while csv mode is
     * unaffected.
     */
    public function testCurrencyFieldIsTypedFloatInXlsxMode()
    {
        $row = ['debit' => '1234.5'];

        $xlsx_value = $this->callArrayField('vue_comptes', 'debit', '1234.5', $row, 'xlsx');
        $this->assertIsFloat($xlsx_value, "xlsx mode should return a float for a currency field");
        $this->assertEqualsWithDelta(1234.5, $xlsx_value, 0.001);

        $csv_value = $this->callArrayField('vue_comptes', 'debit', '1234.5', $row, 'csv');
        $this->assertIsString($csv_value, "csv mode should still return a formatted string (unchanged behaviour)");
    }

    /**
     * A 'boolean' field must come back as an int 0/1 in xlsx mode (the
     * underlying xlsx writer has no boolean cell type and silently drops
     * unrecognised PHP types).
     */
    public function testBooleanFieldIsTypedIntInXlsxMode()
    {
        $row = [];
        $this->assertSame(1, $this->callArrayField('volsp', 'vpdc', 1, $row, 'xlsx'));
        $this->assertSame(0, $this->callArrayField('volsp', 'vpdc', 0, $row, 'xlsx'));
    }

    /**
     * Empty/null values must come back as an empty string in xlsx mode
     * (rather than "0.0" or similar) so cells stay genuinely blank.
     */
    public function testEmptyValueIsEmptyStringInXlsxMode()
    {
        $row = ['debit' => ''];
        $this->assertSame('', $this->callArrayField('vue_comptes', 'debit', '', $row, 'xlsx'));
    }

    /**
     * xlsx_table() builds its workbook via xlsx_workbook(), which must
     * produce a valid xlsx (zip) binary: PK signature, non-trivial size.
     * Tested through xlsx_workbook() directly (no downloadAs()/headers), see
     * xlsx_table()'s own doc comment for why the two are split.
     */
    public function testXlsxTableProducesValidWorkbook()
    {
        $data = [
            ['debit' => '100.00', 'credit' => '0.00'],
            ['debit' => '0.00', 'credit' => '50.25'],
        ];

        list($xlsx, $filename) = $this->gvvmetadata->xlsx_workbook('vue_comptes', $data, [
            'title' => 'Test export xlsx',
            'fields' => ['debit', 'credit'],
        ]);
        $content = (string) $xlsx;

        $this->assertStringStartsWith('PK', $content, "xlsx_workbook() output should be a valid zip/xlsx binary (PK signature)");
        $this->assertGreaterThan(1000, strlen($content), "generated xlsx should not be empty/truncated");
        $this->assertStringEndsWith('.xlsx', $filename, "filename should have an .xlsx extension");
    }
}
