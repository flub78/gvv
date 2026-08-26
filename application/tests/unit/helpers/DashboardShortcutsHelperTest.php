<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

class DashboardShortcutsHelperTest extends PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        $CI = &get_instance();
        $CI->load->helper('dashboard_shortcuts');
    }

    public function test_helper_loads_successfully() {
        $this->assertTrue(function_exists('normalize_dashboard_shortcut_url'));
    }

    public function test_relative_url_is_left_unchanged() {
        $this->assertSame(
            'forms_admin/submissions/14',
            normalize_dashboard_shortcut_url('forms_admin/submissions/14', 'http://gvv.net/')
        );
    }

    public function test_empty_url_is_left_unchanged() {
        $this->assertSame('', normalize_dashboard_shortcut_url('', 'http://gvv.net/'));
    }

    public function test_absolute_url_on_own_domain_is_made_relative() {
        $this->assertSame(
            'forms_admin/submissions/14',
            normalize_dashboard_shortcut_url(
                'https://gestion.aeroclub-abbeville.fr/forms_admin/submissions/14',
                'https://gestion.aeroclub-abbeville.fr/'
            )
        );
    }

    public function test_absolute_url_on_own_domain_keeps_query_and_fragment() {
        $this->assertSame(
            'forms_admin/submissions/14?tab=x#section',
            normalize_dashboard_shortcut_url(
                'https://gvv.net/forms_admin/submissions/14?tab=x#section',
                'http://gvv.net/'
            )
        );
    }

    public function test_absolute_url_on_own_domain_matches_regardless_of_scheme() {
        $this->assertSame(
            'forms_admin/submissions/14',
            normalize_dashboard_shortcut_url(
                'http://gvv.net/forms_admin/submissions/14',
                'https://gvv.net/'
            )
        );
    }

    public function test_absolute_url_on_other_domain_is_left_unchanged() {
        $external = 'https://www.aerovfr.com/some/page';
        $this->assertSame(
            $external,
            normalize_dashboard_shortcut_url($external, 'http://gvv.net/')
        );
    }

    public function test_uses_config_base_url_when_none_given() {
        $CI = &get_instance();
        $CI->config->set_item('base_url', 'http://gvv.net/');

        $this->assertSame(
            'some/path',
            normalize_dashboard_shortcut_url('http://gvv.net/some/path')
        );
    }
}
