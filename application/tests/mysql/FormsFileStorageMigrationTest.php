<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 165 (Lot 2-bis — bascule du stockage des
 * formulaires de la base vers le fichier). Idempotence is the point of this
 * migration: EF2-ter requires it to remain in the project indefinitely as a
 * permanent no-op once every installation has migrated, so it must never
 * error or duplicate/clobber content on a second run.
 *
 * Migration 166 (suppression de form_fields, bascule vers field_name) is a
 * one-shot, non-idempotent, irreversible schema cutover already applied to
 * this database — its effect is exercised throughout the rest of the MySQL
 * suite (every test writing/reading form_submission_values.field_name or
 * form_submission_files.widget_name) rather than re-simulated here.
 */
class FormsFileStorageMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $storage;
    private $form_ids = array();
    private $codes = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->library('forms_file_storage');
        $this->storage = $CI->forms_file_storage;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/165_forms_file_storage.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->form_ids as $form_id) {
            $this->db->where('form_id', $form_id)->delete('form_pages');
            $this->db->where('id', $form_id)->delete('forms');
        }
        foreach ($this->codes as $code) {
            $this->storage->delete_form_dir($code);
        }
    }

    private function runMigrationUp()
    {
        $migration = new Migration_Forms_file_storage();
        return $migration->up();
    }

    private function createForm($code, $global_css, array $pages)
    {
        $this->db->insert('forms', array(
            'code'        => $code,
            'title'       => 'Migration 165 test',
            'public_slug' => $code,
            'status'      => 'draft',
            'global_css'  => $global_css,
        ));
        $form_id = $this->db->insert_id();
        $this->form_ids[] = $form_id;
        $this->codes[] = $code;

        foreach ($pages as $page_number => $content_html) {
            $this->db->insert('form_pages', array(
                'form_id'      => $form_id,
                'page_number'  => $page_number,
                'content_html' => $content_html,
            ));
        }

        return $form_id;
    }

    public function testUpWritesCssAndPagesToDiskForAFormNotYetMigrated()
    {
        $code = 'mig165_test_' . uniqid();
        $this->createForm($code, 'body { color: red; }', array(1 => '<p>Contenu page 1</p>'));

        $this->assertTrue($this->runMigrationUp(), 'Migration 165 up() should succeed');

        $this->assertSame('body { color: red; }', $this->storage->read_css($code));
        $this->assertSame('<p>Contenu page 1</p>', $this->storage->read_page($code, 1));
    }

    public function testUpIsIdempotentAndDoesNotClobberAlreadyMigratedContent()
    {
        $code = 'mig165_test_' . uniqid();
        $this->createForm($code, 'body { color: red; }', array(1 => '<p>Contenu original</p>'));

        $this->assertTrue($this->runMigrationUp());

        // Simulate the file having since been edited directly via the admin
        // (write_page()/write_css() — the normal path once file-backed) —
        // a second migration run must leave this edited content untouched,
        // it must not re-derive from the (now stale) DB content_html.
        $this->storage->write_css($code, 'body { color: blue; }');
        $this->storage->write_page($code, 1, '<p>Contenu édité depuis l\'admin</p>');

        $this->assertTrue($this->runMigrationUp(), 'Second up() run must still succeed (idempotent)');

        $this->assertSame('body { color: blue; }', $this->storage->read_css($code));
        $this->assertSame('<p>Contenu édité depuis l\'admin</p>', $this->storage->read_page($code, 1));
    }

    public function testUpSkipsFormsWithEmptyContent()
    {
        $code = 'mig165_test_' . uniqid();
        $this->createForm($code, '', array(1 => ''));

        $this->assertTrue($this->runMigrationUp());

        $this->assertNull($this->storage->read_css($code));
        $this->assertNull($this->storage->read_page($code, 1));
    }

    public function testUpHandlesMultiplePagesIndependently()
    {
        $code = 'mig165_test_' . uniqid();
        $this->createForm($code, '', array(
            1 => '<p>Page un</p>',
            2 => '<p>Page deux</p>',
        ));

        $this->assertTrue($this->runMigrationUp());

        $this->assertSame('<p>Page un</p>', $this->storage->read_page($code, 1));
        $this->assertSame('<p>Page deux</p>', $this->storage->read_page($code, 2));
    }

    public function testDownIsANoOpAndAlwaysSucceeds()
    {
        $code = 'mig165_test_' . uniqid();
        $this->createForm($code, 'body{}', array(1 => '<p>x</p>'));
        $this->runMigrationUp();

        $migration = new Migration_Forms_file_storage();
        $this->assertTrue($migration->down(), 'Migration 165 down() should succeed (no-op, does not delete files)');

        // down() must not have removed the file — the design explicitly
        // keeps disk content as-is until Lot 2-bis fully switches over.
        $this->assertSame('<p>x</p>', $this->storage->read_page($code, 1));
    }
}
