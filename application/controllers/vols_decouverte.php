<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource vols_decouverte.php
 * @package controllers
 * Contrôleur de gestion des avions.
 */
include('./application/libraries/Gvv_Controller.php');
class Vols_decouverte extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'vols_decouverte';
    protected $model = 'vols_decouverte_model';
    protected $modification_level = 'ca';
    protected $rules = array();

    /**
     * Transforme un entier en un autre entier de façon réversible mais difficile à inverser
     * sans connaître l'algorithme et les clés utilisées
     * 
     * @param int $input L'entier à transformer
     * @param int $key1 Première clé secrète
     * @param int $key2 Deuxième clé secrète
     * @return int L'entier transformé
     */
    function transformInteger($input, $key1 = 12345, $key2 = 67890) {
        // Validation de l'entrée
        if (!is_int($input) || $input < 0) {
            throw new InvalidArgumentException("L'entrée doit être un entier positif");
        }

        // Limite pour rester dans un intervalle gérable
        $prime = 1000000007; // Nombre premier souvent utilisé en cryptographie

        // Étape 1: Mélange initial avec XOR
        $result = $input ^ $key1;

        // Étape 2: Permutation avec multiplication
        $result = ($result * $key2) % $prime;

        // Étape 3: Autre mélange avec XOR et décalage
        $result = $result ^ ($key1 << 4);

        // Étape 4: Ajout d'une constante dérivée des clés
        $mix = (($key1 + $key2) % $prime);
        $result = ($result + $mix) % $prime;

        return $result;
    }

    /**
     * Fonction inverse qui permet de retrouver l'entier original
     * 
     * @param int $encoded L'entier transformé
     * @param int $key1 Première clé secrète (doit être identique à celle utilisée pour l'encodage)
     * @param int $key2 Deuxième clé secrète (doit être identique à celle utilisée pour l'encodage)
     * @return int L'entier original
     */
    function reverseTransform($encoded, $key1 = 12345, $key2 = 67890) {
        $prime = 1000000007;

        // Étape 4 inverse: Soustraction de la constante
        $mix = (($key1 + $key2) % $prime);
        $result = ($encoded - $mix) % $prime;
        if ($result < 0) $result += $prime;

        // Étape 3 inverse: Annulation du XOR avec décalage
        $result = $result ^ ($key1 << 4);

        // Étape 2 inverse: Annulation de la multiplication en utilisant l'inverse modulaire
        $inverseKey2 = $this->modInverse($key2, $prime);
        $result = ($result * $inverseKey2) % $prime;

        // Étape 1 inverse: Annulation du XOR initial
        $result = $result ^ $key1;

        return $result;
    }

    /**
     * Calcule l'inverse modulaire (a^-1 mod m)
     * Utilise l'algorithme d'Euclide étendu
     * 
     * @param int $a Le nombre dont on cherche l'inverse
     * @param int $m Le modulo
     * @return int L'inverse modulaire de a mod m
     */
    function modInverse($a, $m) {
        // Cas spécial
        if ($a == 0) {
            throw new InvalidArgumentException("L'inverse modulaire de 0 n'existe pas");
        }

        // Garde a positif et dans le bon intervalle
        $a = $a % $m;
        if ($a < 0) {
            $a += $m;
        }

        // Algorithme d'Euclide étendu
        $s = 0;
        $oldS = 1;
        $t = 1;
        $oldT = 0;
        $r = $m;
        $oldR = $a;

        while ($r != 0) {
            $quotient = intdiv($oldR, $r);

            // Mise à jour des valeurs
            $temp = $r;
            $r = $oldR - $quotient * $r;
            $oldR = $temp;

            $temp = $s;
            $s = $oldS - $quotient * $s;
            $oldS = $temp;

            $temp = $t;
            $t = $oldT - $quotient * $t;
            $oldT = $temp;
        }

        // Si le PGCD n'est pas 1, l'inverse n'existe pas
        if ($oldR != 1) {
            throw new InvalidArgumentException("L'inverse modulaire n'existe pas car $a et $m ne sont pas premiers entre eux");
        }

        // Ajustement pour obtenir une valeur positive
        if ($oldS < 0) {
            $oldS += $m;
        }

        return $oldS;
    }

    /**
     * Génération des éléments statiques à passer au formulaire en cas de création,
     * modification ou ré-affichage après erreur.
     * Sont statiques les parties qui ne changent pas d'un élément sur l'autre.
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     * @see constants.php
     */
    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }

        $this->data['saisie_par'] = $this->dx_auth->get_username();

        $pilote_selector = $this->membres_model->selector_with_null(['actif' => 1]);
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
    }

    function qr() {

        $originalNumber = 12345;
        $transformed = $this->transformInteger($originalNumber);
        $recovered = $this->reverseTransform($transformed);

        echo "QR:";
        echo "Nombre original: " . $originalNumber . "\n";
        echo "Nombre transformé: " . $transformed . "\n";
        echo "Nombre récupéré: " . $recovered . "\n";

        // Test avec quelques autres valeurs
        $testValues = [0, 1, 42, 99999, 1000000];
        foreach ($testValues as $value) {
            $transformed = $this->transformInteger($value);
            $recovered = $this->reverseTransform($transformed);
            echo "Test avec $value: transformé = $transformed, récupéré = $recovered\n";
        }
    }
}
