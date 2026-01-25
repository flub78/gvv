<?php
/**
 *    GVV Gestion vol a voile
 *    Copyright (C) 2011  Philippe Boissel & Frederic Peignot
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
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Markdown Parser
 *
 * Parses Markdown training program files into structured data.
 * Expected format:
 * - H1 (#) : Program title
 * - H2 (##) : Lesson (Leçon)
 * - H3 (###) : Topic (Sujet)
 * - Content : Description/objectives text
 *
 * Example:
 * ```markdown
 * # Formation Initiale Planeur
 * 
 * ## Leçon 1 : Découverte du planeur
 * 
 * ### Sujet 1.1 : Présentation de l'aéronef
 * Description text...
 * 
 * - Bullet point objectives
 * ```
 *
 * @see doc/prds/suivi_formation_prd.md
 * @see doc/test-data/formation_spl.md
 */
class Formation_markdown_parser
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Parse a Markdown training program file
     *
     * @param string $markdown_content Full Markdown content
     * @return array Structured data with title, lecons, and sujets
     * @throws Exception if parsing fails with detailed error message
     */
    public function parse($markdown_content)
    {
        if (empty($markdown_content)) {
            throw new Exception('Contenu Markdown vide');
        }

        $lines = explode("\n", $markdown_content);
        $result = [
            'titre' => '',
            'lecons' => []
        ];

        $current_lecon = null;
        $current_sujet = null;
        $current_content = [];
        $titre_found = false;

        foreach ($lines as $line_num => $line) {
            $line = rtrim($line);

            // H1 - Program title
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                if ($titre_found) {
                    throw new Exception(sprintf(
                        "Erreur ligne %d : Plusieurs titres H1 trouvés\nLigne : %s\nUn seul titre # est autorisé au début du fichier",
                        $line_num + 1,
                        $line
                    ));
                }
                $result['titre'] = trim($matches[1]);
                $titre_found = true;
                continue;
            }

            // H2 - Lesson
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                // Save previous sujet if any
                if ($current_sujet !== null && $current_lecon !== null) {
                    $this->save_sujet($result['lecons'][$current_lecon], $current_sujet, $current_content);
                    $current_content = [];
                }

                // Parse lesson title: "Leçon X : Title"
                $lecon_title = trim($matches[1]);
                if (preg_match('/^Leçon\s+(\d+)\s*:\s*(.+)$/i', $lecon_title, $lecon_matches)) {
                    $lecon_numero = (int) $lecon_matches[1];
                    $lecon_titre = trim($lecon_matches[2]);
                } else {
                    // Fallback: use full title
                    $lecon_numero = count($result['lecons']) + 1;
                    $lecon_titre = $lecon_title;
                }

                $current_lecon = count($result['lecons']);
                $result['lecons'][] = [
                    'numero' => $lecon_numero,
                    'titre' => $lecon_titre,
                    'description' => '',
                    'ordre' => $current_lecon + 1,
                    'sujets' => []
                ];
                $current_sujet = null;
                continue;
            }

            // H3 - Topic (Sujet)
            if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                // Save previous sujet if any
                if ($current_sujet !== null && $current_lecon !== null) {
                    $this->save_sujet($result['lecons'][$current_lecon], $current_sujet, $current_content);
                    $current_content = [];
                }

                if ($current_lecon === null) {
                    throw new Exception(sprintf(
                        "Erreur ligne %d : Sujet trouvé avant toute leçon\nLigne : %s\nUn sujet (###) doit être précédé d'une leçon (##)",
                        $line_num + 1,
                        $line
                    ));
                }

                // Parse sujet title: "Sujet X.Y : Title"
                $sujet_title = trim($matches[1]);
                if (preg_match('/^Sujet\s+([\d\.]+)\s*:\s*(.+)$/i', $sujet_title, $sujet_matches)) {
                    $sujet_numero = $sujet_matches[1];
                    $sujet_titre = trim($sujet_matches[2]);
                } else {
                    // Fallback
                    $sujet_numero = ($result['lecons'][$current_lecon]['numero']) . '.' . (count($result['lecons'][$current_lecon]['sujets']) + 1);
                    $sujet_titre = $sujet_title;
                }

                $current_sujet = [
                    'numero' => $sujet_numero,
                    'titre' => $sujet_titre,
                    'description' => '',
                    'objectifs' => '',
                    'ordre' => count($result['lecons'][$current_lecon]['sujets']) + 1
                ];
                continue;
            }

            // Content lines
            if ($current_sujet !== null) {
                // Accumulate content for current sujet
                $current_content[] = $line;
            } elseif ($current_lecon !== null && empty($line) === false) {
                // Content between lecon header and first sujet = lecon description
                if (!empty($result['lecons'][$current_lecon]['description'])) {
                    $result['lecons'][$current_lecon]['description'] .= "\n";
                }
                $result['lecons'][$current_lecon]['description'] .= $line;
            }
        }

        // Save last sujet
        if ($current_sujet !== null && $current_lecon !== null) {
            $this->save_sujet($result['lecons'][$current_lecon], $current_sujet, $current_content);
        }

        // Validate basic structure
        if (empty($result['titre'])) {
            throw new Exception(
                "Erreur : Titre du programme manquant\n" .
                "Le fichier doit commencer par un titre de niveau H1 :\n" .
                "Exemple : # Formation Initiale Planeur"
            );
        }

        if (empty($result['lecons'])) {
            throw new Exception(
                "Erreur : Aucune leçon trouvée\n" .
                "Le programme doit contenir au moins une leçon (##) :\n" .
                "Exemple : ## Leçon 1 : Découverte du planeur"
            );
        }

        // Validate each lesson has at least one sujet
        foreach ($result['lecons'] as $lecon) {
            if (empty($lecon['sujets'])) {
                throw new Exception(sprintf(
                    "Erreur : Leçon %d '%s' ne contient aucun sujet\n" .
                    "Chaque leçon doit contenir au moins un sujet (###) :\n" .
                    "Exemple : ### Sujet 1.1 : Présentation de l'aéronef",
                    $lecon['numero'],
                    $lecon['titre']
                ));
            }
        }

        return $result;
    }

    /**
     * Save a sujet with its accumulated content
     *
     * @param array &$lecon Lecon array (passed by reference)
     * @param array $sujet Sujet data
     * @param array $content_lines Accumulated content lines
     */
    private function save_sujet(&$lecon, $sujet, $content_lines)
    {
        // Join content lines
        $content = implode("\n", $content_lines);
        $content = trim($content);

        // Split into description and objectives
        // First paragraph = description, bullet list = objectives
        $parts = preg_split('/\n\s*\n/', $content, 2);
        
        if (count($parts) > 0) {
            $sujet['description'] = trim($parts[0]);
        }
        
        if (count($parts) > 1) {
            $sujet['objectifs'] = trim($parts[1]);
        }

        $lecon['sujets'][] = $sujet;
    }

    /**
     * Validate parsed structure
     *
     * @param array $parsed_data Data from parse()
     * @return true|string TRUE if valid, detailed error message if invalid
     */
    public function validate($parsed_data)
    {
        $errors = [];

        if (empty($parsed_data['titre'])) {
            $errors[] = 'Titre du programme manquant';
        }

        if (empty($parsed_data['lecons'])) {
            $errors[] = 'Aucune leçon trouvée';
            return "Validation échouée :\n- " . implode("\n- ", $errors);
        }

        foreach ($parsed_data['lecons'] as $idx => $lecon) {
            $lecon_label = "Leçon {$lecon['numero']}";

            if (empty($lecon['titre'])) {
                $errors[] = "$lecon_label : Titre manquant";
            }

            if (empty($lecon['sujets'])) {
                $errors[] = "$lecon_label : Aucun sujet trouvé";
            }

            foreach ($lecon['sujets'] as $sujet_idx => $sujet) {
                $sujet_label = "$lecon_label > Sujet {$sujet['numero']}";

                if (empty($sujet['titre'])) {
                    $errors[] = "$sujet_label : Titre manquant";
                }

                if (empty($sujet['description']) && empty($sujet['objectifs'])) {
                    $errors[] = "$sujet_label : Aucun contenu (description ou objectifs)";
                }
            }
        }

        if (!empty($errors)) {
            return "Validation échouée :\n- " . implode("\n- ", $errors);
        }

        return TRUE;
    }

    /**
     * Export structured data back to Markdown
     *
     * @param string $titre Program title
     * @param array $lecons Array of lecons with sujets
     * @return string Markdown content
     */
    public function export($titre, $lecons)
    {
        $markdown = "# $titre\n\n";

        foreach ($lecons as $lecon) {
            $markdown .= "## Leçon {$lecon['numero']} : {$lecon['titre']}\n\n";

            if (!empty($lecon['description'])) {
                $markdown .= trim($lecon['description']) . "\n\n";
            }

            foreach ($lecon['sujets'] as $sujet) {
                $markdown .= "### Sujet {$sujet['numero']} : {$sujet['titre']}\n";

                if (!empty($sujet['description'])) {
                    $markdown .= trim($sujet['description']) . "\n\n";
                }

                if (!empty($sujet['objectifs'])) {
                    $markdown .= trim($sujet['objectifs']) . "\n\n";
                }
            }
        }

        return $markdown;
    }
}

/* End of file Formation_markdown_parser.php */
/* Location: ./application/libraries/Formation_markdown_parser.php */
