<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests — Forms_renderer::rewrite_local_image_urls() /
 * rewrite_shared_css_import() (Lot 2-quater).
 *
 * A form's stored page/style.css always references its own images
 * (`images/{file}`) and shared resources (`.commun/style.css`,
 * `.commun/images/{file}`) by a plain relative path — never a GVV route —
 * so the file stays openable in a browser (`file://`) and portable across
 * installations. These two methods rewrite that convention into the actual
 * serving route at render time, never mutating the stored file itself. See
 * "Ressources locales et partagées" in doc/design_notes/formulaires_sync_fichiers_design.md.
 */
class FormsRendererLocalUrlRewriteTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();

        global $CI;
        $CI->config->set_item('base_url', 'http://gvv.net/');
        $CI->config->set_item('index_page', '');
    }

    // -------------------------------------------------------------------
    // rewrite_local_image_urls()
    // -------------------------------------------------------------------

    public function testOwnFormImageIsRewrittenToImageRoute()
    {
        $html = '<img src="images/logo.jpg" alt="Logo">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame(
            '<img src="http://gvv.net/forms_public/image/mon-formulaire/logo.jpg" alt="Logo">',
            $out
        );
    }

    public function testSharedImageIsRewrittenToSharedImageRoute()
    {
        $html = '<img src=".commun/images/logo-club.jpg" alt="Club">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame(
            '<img src="http://gvv.net/forms_public/shared_image/logo-club.jpg" alt="Club">',
            $out
        );
    }

    public function testSingleQuotedSrcIsAlsoRewritten()
    {
        $html = "<img src='images/logo.jpg'>";

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame("<img src='http://gvv.net/forms_public/image/mon-formulaire/logo.jpg'>", $out);
    }

    public function testAbsoluteUrlIsLeftUntouched()
    {
        $html = '<img src="https://exemple.fr/logo.jpg">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame($html, $out);
    }

    public function testRootRelativeUrlIsLeftUntouched()
    {
        $html = '<img src="/assets/images/forms-widgets/signature-placeholder.svg">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame($html, $out);
    }

    public function testDataUriIsLeftUntouched()
    {
        $html = '<img src="data:image/png;base64,iVBORw0KGgo=">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame($html, $out);
    }

    public function testUnrelatedRelativePathIsLeftUntouched()
    {
        $html = '<img src="autre-dossier/logo.jpg">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire');

        $this->assertSame($html, $out);
    }

    public function testHtmlWithoutImgTagIsReturnedUnchanged()
    {
        $html = '<p>Aucune image ici.</p>';

        $this->assertSame($html, $this->renderer->rewrite_local_image_urls($html, 'mon-formulaire'));
    }

    public function testMultipleImagesWithMixedConventionsAreAllRewritten()
    {
        $html = '<img src="images/a.jpg"><img src=".commun/images/b.jpg"><img src="https://ext.example/c.jpg">';

        $out = $this->renderer->rewrite_local_image_urls($html, 'code123');

        $this->assertStringContainsString('src="http://gvv.net/forms_public/image/code123/a.jpg"', $out);
        $this->assertStringContainsString('src="http://gvv.net/forms_public/shared_image/b.jpg"', $out);
        $this->assertStringContainsString('src="https://ext.example/c.jpg"', $out);
    }

    // -------------------------------------------------------------------
    // rewrite_shared_css_import()
    // -------------------------------------------------------------------

    public function testSharedCssImportIsRewrittenToSharedCssRoute()
    {
        $css = '@import url(".commun/style.css");' . "\n.forms-public-root h1 { color: red; }";

        $out = $this->renderer->rewrite_shared_css_import($css);

        $this->assertStringContainsString('@import url("http://gvv.net/forms_public/shared_css");', $out);
        $this->assertStringContainsString('.forms-public-root h1 { color: red; }', $out);
    }

    public function testCssWithoutSharedImportIsReturnedUnchanged()
    {
        $css = '.forms-public-root h1 { color: red; }';

        $this->assertSame($css, $this->renderer->rewrite_shared_css_import($css));
    }

    public function testExternalImportIsLeftUntouched()
    {
        $css = "@import url('https://fonts.googleapis.com/css2?family=Caveat');";

        $this->assertSame($css, $this->renderer->rewrite_shared_css_import($css));
    }
}
