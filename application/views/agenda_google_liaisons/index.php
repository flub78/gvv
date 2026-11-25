<?php
/**
 * Liaisons Google Agenda suspendues (administrateur du club).
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div class="container-fluid py-3">
  <h3><i class="fab fa-google me-2"></i><?= $this->lang->line('agenda_google_liste_titre') ?></h3>
  <p class="text-muted"><?= $this->lang->line('agenda_google_liste_aide') ?></p>

  <?php if (!$module_enabled): ?>
    <div class="alert alert-info"><?= $this->lang->line('agenda_google_liste_module_inactif') ?></div>
  <?php endif; ?>

  <?php if ($count === 0): ?>
    <div class="alert alert-success" id="agenda-google-aucune-suspension">
      <i class="fas fa-check-circle me-1"></i><?= $this->lang->line('agenda_google_liste_vide') ?>
    </div>
  <?php else: ?>
    <?= $this->gvvmetadata->table('vue_agenda_google_suspendues', array(
        'controller' => $controller,
        'actions'    => array(),
        'fields'     => array('membre', 'etat', 'cause_suspension', 'updated_at', 'derniere_reussite_at'),
        'mode'       => 'ro',
        'class'      => 'datatable table table-striped',
    ), '') ?>
  <?php endif; ?>
</div>
