<?php
/**
 * Test compression on the generated test files
 * This script demonstrates the compression scenarios defined in the PRD
 */

echo "=== ATTACHMENT COMPRESSION TEST SCRIPT ===\n\n";

$testDir = __DIR__ . '/attachments';

if (!is_dir($testDir)) {
    echo "Error: Test files directory not found: $testDir\n";
    exit(1);
}

// Check required PHP extensions
$requiredExtensions = ['gd', 'zlib'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "Error: Missing required PHP extensions: " . implode(', ', $missingExtensions) . "\n";
    exit(1);
}

echo "✓ Required PHP extensions available: " . implode(', ', $requiredExtensions) . "\n\n";

/**
 * Test compression on a file (simulation of PRD compression logic)
 */
function testCompression($filePath) {
    $originalSize = filesize($filePath);
    $filename = basename($filePath);
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    echo "Testing: $filename\n";
    echo "  Original size: " . formatBytes($originalSize) . "\n";
    
    // PRD CA2.3: Files < 100KB preserved
    if ($originalSize < 100 * 1024) {
        echo "  Result: SKIPPED (< 100KB threshold)\n";
        echo "  Compression: NONE (file preserved as-is)\n\n";
        return;
    }
    
    $compressedSize = null;
    $method = '';
    
    // PRD CA2.2: Compression strategy based on file type
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
        // Image compression simulation
        $method = 'Image resize + JPEG conversion + gzip';
        
        // Simulate image processing
        if ($extension === 'png' || $extension === 'gif') {
            // Conversion to JPEG typically reduces PNG/GIF significantly
            $compressedSize = $originalSize * 0.3; // ~70% reduction typical
        } else {
            // JPEG files might have less compression gain
            $compressedSize = $originalSize * 0.6; // ~40% reduction typical
        }
        
        // Apply gzip to the result
        $compressedSize = $compressedSize * 0.9; // Additional 10% from gzip
        
    } else {
        // Other files: gzip compression only
        $method = 'gzip compression (level 9)';
        
        // Test actual gzip compression on a sample
        $sampleSize = min(1024 * 1024, $originalSize); // Sample up to 1MB
        $sampleData = file_get_contents($filePath, false, null, 0, $sampleSize);
        $compressedSample = gzencode($sampleData, 9);
        
        if ($compressedSample !== false) {
            $sampleRatio = strlen($compressedSample) / strlen($sampleData);
            $compressedSize = $originalSize * $sampleRatio;
        } else {
            $compressedSize = $originalSize; // No compression possible
        }
    }
    
    $ratio = ($originalSize - $compressedSize) / $originalSize * 100;
    
    // PRD CA2.3: Files with < 10% compression ratio preserved
    if ($ratio < 10) {
        echo "  Result: SKIPPED (compression ratio < 10%)\n";
        echo "  Compression: NONE (file preserved as-is)\n\n";
        return;
    }
    
    echo "  Compressed size: " . formatBytes($compressedSize) . "\n";
    echo "  Compression ratio: " . number_format($ratio, 1) . "%\n";
    echo "  Method: $method\n";
    echo "  Storage saved: " . formatBytes($originalSize - $compressedSize) . "\n\n";
    
    return [
        'original' => $originalSize,
        'compressed' => $compressedSize,
        'ratio' => $ratio,
        'method' => $method
    ];
}

function formatBytes($bytes) {
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Test files by category
$categories = [
    'Text Files' => 'text',
    'Document Files' => 'documents',
    'Image Files' => 'images',
    'Archive Files' => 'archives'
];

$totalOriginal = 0;
$totalCompressed = 0;
$processedFiles = 0;

foreach ($categories as $categoryName => $categoryDir) {
    echo "=== $categoryName ===\n";
    
    $categoryPath = $testDir . '/' . $categoryDir;
    if (!is_dir($categoryPath)) {
        echo "Directory not found: $categoryPath\n\n";
        continue;
    }
    
    $files = glob($categoryPath . '/*');
    if (empty($files)) {
        echo "No files found in $categoryPath\n\n";
        continue;
    }
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $result = testCompression($file);
            if ($result) {
                $totalOriginal += $result['original'];
                $totalCompressed += $result['compressed'];
                $processedFiles++;
            } else {
                // Files not compressed still count towards total
                $totalOriginal += filesize($file);
                $totalCompressed += filesize($file);
            }
        }
    }
}

// Overall summary
echo "=== OVERALL COMPRESSION SUMMARY ===\n";
echo "Files processed: $processedFiles\n";
echo "Total original size: " . formatBytes($totalOriginal) . "\n";
echo "Total compressed size: " . formatBytes($totalCompressed) . "\n";

if ($totalOriginal > 0) {
    $overallRatio = ($totalOriginal - $totalCompressed) / $totalOriginal * 100;
    echo "Overall compression ratio: " . number_format($overallRatio, 1) . "%\n";
    echo "Total storage saved: " . formatBytes($totalOriginal - $totalCompressed) . "\n";
} else {
    echo "No files were processed\n";
}

echo "\n=== PRD COMPRESSION TARGETS ===\n";
echo "Target storage reduction: 30-50% (PRD Objective 3.1.2)\n";
echo "Expected ratios by file type:\n";
echo "• Smartphone photos (3-8MB): 80-90% reduction → 500KB-1MB\n";
echo "• Scanned images (1-5MB): 60-80% reduction\n";
echo "• Text files (TXT, CSV): 80-95% reduction\n";
echo "• Office documents (PDF, DOCX, XLSX): 10-40% reduction\n";
echo "• Already compressed (ZIP, RAR): Skip compression\n";

if ($totalOriginal > 0) {
    echo "\nResult: ";
    if ($overallRatio >= 30 && $overallRatio <= 70) {
        echo "✓ MEETS PRD TARGETS\n";
    } elseif ($overallRatio > 70) {
        echo "✓ EXCEEDS PRD TARGETS (excellent compression)\n";
    } else {
        echo "⚠ BELOW PRD TARGETS (may need optimization)\n";
    }
}

echo "\nNOTE: This is a simulation. Actual compression will vary based on file content.\n";
echo "      Image compression simulation does not perform actual image processing.\n";

?>