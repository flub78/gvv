<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for URL helper functions
 *
 * This test validates URL generation functions to ensure proper formatting
 * with different base_url configurations (with/without trailing slash, with/without index.php).
 *
 * NOTE: This test uses a custom bootstrap (url_helper_bootstrap.php) instead of minimal_bootstrap.php
 * to avoid loading real models with database dependencies.
 */
class UrlHelperTest extends TestCase
{
    private $original_base_url;
    private $original_index_page;

    /**
     * Set up before each test
     * Note: Bootstrap is loaded by PHPUnit before this method runs
     */
    protected function setUp(): void
    {
        // Get CodeIgniter instance (already initialized by bootstrap)
        $CI =& get_instance();

        // Create mock comptes_model
        $CI->comptes_model = new class {
            public function get_by_id($key, $id) {
                return ['id' => $id, 'nom' => 'Test Compte ' . $id, 'codec' => '411'];
            }
            public function image($id) {
                return 'Compte ' . $id;
            }
        };

        // Create mock ecritures_model
        $CI->ecritures_model = new class {
            public function image($id) {
                return 'Ecriture ' . $id;
            }
            public function get_by_id($key, $id) {
                return (object)['id' => $id, 'date' => '2025-01-01', 'montant' => '100.00'];
            }
        };
    }

    /**
     * Test anchor_compte with base_url without trailing slash (DEV config)
     */
    public function testAnchorCompteWithoutTrailingSlash()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'http://gvv.net');
        $CI->config->set_item('index_page', '');

        $result = anchor_compte(1365);

        // Should contain the correct URL format
        $this->assertStringContainsString('href="http://gvv.net/compta/journal_compte/1365"', $result);

        // Should NOT contain the broken format
        $this->assertStringNotContainsString('journal_compte1365', $result);
    }

    /**
     * Test anchor_compte with base_url with trailing slash (PROD config)
     */
    public function testAnchorCompteWithTrailingSlash()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'https://gvv.planeur-abbeville.fr/');
        $CI->config->set_item('index_page', 'index.php');

        $result = anchor_compte(1365);

        // Should contain the correct URL format with index.php
        $this->assertStringContainsString('href="https://gvv.planeur-abbeville.fr/index.php/compta/journal_compte/1365"', $result);

        // Should NOT contain the broken format
        $this->assertStringNotContainsString('journal_compte1365', $result);
    }

    /**
     * Test anchor_ecriture with base_url without trailing slash (DEV config)
     */
    public function testAnchorEcritureWithoutTrailingSlash()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'http://gvv.net');
        $CI->config->set_item('index_page', '');

        $result = anchor_ecriture(35348);

        // Should contain the correct URL format
        $this->assertStringContainsString('href="http://gvv.net/compta/edit/35348"', $result);

        // Should NOT contain the broken format
        $this->assertStringNotContainsString('edit35348', $result);
    }

    /**
     * Test anchor_ecriture with base_url with trailing slash (PROD config)
     */
    public function testAnchorEcritureWithTrailingSlash()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'https://gvv.planeur-abbeville.fr/');
        $CI->config->set_item('index_page', 'index.php');

        $result = anchor_ecriture(35348);

        // Should contain the correct URL format with index.php
        $this->assertStringContainsString('href="https://gvv.planeur-abbeville.fr/index.php/compta/edit/35348"', $result);

        // Should NOT contain the broken format
        $this->assertStringNotContainsString('edit35348', $result);
    }

    /**
     * Test anchor_ecriture_edit with base_url without trailing slash (DEV config)
     */
    public function testAnchorEcritureEditWithoutTrailingSlash()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'http://gvv.net');
        $CI->config->set_item('index_page', '');

        $result = anchor_ecriture_edit(35348);

        // Should contain the correct URL format
        $this->assertStringContainsString('href="http://gvv.net/compta/edit/35348"', $result);

        // Should NOT contain the broken format
        $this->assertStringNotContainsString('edit35348', $result);
    }

    /**
     * Test anchor_ecriture_delete with base_url without trailing slash (DEV config)
     */
    public function testAnchorEcritureDeleteWithoutTrailingSlash()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'http://gvv.net');
        $CI->config->set_item('index_page', '');

        $result = anchor_ecriture_delete(35348);

        // Should contain the correct URL format
        $this->assertStringContainsString('href="http://gvv.net/compta/delete/35348"', $result);

        // Should NOT contain the broken format
        $this->assertStringNotContainsString('delete35348', $result);
    }

    /**
     * Test all URL functions with PROD configuration
     */
    public function testAllUrlFunctionsWithProdConfig()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'https://gvv.planeur-abbeville.fr/');
        $CI->config->set_item('index_page', 'index.php');

        $test_cases = [
            ['anchor_compte', 1365, 'compta/journal_compte/1365'],
            ['anchor_ecriture', 35348, 'compta/edit/35348'],
            ['anchor_ecriture_edit', 35348, 'compta/edit/35348'],
            ['anchor_ecriture_delete', 35348, 'compta/delete/35348'],
        ];

        foreach ($test_cases as $test) {
            list($function, $id, $expected_path) = $test;

            $result = call_user_func($function, $id);

            // Should contain the correct URL with index.php
            $expected_url = 'href="https://gvv.planeur-abbeville.fr/index.php/' . $expected_path . '"';
            $this->assertStringContainsString($expected_url, $result,
                "Function {$function} should generate correct URL with index.php");
        }
    }

    /**
     * Test that URLs don't have double slashes or missing slashes
     */
    public function testUrlsDoNotHaveSlashIssues()
    {
        $CI =& get_instance();
        $CI->config->set_item('base_url', 'http://gvv.net');
        $CI->config->set_item('index_page', '');

        $functions = [
            ['anchor_compte', 1365],
            ['anchor_ecriture', 35348],
            ['anchor_ecriture_edit', 35348],
            ['anchor_ecriture_delete', 35348],
        ];

        foreach ($functions as $func) {
            list($function, $id) = $func;
            $result = call_user_func($function, $id);

            // Should not have double slashes (except in http://)
            $this->assertStringNotContainsString('//compta', $result,
                "Function {$function} should not generate double slashes");

            // Should not have missing slashes (ID directly concatenated)
            $url_without_protocol = str_replace('http://', '', $result);
            $this->assertStringNotContainsString('compte' . $id, $url_without_protocol,
                "Function {$function} should not concatenate ID without slash");
            $this->assertStringNotContainsString('edit' . $id, $url_without_protocol,
                "Function {$function} should not concatenate ID without slash");
            $this->assertStringNotContainsString('delete' . $id, $url_without_protocol,
                "Function {$function} should not concatenate ID without slash");
        }
    }
}
