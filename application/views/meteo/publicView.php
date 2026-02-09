<!-- VIEW: application/views/meteo/publicView.php -->
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
 * Page publique: cartes de préparation des vols
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('meteo');

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line('meteo_public_title'), 3);

if (!empty($can_manage)) {
    echo '<div class="mb-3">'
        . '<a href="' . site_url('meteo/page') . '" class="btn btn-sm btn-outline-secondary">'
        . '<i class="fas fa-cog" aria-hidden="true"></i> '
        . $this->lang->line('meteo_manage_cards')
        . '</a>'
        . '</div>';
}

if (empty($cards)) {
    echo '<div class="alert alert-info">' . $this->lang->line('meteo_no_cards') . '</div>';
    echo '</div>';
    return;
}

echo '<div class="row g-3">';

foreach ($cards as $card) {
    $title = html_escape($card['title']);
    $category = isset($card['category']) ? $card['category'] : '';
    $type = isset($card['type']) ? $card['type'] : 'html';
    $image_url = isset($card['image_url']) ? $card['image_url'] : '';
    $link_url = isset($card['link_url']) ? $card['link_url'] : '';
    $resolved_image_url = $image_url;
    if (!empty($image_url) && !preg_match('#^(https?:)?//#i', $image_url) && !preg_match('#^data:#i', $image_url)) {
        $resolved_image_url = base_url(ltrim($image_url, '/'));
    }

    echo '<div class="col-12 col-md-6 col-lg-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<span>' . $title . '</span>';
    if (!empty($category)) {
        echo '<span class="badge bg-secondary">' . html_escape($category) . '</span>';
    }
    echo '</div>';
    echo '<div class="card-body">';

    if ($type === 'html') {
        if (!empty($card['html_fragment'])) {
            echo $card['html_fragment'];
        } else {
            echo '<div class="text-muted">' . $this->lang->line('meteo_html_unavailable') . '</div>';
        }

        if (!empty($link_url)) {
            echo '<div class="mt-3">'
                . '<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" href="'
                . html_escape($link_url) . '">' . $this->lang->line('meteo_open_link') . '</a>'
                . '</div>';
        }
    } elseif ($type === 'iframe') {
        if (!empty($link_url)) {
            echo '<div class="ratio ratio-16x9 border rounded overflow-hidden position-relative">'
                . '<iframe src="' . html_escape($link_url) . '" title="' . $title . '" style="pointer-events: none;"></iframe>'
                . '<a href="' . html_escape($link_url) . '" target="_blank" rel="noopener noreferrer" '
                . 'class="stretched-link" aria-label="' . $this->lang->line('meteo_open_link') . '"></a>'
                . '</div>'
                . '<div class="mt-2">'
                . '<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" href="'
                . html_escape($link_url) . '">' . $this->lang->line('meteo_open_link') . '</a>'
                . '</div>';
        } else {
            echo '<div class="text-muted">' . $this->lang->line('meteo_link_unavailable') . '</div>';
        }
    } else {
        if (!empty($resolved_image_url)) {
            echo '<img src="' . html_escape($resolved_image_url) . '" class="img-fluid rounded mb-2" alt="' . $title . '">';
        }
        if (!empty($link_url)) {
            echo '<div class="mt-2">'
                . '<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" href="'
                . html_escape($link_url) . '">' . $this->lang->line('meteo_open_link') . '</a>'
                . '</div>';
        } else {
            echo '<div class="text-muted">' . $this->lang->line('meteo_link_unavailable') . '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '</div>';

