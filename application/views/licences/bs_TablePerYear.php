<!-- VIEW: application/views/licences/bs_TablePerYear.php -->
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
 * Vue table pour les licences
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->load->library('DataTable');

echo '<div id="body" class="body container-fluid">';

echo heading("Licences et cotisations", 3);

?>
<!-- ===== Onglets principaux ===== -->
<ul class="nav nav-tabs mt-2" id="mainTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-global" data-bs-toggle="tab"
                data-bs-target="#pane-global" type="button" role="tab"
                aria-controls="pane-global" aria-selected="false">
            <i class="fas fa-table me-2"></i> Vue globale
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-detail" data-bs-toggle="tab"
                data-bs-target="#pane-detail" type="button" role="tab"
                aria-controls="pane-detail" aria-selected="true">
            <i class="fas fa-calendar-alt me-2"></i> Vue par année
        </button>
    </li>
</ul>
<div class="tab-content border border-top-0 rounded-bottom p-3" id="mainTabsContent">

    <!-- ============================= -->
    <!-- Onglet 1 : Vue globale        -->
    <!-- ============================= -->
    <div class="tab-pane fade" id="pane-global" role="tabpanel" aria-labelledby="tab-global">
<?php
// Sélecteur de type de licence
echo licence_selector($controller, $type);
?>
<!-- Filtres -->
<div class="row mb-3 mt-3">
    <div class="col-md-12">
        <div class="accordion" id="filtersAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFilters">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-expanded="true" aria-controls="collapseFilters">
                        Filtres
                    </button>
                </h2>
                <div id="collapseFilters" class="accordion-collapse collapse show" aria-labelledby="headingFilters" data-bs-parent="#filtersAccordion">
                    <div class="accordion-body">
                        <!-- Filtre statut des membres -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Membres:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="member_status" id="status_all"
                                           value="all" <?php echo ($member_status === 'all') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_all">Tous</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="member_status" id="status_inactive"
                                           value="inactive" <?php echo ($member_status === 'inactive') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_inactive">Non actif</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="member_status" id="status_active"
                                           value="active" <?php echo ($member_status === 'active') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_active">Actif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="section_selector" class="form-label">Section:</label>
                                <select class="form-select" id="section_selector" name="section_id">
                                    <option value="all" <?php echo ($section_id === 'all') ? 'selected' : ''; ?>>Toutes les sections</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id']; ?>" <?php echo ($section_id == $section['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($section['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Sliders pour la plage d'années -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Plage d'années</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="year_min_slider" class="form-label">
                                    Année de début: <span id="year_min_value"><?php echo $year_min; ?></span>
                                </label>
                                <input type="range" class="form-range" id="year_min_slider"
                                       min="<?php echo $min_year_data; ?>"
                                       max="<?php echo $max_year_data; ?>"
                                       value="<?php echo $year_min; ?>"
                                       step="1">
                            </div>
                            <div class="col-md-6">
                                <label for="year_max_slider" class="form-label">
                                    Année de fin: <span id="year_max_value"><?php echo $year_max; ?></span>
                                </label>
                                <input type="range" class="form-range" id="year_max_slider"
                                       min="<?php echo $min_year_data; ?>"
                                       max="<?php echo $max_year_data; ?>"
                                       value="<?php echo $year_max; ?>"
                                       step="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

echo br(1);
$dt = new DataTable(array(
	'title' => "",
	'values' => $table,
	'controller' => '',
	'class' => "datatable table table-striped",
	'create' => "",
    'first' => 0));

$dt->display();

// Afficher la ligne de total en dehors du DataTable
echo '<div class="row mt-2">';
echo '<div class="col-md-12">';
echo '<table class="table table-bordered table-sm" id="total-table">';
echo '<thead class="table-secondary">';
echo '<tr id="total-row">';
$col_index = 0;
foreach ($total as $value) {
    echo '<th class="text-center" data-col-index="' . $col_index . '">' . $value . '</th>';
    $col_index++;
}
echo '</tr>';
echo '</thead>';
echo '</table>';
echo '</div>';
echo '</div>';

?>
    </div><!-- /#pane-global -->

    <!-- ============================= -->
    <!-- Onglet 2 : Vue par année      -->
    <!-- ============================= -->
    <div class="tab-pane fade show active" id="pane-detail" role="tabpanel" aria-labelledby="tab-detail">

                <!-- Sélecteur d'année + filtre cotisation -->
                <div class="row mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="detail_year_selector" class="form-label fw-bold">Année :</label>
                        <select class="form-select" id="detail_year_selector">
                            <?php for ($y = $max_year_data; $y >= $min_year_data; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($y == $detail_year) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Cotisation :</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cotisation_filter" id="cot_all" value="all" <?php echo ($cotisation_filter === 'all') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cot_all">Toutes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cotisation_filter" id="cot_paid" value="paid" <?php echo ($cotisation_filter === 'paid') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cot_paid">Payée</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="cotisation_filter" id="cot_unpaid" value="unpaid" <?php echo ($cotisation_filter === 'unpaid') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cot_unpaid">Non payée</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau détaillé -->
                <div class="table-responsive">
                    <table id="detail-table" class="datatable table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Pilote</th>
                                <th>Email</th>
                                <th class="text-center">Cotisation</th>
                                <?php foreach ($detail_data['sections'] as $s): ?>
                                    <th class="text-center"><?php echo htmlspecialchars($s['nom']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail_data['members'] as $m): ?>
                            <tr data-cotisation="<?php echo $m['cotisation'] ? '1' : '0'; ?>">
                                <td>
                                    <a href="<?php echo controller_url('event/page/' . htmlspecialchars($m['mlogin'])); ?>">
                                        <?php echo htmlspecialchars($m['nom'] . ' ' . $m['prenom']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($m['email']); ?></td>
                                <td class="text-center">
                                    <input type="checkbox" class="detail-checkbox"
                                           data-pilote="<?php echo htmlspecialchars($m['mlogin']); ?>"
                                           data-year="<?php echo $detail_year; ?>"
                                           data-type="0"
                                           <?php echo $m['cotisation'] ? 'checked' : ''; ?>>
                                </td>
                                <?php foreach ($detail_data['sections'] as $s): ?>
                                <td class="text-center">
                                    <input type="checkbox" class="detail-checkbox"
                                           data-pilote="<?php echo htmlspecialchars($m['mlogin']); ?>"
                                           data-year="<?php echo $detail_year; ?>"
                                           data-type="<?php echo $s['licence_type']; ?>"
                                           <?php echo $m['section_' . $s['id']] ? 'checked' : ''; ?>>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
<?php
$bar = array(
    array('label' => "Excel", 'url' => "licences/per_year_detail_csv"),
    array('label' => "Pdf",   'url' => "licences/per_year_detail_pdf"),
);
echo button_bar4($bar);
?>
    </div><!-- /#pane-detail -->

</div><!-- /#mainTabsContent -->

<?php echo '</div>'; /* #body */ ?>

<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
<script>
$(document).ready(function() {

    // ============================================================
    // Vue globale – sliders, statut membre, section, checkboxes
    // ============================================================
    var yearMinSlider = $('#year_min_slider');
    var yearMaxSlider = $('#year_max_slider');
    var yearMinValue = $('#year_min_value');
    var yearMaxValue = $('#year_max_value');
    var updateTimeout = null;

    function updateYearDisplay() {
        var minVal = parseInt(yearMinSlider.val());
        var maxVal = parseInt(yearMaxSlider.val());
        if (minVal > maxVal) { yearMinSlider.val(maxVal); minVal = maxVal; }
        yearMinValue.text(minVal);
        yearMaxValue.text(maxVal);
    }

    function handleSliderChange() {
        updateYearDisplay();
        if (updateTimeout) clearTimeout(updateTimeout);
        updateTimeout = setTimeout(function() {
            var minVal = parseInt(yearMinSlider.val());
            var maxVal = parseInt(yearMaxSlider.val());
            $.ajax({
                url: '<?php echo site_url('licences/set_year_range'); ?>/' + minVal + '/' + maxVal,
                type: 'GET', dataType: 'json',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
                success: function(r) { if (r.success) window.location.reload(); },
                error: function(xhr, s, e) { console.error('Erreur AJAX:', e); }
            });
        }, 500);
    }

    yearMinSlider.on('input', updateYearDisplay);
    yearMaxSlider.on('input', updateYearDisplay);
    yearMinSlider.on('change', handleSliderChange);
    yearMaxSlider.on('change', handleSliderChange);

    $('input[name="member_status"]').on('change', function() {
        $.ajax({
            url: '<?php echo site_url('licences/set_member_status'); ?>/' + $(this).val(),
            type: 'GET', dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(r) { if (r.success) window.location.reload(); },
            error: function(xhr, s, e) { console.error('Erreur AJAX:', e); }
        });
    });

    $('#section_selector').on('change', function() {
        $.ajax({
            url: '<?php echo site_url('licences/set_section'); ?>/' + $(this).val(),
            type: 'GET', dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(r) { if (r.success) window.location.reload(); },
            error: function(xhr, s, e) { console.error('Erreur AJAX:', e); }
        });
    });

    // Checkboxes Vue globale
    $('.licence-checkbox').on('change', function() {
        var cb = $(this);
        var pilote = cb.data('pilote');
        var year   = cb.data('year');
        var type   = cb.data('type');
        var checked = cb.is(':checked');
        cb.prop('disabled', true);
        var url = checked
            ? '<?php echo site_url('licences/set'); ?>/' + pilote + '/' + year + '/' + type
            : '<?php echo site_url('licences/switch_it'); ?>/' + pilote + '/' + year + '/' + type;
        $.ajax({
            url: url, type: 'GET', dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(r) {
                cb.prop('disabled', false);
                if (!r.success) {
                    alert('Erreur: ' + r.error);
                    cb.prop('checked', !checked);
                }
            },
            error: function(xhr, s, e) {
                cb.prop('disabled', false);
                cb.prop('checked', !checked);
                alert('Erreur lors de la mise à jour de la licence: ' + e);
            }
        });
    });

    // ============================================================
    // Vue par année – filtre cotisation (avant init par le footer)
    // ============================================================
    $.fn.dataTableExt.afnFiltering.push(function(oSettings, aData, iDataIndex) {
        if (oSettings.nTable.id !== 'detail-table') return true;
        var filter = $('input[name="cotisation_filter"]:checked').val();
        if (!filter || filter === 'all') return true;
        var nTr = oSettings.aoData[iDataIndex].nTr;
        var cot = parseInt($(nTr).data('cotisation'), 10);
        return filter === 'paid' ? cot === 1 : cot === 0;
    });

    $('input[name="cotisation_filter"]').on('change', function() {
        var filter = $(this).val();
        $.ajax({
            url: '<?php echo site_url('licences/set_cotisation_filter'); ?>/' + filter,
            type: 'GET', dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(r) {
                if (r.success && $.fn.dataTable && $.fn.dataTable.fnIsDataTable(document.getElementById('detail-table'))) {
                    $('#detail-table').dataTable().fnDraw();
                }
            },
            error: function(xhr, s, e) { console.error('Erreur AJAX:', e); }
        });
    });

    // ============================================================
    // Vue par année – DataTable + sélecteur d'année + checkboxes
    // ============================================================
    // Ré-initialiser la DataTable quand l'onglet "Vue par année" devient visible
    // (nécessaire car le footer initialise les tables cachées sans calculer les colonnes)
    $('#tab-detail').on('shown.bs.tab', function () {
        if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable(document.getElementById('detail-table'))) {
            $('#detail-table').dataTable().fnAdjustColumnSizing();
        }
    });

    // Sélecteur d'année
    $('#detail_year_selector').on('change', function() {
        var year = $(this).val();
        $.ajax({
            url: '<?php echo site_url('licences/set_detail_year'); ?>/' + year,
            type: 'GET', dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(r) { if (r.success) window.location.reload(); },
            error: function(xhr, s, e) { console.error('Erreur AJAX:', e); }
        });
    });

    // Checkboxes Vue par année
    $(document).on('change', '.detail-checkbox', function() {
        var cb = $(this);
        var pilote = cb.data('pilote');
        var year   = cb.data('year');
        var type   = cb.data('type');
        var checked = cb.is(':checked');
        cb.prop('disabled', true);
        var url = checked
            ? '<?php echo site_url('licences/set'); ?>/' + pilote + '/' + year + '/' + type
            : '<?php echo site_url('licences/switch_it'); ?>/' + pilote + '/' + year + '/' + type;
        $.ajax({
            url: url, type: 'GET', dataType: 'json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); },
            success: function(r) {
                cb.prop('disabled', false);
                if (!r.success) {
                    alert('Erreur: ' + r.error);
                    cb.prop('checked', !checked);
                }
            },
            error: function(xhr, s, e) {
                cb.prop('disabled', false);
                cb.prop('checked', !checked);
                alert('Erreur lors de la mise à jour: ' + e);
            }
        });
    });
});
</script>
