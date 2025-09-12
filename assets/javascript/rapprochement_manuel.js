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

    // Fonction pour vérifier les sélections et activer/désactiver le bouton
    function updateRapprocherButton() {
        const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name^="cb_"]:checked');
        const allCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="cb_"]');
        console.log('Checkboxes found:', allCheckboxes.length, 'checked:', checkedBoxes.length);
        
        // Debug: log tous les noms des checkboxes trouvées
        allCheckboxes.forEach(function(checkbox) {
            console.log('Checkbox found:', checkbox.name, 'checked:', checkbox.checked);
        });
        
        if (rapprocherBtn) {
            if (checkedBoxes.length === 0) {
                rapprocherBtn.setAttribute('disabled', 'disabled');
            } else {
                rapprocherBtn.removeAttribute('disabled');
            }
            console.log('Button disabled:', rapprocherBtn.hasAttribute('disabled'));
        }
    }

    // Gestion du clic sur les checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && e.target.name.startsWith('cb_')) {
            updateRapprocherButton();
        }
    });

    // Filtrage des écritures
    document.querySelectorAll('input[name="ecriture-filter"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const filterValue = this.value;
            const operationAmount = window.OPERATION_AMOUNT || 0;
            
            document.querySelectorAll('tr').forEach(function(row) {
                // Chercher une checkbox dans cette ligne pour déterminer si c'est une ligne d'écriture
                const checkbox = row.querySelector('input[type="checkbox"][name^="cb_"]');
                if (!checkbox) return; // Skip si pas de checkbox
                
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
            
            // Mettre à jour le bouton après filtrage
            updateRapprocherButton();
        });
    });

    // État initial du bouton
    updateRapprocherButton();
});
