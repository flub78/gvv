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
    // pdf template (Lot 16 / EF18)
    // -------------------------------------------------------------------

    public function testHasPdfTemplateFalseWhenAbsent()
    {
        $this->assertFalse($this->storage->has_pdf_template('mon-form'));
        $this->assertNull($this->storage->read_pdf_template('mon-form'));
    }

    public function testWritePdfTemplateThenReadRoundTrips()
    {
        $this->storage->write_pdf_template('mon-form', '%PDF-1.4 fake content');

        $this->assertTrue($this->storage->has_pdf_template('mon-form'));
        $this->assertSame('%PDF-1.4 fake content', $this->storage->read_pdf_template('mon-form'));
    }

    public function testWritePdfTemplateOverwritesPreviousFileRatherThanAccumulating()
    {
        $this->storage->write_pdf_template('mon-form', 'ancien contenu');
        $this->storage->write_pdf_template('mon-form', 'nouveau contenu');

        $this->assertSame('nouveau contenu', $this->storage->read_pdf_template('mon-form'));
        // Single fixed filename: nothing else on disk to leave behind.
        $this->assertCount(1, glob($this->storage->form_dir('mon-form') . '/*.pdf'));
    }

    public function testDeletePdfTemplateRemovesFile()
    {
        $this->storage->write_pdf_template('mon-form', 'contenu');
        $this->storage->delete_pdf_template('mon-form');

        $this->assertFalse($this->storage->has_pdf_template('mon-form'));
        $this->assertNull($this->storage->read_pdf_template('mon-form'));
    }

    public function testDeletePdfTemplateIsNoOpWhenAbsent()
    {
        // Must not throw/warn.
        $this->storage->delete_pdf_template('inexistant');
        $this->assertFalse($this->storage->has_pdf_template('inexistant'));
    }

    // -------------------------------------------------------------------
    // rename_form_dir()
    // -------------------------------------------------------------------

    public function testRenameFormDirMovesDirectoryIncludingImages()
    {
        $this->storage->write_page('ancien-code', 1, '<p>x</p>');
        $this->storage->write_image('ancien-code', 'logo.png', 'X');
        $this->storage->write_pdf_template('ancien-code', 'PDF');

        $this->storage->rename_form_dir('ancien-code', 'nouveau-code');

        $this->assertFalse(is_dir($this->storage->form_dir('ancien-code')));
        $this->assertSame('<p>x</p>', $this->storage->read_page('nouveau-code', 1));
        $this->assertSame(array('logo.png'), $this->storage->list_images('nouveau-code'));
        // rename_form_dir() moves the whole directory: the PDF template
        // follows without any dedicated code (Lot 16 design decision).
        $this->assertSame('PDF', $this->storage->read_pdf_template('nouveau-code'));
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
        $this->storage->write_pdf_template('original', 'PDF');

        $this->storage->copy_form_dir('original', 'copie');

        $this->assertSame('<p>contenu</p>', $this->storage->read_page('copie', 1));
        $this->assertSame('body { color: blue; }', $this->storage->read_css('copie'));
        $this->assertSame(array('logo.png'), $this->storage->list_images('copie'));
        // copy_form_dir() copies every top-level file: the PDF template
        // follows without any dedicated code (Lot 16 design decision).
        $this->assertSame('PDF', $this->storage->read_pdf_template('copie'));
        // Source untouched.
        $this->assertSame('<p>contenu</p>', $this->storage->read_page('original', 1));
        $this->assertSame('PDF', $this->storage->read_pdf_template('original'));
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
        $this->storage->write_pdf_template('a-supprimer', 'PDF');

        $this->storage->delete_form_dir('a-supprimer');

        $this->assertFalse(is_dir($this->storage->form_dir('a-supprimer')), 'form directory must not survive delete_form_dir() (regression: .htaccess left behind, rmdir() silently failing)');
    }

    public function testDeleteFormDirIsNoOpWhenDirectoryMissing()
    {
        // Must not throw/warn.
        $this->storage->delete_form_dir('inexistant');
        $this->assertFalse(is_dir($this->storage->form_dir('inexistant')));
    }

    // -------------------------------------------------------------------
    // page_numbers() (Lot 2-ter)
    // -------------------------------------------------------------------

    public function testPageNumbersReturnsSortedNumbersFromFilesOnDisk()
    {
        $this->storage->write_page('multi', 2, '<p>deux</p>');
        $this->storage->write_page('multi', 1, '<p>un</p>');
        $this->storage->write_page('multi', 10, '<p>dix</p>');

        $this->assertSame(array(1, 2, 10), $this->storage->page_numbers('multi'));
    }

    public function testPageNumbersEmptyWhenFormDoesNotExist()
    {
        $this->assertSame(array(), $this->storage->page_numbers('inexistant'));
    }

    // -------------------------------------------------------------------
    // meta.json (Lot 2-ter)
    // -------------------------------------------------------------------

    public function testWriteThenReadMetaRoundTrips()
    {
        $meta = array(
            'title'           => 'Inscription au concours',
            'description'     => 'Formulaire du club',
            'css_scope'       => '',
            'required_params' => 'none',
            'pages'           => array(
                array('page_number' => 1, 'title' => 'Pilote'),
            ),
        );

        $this->storage->write_meta('mon-form', $meta);

        $this->assertSame($meta, $this->storage->read_meta('mon-form'));
    }

    public function testReadMetaReturnsNullWhenAbsent()
    {
        $this->assertNull($this->storage->read_meta('inexistant'));
    }

    public function testReadMetaReturnsNullWhenFileIsNotValidJson()
    {
        $this->storage->ensure_dir('broken-meta');
        file_put_contents($this->storage->form_dir('broken-meta') . '/meta.json', 'not json');

        $this->assertNull($this->storage->read_meta('broken-meta'));
    }

    public function testWriteMetaProducesHumanReadableUnescapedJson()
    {
        $this->storage->write_meta('accents', array('title' => 'Épreuve à Nîmes'));

        $raw = file_get_contents($this->storage->form_dir('accents') . '/meta.json');

        $this->assertStringContainsString('Épreuve à Nîmes', $raw);
        $this->assertStringContainsString("\n", $raw, 'expected pretty-printed JSON');
    }

    // -------------------------------------------------------------------
    // replace_all_from_dir() (Lot 2-ter) — archive/directory symmetry
    // -------------------------------------------------------------------

    public function testReplaceAllFromDirInstallsPagesCssAndMeta()
    {
        $src = self::$tmp_root . '/archive_src_' . uniqid();
        mkdir($src, 0770, true);
        file_put_contents($src . '/page01.html', '<!DOCTYPE html><html><body class="forms-public-root"><p>Un</p></body></html>');
        file_put_contents($src . '/page02.html', '<!DOCTYPE html><html><body class="forms-public-root"><p>Deux</p></body></html>');
        file_put_contents($src . '/style.css', '.forms-public-root { color: red; }');
        file_put_contents($src . '/meta.json', json_encode(array('title' => 'Depuis archive')));

        $this->storage->replace_all_from_dir('cible', $src);

        $this->assertSame('<p>Un</p>', $this->storage->read_page('cible', 1));
        $this->assertSame('<p>Deux</p>', $this->storage->read_page('cible', 2));
        $this->assertSame('.forms-public-root { color: red; }', $this->storage->read_css('cible'));
        $this->assertSame(array('title' => 'Depuis archive'), $this->storage->read_meta('cible'));

        self::_rrmdir_static($src);
    }

    public function testReplaceAllFromDirRemovesPagesNoLongerPresentInArchive()
    {
        // Existing form has 2 pages; the deposited archive only has 1 — the
        // old page 2 must not linger on disk after the replacement.
        $this->storage->write_page('existant', 1, '<p>ancien un</p>');
        $this->storage->write_page('existant', 2, '<p>ancien deux</p>');

        $src = self::$tmp_root . '/archive_src_' . uniqid();
        mkdir($src, 0770, true);
        file_put_contents($src . '/page01.html', '<!DOCTYPE html><html><body class="forms-public-root"><p>nouveau un</p></body></html>');

        $this->storage->replace_all_from_dir('existant', $src);

        $this->assertSame('<p>nouveau un</p>', $this->storage->read_page('existant', 1));
        $this->assertNull($this->storage->read_page('existant', 2));

        self::_rrmdir_static($src);
    }

    // -------------------------------------------------------------------
    // shared CSS (.commun/style.css) (Lot 2-ter)
    // -------------------------------------------------------------------

    public function testWriteThenReadSharedCssRoundTrips()
    {
        $this->storage->write_shared_css('.club-header { color: navy; }');

        $this->assertSame('.club-header { color: navy; }', $this->storage->read_shared_css());
    }

    public function testReadSharedCssReturnsNullWhenAbsent()
    {
        $this->assertNull($this->storage->read_shared_css());
    }

    public function testSharedCssDirectoryDoesNotCollideWithAFormCodeSanitizedFromDotCommun()
    {
        // safe_code() must never be used to build the shared-CSS path — a
        // form whose sanitized code happens to be "commun" must not be able
        // to shadow the reserved shared CSS.
        $this->storage->write_shared_css('shared');
        $this->storage->write_css('commun', 'form-specific');

        $this->assertSame('shared', $this->storage->read_shared_css());
        $this->assertSame('form-specific', $this->storage->read_css('commun'));
    }

    public function testWriteSharedCssCreatesDenyAllHtaccess()
    {
        $this->storage->write_shared_css('body {}');

        $htaccess = $this->storage->shared_dir() . '/.htaccess';
        $this->assertFileExists($htaccess);
        $this->assertStringContainsString('Require all denied', file_get_contents($htaccess));
    }

    // -------------------------------------------------------------------
    // shared images (.commun/images/) (Lot 2-quater)
    // -------------------------------------------------------------------

    public function testWriteThenReadSharedImageRoundTrips()
    {
        $stored_name = $this->storage->write_shared_image('Logo Club.png', 'PNGDATA');

        $this->assertSame('Logo-Club.png', $stored_name);
        $this->assertSame(array('Logo-Club.png'), $this->storage->list_shared_images());
        $this->assertSame('PNGDATA', $this->storage->read_shared_image('Logo-Club.png'));
    }

    public function testReadSharedImageReturnsNullWhenAbsent()
    {
        $this->assertNull($this->storage->read_shared_image('inexistant.png'));
    }

    public function testListSharedImagesEmptyWhenNoSharedImagesDir()
    {
        $this->assertSame(array(), $this->storage->list_shared_images());
    }

    public function testWriteSharedImageCreatesDenyAllHtaccessAtSharedRootNotOnlyImagesDir()
    {
        $this->storage->write_shared_image('logo.png', 'X');

        $htaccess = $this->storage->shared_dir() . '/.htaccess';
        $this->assertFileExists($htaccess);
    }
}
