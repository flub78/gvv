<!-- VIEW: application/views/comptes/bs_resultat_par_sectionsView.php -->
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
 * Vue du résultat d'exploitation par sections pour deux années consécutives
 *
 * @packages vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '';

$title = $this->lang->line("gvv_comptes_title_resultat_par_sections");

$url = controller_url($controller);

?>

<!-- Styles pour distinguer les colonnes par année - Palette Bleu gris / Beige sable -->
<style>
    /* Table générale */
    .resultat-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }

    /* En-têtes */
    .resultat-table thead th {
        color: #212529;
        font-weight: 600;
        text-align: center;
        border: 1px solid #dee2e6;
        padding: 0.5rem;
        vertical-align: middle;
    }

    /* En-têtes Code et Comptes */
    .resultat-table thead th.col-label {
        background-color: #f8f9fa !important;
    }

    /* En-têtes sections (ligne 1) */
    .resultat-table thead th.section-header {
        background-color: #dae3ec;
        border-bottom: none;
    }

    /* Colonnes année courante (N) : Bleu gris clair */
    .resultat-table thead th.year-current {
        background-color: #c8d9e6 !important;
    }
    .resultat-table tbody tr td.year-current {
        background-color: #e3f0f7 !important;
    }

    /* Colonnes année précédente (N-1) : Beige sable */
    .resultat-table thead th.year-previous {
        background-color: #f5ead4 !important;
    }
    .resultat-table tbody tr td.year-previous {
        background-color: #fef5e7 !important;
    }

    /* Colonnes Code et Comptes : fond blanc */
    .resultat-table tbody tr td.col-label {
        background-color: white !important;
        text-align: left;
    }

    /* Colonnes numériques : alignement à droite */
    .resultat-table tbody tr td.col-numeric {
        text-align: right;
        padding-right: 0.75rem;
    }

    /* Alternance des lignes : légère variation */
    .resultat-table tbody tr:nth-child(even) td {
        filter: brightness(0.97);
    }

    /* Bordures */
    .resultat-table tbody tr td {
        border: 1px solid #dee2e6;
        padding: 0.5rem;
    }

    /* Centrage pour Code */
    .resultat-table tbody tr td:first-child {
        text-align: center;
    }

    /* Bordures verticales entre sections */
    .resultat-table thead th.section-border-left {
        border-left: 3px solid #8b939a !important;
    }
    .resultat-table tbody tr td.section-border-left {
        border-left: 3px solid #8b939a !important;
    }

    /* Bordure verticale après Comptes (2ème colonne pour tableaux avec Code et Comptes) */
    .resultat-table thead th:nth-child(2).col-label {
        border-right: 3px solid #8b939a !important;
    }
    .resultat-table tbody tr td:nth-child(2).col-label {
        border-right: 3px solid #8b939a !important;
    }

    /* Bordure verticale après la colonne de titre (1ère colonne pour tableau Total) */
    .resultat-table thead th:nth-child(1).col-label:only-of-type {
        border-right: 3px solid #8b939a !important;
    }
    .resultat-table tbody tr td:nth-child(1).col-label:only-of-type {
        border-right: 3px solid #8b939a !important;
    }

    /* Mise en gras des labels Charges, Produits et Total dans le tableau Total */
    .resultat-table tbody tr td.col-label {
        font-weight: bold;
    }
</style>

<?php
/**
 * Fonction helper pour générer un tableau avec en-tête sur deux lignes
 * @param array $data Les données (ligne 0 = en-tête original)
 * @param string $table_class Classe CSS du tableau
 * @param bool $skip_label_cols Si true, supprime les colonnes Code et Comptes
 * @return string HTML du tableau
 */
function render_two_line_header_table($data, $table_class = 'resultat-table', $skip_label_cols = false) {
    if (empty($data)) {
        return '<p class="text-muted">Aucune donnée disponible</p>';
    }

    $header = $data[0];
    $rows = array_slice($data, 1);

    // Parser l'en-tête pour extraire sections et années
    // Format: ['Code', 'Comptes', 'Section A 2024', 'Section A 2023', ...]
    $sections = [];
    $current_section = null;

    $start_col = $skip_label_cols ? 0 : 2;
    for ($i = $start_col; $i < count($header); $i++) {
        $col_name = $header[$i];
        // Extraire section et année
        if (preg_match('/^(.+?)\s+(\d{4})$/', $col_name, $matches)) {
            $section_name = $matches[1];
            $year = $matches[2];

            if ($current_section === null || $current_section['name'] !== $section_name) {
                // Sauvegarder la section précédente si elle existe
                if ($current_section !== null) {
                    $sections[] = $current_section;
                }
                // Créer une nouvelle section
                $current_section = ['name' => $section_name, 'years' => []];
            }
            $current_section['years'][] = $year;
        }
    }
    // Ajouter la dernière section
    if ($current_section !== null) {
        $sections[] = $current_section;
    }

    // Générer le HTML
    $html = "<table class=\"{$table_class} table table-sm\">\n";
    $html .= "<thead>\n";

    // Ligne 1 : Sections
    $html .= "<tr>\n";
    if (!$skip_label_cols) {
        $html .= "<th rowspan=\"2\" class=\"col-label\">Code</th>\n";
        $html .= "<th rowspan=\"2\" class=\"col-label\">Comptes</th>\n";
    } else {
        // Pour le tableau Total, une colonne de titre vide
        $html .= "<th rowspan=\"2\" class=\"col-label\"></th>\n";
    }
    foreach ($sections as $section_idx => $section) {
        $colspan = count($section['years']);
        $border_class = ($section_idx > 0 || $skip_label_cols) ? ' section-border-left' : '';
        $html .= "<th colspan=\"{$colspan}\" class=\"section-header{$border_class}\">{$section['name']}</th>\n";
    }
    $html .= "</tr>\n";

    // Ligne 2 : Années
    $html .= "<tr>\n";
    foreach ($sections as $section_idx => $section) {
        foreach ($section['years'] as $idx => $year) {
            $class = ($idx % 2 == 0) ? 'year-current' : 'year-previous';
            $border_class = (($section_idx > 0 || $skip_label_cols) && $idx == 0) ? ' section-border-left' : '';
            $html .= "<th class=\"{$class}{$border_class}\">{$year}</th>\n";
        }
    }
    $html .= "</tr>\n";
    $html .= "</thead>\n";

    // Corps du tableau
    $html .= "<tbody>\n";
    foreach ($rows as $row) {
        $html .= "<tr>\n";
        foreach ($row as $col_idx => $cell_value) {
            if ($col_idx == 0 && $skip_label_cols) {
                // Sauter la colonne 0 (vide) pour le tableau Total
                continue;
            } else if ($col_idx == 1 && $skip_label_cols) {
                // Colonne de titre pour le tableau Total (Charges, Produits, Total)
                $html .= "<td class=\"col-label\">{$cell_value}</td>\n";
            } else if ($col_idx < 2 && !$skip_label_cols) {
                // Colonnes Code et Comptes (tableaux normaux)
                $html .= "<td class=\"col-label\">{$cell_value}</td>\n";
            } else if ($col_idx >= 2) {
                // Colonnes numériques
                $data_col = $skip_label_cols ? ($col_idx - 2) : ($col_idx - 2);
                $year_class = ($data_col % 2 == 0) ? 'year-current' : 'year-previous';

                // Déterminer si c'est le début d'une nouvelle section (bordure gauche)
                $section_start_col = 0;
                $border_class = '';
                foreach ($sections as $section_idx => $section) {
                    $years_count = count($section['years']);
                    if ($data_col == $section_start_col && ($section_idx > 0 || $skip_label_cols)) {
                        $border_class = ' section-border-left';
                        break;
                    }
                    $section_start_col += $years_count;
                }

                $html .= "<td class=\"col-numeric {$year_class}{$border_class}\">{$cell_value}</td>\n";
            }
        }
        $html .= "</tr>\n";
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";

    return $html;
}
?>

<div id="body" class="body container-fluid">
    <h2><?= $title ?></h2>

    <input type="hidden" name="controller_url" value="<?= $url ?>" />

    <!-- Sélecteur d'année -->
    <div class="mb-3">
        <?= year_selector($controller, $year, $year_selector) ?>
    </div>

    <!-- Vue par exercice: pas de sélecteur de date -->

    <!-- Tableau des charges -->
    <h3 class="mt-4"><?= $this->lang->line("comptes_label_charges") ?></h3>
    <?= render_two_line_header_table($charges) ?>

    <!-- Tableau des produits -->
    <h3 class="mt-4"><?= $this->lang->line("comptes_label_produits") ?></h3>
    <?= render_two_line_header_table($produits) ?>

    <!-- Tableau du résultat -->
    <h3 class="mt-4"><?= $this->lang->line("comptes_label_total") ?></h3>
    <?= render_two_line_header_table($resultat, 'resultat-table', true) ?>

    <?php
    echo br(2);

    // Boutons d'export
    $bar = array(
        array('label' => "Excel", 'url' => "comptes/resultat_par_sections/csv", 'role' => 'ca'),
        array('label' => "Pdf", 'url' => "comptes/resultat_par_sections/pdf", 'role' => 'ca'),
    );
    echo button_bar4($bar);

    echo '</div>';
    ?>
    <script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
