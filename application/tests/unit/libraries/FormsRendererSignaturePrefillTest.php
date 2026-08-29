<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Forms_renderer::render_signature_widget() prefill handling.
 *
 * The GVV reference signature (membres.signature_path) may be a drawn/typed PNG or
 * an uploaded JPEG/GIF/WebP. Forms_public::_collect_gvv_sig_prefill() now passes a
 * full "data:<mime>;base64,<...>" URI; the widget must split it into a bare base64
 * payload (kept in the hidden value — CI2 XSS filtering strips "data:...;base64,")
 * plus a data-sig-prefill-mime attribute the JS uses to rebuild the data URI.
 *
 * Regression: JPEG reference signatures rendered blank because the pipeline
 * hard-coded "data:image/png;base64,".
 */
class FormsRendererSignaturePrefillTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();
    }

    public function test_bare_base64_prefill_defaults_to_png()
    {
        $html = $this->renderer->render_signature_widget('sig', 'Signature', false, 'QUJD');

        $this->assertStringContainsString('data-sig-prefill="1"', $html);
        $this->assertStringContainsString('data-sig-prefill-mime="image/png"', $html);
        $this->assertStringContainsString('value="QUJD"', $html);
    }

    public function test_data_uri_prefill_splits_mime_and_payload()
    {
        $html = $this->renderer->render_signature_widget('sig', 'Signature', false, 'data:image/jpeg;base64,QUJD');

        $this->assertStringContainsString('data-sig-prefill="1"', $html);
        $this->assertStringContainsString('data-sig-prefill-mime="image/jpeg"', $html);
        // The hidden field holds only the bare payload, never the "data:...;base64," prefix.
        $this->assertStringContainsString('value="QUJD"', $html);
        $this->assertStringNotContainsString('value="data:image/jpeg', $html);
    }

    public function test_no_prefill_emits_no_prefill_attributes()
    {
        $html = $this->renderer->render_signature_widget('sig', 'Signature', false, '');

        $this->assertStringNotContainsString('data-sig-prefill=', $html);
        $this->assertStringNotContainsString('data-sig-prefill-mime=', $html);
    }
}
