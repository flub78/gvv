/**
 * formation_participants.js
 * Composant de sélection multi-participants avec recherche AJAX.
 *
 * Usage (dans la vue) :
 *   FormationParticipants.init({
 *     searchUrl: '<url ajax>',
 *     initialParticipants: [{id:'p01', label:'Dupont Jean'}, ...]
 *   });
 */
var FormationParticipants = (function () {
    'use strict';

    var _searchUrl = '';
    var _participants = {};   // {id: label}
    var _timer = null;

    // -----------------------------------------------------------------------
    // Init
    // -----------------------------------------------------------------------
    function init(options) {
        _searchUrl = options.searchUrl || '';
        var initial = options.initialParticipants || [];
        initial.forEach(function (p) {
            _participants[p.id] = p.label;
        });

        _render();
        _bindSearch();
    }

    // -----------------------------------------------------------------------
    // Rendu de la liste des participants (badges + champs hidden)
    // -----------------------------------------------------------------------
    function _render() {
        var container = document.getElementById('participants-badges');
        var inputs    = document.getElementById('participants-inputs');
        if (!container || !inputs) return;

        container.innerHTML = '';
        inputs.innerHTML    = '';

        Object.keys(_participants).forEach(function (id) {
            var label = _participants[id];

            // Badge
            var badge = document.createElement('span');
            badge.className = 'badge bg-primary me-1 mb-1';
            badge.style.fontSize = '0.9em';
            badge.innerHTML =
                '<i class="fas fa-user me-1" aria-hidden="true"></i>' +
                _esc(label) +
                ' <button type="button" class="btn-close btn-close-white ms-1" ' +
                'aria-label="Retirer" data-id="' + _esc(id) + '" style="font-size:0.6em;vertical-align:middle;"></button>';
            container.appendChild(badge);

            // Champ hidden
            var inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'participants[]';
            inp.value = id;
            inputs.appendChild(inp);
        });

        // Bouton de suppression sur chaque badge
        container.querySelectorAll('[data-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                delete _participants[this.getAttribute('data-id')];
                _render();
            });
        });

        // Message si vide
        if (Object.keys(_participants).length === 0) {
            var empty = document.createElement('span');
            empty.className = 'text-muted fst-italic';
            empty.id = 'participants-empty';
            empty.textContent = document.getElementById('participants-empty-label') ?
                document.getElementById('participants-empty-label').value : 'Aucun participant';
            container.appendChild(empty);
        }
    }

    // -----------------------------------------------------------------------
    // Recherche AJAX
    // -----------------------------------------------------------------------
    function _bindSearch() {
        var input = document.getElementById('participant-search');
        var list  = document.getElementById('participant-suggestions');
        if (!input || !list) return;

        input.addEventListener('input', function () {
            clearTimeout(_timer);
            var q = this.value.trim();
            if (q.length < 2) {
                list.innerHTML = '';
                list.style.display = 'none';
                return;
            }
            _timer = setTimeout(function () { _search(q); }, 250);
        });

        // Fermer la liste si clic ailleurs
        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !list.contains(e.target)) {
                list.innerHTML = '';
                list.style.display = 'none';
            }
        });
    }

    function _search(q) {
        var list = document.getElementById('participant-suggestions');
        var url  = _searchUrl + '?q=' + encodeURIComponent(q);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var results = JSON.parse(xhr.responseText);
                    _renderSuggestions(results);
                } catch (e) {
                    list.innerHTML = '';
                    list.style.display = 'none';
                }
            }
        };
        xhr.send();
    }

    function _renderSuggestions(results) {
        var list  = document.getElementById('participant-suggestions');
        var input = document.getElementById('participant-search');
        list.innerHTML = '';

        if (!results || results.length === 0) {
            list.style.display = 'none';
            return;
        }

        results.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';

            var already = !!_participants[item.id];
            if (already) {
                li.innerHTML = '<i class="fas fa-check text-success me-1" aria-hidden="true"></i>' + _esc(item.label);
                li.classList.add('text-muted');
            } else {
                li.textContent = item.label;
                li.addEventListener('click', function () {
                    _participants[item.id] = item.label;
                    _render();
                    input.value  = '';
                    list.innerHTML = '';
                    list.style.display = 'none';
                    input.focus();
                });
            }
            list.appendChild(li);
        });

        list.style.display = 'block';
    }

    // -----------------------------------------------------------------------
    // Utilitaires
    // -----------------------------------------------------------------------
    function _esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return { init: init };
}());
