<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Forms_field_parser (Lot 2-bis) — parses field descriptors
 * on demand from a page's HTML, replacing the form_fields table removed by
 * migration 166. Pure DOM parsing, no CodeIgniter dependency.
 */
class FormsFieldParserTest extends TestCase
{
    private $parser;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_field_parser.php';
        $this->parser = new Forms_field_parser();
    }

    public function testParsesTextInputWithLabelAndRequired()
    {
        $html = '<label for="nom">Nom du candidat</label>'
              . '<input type="text" id="nom" name="nom" required>';

        $fields = $this->parser->parse_fields($html);

        $this->assertCount(1, $fields);
        $this->assertSame('nom', $fields[0]['name']);
        $this->assertSame('Nom du candidat', $fields[0]['label']);
        $this->assertSame('text', $fields[0]['field_type']);
        $this->assertSame(1, $fields[0]['is_required']);
    }

    public function testFallsBackToNameWhenNoLabel()
    {
        $fields = $this->parser->parse_fields('<input type="email" name="courriel">');

        $this->assertSame('courriel', $fields[0]['label']);
        $this->assertSame('email', $fields[0]['field_type']);
        $this->assertSame(0, $fields[0]['is_required']);
    }

    public function testSkipsHiddenSubmitResetButtonAndImageInputs()
    {
        $html = '<input type="text" name="visible">'
              . '<input type="hidden" name="token">'
              . '<input type="submit" name="go" value="Envoyer">'
              . '<input type="reset" name="clear">'
              . '<input type="button" name="btn">'
              . '<input type="image" name="pic">';

        $fields = $this->parser->parse_fields($html);

        $this->assertCount(1, $fields);
        $this->assertSame('visible', $fields[0]['name']);
    }

    public function testDedupesRepeatedNameKeepingFirstOccurrence()
    {
        $html = '<input type="radio" name="choix" value="a">'
              . '<input type="radio" name="choix" value="b">';

        $fields = $this->parser->parse_fields($html);

        $this->assertCount(1, $fields);
    }

    public function testExtractsSelectOptionsSkippingEmptyValue()
    {
        $html = '<select name="niveau">'
              . '<option value="">-- choisir --</option>'
              . '<option value="1">Débutant</option>'
              . '<option value="2">Confirmé</option>'
              . '</select>';

        $fields = $this->parser->parse_fields($html);

        $this->assertSame('select', $fields[0]['field_type']);
        $this->assertSame(array('Débutant', 'Confirmé'), $fields[0]['options']);
    }

    /**
     * Label extraction must survive the widget placeholder <img>/<br> markup
     * added by the substitution-image convention (Lot 2-bis, task #8) — the
     * visible text label is what remains after strip_tags(), same mechanism
     * used by Forms_renderer::inject_signature_widgets()/inject_subform_widgets().
     */
    public function testSignatureWidgetLabelSurvivesPlaceholderImageMarkup()
    {
        $html = '<div data-gvv-type="signature" data-gvv-name="signature_membre" data-gvv-required="true">'
              . '<img src="/assets/images/forms-widgets/signature-placeholder.svg" alt="Zone de signature"><br>'
              . 'Signature du membre</div>';

        $fields = $this->parser->parse_fields($html);

        $this->assertCount(1, $fields);
        $this->assertSame('signature_membre', $fields[0]['name']);
        $this->assertSame('signature', $fields[0]['field_type']);
        $this->assertSame('Signature du membre', $fields[0]['label']);
        $this->assertSame(1, $fields[0]['is_required']);
    }

    public function testSubformWidgetLabelSurvivesPlaceholderImageMarkup()
    {
        $html = '<div data-gvv-type="subform" data-gvv-name="inscription_bia" data-gvv-form-slug="inscription-bia">'
              . '<img src="/assets/images/forms-widgets/subform-placeholder.svg" alt="Sous-formulaire à compléter"><br>'
              . 'Brevet d\'Initiation Aéronautique (BIA)</div>';

        $fields = $this->parser->parse_fields($html);

        $this->assertCount(1, $fields);
        $this->assertSame('inscription_bia', $fields[0]['name']);
        $this->assertSame('subform', $fields[0]['field_type']);
        $this->assertSame('Brevet d\'Initiation Aéronautique (BIA)', $fields[0]['label']);
    }

    public function testParsesValidationRulesAndIdentifierAttributes()
    {
        $html = '<input type="text" name="tel" data-gvv-validation="required|numeric" data-gvv-identifier="true">';

        $fields = $this->parser->parse_fields($html);

        $this->assertSame('required|numeric', $fields[0]['validation_rules']);
        $this->assertSame(1, $fields[0]['is_identifier']);
    }

    public function testIdentifierFalseIsNotTreatedAsIdentifier()
    {
        $html = '<input type="text" name="tel" data-gvv-identifier="false">';

        $fields = $this->parser->parse_fields($html);

        $this->assertSame(0, $fields[0]['is_identifier']);
    }

    public function testEmptyHtmlReturnsEmptyArray()
    {
        $this->assertSame(array(), $this->parser->parse_fields(''));
        $this->assertSame(array(), $this->parser->parse_fields('   '));
    }

    public function testParseFormPagesTagsEachFieldWithItsPageNumber()
    {
        $pages = array(
            array('page_number' => 1, 'content_html' => '<input type="text" name="nom">'),
            array('page_number' => 2, 'content_html' => '<input type="text" name="prenom">'),
        );

        $fields = $this->parser->parse_form_pages($pages);

        $this->assertCount(2, $fields);
        $this->assertSame(1, $fields[0]['page_number']);
        $this->assertSame('nom', $fields[0]['name']);
        $this->assertSame(2, $fields[1]['page_number']);
        $this->assertSame('prenom', $fields[1]['name']);
    }

    public function testFieldsByNameIndexesByName()
    {
        $fields = array(
            array('name' => 'nom', 'label' => 'Nom'),
            array('name' => 'prenom', 'label' => 'Prénom'),
        );

        $by_name = $this->parser->fields_by_name($fields);

        $this->assertSame(array('nom', 'prenom'), array_keys($by_name));
        $this->assertSame('Prénom', $by_name['prenom']['label']);
    }
}
