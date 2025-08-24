<?php

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('associations_ecriture');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_associations_ecriture_title_associations", 3);
echo p("Cette table associe les lignes du relevé bancaire avec des écritures dans GVV.");
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('id','string_releve', 'id_ecriture_gvv', 'nom_section'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

echo $this->gvvmetadata->table("vue_associations_ecriture", $attrs, "");

echo '</div>';

?>
<script>

    // Callback function called when select changes
    function associateEcriture(selectElement, str) {
        const ecriture = selectElement.value;

        console.log("associateEcriture, ecriture=" + ecriture + ", str=" + str);

        // Call server to associate account
        fetch('<?= site_url() ?>/associations_ecriture/associate?string_releve=' + str + '&ecriture=' + encodeURIComponent(ecriture))
            .then(response => response.json())
            .then(data => console.log('Association response:', data))
            .catch(error => console.error('Error:', error));

        location.reload();
    }

</script>