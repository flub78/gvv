<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Forms_renderer::repopulate_html_fields() checkbox handling.
 *
 * A "checkbox" field in practice is a single toggle: extract_html_fields() dedupes
 * form_fields by exact HTML name, so several <input type="checkbox"> sharing one name
 * can never produce more than one form_fields row — every real form (e.g.
 * attestation_de_fin_de_formation_spl-planeur) instead declares one uniquely-named
 * checkbox per option. Its stored value_text is therefore the plain string the browser
 * submits when checked ("on", or the input's own value= attribute) or "" when not —
 * never a JSON array. repopulate_html_fields() used to only handle an array $value
 * (checked_values), so every single-checkbox old_value was silently treated as
 * "nothing checked" — this broke checkbox prefill in Forms_admin::submission_edit().
 */
class FormsRendererCheckboxTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();
    }

    private function repopulate($html, array $fields, array $old_values)
    {
        return $this->renderer->repopulate_html_fields($html, $fields, $old_values);
    }

    public function test_single_checkbox_checked_when_value_is_non_empty_string()
    {
        $fields = array(array('id' => 1, 'name' => 'rp_masculin', 'field_type' => 'checkbox'));
        $html = '<input type="checkbox" name="rp_masculin">';

        $out = $this->repopulate($html, $fields, array(1 => 'on'));

        $this->assertStringContainsString('checked', $out);
    }

    public function test_single_checkbox_not_checked_when_value_is_empty_string()
    {
        $fields = array(array('id' => 1, 'name' => 'candidat_male', 'field_type' => 'checkbox'));
        $html = '<input type="checkbox" name="candidat_male">';

        $out = $this->repopulate($html, $fields, array(1 => ''));

        $this->assertStringNotContainsString('checked', $out);
    }

    public function test_multiple_independently_named_checkboxes_are_each_repopulated_correctly()
    {
        // Reproduces the real attestation_de_fin_de_formation_spl-planeur shape: several
        // single-toggle checkbox fields on the same page, a mix of checked/unchecked.
        $fields = array(
            array('id' => 1, 'name' => 'instruction_15', 'field_type' => 'checkbox'),
            array('id' => 2, 'name' => 'dc_10', 'field_type' => 'checkbox'),
            array('id' => 3, 'name' => 'treuil', 'field_type' => 'checkbox'),
        );
        $html = '<input type="checkbox" name="instruction_15">'
            . '<input type="checkbox" name="dc_10">'
            . '<input type="checkbox" name="treuil">';

        $out = $this->repopulate($html, $fields, array(1 => 'on', 2 => 'on', 3 => ''));

        $this->assertRegExp('/name="instruction_15"[^>]*checked/', $out);
        $this->assertRegExp('/name="dc_10"[^>]*checked/', $out);
        $this->assertNotRegExp('/name="treuil"[^>]*checked/', $out);
    }

    public function test_checkbox_group_with_array_value_still_works()
    {
        // Theoretical multi-value case (kept for backward compatibility): $value is an
        // array of checked option values, matched against each checkbox's own value=.
        $fields = array(array('id' => 1, 'name' => 'options', 'field_type' => 'checkbox'));
        $html = '<input type="checkbox" name="options" value="a">'
            . '<input type="checkbox" name="options" value="b">';

        $out = $this->repopulate($html, $fields, array(1 => array('b')));

        $this->assertNotRegExp('/value="a"[^>]*checked/', $out);
        $this->assertRegExp('/value="b"[^>]*checked/', $out);
    }
}
