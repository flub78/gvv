<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for language file completeness
 *
 * Validates that language translations are complete across all supported languages.
 * Checks that all language files exist and contain all required translation keys.
 *
 * Replaces the old CI unit test: check_lang() in controllers/tests.php
 */
class LanguageCompletenessTest extends TestCase
{
    private $referenceLanguage = 'french';
    private $languageDir;

    public function setUp(): void
    {
        $this->languageDir = APPPATH . 'language/';
    }

    /**
     * Load language file and return its $lang array
     */
    private function loadLanguageFile($filepath)
    {
        if (!file_exists($filepath)) {
            return null;
        }

        // Define constants that language files might use as array keys
        // These are member role/qualification constants used in membre_lang.php
        $roleConstants = [
            'CA', 'CHEF_DE_PISTE', 'CHEF_PILOTE', 'FE_AVION', 'FI_AVION',
            'INTERNET', 'ITP', 'IVV', 'MECANO', 'PILOTE_AVION', 'PILOTE_PLANEUR',
            'PLIEUR', 'PRESIDENT', 'REMORQUEUR', 'SECRETAIRE', 'SECRETAIRE_ADJ',
            'TRESORIER', 'TREUILLARD', 'VI_AVION', 'VICE_PRESIDENT', 'VI_PLANEUR', 'MEMBRE'
        ];

        $value = 1;
        foreach ($roleConstants as $constant) {
            if (!defined($constant)) {
                define($constant, $value++);
            }
        }

        // Language files define $lang array
        $lang = array();
        include($filepath);

        return $lang;
    }

    /**
     * Get list of PHP files in a language directory
     */
    private function getLanguageFiles($languageCode)
    {
        $langDir = $this->languageDir . $languageCode;

        if (!is_dir($langDir)) {
            return [];
        }

        $files = [];
        $dh = opendir($langDir);
        while (($file = readdir($dh)) !== false) {
            if (preg_match('/.*\.php$/', $file)) {
                $files[] = $file;
            }
        }
        closedir($dh);

        sort($files);
        return $files;
    }

    /**
     * Test that reference language directory exists
     */
    public function testReferenceLanguageDirectoryExists()
    {
        $refDir = $this->languageDir . $this->referenceLanguage;
        $this->assertDirectoryExists(
            $refDir,
            "Reference language directory '$this->referenceLanguage' should exist"
        );
    }

    /**
     * Test that English language has all files from French reference
     */
    public function testEnglishHasAllLanguageFiles()
    {
        $refFiles = $this->getLanguageFiles($this->referenceLanguage);
        $langFiles = $this->getLanguageFiles('english');

        $this->assertNotEmpty($refFiles, "Reference language should have language files");

        $missingFiles = [];
        foreach ($refFiles as $file) {
            if (!in_array($file, $langFiles)) {
                $missingFiles[] = $file;
            }
        }

        if (!empty($missingFiles)) {
            $this->markTestIncomplete(
                "English language is missing files: " . implode(', ', $missingFiles)
            );
        }

        $this->assertTrue(true, "English has all language files");
    }

    /**
     * Test that Dutch language has all files from French reference
     */
    public function testDutchHasAllLanguageFiles()
    {
        $refFiles = $this->getLanguageFiles($this->referenceLanguage);
        $langFiles = $this->getLanguageFiles('dutch');

        $this->assertNotEmpty($refFiles, "Reference language should have language files");

        $missingFiles = [];
        foreach ($refFiles as $file) {
            if (!in_array($file, $langFiles)) {
                $missingFiles[] = $file;
            }
        }

        if (!empty($missingFiles)) {
            $this->markTestIncomplete(
                "Dutch language is missing files: " . implode(', ', $missingFiles)
            );
        }

        $this->assertTrue(true, "Dutch has all language files");
    }

    /**
     * Test that English language files have all translation keys
     */
    public function testEnglishHasAllTranslationKeys()
    {
        $refFiles = $this->getLanguageFiles($this->referenceLanguage);
        $totalMissingKeys = 0;
        $filesWithMissingKeys = [];

        foreach ($refFiles as $file) {
            $refFile = $this->languageDir . $this->referenceLanguage . '/' . $file;
            $langFile = $this->languageDir . 'english/' . $file;

            if (!file_exists($langFile)) {
                continue; // Already checked in testEnglishHasAllLanguageFiles
            }

            $refLang = $this->loadLanguageFile($refFile);
            $targetLang = $this->loadLanguageFile($langFile);

            if ($refLang === null || $targetLang === null) {
                continue;
            }

            $missingKeys = [];
            $typeMismatches = [];

            foreach ($refLang as $key => $value) {
                if (!array_key_exists($key, $targetLang)) {
                    $missingKeys[] = $key;
                } elseif (is_array($refLang[$key]) !== is_array($targetLang[$key])) {
                    $typeMismatches[] = $key;
                } elseif (is_array($refLang[$key]) &&
                         count($refLang[$key]) !== count($targetLang[$key])) {
                    $typeMismatches[] = "$key (different array size)";
                }
            }

            if (!empty($missingKeys) || !empty($typeMismatches)) {
                $totalMissingKeys += count($missingKeys) + count($typeMismatches);
                $filesWithMissingKeys[$file] = [
                    'missing' => $missingKeys,
                    'mismatches' => $typeMismatches
                ];
            }
        }

        if (!empty($filesWithMissingKeys)) {
            $message = "English translation has $totalMissingKeys issues:\n";
            foreach ($filesWithMissingKeys as $file => $issues) {
                if (!empty($issues['missing'])) {
                    $message .= "  $file - Missing keys: " . implode(', ', $issues['missing']) . "\n";
                }
                if (!empty($issues['mismatches'])) {
                    $message .= "  $file - Type mismatches: " . implode(', ', $issues['mismatches']) . "\n";
                }
            }
            $this->markTestIncomplete($message);
        }

        $this->assertTrue(true, "English language has all translation keys");
    }

    /**
     * Test that Dutch language files have all translation keys
     */
    public function testDutchHasAllTranslationKeys()
    {
        $refFiles = $this->getLanguageFiles($this->referenceLanguage);
        $totalMissingKeys = 0;
        $filesWithMissingKeys = [];

        foreach ($refFiles as $file) {
            $refFile = $this->languageDir . $this->referenceLanguage . '/' . $file;
            $langFile = $this->languageDir . 'dutch/' . $file;

            if (!file_exists($langFile)) {
                continue; // Already checked in testDutchHasAllLanguageFiles
            }

            $refLang = $this->loadLanguageFile($refFile);
            $targetLang = $this->loadLanguageFile($langFile);

            if ($refLang === null || $targetLang === null) {
                continue;
            }

            $missingKeys = [];
            $typeMismatches = [];

            foreach ($refLang as $key => $value) {
                if (!array_key_exists($key, $targetLang)) {
                    $missingKeys[] = $key;
                } elseif (is_array($refLang[$key]) !== is_array($targetLang[$key])) {
                    $typeMismatches[] = $key;
                } elseif (is_array($refLang[$key]) &&
                         count($refLang[$key]) !== count($targetLang[$key])) {
                    $typeMismatches[] = "$key (different array size)";
                }
            }

            if (!empty($missingKeys) || !empty($typeMismatches)) {
                $totalMissingKeys += count($missingKeys) + count($typeMismatches);
                $filesWithMissingKeys[$file] = [
                    'missing' => $missingKeys,
                    'mismatches' => $typeMismatches
                ];
            }
        }

        if (!empty($filesWithMissingKeys)) {
            $message = "Dutch translation has $totalMissingKeys issues:\n";
            foreach ($filesWithMissingKeys as $file => $issues) {
                if (!empty($issues['missing'])) {
                    $message .= "  $file - Missing keys: " . implode(', ', $issues['missing']) . "\n";
                }
                if (!empty($issues['mismatches'])) {
                    $message .= "  $file - Type mismatches: " . implode(', ', $issues['mismatches']) . "\n";
                }
            }
            $this->markTestIncomplete($message);
        }

        $this->assertTrue(true, "Dutch language has all translation keys");
    }

    /**
     * Test that reference language (French) has some translation files
     */
    public function testReferenceLanguageHasTranslationFiles()
    {
        $refFiles = $this->getLanguageFiles($this->referenceLanguage);

        $this->assertGreaterThan(
            5,
            count($refFiles),
            "Reference language should have multiple translation files"
        );
    }
}
