<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — File_rotator (Lot 9, étape 2).
 *
 * Filet de sécurité ajouté avant de refactorer Archived_documents::rotate()
 * pour déléguer à cette librairie. Les tests s'adaptent à la disponibilité
 * des binaires qpdf/convert dans l'environnement (skip gracieux sinon).
 */
class FileRotatorTest extends TestCase
{
    private $rotator;
    private $tmp_files = array();

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/File_rotator.php';
        $this->rotator = new File_rotator();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp_files as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tmp_files = array();
    }

    private function copy_fixture_to_tmp($fixture_relative, $ext)
    {
        $source = APPPATH . 'tests/data/attachments/' . $fixture_relative;
        $this->assertFileExists($source, 'Fixture manquante: ' . $source);

        $tmp = sys_get_temp_dir() . '/file_rotator_test_' . uniqid() . '.' . $ext;
        copy($source, $tmp);
        $this->tmp_files[] = $tmp;

        return $tmp;
    }

    private function binary_available($name)
    {
        $path = trim((string) @shell_exec('which ' . escapeshellarg($name) . ' 2>/dev/null'));
        return $path !== '' && file_exists($path);
    }

    public function test_invalid_direction_is_rejected()
    {
        $result = $this->rotator->rotate('/tmp/does-not-matter.pdf', 'application/pdf', 'sideways');

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_direction', $result['error_code']);
    }

    public function test_missing_file_is_not_supported()
    {
        $result = $this->rotator->rotate('/tmp/gvv_file_rotator_missing_' . uniqid() . '.pdf', 'application/pdf', 'cw');

        $this->assertFalse($result['success']);
        $this->assertSame('not_supported', $result['error_code']);
    }

    public function test_unsupported_mime_type_is_rejected()
    {
        $tmp = $this->copy_fixture_to_tmp('images/small_invoice_photo_640x480.jpg', 'txt');

        $result = $this->rotator->rotate($tmp, 'text/plain', 'cw');

        $this->assertFalse($result['success']);
        $this->assertSame('not_supported', $result['error_code']);
    }

    public function test_rotate_image_cw_swaps_dimensions()
    {
        if (!$this->binary_available('convert')) {
            $this->markTestSkipped('ImageMagick convert non disponible dans cet environnement.');
        }

        $tmp = $this->copy_fixture_to_tmp('images/small_receipt_scan_600x400.png', 'png');
        $before = getimagesize($tmp);

        $result = $this->rotator->rotate($tmp, 'image/png', 'cw');

        $this->assertTrue($result['success'], 'Rotation image attendue en succès: ' . json_encode($result));
        $after = getimagesize($tmp);
        $this->assertSame($before[1], $after[0], 'La largeur après rotation doit être l\'ancienne hauteur');
        $this->assertSame($before[0], $after[1], 'La hauteur après rotation doit être l\'ancienne largeur');
    }

    public function test_rotate_image_ccw_swaps_dimensions()
    {
        if (!$this->binary_available('convert')) {
            $this->markTestSkipped('ImageMagick convert non disponible dans cet environnement.');
        }

        $tmp = $this->copy_fixture_to_tmp('images/small_receipt_scan_600x400.png', 'png');
        $before = getimagesize($tmp);

        $result = $this->rotator->rotate($tmp, 'image/png', 'ccw');

        $this->assertTrue($result['success']);
        $after = getimagesize($tmp);
        $this->assertSame($before[1], $after[0]);
        $this->assertSame($before[0], $after[1]);
    }

    public function test_rotate_pdf_reports_tool_missing_or_succeeds()
    {
        $tmp = $this->copy_fixture_to_tmp('documents/small_invoice_90kb.pdf', 'pdf');
        $original_size = filesize($tmp);

        $result = $this->rotator->rotate($tmp, 'application/pdf', 'cw');

        if (!$this->binary_available('qpdf')) {
            $this->assertFalse($result['success']);
            $this->assertSame('tool_missing', $result['error_code']);
            $this->assertSame('qpdf', $result['tool']);
        } else {
            $this->assertTrue($result['success'], 'Rotation PDF attendue en succès: ' . json_encode($result));
            $this->assertFileExists($tmp);
            $this->assertGreaterThan(0, filesize($tmp));
        }
    }

    /**
     * Régression (Lot 9, étape 4) : rename() peut échouer silencieusement (permissions,
     * répertoire non inscriptible...) sans que le code le vérifie — le résultat était
     * alors 'success' alors que le fichier original n'avait jamais été remplacé.
     */
    public function test_rotate_image_reports_failure_when_target_directory_is_not_writable()
    {
        if (!$this->binary_available('convert')) {
            $this->markTestSkipped('ImageMagick convert non disponible dans cet environnement.');
        }

        $dir = sys_get_temp_dir() . '/file_rotator_readonly_' . uniqid();
        mkdir($dir, 0755);
        $target = $dir . '/img.png';
        copy(APPPATH . 'tests/data/attachments/images/small_receipt_scan_600x400.png', $target);
        chmod($dir, 0555);

        if (is_writable($dir)) {
            chmod($dir, 0755);
            @unlink($target);
            @rmdir($dir);
            $this->markTestSkipped('Le process de test a des privilèges qui contournent les permissions (root ?).');
        }

        $before_md5 = md5_file($target);
        $result = $this->rotator->rotate($target, 'image/png', 'cw');

        chmod($dir, 0755);
        $after_md5 = md5_file($target);
        @unlink($target);
        @rmdir($dir);

        $this->assertFalse($result['success'], 'rotate() ne doit pas rapporter un succès quand rename() échoue: ' . json_encode($result));
        $this->assertSame('rotate_failed', $result['error_code']);
        $this->assertSame($before_md5, $after_md5, 'Le fichier original ne doit pas être modifié quand la rotation échoue.');
    }
}
