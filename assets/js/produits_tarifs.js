// Produits - Tarifs panel (produits/create, produits/edit)
// Gestion en mémoire de la liste de tarifs d'un produit : ajout, modification
// et suppression de lignes sans quitter la page. L'état courant est
// sérialisé dans le champ caché #tarifs_json à chaque changement ; la
// validation "au moins un tarif" est faite côté serveur (callback
// at_least_one_tarif, Produits::formValidation()) — le contrôle ici n'est
// qu'un confort pour éviter un aller-retour serveur inutile.
(function () {
    'use strict';

    var tarifs = (window.GVV_PRODUITS_INITIAL_TARIFS || []).map(function (t) {
        return { id: t.id, date: t.date, prix: t.prix, nb_tickets: t.nb_tickets };
    });
    var editingIndex = null;
    var gestionTickets = !!window.GVV_PRODUITS_GESTION_TICKETS;
    var lang = window.GVV_PRODUITS_LANG || {
        last_one: 'Il doit rester au moins un tarif.',
        invalid: 'Date et prix sont requis.',
        add: 'Ajouter',
        update: 'Mettre à jour'
    };

    function el(id) { return document.getElementById(id); }

    function showError(msg) {
        var box = el('tarifs_error');
        if (!box) return;
        box.textContent = msg;
        box.style.display = 'block';
    }

    function hideError() {
        var box = el('tarifs_error');
        if (!box) return;
        box.style.display = 'none';
    }

    function render() {
        var tbody = el('tarifs_tbody');
        tbody.innerHTML = '';
        tarifs.forEach(function (t, index) {
            var tr = document.createElement('tr');

            var tdDate = document.createElement('td');
            tdDate.textContent = t.date;
            tr.appendChild(tdDate);

            var tdPrix = document.createElement('td');
            tdPrix.textContent = t.prix;
            tr.appendChild(tdPrix);

            if (gestionTickets) {
                var tdTickets = document.createElement('td');
                tdTickets.textContent = t.nb_tickets;
                tr.appendChild(tdTickets);
            }

            var tdActions = document.createElement('td');
            tdActions.className = 'text-nowrap';

            var editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'btn btn-sm btn-primary me-1';
            editBtn.innerHTML = '<i class="fas fa-edit" aria-hidden="true"></i>';
            editBtn.addEventListener('click', function () { startEdit(index); });
            tdActions.appendChild(editBtn);

            var delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn btn-sm btn-danger';
            delBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
            delBtn.addEventListener('click', function () { removeTarif(index); });
            tdActions.appendChild(delBtn);

            tr.appendChild(tdActions);
            tbody.appendChild(tr);
        });

        el('tarifs_json').value = JSON.stringify(tarifs);
        if (tarifs.length > 0) {
            hideError();
        }
    }

    function resetForm() {
        el('tarif_date').value = new Date().toISOString().slice(0, 10);
        el('tarif_prix').value = '';
        if (gestionTickets) {
            el('tarif_nb_tickets').value = '';
        }
        editingIndex = null;
        el('tarif_add_btn').textContent = lang.add;
        el('tarif_cancel_btn').style.display = 'none';
    }

    function startEdit(index) {
        var t = tarifs[index];
        el('tarif_date').value = t.date;
        el('tarif_prix').value = t.prix;
        if (gestionTickets) {
            el('tarif_nb_tickets').value = t.nb_tickets;
        }
        editingIndex = index;
        el('tarif_add_btn').textContent = lang.update;
        el('tarif_cancel_btn').style.display = '';
    }

    function removeTarif(index) {
        if (tarifs.length <= 1) {
            showError(lang.last_one);
            return;
        }
        tarifs.splice(index, 1);
        if (editingIndex === index) {
            resetForm();
        }
        render();
    }

    function addOrUpdateTarif() {
        var date = el('tarif_date').value.trim();
        var prix = el('tarif_prix').value.trim();
        var nb_tickets = gestionTickets ? el('tarif_nb_tickets').value.trim() : 0;

        if (!date || !prix) {
            showError(lang.invalid);
            return;
        }

        var entry = {
            id: editingIndex !== null ? tarifs[editingIndex].id : null,
            date: date,
            prix: prix,
            nb_tickets: nb_tickets
        };

        if (editingIndex !== null) {
            tarifs[editingIndex] = entry;
        } else {
            tarifs.push(entry);
        }

        resetForm();
        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!el('tarifs_tbody')) return; // form not present on this page

        el('tarif_add_btn').addEventListener('click', addOrUpdateTarif);
        el('tarif_cancel_btn').addEventListener('click', resetForm);
        resetForm();
        render();

        var form = document.forms['saisie'];
        if (form) {
            form.addEventListener('submit', function (event) {
                el('tarifs_json').value = JSON.stringify(tarifs);
                if (tarifs.length === 0) {
                    event.preventDefault();
                    showError(lang.last_one);
                }
            });
        }
    });
})();
