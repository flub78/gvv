<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Forms_file_storage (Lot 2-bis) — on-disk representation
 * of a form's content under uploads/formulaires/{code}/, the source of truth
 * for form content since migration 165/166 (see doc/prds/remplissage_formulaires_prd.md
 * EF2-bis).
 *
 * FCPATH is redirected to a throwaway temp directory so this test never
 * touches the real uploads/formulaires/ tree. FCPATH is a constant — it can
 * only be defined once per PHP process — so it is set once for the whole
 * class (setUpBeforeClass) and each test instead gets a clean slate by
 * wiping uploads/formulaires/ between tests (tearDown), not by swapping FCPATH.
 */
class FormsFileStorageTest extends TestCase
{
    private $storage;
    private static $tmp_root;

    public static function setUpBeforeClass(): void
    {
        require_once APPPATH . 'libraries/Forms_file_storage.php';

        self::$tmp_root = sys_get_temp_dir() . '/gvv_test_ffs_' . uniqid();
        mkdir(self::$tmp_root, 0770, true);
        if (!defined('FCPATH')) {
            define('FCPATH', self::$tmp_root . '/');
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::_rrmdir_static(self::$tmp_root);
    }

    protected function setUp(): void
    {
        $this->storage = new Forms_file_storage();
    }

    protected function tearDown(): void
    {
        // Wipe uploads/formulaires/ contents so the next test starts clean,
        // without touching FCPATH itself (immutable after the first define()).
        self::_rrmdir_static(rtrim(FCPATH, '/') . '/uploads');
    }

    private static function _rrmdir_static($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $path = $dir . '/' . $f;
            is_dir($path) ? self::_rrmdir_static($path) : unlink($path);
        }
        rmdir($dir);
    }

    // -------------------------------------------------------------------
    // safe_code() / form_dir()
    // -------------------------------------------------------------------

    public function testSafeCodeStripsUnsafeCharactersAndTrimsDashes()
    {
        $this->assertSame('inscription_bia', $this->storage->safe_code('inscription_bia'));
        $this->assertSame('a-b-c', $this->storage->safe_code('a/../b?c'));
        $this->assertSame('code', $this->storage->safe_code('  code  '));
    }

    // -------------------------------------------------------------------
    // write_page() / read_page() — standalone-document envelope
    // -------------------------------------------------------------------

    public function testWritePageWrapsFragmentInStandaloneHtmlDocument()
    {
        $this->storage->write_page('mon-form', 1, '<p>Bonjour</p>');

        $raw = file_get_contents($this->storage->form_dir('mon-form') . '/page01.html');

        $this->assertStringStartsWith('<!DOCTYPE html>', $raw);
        $this->assertStringContainsString('<link rel="stylesheet" href="style.css">', $raw);
        $this->assertStringContainsString('<body class="forms-public-root">', $raw);
        $this->assertStringContainsString('<p>Bonjour</p>', $raw);
    }

    public function testReadPageReturnsOriginalFragmentUnwrapped()
    {
        $fragment = "<form>\n  <input type=\"text\" name=\"nom\">\n</form>";
        $this->storage->write_page('mon-form', 1, $fragment);

        $this->assertSame($fragment, $this->storage->read_page('mon-form', 1));
    }

    public function testReadPageReturnsNullWhenPageDoesNotExist()
    {
        $this->assertNull($this->storage->read_page('inexistant', 1));
    }

    public function testReadPageUnwrapsLegacyUnwrappedFileForBackwardCompatibility()
    {
        // Files written before the write-time envelope was introduced are bare
        // fragments with no <body> tag — read_page() must return them as-is
        // rather than losing content.
        $this->storage->ensure_dir('legacy-form');
        file_put_contents($this->storage->form_dir('legacy-form') . '/page01.html', '<p>Ancien contenu</p>');

        $this->assertSame('<p>Ancien contenu</p>', $this->storage->read_page('legacy-form', 1));
    }

    public function testWriteThenReadRoundTripsForMultiplePages()
    {
        $this->storage->write_page('multi', 1, '<p>Page un</p>');
        $this->storage->write_page('multi', 2, '<p>Page deux</p>');

        $this->assertSame('<p>Page un</p>', $this->storage->read_page('multi', 1));
        $this->assertSame('<p>Page deux</p>', $this->storage->read_page('multi', 2));
    }

    public function testDeletePageRemovesFile()
    {
        $this->storage->write_page('mon-form', 1, '<p>x</p>');
        $this->assertTrue($this->storage->page_exists('mon-form', 1));

        $this->storage->delete_page('mon-form', 1);

        $this->assertFalse($this->storage->page_exists('mon-form', 1));
        $this->assertNull($this->storage->read_page('mon-form', 1));
    }

    // -------------------------------------------------------------------
    // write_css() / read_css()
    // -------------------------------------------------------------------

    public function testWriteThenReadCssRoundTrips()
    {
        $this->storage->write_css('mon-form', 'body { color: red; }');

        $this->assertSame('body { color: red; }', $this->storage->read_css('mon-form'));
    }

    public function testReadCssReturnsNullWhenAbsent()
    {
        $this->assertNull($this->storage->read_css('inexistant'));
    }

    // -------------------------------------------------------------------
    // .htaccess / ensure_dir()
    // -------------------------------------------------------------------

    public function testEnsureDirCreatesDenyAllHtaccess()
    {
        $this->storage->ensure_dir('mon-form');

        $htaccess = $this->storage->form_dir('mon-form') . '/.htaccess';
        $this->assertFileExists($htaccess);
        $this->assertStringContainsString('Require all denied', file_get_contents($htaccess));
    }

    // -------------------------------------------------------------------
    // has_content()
    // -------------------------------------------------------------------

    public function testHasContentFalseForUnknownForm()
    {
        $this->assertFalse($this->storage->has_content('inexistant'));
    }

    public function testHasContentTrueAfterWritingAPage()
    {
        $this->storage->write_page('mon-form', 1, '<p>x</p>');

        $this->assertTrue($this->storage->has_content('mon-form'));
    }

    // -------------------------------------------------------------------
    // images
    // -------------------------------------------------------------------

    public function testSafeImageNameSanitizesAndStripsPath()
    {
        $this->assertSame('logo.png', $this->storage->safe_image_name('../../etc/logo.png'));
        $this->assertSame('mon-logo.png', $this->storage->safe_image_name('mon logo.png'));
    }

    public function testWriteImageThenListAndReadRoundTrips()
    {
        $stored_name = $this->storage->write_image('mon-form', 'Logo Club.png', 'PNGDATA');

        $this->assertSame('Logo-Club.png', $stored_name);
        $this->assertSame(array('Logo-Club.png'), $this->storage->list_images('mon-form'));
        $this->assertSame('PNGDATA', $this->storage->read_image('mon-form', 'Logo-Club.png'));
    }

    public function testListImagesEmptyWhenNoImagesDir()
    {
        $this->assertSame(array(), $this->storage->list_images('mon-form'));
    }

    public function testDeleteImageRemovesFile()
    {
        $this->storage->write_image('mon-form', 'logo.png', 'X');
        $this->storage->delete_image('mon-form', 'logo.png');

        $this->assertSame(array(), $this->storage->list_images('mon-form'));
        $this->assertNull($this->storage->read_image('mon-form', 'logo.png'));
    }

    // -------------------------------------------------------------------
    // rename_form_dir()
    // -------------------------------------------------------------------

    public function testRenameFormDirMovesDirectoryIncludingImages()
    {
        $this->storage->write_page('ancien-code', 1, '<p>x</p>');
        $this->storage->write_image('ancien-code', 'logo.png', 'X');

        $this->storage->rename_form_dir('ancien-code', 'nouveau-code');

        $this->assertFalse(is_dir($this->storage->form_dir('ancien-code')));
        $this->assertSame('<p>x</p>', $this->storage->read_page('nouveau-code', 1));
        $this->assertSame(array('logo.png'), $this->storage->list_images('nouveau-code'));
    }

    public function testRenameFormDirIsNoOpWhenSourceMissing()
    {
        $this->storage->rename_form_dir('inexistant', 'cible');

        $this->assertFalse(is_dir($this->storage->form_dir('cible')));
    }

    public function testRenameFormDirIsNoOpWhenTargetAlreadyExists()
    {
        $this->storage->write_page('source', 1, '<p>source</p>');
        $this->storage->write_page('cible', 1, '<p>cible</p>');

        $this->storage->rename_form_dir('source', 'cible');

        // Source untouched, target untouched — no clobbering.
        $this->assertSame('<p>source</p>', $this->storage->read_page('source', 1));
        $this->assertSame('<p>cible</p>', $this->storage->read_page('cible', 1));
    }

    // -------------------------------------------------------------------
    // copy_form_dir()
    // -------------------------------------------------------------------

    public function testCopyFormDirCopiesPagesCssAndImages()
    {
        $this->storage->write_page('original', 1, '<p>contenu</p>');
        $this->storage->write_css('original', 'body { color: blue; }');
        $this->storage->write_image('original', 'logo.png', 'X');

        $this->storage->copy_form_dir('original', 'copie');

        $this->assertSame('<p>contenu</p>', $this->storage->read_page('copie', 1));
        $this->assertSame('body { color: blue; }', $this->storage->read_css('copie'));
        $this->assertSame(array('logo.png'), $this->storage->list_images('copie'));
        // Source untouched.
        $this->assertSame('<p>contenu</p>', $this->storage->read_page('original', 1));
    }

    public function testCopyFormDirIsNoOpWhenSourceMissing()
    {
        $this->storage->copy_form_dir('inexistant', 'copie');

        $this->assertFalse(is_dir($this->storage->form_dir('copie')));
    }

    // -------------------------------------------------------------------
    // delete_form_dir() — regression test: glob('/*') does not match
    // dotfiles, .htaccess used to survive and leave an orphaned directory.
    // -------------------------------------------------------------------

    public function testDeleteFormDirRemovesEverythingIncludingHtaccessAndImages()
    {
        $this->storage->write_page('a-supprimer', 1, '<p>x</p>');
        $this->storage->write_css('a-supprimer', 'body{}');
        $this->storage->write_image('a-supprimer', 'logo.png', 'X');

        $this->storage->delete_form_dir('a-supprimer');

        $this->assertFalse(is_dir($this->storage->form_dir('a-supprimer')), 'form directory must not survive delete_form_dir() (regression: .htaccess left behind, rmdir() silently failing)');
    }

    public function testDeleteFormDirIsNoOpWhenDirectoryMissing()
    {
        // Must not throw/warn.
        $this->storage->delete_form_dir('inexistant');
        $this->assertFalse(is_dir($this->storage->form_dir('inexistant')));
    }
}
