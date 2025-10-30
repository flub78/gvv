<?php
/*
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Génère le HTML pour le titre d'un accordéon de balance hiérarchique
 * Affiche une ligne de balance générale dans un tableau HTML
 *
 * @param array $row Les données de la ligne de balance générale
 * @param string $codec Le code de la classe de compte
 * @param object $gvvmetadata Instance de Gvvmetadata pour les labels
 * @return string Le HTML du titre d'accordéon
 */
if (!function_exists('balance_accordion_header')) {
    function balance_accordion_header($row, $codec, $gvvmetadata) {
        $solde_debit = isset($row['solde_debit']) && $row['solde_debit'] ? euro($row['solde_debit']) : '';
        $solde_credit = isset($row['solde_credit']) && $row['solde_credit'] ? euro($row['solde_credit']) : '';

        $html = '<table class="table table-sm mb-0">';
        $html .= '<thead class="table-light">';
        $html .= '<tr>';
        $html .= '<th style="width: 15%">' . $gvvmetadata->label('vue_comptes', 'codec') . '</th>';
        $html .= '<th style="width: 35%">' . $gvvmetadata->label('vue_comptes', 'nom') . '</th>';
        $html .= '<th style="width: 25%" class="text-end">' . $gvvmetadata->label('vue_comptes', 'solde_debit') . '</th>';
        $html .= '<th style="width: 25%" class="text-end">' . $gvvmetadata->label('vue_comptes', 'solde_credit') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td><strong>' . htmlspecialchars($row['codec']) . '</strong></td>';
        $html .= '<td><strong>' . htmlspecialchars($row['nom']) . '</strong></td>';
        $html .= '<td class="text-end"><strong>' . $solde_debit . '</strong></td>';
        $html .= '<td class="text-end"><strong>' . $solde_credit . '</strong></td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}

/**
 * Génère le HTML pour une datatable de balance détaillée
 * Utilise les mêmes attributs que comptes/page
 *
 * @param array $details Les données détaillées pour un codec
 * @param string $codec Le code de la classe de compte
 * @param object $gvvmetadata Instance de Gvvmetadata pour les labels
 * @param string $controller Le nom du contrôleur
 * @param bool $has_modification_rights Si l'utilisateur a les droits de modification
 * @param array $section Informations sur la section
 * @return string Le HTML de la datatable
 */
if (!function_exists('balance_detail_datatable')) {
    function balance_detail_datatable($details, $codec, $gvvmetadata, $controller, $has_modification_rights, $section) {
        $CI = &get_instance();
        $unique_id = 'datatable_' . str_replace('.', '_', $codec);

        // Déterminer la classe CSS en fonction du nombre de lignes
        // Plus de 12 lignes: utiliser searchable_nosort_datatable (avec pagination et recherche)
        // 12 lignes ou moins: utiliser table simple sans DataTables
        $row_count = count($details);
        $table_class = 'table table-striped table-hover table-sm';
        if ($row_count > 12) {
            $table_class .= ' searchable_nosort_datatable';
        }

        $html = '<div class="balance-datatable-wrapper">';
        $html .= '<table id="' . $unique_id . '" class="' . $table_class . '">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . $CI->lang->line('gvv_str_actions') . '</th>';
        $html .= '<th>' . $gvvmetadata->label('vue_comptes', 'codec') . '</th>';
        $html .= '<th>' . $gvvmetadata->label('vue_comptes', 'nom') . '</th>';
        $html .= '<th>' . $gvvmetadata->label('vue_comptes', 'section_name') . '</th>';
        $html .= '<th class="text-end">' . $gvvmetadata->label('vue_comptes', 'solde_debit') . '</th>';
        $html .= '<th class="text-end">' . $gvvmetadata->label('vue_comptes', 'solde_credit') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($details as $row) {
            $html .= '<tr>';
            $html .= '<td>';
            if ($has_modification_rights && $section) {
                $html .= '<a href="' . site_url($controller . '/edit/' . $row['id']) . '" class="btn btn-sm btn-primary" title="' . htmlspecialchars($CI->lang->line('gvv_button_edit')) . '">';
                $html .= '<i class="fas fa-edit" aria-hidden="true"></i>';
                $html .= '</a> ';
                $html .= '<a href="' . site_url($controller . '/delete/' . $row['id']) . '" class="btn btn-sm btn-danger" title="' . htmlspecialchars($CI->lang->line('gvv_button_delete')) . '" onclick="return confirm(\'' . htmlspecialchars($CI->lang->line('gvv_str_confirm_delete')) . '\')">';
                $html .= '<i class="fas fa-trash" aria-hidden="true"></i>';
                $html .= '</a>';
            }
            $html .= '</td>';
            $html .= '<td>' . htmlspecialchars($row['codec']) . '</td>';
            // Lien vers les opérations du compte (journal)
            $html .= '<td><a href="' . site_url('compta/journal_compte/' . $row['id']) . '">' . htmlspecialchars($row['nom']) . '</a></td>';
            $html .= '<td>' . (isset($row['section_name']) ? htmlspecialchars($row['section_name']) : '') . '</td>';
            $html .= '<td class="text-end">' . (isset($row['solde_debit']) && $row['solde_debit'] ? euro($row['solde_debit']) : '') . '</td>';
            $html .= '<td class="text-end">' . (isset($row['solde_credit']) && $row['solde_credit'] ? euro($row['solde_credit']) : '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>'; // Fermeture du wrapper

        return $html;
    }
}

/**
 * Génère le HTML complet pour un accordéon de balance hiérarchique
 * Combine le titre et le contenu détaillé
 *
 * @param array $general_row Les données de la ligne générale
 * @param array $details Les données détaillées
 * @param int $index L'index de l'accordéon
 * @param object $gvvmetadata Instance de Gvvmetadata
 * @param string $controller Le nom du contrôleur
 * @param bool $has_modification_rights Si l'utilisateur a les droits de modification
 * @param array $section Informations sur la section
 * @return string Le HTML de l'accordéon complet
 */
if (!function_exists('balance_accordion_item')) {
    function balance_accordion_item($general_row, $details, $index, $gvvmetadata, $controller, $has_modification_rights, $section) {
        $codec = $general_row['codec'];
        $accordion_id = 'accordion_' . str_replace('.', '_', $codec);
        $heading_id = 'heading_' . str_replace('.', '_', $codec);
        $collapse_id = 'collapse_' . str_replace('.', '_', $codec);

        $html = '<div class="accordion-item">';
        $html .= '<h2 class="accordion-header" id="' . $heading_id . '">';
        $html .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapse_id . '" aria-expanded="false" aria-controls="' . $collapse_id . '">';
        $html .= balance_accordion_header($general_row, $codec, $gvvmetadata);
        $html .= '</button>';
        $html .= '</h2>';
        $html .= '<div id="' . $collapse_id . '" class="accordion-collapse collapse" aria-labelledby="' . $heading_id . '" data-bs-parent="#balanceAccordion">';
        $html .= '<div class="accordion-body p-3">';

        if (!empty($details)) {
            $html .= balance_detail_datatable($details, $codec, $gvvmetadata, $controller, $has_modification_rights, $section);
        } else {
            $CI = &get_instance();
            $html .= '<p class="text-muted">' . $CI->lang->line('gvv_str_no_data') . '</p>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
