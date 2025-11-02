<!-- VIEW: application/views/email_lists/view.php -->
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
 * Vue prévisualisation et export de liste de diffusion
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('email_lists');

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= htmlspecialchars($list['name']) ?></h3>
        <div>
            <a href="<?= controller_url($controller) ?>/edit/<?= $list['id'] ?>" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> <?= $this->lang->line("email_lists_edit") ?>
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> <?= $this->lang->line("gvv_str_back") ?>
            </a>
        </div>
    </div>

<?php
// Show success message
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-check-circle"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

    <!-- List information -->
    <div class="card mb-3">
        <div class="card-body">
            <?php if (!empty($list['description'])): ?>
            <p class="card-text"><?= nl2br(htmlspecialchars($list['description'])) ?></p>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong><?= $this->lang->line("email_lists_active_member") ?>:</strong>
                        <?php
                        switch ($list['active_member']) {
                            case 'active':
                                echo $this->lang->line("email_lists_active_members_only");
                                break;
                            case 'inactive':
                                echo $this->lang->line("email_lists_inactive_members_only");
                                break;
                            case 'all':
                                echo $this->lang->line("email_lists_all_members");
                                break;
                        }
                        ?>
                    </p>
                    <p class="mb-1">
                        <strong><?= $this->lang->line("email_lists_visible") ?>:</strong>
                        <?= $list['visible'] ? $this->lang->line("gvv_str_yes") : $this->lang->line("gvv_str_no") ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1">
                        <strong><?= $this->lang->line("email_lists_created") ?>:</strong>
                        <?= date('d/m/Y H:i', strtotime($list['created_at'])) ?>
                    </p>
                    <p class="mb-1">
                        <strong><?= $this->lang->line("email_lists_updated") ?>:</strong>
                        <?= date('d/m/Y H:i', strtotime($list['updated_at'])) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recipients list -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-people"></i>
                <?= $this->lang->line("email_lists_recipients") ?>
                <span class="badge bg-primary"><?= count($emails) ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($emails)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> <?= $this->lang->line("email_lists_no_recipients") ?>
                </div>
            <?php else: ?>
                <!-- Export section -->
                <?php $this->load->view('email_lists/_export_section'); ?>

                <!-- Email list display -->
                <div class="mt-4">
                    <h6><?= $this->lang->line("email_lists_email_addresses") ?>:</h6>
                    <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($emails as $email): ?>
                            <div class="mb-1">
                                <i class="bi bi-envelope"></i>
                                <code><?= htmlspecialchars($email['email']) ?></code>
                                <?php if (!empty($email['name'])): ?>
                                    - <span class="text-muted"><?= htmlspecialchars($email['name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($email['source'])): ?>
                                    <span class="badge bg-secondary ms-2"><?= htmlspecialchars($email['source']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sources breakdown -->
    <?php if (!empty($roles) || !empty($manual_members) || !empty($external_emails)): ?>
    <div class="accordion mb-3" id="sourcesAccordion">
        <!-- Roles criteria -->
        <?php if (!empty($roles)): ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingRoles">
                <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseRoles"
                        aria-expanded="false"
                        aria-controls="collapseRoles">
                    <i class="bi bi-funnel me-2"></i>
                    <?= $this->lang->line("email_lists_tab_criteria") ?>
                    <span class="badge bg-secondary ms-2"><?= count($roles) ?></span>
                </button>
            </h2>
            <div id="collapseRoles"
                 class="accordion-collapse collapse"
                 aria-labelledby="headingRoles"
                 data-bs-parent="#sourcesAccordion">
                <div class="accordion-body">
                    <ul>
                        <?php foreach ($roles as $role): ?>
                        <li>
                            <strong><?= htmlspecialchars($role['role_name']) ?></strong>
                            <?php if (!empty($role['section_name'])): ?>
                                - <?= htmlspecialchars($role['section_name']) ?>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Manual members -->
        <?php if (!empty($manual_members)): ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingManual">
                <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseManual"
                        aria-expanded="false"
                        aria-controls="collapseManual">
                    <i class="bi bi-person-plus me-2"></i>
                    <?= $this->lang->line("email_lists_manual_members") ?>
                    <span class="badge bg-secondary ms-2"><?= count($manual_members) ?></span>
                </button>
            </h2>
            <div id="collapseManual"
                 class="accordion-collapse collapse"
                 aria-labelledby="headingManual"
                 data-bs-parent="#sourcesAccordion">
                <div class="accordion-body">
                    <ul>
                        <?php foreach ($manual_members as $member): ?>
                        <li><?= htmlspecialchars($member['name']) ?> - <code><?= htmlspecialchars($member['email']) ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- External emails -->
        <?php if (!empty($external_emails)): ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingExternal">
                <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseExternal"
                        aria-expanded="false"
                        aria-controls="collapseExternal">
                    <i class="bi bi-envelope-at me-2"></i>
                    <?= $this->lang->line("email_lists_external_emails") ?>
                    <span class="badge bg-secondary ms-2"><?= count($external_emails) ?></span>
                </button>
            </h2>
            <div id="collapseExternal"
                 class="accordion-collapse collapse"
                 aria-labelledby="headingExternal"
                 data-bs-parent="#sourcesAccordion">
                <div class="accordion-body">
                    <ul>
                        <?php foreach ($external_emails as $ext): ?>
                        <li>
                            <code><?= htmlspecialchars($ext['external_email']) ?></code>
                            <?php if (!empty($ext['external_name'])): ?>
                                - <?= htmlspecialchars($ext['external_name']) ?>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Load JavaScript for export functionality -->
<script src="<?= base_url('assets/javascript/email_lists.js') ?>"></script>
<script>
// Initialize with email data
var emailList = <?= json_encode(array_column($emails, 'email')) ?>;
</script>

<?php
$this->load->view('bs_footer');
?>
