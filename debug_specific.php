<?php

define('BASEPATH', true);
require_once('application/helpers/validation_helper.php');

echo "Test spécifique pour '1 000.00 €'\n";
echo "====================================\n\n";

$input = '1 000.00 €';
echo "Input: '$input'\n";

$cleaned = clean_currency_input($input);
echo "Cleaned: '$cleaned'\n";

$is_numeric = is_numeric($cleaned);
echo "Is numeric: " . ($is_numeric ? 'YES' : 'NO') . "\n";

if (!$is_numeric) {
    echo "PROBLÈME détecté!\n";
    echo "Input hex: " . bin2hex($input) . "\n";
    echo "Cleaned hex: " . bin2hex($cleaned) . "\n";
}

// Test des étapes de nettoyage une par une
echo "\nDébug étape par étape:\n";
echo "1. Suppression espaces: '" . preg_replace('/\s+/u', '', $input) . "'\n";
$step1 = preg_replace('/\s+/u', '', $input);

echo "2. Suppression devises: '" . preg_replace('/[€$£¥₹₽]/u', '', $step1) . "'\n";
$step2 = preg_replace('/[€$£¥₹₽]/u', '', $step1);

echo "3. Suppression non-num sauf .,: '" . preg_replace('/[^0-9.,]/', '', $step2) . "'\n";
$step3 = preg_replace('/[^0-9.,]/', '', $step2);

echo "4. Gestion séparateurs: ";
if (preg_match('/^[\d.,]+$/', $step3)) {
    echo "Pattern match OK\n";
    $lastCommaPos = strrpos($step3, ',');
    $lastDotPos = strrpos($step3, '.');
    
    echo "   Last comma: " . ($lastCommaPos !== false ? $lastCommaPos : 'none') . "\n";
    echo "   Last dot: " . ($lastDotPos !== false ? $lastDotPos : 'none') . "\n";
    
    $lastSeparatorPos = max($lastCommaPos, $lastDotPos);
    echo "   Last separator pos: $lastSeparatorPos\n";
    
    if ($lastSeparatorPos !== false) {
        $afterSeparator = substr($step3, $lastSeparatorPos + 1);
        echo "   After separator: '$afterSeparator' (length: " . strlen($afterSeparator) . ")\n";
        echo "   Is digit: " . (ctype_digit($afterSeparator) ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "Pattern match FAILED\n";
}