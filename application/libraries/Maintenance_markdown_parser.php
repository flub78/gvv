<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Markdown Parser
 *
 * Parses Markdown maintenance program files into structured data.
 * Classe dediee, independante de Formation_markdown_parser malgre un
 * format isomorphe (decision actee, cf. doc/plans/maintenance_aeronefs_plan.md
 * Resume). Plus simple que son homologue Formation : maintenance_programme_sections
 * et maintenance_taches n'ont pas de champ `numero` (seulement `ordre` et
 * `titre`), il n'y a donc pas de prefixe "Leçon X :"/"Sujet X.Y :" a
 * reconnaitre, ni de split description/objectifs.
 *
 * Expected format:
 * - H1 (#)   : Program title
 * - H2 (##)  : Section (maintenance_programme_sections)
 * - H3 (###) : Tache (maintenance_taches)
 * - Content  : Tache description
 *
 * Example:
 * ```markdown
 * # Visite 100 heures cellule
 *
 * ## Cellule
 *
 * ### Controle visuel du fuselage
 * Inspection des surfaces et jonctions, recherche de fissures.
 * ```
 *
 * Du texte place directement sous un H2, avant toute H3, n'a pas de
 * colonne pour etre conserve (maintenance_programme_sections n'a pas de
 * description) : il est ignore, comme Formation_markdown_parser ignore
 * silencieusement le texte place avant la premiere leçon.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2)
 * @see doc/test-data/maintenance_visite_100h.md
 */
class Maintenance_markdown_parser
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Parse a Markdown maintenance program file
     *
     * @param string $markdown_content Full Markdown content
     * @return array Structured data with titre and sections (each with taches)
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
            'sections' => []
        ];

        $current_section = null;
        $current_tache = null;
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

            // H2 - Section
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                if ($current_tache !== null && $current_section !== null) {
                    $this->save_tache($result['sections'][$current_section], $current_tache, $current_content);
                    $current_content = [];
                }

                $current_section = count($result['sections']);
                $result['sections'][] = [
                    'ordre' => $current_section + 1,
                    'titre' => trim($matches[1]),
                    'taches' => []
                ];
                $current_tache = null;
                continue;
            }

            // H3 - Tache
            if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                if ($current_tache !== null && $current_section !== null) {
                    $this->save_tache($result['sections'][$current_section], $current_tache, $current_content);
                    $current_content = [];
                }

                if ($current_section === null) {
                    throw new Exception(sprintf(
                        "Erreur ligne %d : Tâche trouvée avant toute section\nLigne : %s\nUne tâche (###) doit être précédée d'une section (##)",
                        $line_num + 1,
                        $line
                    ));
                }

                $current_tache = [
                    'titre' => trim($matches[1]),
                    'description' => '',
                    'ordre' => count($result['sections'][$current_section]['taches']) + 1
                ];
                continue;
            }

            // Content lines : accumulated only as a tache description. Text found
            // directly under a section header (before its first tache) has no
            // column to be stored in and is silently ignored, same as
            // Formation_markdown_parser ignores text found before the first lecon.
            if ($current_tache !== null) {
                $current_content[] = $line;
            }
        }

        // Save last tache
        if ($current_tache !== null && $current_section !== null) {
            $this->save_tache($result['sections'][$current_section], $current_tache, $current_content);
        }

        // Validate basic structure
        if (empty($result['titre'])) {
            throw new Exception(
                "Erreur : Titre du programme manquant\n" .
                "Le fichier doit commencer par un titre de niveau H1 :\n" .
                "Exemple : # Visite 100 heures cellule"
            );
        }

        if (empty($result['sections'])) {
            throw new Exception(
                "Erreur : Aucune section trouvée\n" .
                "Le programme doit contenir au moins une section (##) :\n" .
                "Exemple : ## Cellule"
            );
        }

        foreach ($result['sections'] as $section) {
            if (empty($section['taches'])) {
                throw new Exception(sprintf(
                    "Erreur : Section '%s' ne contient aucune tâche\n" .
                    "Chaque section doit contenir au moins une tâche (###) :\n" .
                    "Exemple : ### Controle visuel du fuselage",
                    $section['titre']
                ));
            }
        }

        // Renumber sequentially (allows concatenating parts without manual renumbering)
        $this->renumber($result);

        return $result;
    }

    /**
     * Save a tache with its accumulated content
     *
     * @param array &$section Section array (passed by reference)
     * @param array $tache Tache data
     * @param array $content_lines Accumulated content lines
     */
    private function save_tache(&$section, $tache, $content_lines)
    {
        $tache['description'] = trim(implode("\n", $content_lines));
        $section['taches'][] = $tache;
    }

    /**
     * Renumber sections and taches sequentially.
     *
     * @param array &$result Parsed data (modified in place)
     */
    private function renumber(&$result)
    {
        foreach ($result['sections'] as $section_idx => &$section) {
            $section['ordre'] = $section_idx + 1;

            foreach ($section['taches'] as $tache_idx => &$tache) {
                $tache['ordre'] = $tache_idx + 1;
            }
        }
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

        if (empty($parsed_data['sections'])) {
            $errors[] = 'Aucune section trouvée';
            return "Validation échouée :\n- " . implode("\n- ", $errors);
        }

        foreach ($parsed_data['sections'] as $section) {
            $section_label = "Section '" . ($section['titre'] ?? '') . "'";

            if (empty($section['titre'])) {
                $errors[] = "$section_label : Titre manquant";
            }

            if (empty($section['taches'])) {
                $errors[] = "$section_label : Aucune tâche trouvée";
            }

            foreach ($section['taches'] as $tache) {
                if (empty($tache['titre'])) {
                    $errors[] = "$section_label : Tâche sans titre";
                }
            }
        }

        if (!empty($errors)) {
            return "Validation échouée :\n- " . implode("\n- ", $errors);
        }

        return TRUE;
    }
}

/* End of file Maintenance_markdown_parser.php */
/* Location: ./application/libraries/Maintenance_markdown_parser.php */
