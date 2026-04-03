<!-- VIEW: application/views/admin/bs_view_log.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('admin');
?>

<style>
#log-toolbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0.5rem 1rem;
}
#log-viewport {
    height: calc(100vh - 220px);
    overflow-y: auto;
    font-family: monospace;
    font-size: 0.82rem;
    background: #1e1e1e;
    padding: 0.5rem;
}
.log-entry {
    cursor: pointer;
    border-radius: 3px;
    margin-bottom: 2px;
    padding: 2px 6px;
    white-space: pre-wrap;
    word-break: break-all;
}
.log-entry:hover { filter: brightness(0.92); }
.log-entry.level-DEBUG  { background: #1a2e1a; color: #7ec87e; }
.log-entry.level-INFO   { background: #1a1a2e; color: #7e9ec8; }
.log-entry.level-ERROR  { background: #2e1a1a; color: #c87e7e; }
.log-entry.level-HELLOASSO { background: #1a1a2e; color: #7eb8c8; }
.log-entry.collapsed .log-body { display: none; }
.log-more { font-size: 0.75rem; opacity: 0.6; margin-left: 1rem; }
mark { background: #ffe066; color: #000; border-radius: 2px; }
mark.current { background: #ff9900; }
.level-badge {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 0 4px;
    border-radius: 3px;
    margin-right: 4px;
    opacity: 0.85;
}
.badge-DEBUG     { background: #2d6a2d; color: #aeffae; }
.badge-INFO      { background: #2d3e6a; color: #aec8ff; }
.badge-ERROR     { background: #6a2d2d; color: #ffaeae; }
.badge-HELLOASSO { background: #2d4a5a; color: #aedcff; }
</style>

<div id="body" class="body container-fluid py-2">

<?php if ($too_large) : ?>
    <div class="alert alert-warning">
        <strong>Fichier trop volumineux</strong> (<?= number_format($filesize / 1024 / 1024, 1) ?> Mo — limite 5 Mo).
        <a href="<?= controller_url('admin/download_log/' . urlencode($filename)) ?>" class="btn btn-sm btn-primary ms-2">
            <i class="fas fa-download"></i> Télécharger
        </a>
        <a href="<?= controller_url('admin/logs') ?>" class="btn btn-sm btn-secondary ms-1">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
<?php else : ?>

<div id="log-toolbar">
    <div class="d-flex flex-wrap align-items-center gap-2">

        <!-- Retour + titre -->
        <a href="<?= controller_url('admin/logs') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <span class="fw-bold text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($filename) ?>">
            <?= htmlspecialchars($filename) ?>
        </span>

        <div class="vr"></div>

        <!-- Filtres niveau -->
        <div class="d-flex gap-2 align-items-center">
            <label class="form-check-label small fw-bold text-success">
                <input class="form-check-input" type="checkbox" id="lvl-DEBUG" checked> DEBUG
            </label>
            <label class="form-check-label small fw-bold text-primary">
                <input class="form-check-input" type="checkbox" id="lvl-INFO" checked> INFO
            </label>
            <label class="form-check-label small fw-bold text-danger">
                <input class="form-check-input" type="checkbox" id="lvl-ERROR" checked> ERROR
            </label>
            <label class="form-check-label small fw-bold text-info">
                <input class="form-check-input" type="checkbox" id="lvl-HELLOASSO" checked> HELLOASSO
            </label>
        </div>

        <div class="vr"></div>

        <!-- Filtre horaire -->
        <div class="d-flex align-items-center gap-1 small">
            <span>De</span>
            <input type="time" id="time-from" class="form-control form-control-sm" style="width:100px;">
            <span>à</span>
            <input type="time" id="time-to" class="form-control form-control-sm" style="width:100px;">
            <button class="btn btn-sm btn-outline-secondary" id="btn-reset-time" title="Réinitialiser filtre horaire">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="vr"></div>

        <!-- Tout développer / réduire -->
        <button class="btn btn-sm btn-outline-secondary" id="btn-expand-all" title="Tout développer">
            <i class="fas fa-expand-alt"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="btn-collapse-all" title="Tout réduire">
            <i class="fas fa-compress-alt"></i>
        </button>

        <div class="vr"></div>

        <!-- Recherche -->
        <div class="d-flex align-items-center gap-1">
            <input type="text" id="log-search" class="form-control form-control-sm" placeholder="Rechercher…" style="width:180px;">
            <button class="btn btn-sm btn-outline-secondary" id="btn-prev" title="Précédent">
                <i class="fas fa-chevron-up"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="btn-next" title="Suivant">
                <i class="fas fa-chevron-down"></i>
            </button>
            <span id="search-counter" class="small text-muted" style="white-space:nowrap;"></span>
        </div>

    </div>
</div>

<div id="log-viewport"></div>

<script>
(function () {
    // ── Données brutes injectées depuis PHP ──────────────────────────────────
    var RAW = <?= json_encode($content) ?>;

    // ── Parsing des entrées de log ───────────────────────────────────────────
    var RE_CI  = /^(DEBUG|INFO|ERROR|WARNING)\s+-\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+-->\s+([\s\S]*)/;
    var RE_HA  = /^\[(\d{4}-\d{2}-\d{2}\s+(\d{2}:\d{2}:\d{2}))\]\s+\[HELLOASSO\]\s+([\s\S]*)/;

    function parseEntries(raw) {
        var lines   = raw.split('\n');
        var entries = [];
        var current = null;

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            var m;

            if ((m = RE_CI.exec(line))) {
                if (current) entries.push(current);
                current = { level: m[1], time: m[2].substring(11, 16), lines: [line] };
            } else if ((m = RE_HA.exec(line))) {
                if (current) entries.push(current);
                current = { level: 'HELLOASSO', time: m[2], lines: [line] };
            } else {
                if (current) {
                    current.lines.push(line);
                } else if (line.trim()) {
                    // ligne orpheline avant la première entrée
                    if (entries.length) {
                        entries[entries.length - 1].lines.push(line);
                    }
                }
            }
        }
        if (current) entries.push(current);
        return entries;
    }

    var ENTRIES = parseEntries(RAW);

    // ── Rendu initial ────────────────────────────────────────────────────────
    var viewport = document.getElementById('log-viewport');

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderEntry(entry, idx) {
        var div = document.createElement('div');
        div.className = 'log-entry collapsed level-' + entry.level;
        div.dataset.idx   = idx;
        div.dataset.level = entry.level;
        div.dataset.time  = entry.time || '';

        var badge = '<span class="level-badge badge-' + entry.level + '">' + entry.level + '</span>';
        var firstLine = escHtml(entry.lines[0] || '');
        var moreCount = entry.lines.length - 1;

        var moreSpan = moreCount > 0
            ? '<span class="log-more">[+' + moreCount + ' ligne' + (moreCount > 1 ? 's' : '') + ']</span>'
            : '';

        var bodyLines = entry.lines.slice(1).map(escHtml).join('\n');

        div.innerHTML =
            '<div class="log-first">' + badge + firstLine + moreSpan + '</div>' +
            (moreCount > 0 ? '<div class="log-body">' + bodyLines + '</div>' : '');

        div.addEventListener('click', function () {
            if (entry.lines.length > 1) {
                this.classList.toggle('collapsed');
            }
        });

        return div;
    }

    function buildViewport() {
        var frag = document.createDocumentFragment();
        for (var i = 0; i < ENTRIES.length; i++) {
            frag.appendChild(renderEntry(ENTRIES[i], i));
        }
        viewport.appendChild(frag);
    }

    buildViewport();

    // ── Filtrage (niveau + horaire) ──────────────────────────────────────────
    function applyFilters() {
        var lvlDebug     = document.getElementById('lvl-DEBUG').checked;
        var lvlInfo      = document.getElementById('lvl-INFO').checked;
        var lvlError     = document.getElementById('lvl-ERROR').checked;
        var lvlHelloasso = document.getElementById('lvl-HELLOASSO').checked;
        var timeFrom     = document.getElementById('time-from').value;
        var timeTo       = document.getElementById('time-to').value;

        var allowed = {};
        if (lvlDebug)     allowed['DEBUG']     = true;
        if (lvlInfo)      allowed['INFO']      = true;
        if (lvlError)     allowed['ERROR']     = true;
        if (lvlHelloasso) allowed['HELLOASSO'] = true;

        var nodes = viewport.querySelectorAll('.log-entry');
        for (var i = 0; i < nodes.length; i++) {
            var node  = nodes[i];
            var level = node.dataset.level;
            var time  = node.dataset.time;

            var visible = !!allowed[level];
            if (visible && timeFrom && time < timeFrom) visible = false;
            if (visible && timeTo   && time > timeTo)   visible = false;

            node.style.display = visible ? '' : 'none';
        }
        rebuildSearch();
    }

    ['lvl-DEBUG','lvl-INFO','lvl-ERROR','lvl-HELLOASSO'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', applyFilters);
    });
    document.getElementById('time-from').addEventListener('change', applyFilters);
    document.getElementById('time-to').addEventListener('change',   applyFilters);
    document.getElementById('btn-reset-time').addEventListener('click', function () {
        document.getElementById('time-from').value = '';
        document.getElementById('time-to').value   = '';
        applyFilters();
    });

    // ── Tout développer / réduire ────────────────────────────────────────────
    document.getElementById('btn-expand-all').addEventListener('click', function () {
        viewport.querySelectorAll('.log-entry:not([style*="display: none"])').forEach(function (n) {
            n.classList.remove('collapsed');
        });
    });
    document.getElementById('btn-collapse-all').addEventListener('click', function () {
        viewport.querySelectorAll('.log-entry:not([style*="display: none"])').forEach(function (n) {
            n.classList.add('collapsed');
        });
    });

    // ── Recherche ────────────────────────────────────────────────────────────
    var occurrences  = [];   // { node, markEl }
    var currentOcc   = -1;

    function clearMarks() {
        viewport.querySelectorAll('mark').forEach(function (m) {
            var parent = m.parentNode;
            parent.replaceChild(document.createTextNode(m.textContent), m);
            parent.normalize();
        });
        occurrences = [];
        currentOcc  = -1;
    }

    function highlightInNode(node, term) {
        // Parcourt les text nodes de node et surligne term
        var walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null, false);
        var textNodes = [];
        var n;
        while ((n = walker.nextNode())) textNodes.push(n);

        var re = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
        textNodes.forEach(function (tn) {
            var text = tn.nodeValue;
            var match;
            var lastIdx = 0;
            var frag = document.createDocumentFragment();
            var found = false;
            while ((match = re.exec(text)) !== null) {
                found = true;
                if (match.index > lastIdx) {
                    frag.appendChild(document.createTextNode(text.slice(lastIdx, match.index)));
                }
                var mark = document.createElement('mark');
                mark.textContent = match[0];
                frag.appendChild(mark);
                occurrences.push(mark);
                lastIdx = re.lastIndex;
            }
            if (found) {
                if (lastIdx < text.length) frag.appendChild(document.createTextNode(text.slice(lastIdx)));
                tn.parentNode.replaceChild(frag, tn);
            }
        });
    }

    function rebuildSearch() {
        var term = document.getElementById('log-search').value.trim();
        clearMarks();
        if (!term) {
            document.getElementById('search-counter').textContent = '';
            return;
        }
        var visible = viewport.querySelectorAll('.log-entry:not([style*="display: none"])');
        visible.forEach(function (node) {
            highlightInNode(node, term);
        });
        updateCounter();
        if (occurrences.length > 0) {
            currentOcc = 0;
            goTo(0);
        }
    }

    function updateCounter() {
        var el = document.getElementById('search-counter');
        if (occurrences.length === 0) {
            el.textContent = document.getElementById('log-search').value.trim() ? '0 résultat' : '';
        } else {
            el.textContent = (currentOcc + 1) + ' sur ' + occurrences.length;
        }
    }

    function goTo(idx) {
        if (occurrences.length === 0) return;
        if (currentOcc >= 0 && currentOcc < occurrences.length) {
            occurrences[currentOcc].classList.remove('current');
        }
        currentOcc = (idx + occurrences.length) % occurrences.length;
        var mark = occurrences[currentOcc];
        mark.classList.add('current');

        // Développer l'entrée parente si réduite
        var entry = mark.closest('.log-entry');
        if (entry && entry.classList.contains('collapsed')) {
            entry.classList.remove('collapsed');
        }
        mark.scrollIntoView({ behavior: 'smooth', block: 'center' });
        updateCounter();
    }

    var searchTimer;
    document.getElementById('log-search').addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(rebuildSearch, 250);
    });
    document.getElementById('btn-prev').addEventListener('click', function () { goTo(currentOcc - 1); });
    document.getElementById('btn-next').addEventListener('click', function () { goTo(currentOcc + 1); });
    document.getElementById('log-search').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.shiftKey ? goTo(currentOcc - 1) : goTo(currentOcc + 1); }
    });

})();
</script>

<?php endif; ?>
</div>
