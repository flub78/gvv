/**
 * JavaScript for manual reconciliation page
 * Handles selection of entries and reconciliation process
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Rapprochement manuel JS: DOM loaded');
    const rapprocherBtn = document.getElementById('rapprocher-btn');
    console.log('Rapprocher button found:', rapprocherBtn);

    // Désactiver le bouton au démarrage
    if (rapprocherBtn) {
        rapprocherBtn.setAttribute('disabled', 'disabled');
    }

    // Initialize table visibility: hide all rows in "selected" table, show all in "available" table
    function initializeTableVisibility() {
        console.log('Initializing table visibility...');

        // Hide ALL rows in the selected entries table on page load
        const selectedTable = document.querySelector('#selected-entries-container table.table-selected tbody');
        if (selectedTable) {
            const selectedRows = selectedTable.querySelectorAll('tr');
            console.log('Found', selectedRows.length, 'rows in selected table - hiding all');
            selectedRows.forEach(function(row) {
                row.style.display = 'none';
            });
        } else {
            console.log('Selected table not found');
        }

        // Show all rows in the available entries table
        const availableTable = document.querySelector('#available-entries-container table.table-available tbody');
        if (availableTable) {
            const availableRows = availableTable.querySelectorAll('tr');
            console.log('Found', availableRows.length, 'rows in available table - showing all');
            availableRows.forEach(function(row) {
                row.style.display = '';
            });
        } else {
            console.log('Available table not found');
        }
    }

    // Function to update row visibility based on checkbox state
    function updateRowVisibility(checkbox) {
        console.log('updateRowVisibility called, checkbox checked:', checkbox.checked, 'name:', checkbox.name);

        // Get the ecriture ID from the data attribute
        const ecritureId = checkbox.getAttribute('data-ecriture-id');
        console.log('Ecriture ID:', ecritureId);

        if (!ecritureId) {
            console.log('No ecriture ID found');
            return;
        }

        // Find both checkboxes (in available and selected tables)
        const availableCheckbox = document.querySelector('input[name="cb_' + ecritureId + '"]');
        const selectedCheckbox = document.querySelector('input[name="sel_cb_' + ecritureId + '"]');

        console.log('Available checkbox found:', !!availableCheckbox);
        console.log('Selected checkbox found:', !!selectedCheckbox);

        // Find the rows
        const availableRow = availableCheckbox ? availableCheckbox.closest('tr') : null;
        const selectedRow = selectedCheckbox ? selectedCheckbox.closest('tr') : null;

        console.log('Available row found:', !!availableRow);
        console.log('Selected row found:', !!selectedRow);

        // Determine if this is a check or uncheck action
        const isChecking = checkbox.checked;

        // Synchronize both checkboxes
        if (availableCheckbox) availableCheckbox.checked = isChecking;
        if (selectedCheckbox) selectedCheckbox.checked = isChecking;

        if (isChecking) {
            // Hide in available table, show in selected table
            console.log('Checkbox checked - hiding available row, showing selected row');
            if (availableRow) {
                availableRow.style.display = 'none';
            }
            if (selectedRow) {
                selectedRow.style.display = 'table-row';
                selectedRow.style.setProperty('display', 'table-row', 'important');
            }
        } else {
            // Show in available table, hide in selected table
            console.log('Checkbox unchecked - showing available row, hiding selected row');
            if (availableRow) {
                availableRow.style.display = 'table-row';
            }
            if (selectedRow) {
                selectedRow.style.display = 'none';
            }
        }
    }

    // Function to hide DataTables info display and search box (incorrect due to hidden rows)
    function hideDataTablesInfo() {
        // Hide the "Affichage de l\'élément X à Y sur Z éléments" text
        const selectedTableInfo = document.querySelector('#selected-entries-container .dataTables_info');
        const availableTableInfo = document.querySelector('#available-entries-container .dataTables_info');

        if (selectedTableInfo) {
            selectedTableInfo.style.display = 'none';
            console.log('Hidden selected table info');
        }
        if (availableTableInfo) {
            availableTableInfo.style.display = 'none';
            console.log('Hidden available table info');
        }

        // Hide the search box from the selected entries table
        const selectedTableFilter = document.querySelector('#selected-entries-container .dataTables_filter');
        if (selectedTableFilter) {
            selectedTableFilter.style.display = 'none';
            console.log('Hidden selected table search box');
        }
    }

    // Initialize visibility on page load
    initializeTableVisibility();
    hideDataTablesInfo();

    // Re-initialize after DataTables has loaded (with increasing delays)
    setTimeout(function() {
        console.log('Re-initializing table visibility (100ms)...');
        initializeTableVisibility();
        hideDataTablesInfo();
    }, 100);

    setTimeout(function() {
        console.log('Re-initializing table visibility (500ms)...');
        initializeTableVisibility();
        hideDataTablesInfo();
    }, 500);

    setTimeout(function() {
        console.log('Re-initializing table visibility (1000ms)...');
        initializeTableVisibility();
        hideDataTablesInfo();
    }, 1000);

    function getSelectedSum() {
        const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name^="sel_cb_"]:checked');
        let selectedSum = 0;
        if (checkedBoxes.length > 0) {
            checkedBoxes.forEach(function(checkbox) {
                const row = checkbox.closest('tr');
                if (row) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 3) {
                        const amountText = cells[2].textContent.trim();
                        const amount = parseFloat(amountText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                        if (!isNaN(amount)) {
                            selectedSum += amount;
                        }
                    }
                }
            });
        }
        return selectedSum;
    }

    // Fonction pour vérifier les sélections et activer/désactiver le bouton
    function updateRapprocherButton() {
        const operationAmount = window.OPERATION_AMOUNT || 0;
        const selectedSum = getSelectedSum();
        const difference = Math.abs(operationAmount) - selectedSum;
        
        updateAmountIndicator(selectedSum, difference);

        const tolerance = 0.01;
        const shouldEnable = Math.abs(difference) <= tolerance;

        if (rapprocherBtn) {
            if (shouldEnable) {
                rapprocherBtn.removeAttribute('disabled');
                rapprocherBtn.classList.remove('btn-secondary');
                rapprocherBtn.classList.add('btn-primary');
            } else {
                rapprocherBtn.setAttribute('disabled', 'disabled');
                rapprocherBtn.classList.remove('btn-primary');
                rapprocherBtn.classList.add('btn-secondary');
            }
        }
    }

    // Function to update the amount indicator
    function updateAmountIndicator(selectedAmount, difference) {
        const indicator = document.getElementById('amount-indicator');
        const selectedAmountSpan = document.getElementById('selected-amount');
        const differenceAmountSpan = document.getElementById('difference-amount');

        if (indicator && selectedAmountSpan && differenceAmountSpan) {
            selectedAmountSpan.textContent = selectedAmount.toFixed(2).replace('.', ',') + ' €';
            differenceAmountSpan.textContent = difference.toFixed(2).replace('.', ',') + ' €';

            // Change indicator color based on difference
            indicator.classList.remove('alert-info', 'alert-success', 'alert-warning');
            if (Math.abs(difference) <= 0.01) {
                indicator.classList.add('alert-success');
            } else if (Math.abs(difference) <= 1.00) {
                indicator.classList.add('alert-warning');
            } else {
                indicator.classList.add('alert-info');
            }

            // Always keep indicator visible
            indicator.classList.remove('d-none');
        }
    }

    // Gestion du clic sur les checkboxes
    document.addEventListener('change', function(e) {
        console.log('Change event detected:', e.target);
        if (e.target.type === 'checkbox' && (e.target.name.startsWith('cb_') || e.target.name.startsWith('sel_cb_'))) {
            console.log('Checkbox change detected for:', e.target.name, 'checked:', e.target.checked);
            updateRowVisibility(e.target);
            updateRapprocherButton();
        }
    });

    // Filtrage des écritures
    document.querySelectorAll('input[name="ecriture-filter"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const filterValue = this.value;
            const operationAmount = window.OPERATION_AMOUNT || 0;

            // Apply filter to available entries table only
            const availableTable = document.querySelector('#available-entries-container table.table-available tbody');
            if (availableTable) {
                availableTable.querySelectorAll('tr').forEach(function(row) {
                    // Chercher une checkbox dans cette ligne pour déterminer si c'est une ligne d'écriture
                    const checkbox = row.querySelector('input[type="checkbox"][name^="cb_"]');
                    if (!checkbox) return; // Skip si pas de checkbox

                    // Skip if checkbox is checked (row should be hidden anyway)
                    if (checkbox.checked) {
                        row.style.display = 'none';
                        return;
                    }

                    let show = true;

                    if (filterValue === 'non-rapprochees') {
                        const badge = row.querySelector('.bg-success');
                        show = !badge; // Montrer uniquement si pas de badge vert
                    } else if (filterValue === 'montant') {
                        // Chercher le montant dans la ligne
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 3) { // Vérifier qu'il y a au moins 3 colonnes
                            const montantText = cells[2].textContent.trim(); // 3ème colonne pour le montant
                            const montant = parseFloat(montantText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                            if (!isNaN(montant)) {
                                const tolerance = Math.max(0.01, Math.abs(operationAmount) * 0.01); // 1% de tolerance minimum 0.01
                                show = Math.abs(montant - Math.abs(operationAmount)) <= tolerance;
                            }
                        }
                    }

                    row.style.display = show ? '' : 'none';
                });
            }

            // Mettre à jour le bouton après filtrage
            updateRapprocherButton();
        });
    });

    // État initial du bouton
    updateRapprocherButton();

    // Override form submission to bypass validation popup
    const form = document.querySelector('form[action*="rapprochez"]');
    if (form && rapprocherBtn) {
        form.addEventListener('submit', function(e) {
            const selectedSum = getSelectedSum();
            const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name^="sel_cb_"]:checked');

            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins une écriture à rapprocher');
                return false;
            }
            
            const operationAmount = window.OPERATION_AMOUNT || 0;
            const difference = Math.abs(operationAmount) - selectedSum;
            const tolerance = 0.01;
            
            if (Math.abs(difference) > tolerance) {
                e.preventDefault();
                const message = `Le montant total des écritures sélectionnées (${selectedSum.toFixed(2)} €) ne correspond pas au montant de l'opération bancaire (${Math.abs(operationAmount).toFixed(2)} €).\n\nÉcart: ${difference.toFixed(2)} €`;
                alert(message);
                return false;
            }

            // Create hidden inputs for submission
            checkedBoxes.forEach(function(checkbox) {
                const ecritureId = checkbox.getAttribute('data-ecriture-id');
                if (ecritureId) {
                    const hiddenInputCb = document.createElement('input');
                    hiddenInputCb.type = 'hidden';
                    hiddenInputCb.name = 'cb_' + ecritureId;
                    hiddenInputCb.value = '1';
                    form.appendChild(hiddenInputCb);

                    const hiddenInputStringReleve = document.createElement('input');
                    hiddenInputStringReleve.type = 'hidden';
                    hiddenInputStringReleve.name = 'string_releve_' + ecritureId;
                    hiddenInputStringReleve.value = window.STRING_RELEVE;
                    form.appendChild(hiddenInputStringReleve);
                }
            });
            
            return true;
        }, true);
        
        rapprocherBtn.addEventListener('click', function(e) {
            if (rapprocherBtn.hasAttribute('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);
    }

    // Post-init: renforcer l'apparence cliquable des badges et accessibilité
    function enhanceRapprochementBadges() {
        document.querySelectorAll('.supprimer-rapprochement-badge').forEach(function(badge){
            badge.style.cursor = 'pointer';
            badge.setAttribute('role', 'button');
            badge.setAttribute('tabindex', '0');
            if (!badge.getAttribute('title')) {
                badge.setAttribute('title', "Cliquez pour supprimer le rapprochement");
            }
        });
    }
    enhanceRapprochementBadges();

    // Support clavier (Entrée / Espace)
    document.addEventListener('keydown', function(e){
        if ((e.key === 'Enter' || e.key === ' ') && e.target.classList && e.target.classList.contains('supprimer-rapprochement-badge')) {
            e.preventDefault();
            e.target.click();
        }
    });

    // Observer si le tableau est redraw (ex: DataTables) pour réappliquer
    const observer = new MutationObserver(function(mutations){
        let shouldEnhance = false;
        mutations.forEach(m => {
            if (m.addedNodes && m.addedNodes.length) {
                m.addedNodes.forEach(n => {
                    if (n.nodeType === 1 && (n.classList.contains('supprimer-rapprochement-badge') || n.querySelector && n.querySelector('.supprimer-rapprochement-badge'))) {
                        shouldEnhance = true;
                    }
                });
            }
        });
        if (shouldEnhance) enhanceRapprochementBadges();
    });
    observer.observe(document.body, {childList:true, subtree:true});

    // === Suppression d'un rapprochement via clic sur le badge vert (même comportement que sur l'onglet GVV) ===
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('supprimer-rapprochement-badge')) {
            console.log('Click badge suppression rapprochement', e.target);
            e.preventDefault();
            e.stopPropagation();

            const badge = e.target;
            const ecritureId = badge.getAttribute('data-ecriture-id');
            if (!ecritureId) return;

            if (!confirm(window.CONFIRM_DELETE_RAPPROCHEMENT_ECRITURE + ' ' + ecritureId + ' ?')) {
                return;
            }

            const originalText = badge.textContent;
            badge.textContent = '...';
            badge.style.pointerEvents = 'none';

            fetch(window.APP_BASE_URL + 'rapprochements/supprimer_rapprochement_ecriture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'ecriture_id=' + encodeURIComponent(ecritureId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    badge.textContent = originalText;
                    badge.style.pointerEvents = 'auto';
                    alert('Erreur lors de la suppression du rapprochement: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                badge.textContent = originalText;
                badge.style.pointerEvents = 'auto';
                alert('Erreur de communication avec le serveur');
            });
        }
    });
});
