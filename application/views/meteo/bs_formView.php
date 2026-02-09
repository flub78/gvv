<!-- VIEW: application/views/meteo/bs_formView.php -->
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
 * Formulaire de saisie des cartes de préparation des vols
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('meteo');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading($this->lang->line('meteo_admin_title'), 3);

echo '<div class="alert alert-warning small">'
    . $this->lang->line('meteo_admin_notice') . '<br>'
    . $this->lang->line('meteo_admin_https_notice')
    . '</div>';

echo form_open_multipart(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden controller url for javascript access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

echo '<div class="card mb-3">'
    . '<div class="card-header">' . $this->lang->line('meteo_image_section_title') . '</div>'
    . '<div class="card-body">'
    . '<div class="mb-3">'
    . '<label class="form-label" for="image_file">' . $this->lang->line('meteo_image_upload_label') . '</label>'
    . '<input type="file" class="form-control" name="image_file" id="image_file" accept="image/*">'
    . '<div class="form-text">' . $this->lang->line('meteo_image_upload_help') . '</div>'
    . '</div>'
    . '<div class="mb-3">'
    . '<label class="form-label" for="image_paste">' . $this->lang->line('meteo_image_paste_label') . '</label>'
    . '<textarea class="form-control" name="image_paste" id="image_paste" rows="3" placeholder="' . $this->lang->line('meteo_image_paste_placeholder') . '"></textarea>'
    . '<div class="form-text">' . $this->lang->line('meteo_image_paste_help') . '</div>'
    . '</div>'
    . '</div>'
    . '</div>';

$form_fields = array(
    'title' => isset($title) ? $title : '',
    'type' => isset($type) ? $type : 'html',
    'html_fragment' => isset($html_fragment) ? $html_fragment : '',
    'image_url' => isset($image_url) ? $image_url : '',
    'link_url' => isset($link_url) ? $link_url : '',
    'category' => isset($category) ? $category : '',
    'display_order' => isset($display_order) ? $display_order : 0,
    'visible' => isset($visible) ? $visible : 1
);

// Add id field only for edit/view (not creation)
if ($action != CREATION) {
    $form_fields = array_merge(array('id' => $id), $form_fields);
}

echo ($this->gvvmetadata->form('preparation_cards', $form_fields));

echo validation_button($action);
echo form_close();

echo '<script>
    (function () {
        var textarea = document.getElementById("image_paste");
        if (!textarea) return;
        textarea.addEventListener("paste", function (event) {
            var items = (event.clipboardData || window.clipboardData).items || [];
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (item.type && item.type.indexOf("image") !== -1) {
                    var file = item.getAsFile();
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        textarea.value = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    event.preventDefault();
                    return;
                }
            }
        });
    })();
</script>';

echo '</div>';

