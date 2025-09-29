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
            let shouldEnable = false;
            
            if (checkedBoxes.length > 0) {
                // Calculate sum of selected amounts
                const operationAmount = window.OPERATION_AMOUNT || 0;
                let selectedSum = 0;
                
                checkedBoxes.forEach(function(checkbox) {
                    const row = checkbox.closest('tr');
                    if (row) {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 3) {
                            const amountText = cells[2].textContent.trim();
                            const amount = parseFloat(amountText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                            if (!isNaN(amount)) {

                                // We must find if the amount should be added or subtracted.
                                // The amount is subtracted if Compte1 (debit account) is the account being reconciled $gvv_bank_account in the view
                                // and added if Compte2 (credit account) is the account being reconciled.
                                // This logic assumes that the OPERATION_AMOUNT is always positive for credits and negative for debits.

                                // Get the account numbers from the row (assuming columns 5 and 6)
                                // Extract account numbers from HTML using regex
                                const compte1Html = cells[5].innerHTML.trim();
                                const compte2Html = cells[6].innerHTML.trim();
                                const bankAccount = String(window.GVV_BANK_ACCOUNT); // should be set in the view

                                // Regex to extract account number from href attribute
                                function extractAccountNumber(html) {
                                    const match = html.match(/journal_compte\/(\d+)/);
                                    return match ? match[1] : null;
                                }
                                const compte1Num = extractAccountNumber(compte1Html);
                                const compte2Num = extractAccountNumber(compte2Html);

                                console.log('compte1Num:', compte1Num, 'compte2Num:', compte2Num, 'bankAccount:', bankAccount, 'amount:', amount);

                                if (bankAccount) {
                                    if (compte1Num === bankAccount) {
                                        // Subtract amount if Compte1 is the bank account
                                        selectedSum -= amount;
                                    } else if (compte2Num === bankAccount) {
                                        // Add amount if Compte2 is the bank account
                                        selectedSum += amount;
                                    } else {
                                        // Fallback: add amount (should not happen if data is correct)
                                        selectedSum += amount;
                                    }
                                } else {
                                    // If bankAccount is not defined, fallback to adding
                                    selectedSum += amount;
                                }
                            }
                        }
                    }
                });
                
                // Check if amounts match (with small tolerance)
                const tolerance = 0.01;
                const difference = Math.abs(selectedSum - Math.abs(operationAmount));
                shouldEnable = difference <= tolerance;
                
                console.log('Operation amount:', operationAmount);
                console.log('Selected sum:', selectedSum);
                console.log('Difference:', difference);
                console.log('Should enable button:', shouldEnable);
                
                // Update visual indicator
                updateAmountIndicator(Math.abs(operationAmount), selectedSum, difference);
            } else {
                // Hide indicator when nothing is selected
                hideAmountIndicator();
            }
            
            if (shouldEnable) {
                rapprocherBtn.removeAttribute('disabled');
                rapprocherBtn.classList.remove('btn-secondary');
                rapprocherBtn.classList.add('btn-primary');
            } else {
                rapprocherBtn.setAttribute('disabled', 'disabled');
                rapprocherBtn.classList.remove('btn-primary');
                rapprocherBtn.classList.add('btn-secondary');
            }
            console.log('Button disabled:', rapprocherBtn.hasAttribute('disabled'));
        }
    }

    // Function to update the amount indicator
    function updateAmountIndicator(operationAmount, selectedAmount, difference) {
        const indicator = document.getElementById('amount-indicator');
        const selectedAmountSpan = document.getElementById('selected-amount');
        const differenceAmountSpan = document.getElementById('difference-amount');
        
        if (indicator && selectedAmountSpan && differenceAmountSpan) {
            selectedAmountSpan.textContent = selectedAmount.toFixed(2).replace('.', ',') + ' €';
            differenceAmountSpan.textContent = difference.toFixed(2).replace('.', ',') + ' €';
            
            // Change indicator color based on difference
            indicator.classList.remove('alert-info', 'alert-success', 'alert-warning');
            if (difference <= 0.01) {
                indicator.classList.add('alert-success');
            } else if (difference <= 1.00) {
                indicator.classList.add('alert-warning');
            } else {
                indicator.classList.add('alert-info');
            }
            
            indicator.classList.remove('d-none');
        }
    }

    // Function to hide the amount indicator
    function hideAmountIndicator() {
        const indicator = document.getElementById('amount-indicator');
        if (indicator) {
            indicator.classList.add('d-none');
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

    // Override form submission to bypass validation popup
    // This prevents the "Veuillez sélectionner une écriture" alert from showing
    // in manual mode since we have our own checkbox-based validation
    const form = document.querySelector('form[action*="rapprochez"]');
    if (form && rapprocherBtn) {
        // Add debugging
        console.log('Form found:', form);
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        
        // Prevent any external validation by intercepting the submit
        form.addEventListener('submit', function(e) {
            console.log('Manual reconciliation form submitted');
            
            // Check if at least one checkbox is selected
            const checkedBoxes = document.querySelectorAll('input[type="checkbox"][name^="cb_"]:checked');
            console.log('Checked boxes:', checkedBoxes.length);
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins une écriture à rapprocher');
                return false;
            }
            
            // Check that the sum of selected entries matches the statement operation amount
            const operationAmount = window.OPERATION_AMOUNT || 0;
            let selectedSum = 0;
            const bankAccount = String(window.GVV_BANK_ACCOUNT || '');

            // Helper identical to the one used in updateRapprocherButton
            function extractAccountNumber(html) {
                const match = html.match(/journal_compte\/(\d+)/);
                return match ? match[1] : null;
            }
            
            checkedBoxes.forEach(function(checkbox) {
                const row = checkbox.closest('tr');
                if (!row) return;
                const cells = row.querySelectorAll('td');
                if (cells.length < 7) return; // Need amount + account columns
                const amountText = cells[2].textContent.trim();
                const amount = parseFloat(amountText.replace(/[^\d.,-]/g, '').replace(',', '.'));
                if (isNaN(amount)) return;

                // Determine sign according to bank account position (Compte1 = cells[5], Compte2 = cells[6])
                const compte1Html = cells[5].innerHTML.trim();
                const compte2Html = cells[6].innerHTML.trim();
                const compte1Num = extractAccountNumber(compte1Html);
                const compte2Num = extractAccountNumber(compte2Html);
                console.log('[submit] compte1Num:', compte1Num, 'compte2Num:', compte2Num, 'bankAccount:', bankAccount, 'raw amount:', amount);

                if (bankAccount) {
                    if (compte1Num === bankAccount) {
                        // Subtract (same rationale as in updateRapprocherButton)
                        selectedSum -= amount;
                    } else if (compte2Num === bankAccount) {
                        selectedSum += amount;
                    } else {
                        // Fallback add
                        selectedSum += amount;
                    }
                } else {
                    selectedSum += amount;
                }
            });
            
            console.log('Operation amount:', operationAmount);
            console.log('Selected sum (signed logic):', selectedSum);
            
            // Consistent tolerance & comparison with first control
            const tolerance = 0.01;
            const difference = Math.abs(selectedSum - Math.abs(operationAmount));
            
            if (difference > tolerance) {
                e.preventDefault();
                const message = `Le montant total des écritures sélectionnées (${selectedSum.toFixed(2)} €) ne correspond pas au montant de l'opération bancaire (${Math.abs(operationAmount).toFixed(2)} €).\n\nÉcart: ${difference.toFixed(2)} €`;
                alert(message);
                return false;
            }
            
            console.log('Form validation passed with signed logic, submitting...');
            return true;
        }, true); // Use capture phase to run before any other validation
        
        // Also override any click handlers on the submit button
        rapprocherBtn.addEventListener('click', function(e) {
            console.log('Rapprocher button clicked');
            
            // Check if button is disabled
            if (rapprocherBtn.hasAttribute('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Button is disabled, preventing submission');
                return false;
            }
            
            console.log('Button click allowed');
        }, true); // Use capture phase
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

            if (!confirm("Êtes-vous sûr de vouloir supprimer le rapprochement de l'écriture " + ecritureId + ' ?')) {
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
