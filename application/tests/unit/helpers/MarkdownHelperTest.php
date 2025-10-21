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

class MarkdownHelperTest extends PHPUnit\Framework\TestCase {

    private $CI;

    protected function setUp(): void {
        $this->CI = &get_instance();
        $this->CI->load->helper('markdown');
    }

    public function test_markdown_helper_loads_successfully() {
        $this->assertTrue(function_exists('markdown'), 'Markdown helper function should exist');
    }

    public function test_markdown_converts_headers() {
        $input = "# Heading 1\n## Heading 2";
        $output = markdown($input);
        $this->assertStringContainsString('<h1>Heading 1</h1>', $output);
        $this->assertStringContainsString('<h2>Heading 2</h2>', $output);
    }

    public function test_markdown_converts_bold_and_italic() {
        $input = "This is **bold** and *italic* text.";
        $output = markdown($input);
        $this->assertStringContainsString('<strong>bold</strong>', $output);
        $this->assertStringContainsString('<em>italic</em>', $output);
    }

    public function test_markdown_converts_lists() {
        $input = "- Item 1\n- Item 2";
        $output = markdown($input);
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>Item 1</li>', $output);
        $this->assertStringContainsString('<li>Item 2</li>', $output);
    }

    public function test_markdown_handles_empty_string() {
        $output = markdown('');
        $this->assertEquals('', $output);
    }

    public function test_markdown_handles_plain_text() {
        $input = "Just plain text";
        $output = markdown($input);
        $this->assertStringContainsString('<p>Just plain text</p>', $output);
    }

    public function test_markdown_processes_mod_config_format() {
        // Test with the format used in the mod configuration
        $input = "# Messages du jour\n\n## coucou\nCeci est une liste\n* Elément 1\n* Elément 2";
        $output = markdown($input);

        $this->assertStringContainsString('<h1>Messages du jour</h1>', $output);
        $this->assertStringContainsString('<h2>coucou</h2>', $output);
        $this->assertStringContainsString('<p>Ceci est une liste</p>', $output);
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>Elément 1</li>', $output);
        $this->assertStringContainsString('<li>Elément 2</li>', $output);
    }
}

/* End of file MarkdownHelperTest.php */
/* Location: ./application/tests/unit/helpers/MarkdownHelperTest.php */