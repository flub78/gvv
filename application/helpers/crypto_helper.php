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

if (!function_exists('encrypt_file')) {
    /**
     * Chiffre un fichier avec OpenSSL AES-256-CBC
     *
     * @param string $input_file Chemin du fichier à chiffrer
     * @param string $output_file Chemin du fichier chiffré (optionnel, par défaut ajoute .enc)
     * @param string $passphrase Passphrase pour le chiffrement
     * @return bool TRUE en cas de succès, FALSE sinon
     */
    function encrypt_file($input_file, $output_file = null, $passphrase = null) {
        if (!file_exists($input_file)) {
            gvv_error("encrypt_file: Input file does not exist: $input_file");
            return false;
        }

        if (empty($passphrase)) {
            $CI =& get_instance();
            $passphrase = $CI->config->item('passphrase');
            if (empty($passphrase)) {
                gvv_error("encrypt_file: No passphrase provided and no default passphrase configured");
                return false;
            }
        }

        if ($output_file === null) {
            $output_file = $input_file . '.enc';
        }

        // Use OpenSSL command line for encryption
        // -aes-256-cbc: AES 256 bits in CBC mode
        // -pbkdf2: Use PBKDF2 key derivation (more secure)
        // -salt: Add salt (default, but explicit for clarity)
        // -pass pass:xxx: Pass the passphrase directly
        $command = sprintf(
            'openssl enc -aes-256-cbc -pbkdf2 -salt -in %s -out %s -pass pass:%s 2>&1',
            escapeshellarg($input_file),
            escapeshellarg($output_file),
            escapeshellarg($passphrase)
        );

        gvv_debug("encrypt_file: Encrypting $input_file to $output_file");
        exec($command, $output, $return_code);

        if ($return_code !== 0) {
            gvv_error("encrypt_file: OpenSSL encryption failed with code $return_code: " . implode("\n", $output));
            return false;
        }

        if (!file_exists($output_file)) {
            gvv_error("encrypt_file: Output file was not created: $output_file");
            return false;
        }

        gvv_info("encrypt_file: Successfully encrypted $input_file to $output_file");
        return true;
    }
}

if (!function_exists('decrypt_file')) {
    /**
     * Déchiffre un fichier avec OpenSSL AES-256-CBC
     *
     * @param string $input_file Chemin du fichier chiffré
     * @param string $output_file Chemin du fichier déchiffré
     * @param string $passphrase Passphrase pour le déchiffrement
     * @return bool TRUE en cas de succès, FALSE sinon
     */
    function decrypt_file($input_file, $output_file, $passphrase = null) {
        if (!file_exists($input_file)) {
            gvv_error("decrypt_file: Input file does not exist: $input_file");
            return false;
        }

        if (empty($passphrase)) {
            $CI =& get_instance();
            $passphrase = $CI->config->item('passphrase');
            if (empty($passphrase)) {
                gvv_error("decrypt_file: No passphrase provided and no default passphrase configured");
                return false;
            }
        }

        // Use OpenSSL command line for decryption
        // -d: Decrypt mode
        // -aes-256-cbc: AES 256 bits in CBC mode
        // -pbkdf2: Use PBKDF2 key derivation
        // -pass pass:xxx: Pass the passphrase directly
        $command = sprintf(
            'openssl enc -d -aes-256-cbc -pbkdf2 -in %s -out %s -pass pass:%s 2>&1',
            escapeshellarg($input_file),
            escapeshellarg($output_file),
            escapeshellarg($passphrase)
        );

        gvv_debug("decrypt_file: Decrypting $input_file to $output_file");
        exec($command, $output, $return_code);

        if ($return_code !== 0) {
            gvv_error("decrypt_file: OpenSSL decryption failed with code $return_code: " . implode("\n", $output));
            return false;
        }

        if (!file_exists($output_file)) {
            gvv_error("decrypt_file: Output file was not created: $output_file");
            return false;
        }

        gvv_info("decrypt_file: Successfully decrypted $input_file to $output_file");
        return true;
    }
}

if (!function_exists('is_encrypted_backup')) {
    /**
     * Vérifie si un fichier est chiffré (basé sur l'extension)
     *
     * @param string $filename Nom du fichier
     * @return bool TRUE si le fichier semble être chiffré, FALSE sinon
     */
    function is_encrypted_backup($filename) {
        return preg_match('/\.enc\.(zip|tar\.gz|tgz|gz)$/i', $filename) === 1;
    }
}

if (!function_exists('get_decrypted_filename')) {
    /**
     * Obtient le nom du fichier déchiffré à partir du nom du fichier chiffré
     *
     * @param string $encrypted_filename Nom du fichier chiffré (ex: backup.enc.zip)
     * @return string Nom du fichier déchiffré (ex: backup.zip)
     */
    function get_decrypted_filename($encrypted_filename) {
        return preg_replace('/\.enc\.(zip|tar\.gz|tgz|gz)$/i', '.$1', $encrypted_filename);
    }
}

if (!function_exists('get_encrypted_filename')) {
    /**
     * Obtient le nom du fichier chiffré à partir du nom du fichier non chiffré
     *
     * @param string $plain_filename Nom du fichier non chiffré (ex: backup.zip)
     * @return string Nom du fichier chiffré (ex: backup.enc.zip)
     */
    function get_encrypted_filename($plain_filename) {
        return preg_replace('/\.(zip|tar\.gz|tgz|gz)$/i', '.enc.$1', $plain_filename);
    }
}
