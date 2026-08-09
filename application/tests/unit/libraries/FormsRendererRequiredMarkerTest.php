<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests — Forms_renderer::inject_required_markers().
 *
 * A required field is marked with a red asterisk (`text-danger`, the same
 * convention already used by render_signature_widget()) at render time only —
 * the stored file itself is never mutated, same rewrite-only rationale as
 * rewrite_local_image_urls()/rewrite_shared_css_import().
 */
class FormsRendererRequiredMarkerTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();
    }

    public function testRequiredFieldLabelGetsMarked()
    {
        $html = '<label for="nom">Nom</label><input type="text" id="nom" name="nom" required>';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertStringContainsString(
            '<label for="nom">Nom <span class="text-danger">*</span></label>',
            $out
        );
    }

    public function testOptionalFieldLabelIsLeftUntouched()
    {
        $html = '<label for="profession">Profession</label><input type="text" id="profession" name="profession">';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertSame($html, $out);
    }

    public function testRequiredAttributeWithExplicitValueIsAlsoDetected()
    {
        $html = '<label for="nom">Nom</label><input type="text" id="nom" name="nom" required="required">';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertStringContainsString('text-danger', $out);
    }

    public function testRequiredCheckboxWrappedByItsOwnLabelIsMarked()
    {
        $html = '<label for="lu_approuve">'
              . '<input type="checkbox" id="lu_approuve" name="lu_approuve" required>'
              . ' Lu et approuvé</label>';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertStringContainsString(
            'Lu et approuvé <span class="text-danger">*</span></label>',
            $out
        );
    }

    public function testFieldWithoutIdIsNotMarked()
    {
        // No `id` on the input: nothing for a `for="..."` label to pair against.
        $html = '<label>Nom <span class="text-danger">*</span></label><input type="text" name="nom" required>';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertSame($html, $out);
    }

    public function testAlreadyManuallyMarkedLabelIsNotDoubleMarked()
    {
        $html = '<label for="nom">Nom <span class="text-danger">*</span></label>'
              . '<input type="text" id="nom" name="nom" required>';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertSame(1, substr_count($out, 'text-danger'));
    }

    public function testLabelForUnrelatedFieldIsNotMarked()
    {
        $html = '<label for="nom">Nom</label><input type="text" id="nom" name="nom" required>'
              . '<label for="prenom">Prenom</label><input type="text" id="prenom" name="prenom">';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertStringContainsString(
            '<label for="nom">Nom <span class="text-danger">*</span></label>',
            $out
        );
        $this->assertStringContainsString('<label for="prenom">Prenom</label>', $out);
    }

    public function testHtmlWithoutRequiredIsReturnedUnchanged()
    {
        $html = '<label for="nom">Nom</label><input type="text" id="nom" name="nom">';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertSame($html, $out);
    }

    public function testHtmlWithoutLabelIsReturnedUnchanged()
    {
        $html = '<input type="text" id="nom" name="nom" required>';

        $out = $this->renderer->inject_required_markers($html);

        $this->assertSame($html, $out);
    }
}
