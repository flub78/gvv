#!/usr/bin/env php
<?php
/**
 * PHPUnit Code Coverage Merger
 *
 * This script merges multiple PHPUnit .cov files generated with --coverage-php
 * and produces HTML and Clover XML reports.
 *
 * PROBLEM SOLVED:
 * PHPUnit 8.5.44 installed as a PHAR uses the namespace prefix "PHPUnitPHAR\"
 * for all its classes, but the .cov files it generates use the standard
 * "SebastianBergmann\CodeCoverage\" namespace. This mismatch causes
 * "Class not found" errors when trying to load .cov files.
 *
 * SOLUTION:
 * 1. Load the PHPUnit PHAR to get access to PHPUnitPHAR\* classes
 * 2. Create class aliases to map the standard names to PHPUnitPHAR names
 * 3. When loading .cov files, replace the constructor call with reflection
 *    to bypass driver auto-detection (which requires xdebug to be running)
 * 4. Use the PHPUnit PHAR's CodeCoverage classes to merge and generate reports
 *
 * INPUT:  build/coverage-data/*.cov
 * OUTPUT: build/coverage/index.html (HTML report)
 *         build/logs/clover.xml (Clover XML report)
 *
 * Usage: /usr/bin/php7.4 merge-coverage.php
 */

// Load the PHPUnit PHAR to get access to CodeCoverage classes
require __DIR__ . '/vendor/bin/phpunit';

// Create class aliases so we can work with the PHPUnitPHAR namespaced classes
class_alias('PHPUnitPHAR\SebastianBergmann\CodeCoverage\CodeCoverage', 'SebastianBergmann\CodeCoverage\CodeCoverage');
class_alias('PHPUnitPHAR\SebastianBergmann\CodeCoverage\Filter', 'SebastianBergmann\CodeCoverage\Filter');
class_alias('PHPUnitPHAR\SebastianBergmann\CodeCoverage\Report\Html\Facade', 'SebastianBergmann\CodeCoverage\Report\Html\Facade');
class_alias('PHPUnitPHAR\SebastianBergmann\CodeCoverage\Report\Clover', 'SebastianBergmann\CodeCoverage\Report\Clover');
class_alias('PHPUnitPHAR\SebastianBergmann\CodeCoverage\Report\Text', 'SebastianBergmann\CodeCoverage\Report\Text');

// Configuration
$coverageDir = __DIR__ . '/build/coverage-data';
$htmlOutputDir = __DIR__ . '/build/coverage';
$cloverOutputFile = __DIR__ . '/build/logs/clover.xml';

// Ensure output directories exist
if (!is_dir(dirname($cloverOutputFile))) {
    mkdir(dirname($cloverOutputFile), 0755, true);
}

/**
 * Load a .cov file by modifying it to work with the PHPUnit PHAR
 *
 * The .cov files call "new SebastianBergmann\CodeCoverage\CodeCoverage" without arguments,
 * which triggers driver auto-detection and fails. We replace the constructor call
 * with one that creates an instance using reflection instead.
 */
function loadCoverageFile($file) {
    echo "Loading coverage file: " . basename($file) . "\n";

    // Read the .cov file
    $covCode = file_get_contents($file);

    // Replace the CodeCoverage constructor to use reflection
    // This avoids the driver auto-detection which fails without xdebug
    $covCode = preg_replace(
        '/\$coverage = new SebastianBergmann\\\\CodeCoverage\\\\CodeCoverage;/',
        '$reflection = new ReflectionClass(\'PHPUnitPHAR\\\\SebastianBergmann\\\\CodeCoverage\\\\CodeCoverage\');' . PHP_EOL .
        '$coverage = $reflection->newInstanceWithoutConstructor();' . PHP_EOL .
        '$filterProperty = $reflection->getProperty(\'filter\');' . PHP_EOL .
        '$filterProperty->setAccessible(true);' . PHP_EOL .
        '$filterProperty->setValue($coverage, new PHPUnitPHAR\\\\SebastianBergmann\\\\CodeCoverage\\\\Filter());',
        $covCode
    );

    // Execute the modified .cov file
    $coverage = eval('?>' . $covCode);

    if (!$coverage instanceof PHPUnitPHAR\SebastianBergmann\CodeCoverage\CodeCoverage) {
        throw new RuntimeException("Failed to load coverage from: " . $file);
    }

    echo "  Loaded successfully\n";

    return $coverage;
}

/**
 * Merge multiple CodeCoverage objects
 */
function mergeCoverage(array $coverageObjects) {
    if (empty($coverageObjects)) {
        throw new RuntimeException("No coverage objects to merge");
    }

    echo "\nMerging " . count($coverageObjects) . " coverage files...\n";

    // Start with the first coverage object
    $merged = array_shift($coverageObjects);

    // Merge the rest
    foreach ($coverageObjects as $coverage) {
        $merged->merge($coverage);
    }

    echo "Merge complete\n";

    return $merged;
}

// Main execution
try {
    // Find all .cov files
    $covFiles = glob($coverageDir . '/*.cov');

    if (empty($covFiles)) {
        echo "No .cov files found in {$coverageDir}\n";
        exit(1);
    }

    echo "Found " . count($covFiles) . " coverage file(s)\n\n";

    // Load all coverage files
    $coverageObjects = [];
    foreach ($covFiles as $covFile) {
        try {
            $coverageObjects[] = loadCoverageFile($covFile);
        } catch (Exception $e) {
            echo "Error loading {$covFile}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    // Merge coverage
    $mergedCoverage = mergeCoverage($coverageObjects);

    // Apply filter to exclude unwanted directories
    echo "\nApplying coverage filter...\n";
    $filter = new PHPUnitPHAR\SebastianBergmann\CodeCoverage\Filter();

    // Add only the directories we want to include
    $appDir = __DIR__ . '/application';
    $filter->addDirectoryToWhitelist($appDir . '/config');
    $filter->addDirectoryToWhitelist($appDir . '/core');
    $filter->addDirectoryToWhitelist($appDir . '/errors');
    $filter->addDirectoryToWhitelist($appDir . '/helpers');
    $filter->addDirectoryToWhitelist($appDir . '/hooks');
    $filter->addDirectoryToWhitelist($appDir . '/language');
    $filter->addDirectoryToWhitelist($appDir . '/libraries');
    $filter->addDirectoryToWhitelist($appDir . '/migrations');
    $filter->addDirectoryToWhitelist($appDir . '/models');

    // Exclude specific problematic files
    $filter->removeFileFromWhitelist($appDir . '/controllers/achats.php');
    $filter->removeFileFromWhitelist($appDir . '/controllers/vols_planeur.php');
    $filter->removeFileFromWhitelist($appDir . '/controllers/vols_avion.php');

    // Apply the new filter to merged coverage
    $reflection = new ReflectionClass($mergedCoverage);
    $filterProperty = $reflection->getProperty('filter');
    $filterProperty->setAccessible(true);
    $filterProperty->setValue($mergedCoverage, $filter);

    echo "Filter applied (included: config, core, errors, helpers, hooks, language, libraries, migrations, models)\n";

    // Generate HTML report
    echo "\nGenerating HTML report to: {$htmlOutputDir}\n";
    $htmlReport = new PHPUnitPHAR\SebastianBergmann\CodeCoverage\Report\Html\Facade();
    $htmlReport->process($mergedCoverage, $htmlOutputDir);
    echo "HTML report generated\n";

    // Generate Clover XML report
    echo "\nGenerating Clover XML report to: {$cloverOutputFile}\n";
    $cloverReport = new PHPUnitPHAR\SebastianBergmann\CodeCoverage\Report\Clover();
    $cloverReport->process($mergedCoverage, $cloverOutputFile);
    echo "Clover report generated\n";

    // Generate text summary
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "Coverage Summary:\n";
    echo str_repeat("=", 70) . "\n";
    $textReport = new PHPUnitPHAR\SebastianBergmann\CodeCoverage\Report\Text(50, 90, false, false);
    echo $textReport->process($mergedCoverage, false);

    echo "\nDone!\n";
    exit(0);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
