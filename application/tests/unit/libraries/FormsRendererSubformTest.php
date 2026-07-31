<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Forms_renderer::inject_subform_widgets() / render_subform_widget()
 * (Lot 11 — sous-formulaires).
 */
class FormsRendererSubformTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();
    }

    public function test_widget_without_matching_state_is_left_untouched()
    {
        $html = '<div data-gvv-type="subform" data-gvv-name="briefing">Briefing</div>';
        $has_widget = false;

        $out = $this->renderer->inject_subform_widgets($html, $has_widget, array());

        $this->assertFalse($has_widget);
        $this->assertSame($html, $out);
    }

    public function test_empty_state_renders_open_link_and_hides_filled_block()
    {
        $html = '<div data-gvv-type="subform" data-gvv-name="briefing" data-gvv-required="true">Briefing passager</div>';
        $has_widget = false;

        $state = array('briefing' => array(
            'sub_url'    => 'https://gvv.example/forms/briefing?link_token=abc',
            'verify_url' => 'https://gvv.example/forms/subform-status/abc',
            'reset_url'  => 'https://gvv.example/forms/subform-reset/master/briefing?form_slug=briefing',
            'status'     => 'empty',
            'summary'    => array(),
        ));

        $out = $this->renderer->inject_subform_widgets($html, $has_widget, $state);

        $this->assertTrue($has_widget);
        $this->assertStringContainsString('data-subform-status="empty"', $out);
        $this->assertStringContainsString('data-gvv-required="true"', $out);
        $this->assertStringContainsString('https://gvv.example/forms/briefing?link_token=abc', $out);
        $this->assertStringContainsString('gvv-subform-filled d-none', $out);
        $this->assertStringNotContainsString('gvv-subform-empty d-none', $out);
    }

    public function test_submitted_state_shows_summary_and_reveals_filled_block()
    {
        $html = '<div data-gvv-type="subform" data-gvv-name="briefing">Briefing passager</div>';
        $has_widget = false;

        $state = array('briefing' => array(
            'sub_url'    => 'https://gvv.example/forms/briefing?link_token=abc',
            'verify_url' => 'https://gvv.example/forms/subform-status/abc',
            'reset_url'  => 'https://gvv.example/forms/subform-reset/master/briefing?form_slug=briefing',
            'status'     => 'submitted',
            'summary'    => array(array('label' => 'Nom', 'value' => 'Dupont')),
        ));

        $out = $this->renderer->inject_subform_widgets($html, $has_widget, $state);

        $this->assertTrue($has_widget);
        $this->assertStringContainsString('data-subform-status="submitted"', $out);
        $this->assertStringContainsString('gvv-subform-empty d-none', $out);
        $this->assertStringNotContainsString('gvv-subform-filled d-none', $out);
        $this->assertStringContainsString('Nom', $out);
        $this->assertStringContainsString('Dupont', $out);
        $this->assertStringContainsString('Remplir à nouveau', $out);
    }

    public function test_two_widgets_on_the_same_page_are_each_rendered_from_their_own_state()
    {
        $html = '<div data-gvv-type="subform" data-gvv-name="a">A</div>'
              . '<div data-gvv-type="subform" data-gvv-name="b">B</div>';
        $has_widget = false;

        $state = array(
            'a' => array('sub_url' => 'u1', 'verify_url' => 'v1', 'reset_url' => 'r1', 'status' => 'empty', 'summary' => array()),
            'b' => array('sub_url' => 'u2', 'verify_url' => 'v2', 'reset_url' => 'r2', 'status' => 'submitted', 'summary' => array()),
        );

        $out = $this->renderer->inject_subform_widgets($html, $has_widget, $state);

        $this->assertStringContainsString('data-subform-name="a"', $out);
        $this->assertStringContainsString('data-subform-name="b"', $out);
        $this->assertStringContainsString('href="u1"', $out);
        $this->assertStringContainsString('href="r2"', $out);
    }
}
