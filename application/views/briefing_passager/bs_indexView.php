<!-- VIEW: application/views/briefing_passager/bs_indexView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('briefing_passager');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-clipboard-check"></i> <?= $this->lang->line('briefing_passager_title') ?></h3>

<?= $message ?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title"><?= $this->lang->line('briefing_passager_search_vld') ?></h5>

        <div class="mb-3">
            <input type="text" id="vld_search" class="form-control"
                   placeholder="<?= $this->lang->line('briefing_passager_search_placeholder') ?>"
                   autocomplete="off">
            <div id="vld_results" class="list-group mt-1" style="display:none; max-height:300px; overflow-y:auto;"></div>
        </div>

        <div id="vld_selected" style="display:none;">
            <div class="alert alert-info d-flex align-items-center gap-2" id="vld_info">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                <?= $this->lang->line('briefing_passager_redirecting') ?>
            </div>
        </div>
    </div>
</div>

</div><!-- /body -->

<script>
(function() {
    var debounce;
    document.getElementById('vld_search').addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('vld_results').style.display = 'none';
            document.getElementById('vld_selected').style.display = 'none';
            return;
        }
        debounce = setTimeout(function() {
            fetch('<?= site_url('briefing_passager/search_vld') ?>?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var box = document.getElementById('vld_results');
                    box.innerHTML = '';
                    if (!data.length) {
                        box.innerHTML = '<div class="list-group-item text-muted"><?= $this->lang->line('briefing_passager_no_vld_found') ?></div>';
                        box.style.display = 'block';
                        return;
                    }
                    data.forEach(function(item) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        a.textContent = item.label;
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            document.getElementById('vld_search').value = item.label;
                            box.style.display = 'none';
                            document.getElementById('vld_selected').style.display = 'block';
                            window.location.href = '<?= site_url('briefing_passager/upload') ?>/' + item.id;
                        });
                        box.appendChild(a);
                    });
                    box.style.display = 'block';
                });
        }, 300);
    });
})();
</script>
