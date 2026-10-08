<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Forms_renderer::inject_stamp() (Lot 17 / EF19, tampon de l'association).
 */
class FormsRendererStampTest extends TestCase
{
    private $renderer;
    private $uri = 'data:image/png;base64,AAAA';

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();
    }

    public function test_html_without_stamp_widget_is_untouched()
    {
        $html = '<p>Texte</p><div data-gvv-type="signature" data-gvv-name="sig">Signature</div>';
        $this->assertSame($html, $this->renderer->inject_stamp($html, $this->uri));
    }

    public function test_placeholder_is_replaced_by_stamp_image_and_div_style_kept()
    {
        $html = '<div style="position:relative"><div data-gvv-type="stamp" style="position:absolute; right:0; width:4cm">'
              . '<img src="/assets/images/forms-widgets/stamp-placeholder.svg" alt="Tampon"> Tampon de l\'association</div></div>';

        $out = $this->renderer->inject_stamp($html, $this->uri);

        $this->assertStringContainsString('src="' . $this->uri . '"', $out);
        $this->assertStringContainsString('position:absolute; right:0; width:4cm', $out);
        $this->assertStringNotContainsString('stamp-placeholder.svg', $out);
        $this->assertStringNotContainsString('Tampon de l', $out);
    }

    public function test_without_stamp_the_widget_is_emptied()
    {
        $html = '<div data-gvv-type="stamp"><img src="/assets/images/forms-widgets/stamp-placeholder.svg"> Tampon</div>';

        $out = $this->renderer->inject_stamp($html, null);

        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringNotContainsString('Tampon', $out);
        $this->assertStringContainsString('data-gvv-type="stamp"', $out);
    }

    public function test_every_stamp_widget_is_replaced_and_type_is_case_insensitive()
    {
        $html = '<div data-gvv-type="stamp">A</div><p>é</p><div data-gvv-type="STAMP">B</div>';

        $out = $this->renderer->inject_stamp($html, $this->uri);

        $this->assertSame(2, substr_count($out, $this->uri));
        $this->assertStringContainsString('é', $out, 'UTF-8 text must survive the DOM round-trip.');
    }
}
