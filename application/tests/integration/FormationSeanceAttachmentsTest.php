<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for formation séance théorique attachments.
 *
 * Covers:
 * 1. uploads/formation/ directory exists and is writable
 * 2. Insert/retrieve an attachment linked to a formation_seances row
 * 3. Delete removes both DB record and physical file
 * 4. Referential integrity: referenced seance_id must exist
 *
 * Run with:
 *   phpunit --configuration phpunit_integration.xml \
 *           application/tests/integration/FormationSeanceAttachmentsTest.php
 */
class FormationSeanceAttachmentsTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var string[] files to clean up on teardown */
    private $created_files = [];

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->db->trans_start();

        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Attachments_model')) {
            require_once APPPATH . 'models/attachments_model.php';
        }
        $this->CI->attachments_model = new Attachments_model();

        $this->created_files = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->created_files as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }
        $this->CI->db->trans_rollback();
    }

    // ── 1. Upload directory ──────────────────────────────────────────────────

    public function testUploadDirectoryExistsAndIsWritable()
    {
        $dir = FCPATH . 'uploads/formation';
        $this->assertDirectoryExists($dir, 'uploads/formation/ doit exister');
        $this->assertTrue(is_writable($dir), 'uploads/formation/ doit être accessible en écriture');
    }

    public function testAnnualSubdirectoryCanBeCreated()
    {
        $year = date('Y');
        $dir  = FCPATH . 'uploads/formation/' . $year;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->assertDirectoryExists($dir, 'Le sous-répertoire annuel doit pouvoir être créé');
        $this->assertTrue(is_writable($dir));
    }

    // ── 2. Insert & retrieve ─────────────────────────────────────────────────

    public function testAttachmentInsertAndRetrieve()
    {
        // Find a real seance to link to (use any existing row)
        $seance = $this->CI->db->select('id')->from('formation_seances')
            ->limit(1)->get()->row_array();
        $this->assertNotEmpty($seance, 'Il doit exister au moins une séance en base');
        $seance_id = (int)$seance['id'];

        // Create a physical dummy file in the upload directory
        $year     = date('Y');
        $dir      = FCPATH . 'uploads/formation/' . $year . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = rand(100000, 999999) . '_test_attachment.txt';
        $filepath = $dir . $filename;
        file_put_contents($filepath, 'test content');
        $this->created_files[] = $filepath;

        // Relative path as stored by the controller
        $rel_path = './uploads/formation/' . $year . '/' . $filename;

        $data = [
            'referenced_table' => 'formation_seances',
            'referenced_id'    => $seance_id,
            'user_id'          => 'testuser',
            'description'      => 'Document de test',
            'file'             => $rel_path,
            'club'             => 1,
        ];
        $this->CI->db->insert('attachments', $data);
        $id = $this->CI->db->insert_id();
        $this->assertGreaterThan(0, $id, 'L\'insertion doit retourner un ID valide');

        // Retrieve and verify
        $row = $this->CI->db->where('id', $id)->get('attachments')->row_array();
        $this->assertNotEmpty($row, 'L\'enregistrement doit être retrouvable');
        $this->assertEquals('formation_seances', $row['referenced_table']);
        $this->assertEquals($seance_id, (int)$row['referenced_id']);
        $this->assertEquals('Document de test', $row['description']);
        $this->assertEquals($rel_path, $row['file']);
    }

    public function testGetAttachmentsBySeanceId()
    {
        $seance = $this->CI->db->select('id')->from('formation_seances')
            ->limit(1)->get()->row_array();
        $this->assertNotEmpty($seance);
        $seance_id = (int)$seance['id'];

        $year = date('Y');
        $dir  = FCPATH . 'uploads/formation/' . $year . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Insert two attachments for the same seance
        for ($i = 1; $i <= 2; $i++) {
            $filename = rand(100000, 999999) . "_multi_test_{$i}.txt";
            $filepath = $dir . $filename;
            file_put_contents($filepath, "content $i");
            $this->created_files[] = $filepath;

            $this->CI->db->insert('attachments', [
                'referenced_table' => 'formation_seances',
                'referenced_id'    => $seance_id,
                'user_id'          => 'testuser',
                'description'      => "Doc $i",
                'file'             => './uploads/formation/' . $year . '/' . $filename,
                'club'             => 1,
            ]);
        }

        $rows = $this->CI->db
            ->where('referenced_table', 'formation_seances')
            ->where('referenced_id', $seance_id)
            ->get('attachments')->result_array();

        $this->assertGreaterThanOrEqual(2, count($rows),
            'Les deux pièces jointes insérées doivent être retrouvables');
    }

    // ── 3. Delete removes DB record and physical file ────────────────────────

    public function testDeleteAttachmentRemovesRecordAndFile()
    {
        $seance = $this->CI->db->select('id')->from('formation_seances')
            ->limit(1)->get()->row_array();
        $this->assertNotEmpty($seance);
        $seance_id = (int)$seance['id'];

        $year     = date('Y');
        $dir      = FCPATH . 'uploads/formation/' . $year . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = rand(100000, 999999) . '_delete_test.txt';
        $filepath = $dir . $filename;
        file_put_contents($filepath, 'to be deleted');
        $rel_path = './uploads/formation/' . $year . '/' . $filename;

        $this->CI->db->insert('attachments', [
            'referenced_table' => 'formation_seances',
            'referenced_id'    => $seance_id,
            'user_id'          => 'testuser',
            'description'      => 'À supprimer',
            'file'             => $rel_path,
            'club'             => 1,
        ]);
        $id = $this->CI->db->insert_id();
        $this->assertFileExists($filepath);

        // Simulate controller delete logic
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $this->CI->db->where('id', $id)->delete('attachments');

        $this->assertFalse(file_exists($filepath), 'Le fichier physique doit être supprimé');

        $row = $this->CI->db->where('id', $id)->get('attachments')->row_array();
        $this->assertEmpty($row, 'L\'enregistrement DB doit être supprimé');

        // File was already deleted — no need to clean it up
    }

    // ── 4. Referential integrity ─────────────────────────────────────────────

    public function testAttachmentReferencesValidSeance()
    {
        $seance = $this->CI->db->select('id')->from('formation_seances')
            ->limit(1)->get()->row_array();
        $this->assertNotEmpty($seance, 'Il doit exister au moins une séance pour valider le référencement');

        $seance_id = (int)$seance['id'];

        // Verify the seance actually exists
        $found = $this->CI->db->where('id', $seance_id)->get('formation_seances')->row_array();
        $this->assertNotEmpty($found, 'La séance référencée doit exister en base');
        $this->assertEquals($seance_id, (int)$found['id']);

        // Insert an attachment referencing that seance
        $this->CI->db->insert('attachments', [
            'referenced_table' => 'formation_seances',
            'referenced_id'    => $seance_id,
            'user_id'          => 'testuser',
            'description'      => 'Test référence',
            'file'             => './uploads/formation/test_ref.txt',
            'club'             => 1,
        ]);
        $id = $this->CI->db->insert_id();
        $this->assertGreaterThan(0, $id);

        // The referenced_id in the attachment must match a real row in formation_seances
        $attachment = $this->CI->db->where('id', $id)->get('attachments')->row_array();
        $linked_seance = $this->CI->db
            ->where('id', (int)$attachment['referenced_id'])
            ->get('formation_seances')->row_array();

        $this->assertNotEmpty($linked_seance,
            'La séance liée à la pièce jointe doit exister dans formation_seances');
    }

    public function testNonExistentSeanceIdCanBeDetected()
    {
        // Find a seance_id that does not exist
        $row = $this->CI->db->query('SELECT MAX(id) AS max_id FROM formation_seances')->row_array();
        $nonexistent_id = (int)$row['max_id'] + 99999;

        $found = $this->CI->db->where('id', $nonexistent_id)
            ->get('formation_seances')->row_array();

        $this->assertEmpty($found,
            'Un seance_id inexistant ne doit correspondre à aucune séance');
    }
}
