<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Forms_renderer::scope_css().
 *
 * Reproduces the CSS pollution observed on forms_admin/submission_edit/3/2:
 * a form's own `global_css` was injected as-is (only `body` rewritten to
 * `.forms-public-root`), so any other bare selector (e.g. `.header`) also
 * matched unrelated GVV elements sharing the same class name. scope_css()
 * prefixes every selector with the form's scope class, except selectors
 * that deliberately use `:has(<scope class>)` to restyle their own
 * Bootstrap wrapper from the outside (full-page print layouts) — see
 * doc/design_notes/formulaires_css_isolation_design.md.
 */
class FormsRendererCssScopeTest extends TestCase
{
    private $renderer;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Forms_renderer.php';
        $this->renderer = new Forms_renderer();
    }

    private function scope($css, $scope_class = 'forms-public-root')
    {
        return $this->renderer->scope_css($css, $scope_class);
    }

    public function test_bare_selector_is_prefixed_with_scope_class()
    {
        $out = $this->scope('.header { background: #1a3a5c; }');

        $this->assertStringContainsString('.forms-public-root .header', $out);
    }

    public function test_body_selector_becomes_scope_class()
    {
        $out = $this->scope('body { margin: 0; }');

        $this->assertStringContainsString('.forms-public-root {', $out);
        $this->assertStringNotContainsString('body {', $out);
    }

    public function test_grouped_selectors_are_each_prefixed()
    {
        $out = $this->scope('h1, .title { color: red; }');

        $this->assertStringContainsString('.forms-public-root h1', $out);
        $this->assertStringContainsString('.forms-public-root .title', $out);
    }

    public function test_has_escape_hatch_targeting_scope_class_is_left_untouched()
    {
        $out = $this->scope('.card:has(.forms-public-root) { border: none; }');

        $this->assertStringContainsString('.card:has(.forms-public-root) {', $out);
        $this->assertStringNotContainsString('.forms-public-root .card:has', $out);
    }

    public function test_media_query_content_is_prefixed_but_wrapper_kept()
    {
        $out = $this->scope('@media print { .document { box-shadow: none; } }');

        $this->assertStringContainsString('@media print {', $out);
        $this->assertStringContainsString('.forms-public-root .document', $out);
    }

    public function test_keyframes_steps_are_not_prefixed()
    {
        $out = $this->scope('@keyframes fade { 0% { opacity: 0; } 100% { opacity: 1; } }');

        $this->assertStringContainsString('@keyframes fade { 0% { opacity: 0; } 100% { opacity: 1; } }', $out);
    }

    public function test_scope_class_with_css_scope_suffix_uses_compound_selector()
    {
        $out = $this->scope('.title { color: blue; }', 'forms-public-root myform');

        $this->assertStringContainsString('.forms-public-root.myform .title', $out);
    }

    public function test_already_scoped_selector_is_not_double_prefixed()
    {
        $out = $this->scope('.forms-public-root .title { color: blue; }');

        $this->assertStringNotContainsString('.forms-public-root .forms-public-root', $out);
    }

    public function test_real_form_css_header_collision_is_fixed()
    {
        // Reproduces the exact rule from forms.global_css of the attestation_de_formation_ulm
        // form (id 3) that collided with GVV's own <h1 class="header"> page banner.
        $css = '.header { background: #1a3a5c; color: #fff; padding: 14px 20px 12px; }';

        $out = $this->scope($css);

        $this->assertStringContainsString('.forms-public-root .header', $out);
    }

    public function test_empty_css_returns_empty()
    {
        $this->assertSame('', $this->scope(''));
    }
}
