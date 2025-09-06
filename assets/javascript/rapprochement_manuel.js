/**
 * JavaScript for manual reconciliation page
 * Handles selection of entries and reconciliation process
 */

// Variables pour gérer la sélection
let selectedEcritureId = null;
let selectedEcritureRow = null;

document.addEventListener('DOMContentLoaded', function() {
    // Gestion du clic sur les lignes d'écritures
    document.querySelectorAll('.ecriture-row').forEach(function(row) {
        row.addEventListener('click', function() {
            // Déselectionner la ligne précédente
            if (selectedEcritureRow) {
                selectedEcritureRow.classList.remove('selected-ecriture');
            }
            
            // Sélectionner la nouvelle ligne
            this.classList.add('selected-ecriture');
            selectedEcritureRow = this;
            selectedEcritureId = this.getAttribute('data-ecriture-id');
            
            // Activer le bouton de rapprochement
            document.getElementById('rapprocher-btn').disabled = false;
        });
    });

    // Gestion du bouton de rapprochement
    document.getElementById('rapprocher-btn').addEventListener('click', function() {
        if (!selectedEcritureId) {
            alert('Veuillez sélectionner une écriture');
            return;
        }

        const stringReleve = window.STRING_RELEVE;
        
        // Demander confirmation
        if (!confirm('Êtes-vous sûr de vouloir rapprocher cette opération avec l\'écriture ' + selectedEcritureId + ' ?')) {
            return;
        }

        // Désactiver le bouton pendant le traitement
        this.disabled = true;
        this.textContent = 'Rapprochement en cours...';

        // Effectuer la requête AJAX
        fetch(window.APP_BASE_URL + 'rapprochements/rapprocher_unique', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'string_releve=' + encodeURIComponent(stringReleve) +
                  '&ecriture_id=' + encodeURIComponent(selectedEcritureId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Succès - rediriger vers la page principale
                alert('Rapprochement effectué avec succès !');
                window.location.href = window.APP_BASE_URL + 'rapprochements/import_releve_from_file';
            } else {
                // Erreur - remettre le bouton dans son état initial
                this.disabled = false;
                this.textContent = 'Effectuer le rapprochement';
                alert('Erreur lors du rapprochement: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.disabled = false;
            this.textContent = 'Effectuer le rapprochement';
            alert('Erreur de communication avec le serveur');
        });
    });

    // Filtrage des écritures
    document.querySelectorAll('input[name="ecriture-filter"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const filterValue = this.value;
            const operationAmount = window.OPERATION_AMOUNT || 0;
            
            document.querySelectorAll('.ecriture-row').forEach(function(row) {
                let show = true;
                
                if (filterValue === 'non-rapprochees') {
                    show = row.getAttribute('data-rapproche') === 'false';
                } else if (filterValue === 'montant') {
                    const ecritureMontant = parseFloat(row.getAttribute('data-montant'));
                    const tolerance = operationAmount * 0.1; // 10% de tolerance
                    show = Math.abs(ecritureMontant - operationAmount) <= tolerance;
                }
                
                row.style.display = show ? '' : 'none';
            });
        });
    });
});
