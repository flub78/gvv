<?php

use PHPUnit\Framework\TestCase;

// Lightweight StatementOperation stub compatible with SmartAdjustor
class SmartStubStatementOperation
{
    private $type;
    private $nature;
    private $interbank_label;
    private $comments;

    public function __construct($type, $nature, $interbank_label = '', $comments = [])
    {
        $this->type = $type;
        $this->nature = $nature;
        $this->interbank_label = $interbank_label;
        $this->comments = $comments;
    }

    public function type() { return $this->type; }
    public function nature() { return $this->nature; }
    public function interbank_label() { return $this->interbank_label; }
    public function comments() { return $this->comments; }
}

/**
 * Integration tests for SmartAdjustor correlation function.
 *
 * Assertions are limited to checking that correlations are between 0.0 and 1.0.
 */
class SmartAdjustorCorrelationIntegrationTest extends TestCase
{
    private $outputPath;
    private static $fileInitialized = false;

    /**
     * Factory to create a lightweight StatementOperation stub compatible with SmartAdjustor
     */
    private function makeStub(string $type, string $nature, string $interbank_label = '', array $comments = [])
    {
        return new SmartStubStatementOperation($type, $nature, $interbank_label, $comments);
    }

    protected function setUp(): void
    {
        // Ensure CI is initialized by integration bootstrap
        $CI = &get_instance();
        $this->assertTrue($CI !== null, 'CodeIgniter instance should be available');

        // Load SmartAdjustor class (CI loader is mocked and does not include files)
        if (!class_exists('SmartAdjustor')) {
            require_once APPPATH . 'libraries/rapprochements/SmartAdjustor.php';
        }

        // Prepare output file build/results/Correlatiion_test_result.txt (relative to project root)
        $root = realpath(APPPATH . '..');
        $this->outputPath = $root . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . 'Correlatiion_test_result.txt';
        $dir = dirname($this->outputPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!self::$fileInitialized) {
            // Truncate and write header once
            file_put_contents($this->outputPath, 'Correlation test results - ' . date('c') . "\n\n");
            self::$fileInitialized = true;
        }
    }

    private function write(string $text): void
    {
        file_put_contents($this->outputPath, $text, FILE_APPEND);
    }

    /**
     * Helper to load the ecriture images hash used for correlation input
     * @return array<int,string>
     */
    private function loadStringImages(): array
    {
        $path = APPPATH . 'tests/data/string_images.php';
        $this->assertTrue(file_exists($path), 'string_images.php must exist');
        $images = require $path; // returns the $string_images array
        $this->assertTrue(is_array($images), 'string_images should be an array');
        return $images;
    }

    /**
     * Analyze and display correlations for a given StatementOperation and images set.
     * - Writes the StatementOperation summary
     * - Writes a table of [id, correlation, image]
     * - Asserts there is at least one ecriture with the minimal and maximal correlation
     * - If expected min/max are provided, assert they match the computed extremes
     */
    private function analyzeCorrelations($op, array $images, ?float $expectedMin = null, ?float $expectedMax = null): void
    {
        $smart = new SmartAdjustor();
        $correlations = [];

        // StatementOperation summary
        $this->write("=== StatementOperation ===\n");
        $this->write("type: " . $op->type() . "\n");
        $this->write("nature: " . $op->nature() . "\n");
        $this->write("interbank_label: " . $op->interbank_label() . "\n");
        $comments = $op->comments();
        if (!empty($comments)) {
            $this->write("comments:\n");
            foreach ($comments as $c) {
                $this->write("  - " . $c . "\n");
            }
        }

        // Table header
        $this->write("\nID | Correlation | Image\n");
        $this->write(str_repeat('-', 80) . "\n");

        // Compute and log
        foreach ($images as $id => $image) {
            $corr = $smart->correlation($op, (string)$id, $image, $op->type());
            $correlations[$id] = $corr;
            $this->write(sprintf("%6s | %11.3f | %s\n", $id, $corr, $image));
            $this->assertTrue($corr >= 0.0, 'Correlation must be >= 0.0');
            $this->assertTrue($corr <= 1.0, 'Correlation must be <= 1.0');
        }

        $this->assertTrue(count($correlations) > 0, 'There must be at least one ecriture image to compare');

        $minCorr = min($correlations);
        $maxCorr = max($correlations);

        $minIds = array_keys(array_filter($correlations, function ($v) use ($minCorr) { return abs($v - $minCorr) < 1e-9; }));
        $maxIds = array_keys(array_filter($correlations, function ($v) use ($maxCorr) { return abs($v - $maxCorr) < 1e-9; }));

        // Assert at least one reaches min and max among provided images
        $this->assertTrue(count($minIds) > 0, 'At least one ecriture should reach the minimal correlation');
        $this->assertTrue(count($maxIds) > 0, 'At least one ecriture should reach the maximal correlation');

        if ($expectedMin !== null) {
            $this->assertTrue(abs($expectedMin - $minCorr) <= 0.001, 'Computed minimal correlation should match expected');
        }
        if ($expectedMax !== null) {
            $this->assertTrue(abs($expectedMax - $maxCorr) <= 0.001, 'Computed maximal correlation should match expected');
        }

        $this->write("\nSummary: min=$minCorr (ids=" . implode(',', $minIds) . "), max=$maxCorr (ids=" . implode(',', $maxIds) . ")\n\n");
    }

    /**
     * Build a virement_recu StatementOperation stub from parser_result.txt entry [108]
     */
    private function makeFrisonVirementRecu()
    {
        return $this->makeStub(
            'virement_recu',
            'VIR RECU    3290980525S',
            'AUTRES VIREMENTS RECUS',
            [
                'DE: MME   CAROLINE FRISON',
                'MOTIF: Vol a Voile Frison Sebastien',
                'REF: Virement de Mme Caroline Frison',
            ]
        );
    }

    /**
     * Build a virement_recu StatementOperation stub from parser_result.txt entry [110]
     */
    private function makeBleuseVirementRecu()
    {
        return $this->makeStub(
            'virement_recu',
            'VIR INST RE 571777634923',
            '',
            [
                'DE: MR BLEUSE NICOLAS',
                'DATE: 05/08/2025 11:36',
                'MOTIF: VIREMENT DE MR BLEUSE NICOLAS',
                'REF: VIREMENT CUMULUS DE MR BLEUSE',
            ]
        );
    }

    /**
     * Build a virement_recu StatementOperation stub from parser_result.txt entry [107]
     */
    private function makeLobryVirementRecu()
    {
        return $this->makeStub(
            'virement_recu',
            'VIR INST RE 571271822463',
            '',
            [
                'DE: M MAXIME LOBRY',
                'DATE: 31/07/2025 08:13',
                'MOTIF: rem lobry acpam',
            ]
        );
    }

    /**
     * Build a prelevement StatementOperation stub from parser_result.txt entry [106]
     */
    private function makePrelevement()
    {
        return $this->makeStub(
            'prelevement',
            'PRELEVEMENT EUROPEEN 2811039457',
            'PRELEVEMENTS EUROPEENS EMIS',
            []
        );
    }

    /**
     * Build a frais_bancaire StatementOperation stub from parser_result.txt entry [109]
     */
    private function makeFraisBancaire()
    {
        return $this->makeStub(
            'frais_bancaire',
            'FACTURATION PROGELIANCE NET',
            'COMMISSIONS ET FRAIS DIVERS',
            []
        );
    }

    /**
     * Build a virement_emis StatementOperation stub from parser_result.txt entry [101]
     */
    private function makeVirementEmis()
    {
        return $this->makeStub(
            'virement_emis',
            '000001 VIR EUROPEEN EMIS   NET',
            'AUTRES VIREMENTS EMIS',
            []
        );
    }

    public function testCorrelationForFrisonAgainstAllImages()
    {
        $images = $this->loadStringImages();
        $this->analyzeCorrelations($this->makeFrisonVirementRecu(), $images, null, null);
    }

    public function testCorrelationForBleuseAgainstAllImages()
    {
        $images = $this->loadStringImages();
        $this->analyzeCorrelations($this->makeBleuseVirementRecu(), $images, null, null);
    }

    public function testCorrelationForLobryAgainstAllImages()
    {
        $images = $this->loadStringImages();
        $this->analyzeCorrelations($this->makeLobryVirementRecu(), $images, null, null);
    }

    public function testCorrelationForPrelevementAgainstAllImages()
    {
        $images = $this->loadStringImages();
        $this->analyzeCorrelations($this->makePrelevement(), $images, null, null);
    }

    public function testCorrelationForFraisBancaireAgainstAllImages()
    {
        $images = $this->loadStringImages();
        $this->analyzeCorrelations($this->makeFraisBancaire(), $images, null, null);
    }

    public function testCorrelationForVirementEmisAgainstAllImages()
    {
        $images = $this->loadStringImages();
        $this->analyzeCorrelations($this->makeVirementEmis(), $images, null, null);
    }
}
