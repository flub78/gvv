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
 *    Basic cryptographic helper.
 * 
 *    Use for a reversible integer function to obfuscate data.
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('transformInteger')) {

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
        // Conversion si c'est une chaîne numérique
        if (is_string($input) && ctype_digit($input)) {
            $input = (int)$input;
        }
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
}

if (!function_exists('reverseTransform')) {
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
        $inverseKey2 = modInverse($key2, $prime);
        $result = ($result * $inverseKey2) % $prime;

        // Étape 1 inverse: Annulation du XOR initial
        $result = $result ^ $key1;

        return $result;
    }
}

if (!function_exists('modInverse')) {

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
}
