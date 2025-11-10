<?php

use PHPUnit\Framework\TestCase;

/**
 * Test PHPUnit pour la fonctionnalité de nettoyage des montants
 * dans les formulaires de saisie des écritures comptables
 */

class CurrencyCleaningTest extends TestCase {

    protected $CI;

    protected function setUp(): void {
        // Load validation helper for clean_currency_input function
        if (!function_exists('clean_currency_input')) {
            require_once APPPATH . 'helpers/validation_helper.php';
        }
    }

    /**
     * Test de la fonction clean_currency_input avec divers cas de copier-coller
     */
    public function testCleanCurrencyInput() {
        // Cas simples
        $this->assertEquals('123.45', clean_currency_input('123.45'));
        $this->assertEquals('123.45', clean_currency_input('123,45'));
        $this->assertEquals('123', clean_currency_input('123'));
        
        // Avec espaces
        $this->assertEquals('1234.56', clean_currency_input('1 234.56'));
        $this->assertEquals('1234.56', clean_currency_input('1 234,56'));
        
        // Avec caractères de devise
        $this->assertEquals('123.45', clean_currency_input('123.45 €'));
        $this->assertEquals('123.45', clean_currency_input('€ 123.45'));
        $this->assertEquals('123.45', clean_currency_input('123,45€'));
        $this->assertEquals('123.45', clean_currency_input('$123.45'));
        
        // Formatage complexe (milliers + décimales)
        $this->assertEquals('1234.56', clean_currency_input('1.234,56')); // Format français
        $this->assertEquals('1234.56', clean_currency_input('1,234.56')); // Format anglais
        $this->assertEquals('12345.67', clean_currency_input('12 345,67'));
        $this->assertEquals('12345.67', clean_currency_input('12.345,67'));
        
        // Espaces insécables et autres
        $this->assertEquals('1234.56', clean_currency_input("1\xC2\xA0234.56")); // Espace insécable
        $this->assertEquals('1234.56', clean_currency_input("1\t234.56"));       // Tabulation
        
        // Cas extrêmes
        $this->assertEquals('', clean_currency_input(''));
        $this->assertEquals('0.00', clean_currency_input('0.00'));
        $this->assertEquals('123.45', clean_currency_input('  123.45  '));
        
        // Cas d'erreur potentiels
        $this->assertEquals('123.45', clean_currency_input('abc123.45'));
        $this->assertEquals('12345.67', clean_currency_input('123.45.67')); // Multiples points
        $this->assertEquals('12345.67', clean_currency_input('123,45,67')); // Multiples virgules
    }

    /**
     * Test de l'intégration dans le workflow de validation
     * Simule le pré-traitement des champs decimal avant validation
     */
    public function testValidationWorkflow() {
        // Simuler des données POST avec montants formatés
        $_POST = [
            'montant' => '1 234,56 €',
            'description' => 'Test écriture',
            'compte1' => '1',
            'compte2' => '2'
        ];
        
        $original_montant = $_POST['montant'];
        
        // Simuler le pré-traitement dans formValidation pour les champs decimal
        $decimal_fields = ['montant']; // En réalité, détecté par field_type()
        
        foreach ($decimal_fields as $field) {
            $value = $_POST[$field];
            if ($value !== '' && $value !== null) {
                $cleaned_value = clean_currency_input($value);
                $_POST[$field] = $cleaned_value;
            }
        }
        
        // Vérifier que le nettoyage a eu lieu
        $this->assertNotEquals($original_montant, $_POST['montant']);
        $this->assertEquals('1234.56', $_POST['montant']);
        
        // Vérifier que la validation numeric réussit maintenant
        $this->assertTrue(is_numeric($_POST['montant']));
        
        // Les autres champs ne doivent pas être affectés
        $this->assertEquals('Test écriture', $_POST['description']);
    }

    /**
     * Test avec des valeurs null et vides
     */
    public function testEdgeCases() {
        $this->assertEquals('', clean_currency_input(''));
        $this->assertEquals(null, clean_currency_input(null));
        
        // Test du cas '0' qui peut être problématique avec empty()
        $this->assertEquals('0', clean_currency_input('0'));
    }
}