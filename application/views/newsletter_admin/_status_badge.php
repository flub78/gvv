<?php
// Badge d'état d'une édition — paramètre : $status
$classes = array(
    'draft'      => 'bg-secondary',
    'reviewing'  => 'bg-warning text-dark',
    'validating' => 'bg-info text-dark',
    'approved'   => 'bg-success',
    'published'  => 'bg-primary',
);
?>
<span class="badge <?= $classes[$status] ?? 'bg-light text-dark' ?>"><?= htmlspecialchars($this->lang->line('nl_status_' . $status) ?: $status) ?></span>
