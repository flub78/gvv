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
 * @package javascript
 * 
 * Fonctions Javascript pour les rapprochements bancaires
 * 
 */

// Variables globales pour la gestion du scroll
let scrollRestored = false;

// Function to determine the appropriate return URL after reconciliation
function getReturnUrl() {
    // Check if we have a configured return URL
    if (window.RETURN_URL) {
        return window.RETURN_URL;
    }
    
    // Detect if we're on the manual reconciliation page
    if (window.location.href.indexOf('rapprochement_manuel') !== -1) {
        // For manual reconciliation page, return to main reconciliation page
        return window.APP_BASE_URL + 'rapprochements/import_releve_from_file';
    }
    
    // Default: return to main reconciliation page
    return window.APP_BASE_URL + 'rapprochements/import_releve_from_file';
}
function redirectWithScrollPosition(url) {
    const scrollY = window.pageYOffset || document.documentElement.scrollTop;
    localStorage.setItem('scrollPosition', scrollY);
    localStorage.setItem('scrollTimestamp', Date.now());
    window.location.href = url;
}

// Function to restore scroll position
function restoreScrollPosition() {
    if (scrollRestored) return;
    
    const scrollPosition = localStorage.getItem('scrollPosition');
    const timestamp = localStorage.getItem('scrollTimestamp');
    
    if (scrollPosition && timestamp) {
        // Vérifier que le timestamp n'est pas trop ancien (5 minutes max)
        const now = Date.now();
        const age = now - parseInt(timestamp);
        
        if (age < 5 * 60 * 1000) { // 5 minutes
            setTimeout(function() {
                window.scrollTo(0, parseInt(scrollPosition));
                scrollRestored = true;
                // Nettoyer après utilisation
                localStorage.removeItem('scrollPosition');
                localStorage.removeItem('scrollTimestamp');
            }, 100);
        } else {
            // Nettoyer les anciennes données
            localStorage.removeItem('scrollPosition');
            localStorage.removeItem('scrollTimestamp');
        }
    }
}

// Initialize reconciliation functionality
function initReconciliation() {
    // Restore active tab and scroll position on page load
    document.addEventListener('DOMContentLoaded', function() {
        let activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            const tab = new bootstrap.Tab(document.querySelector(activeTab));
            if (tab && tab._element) {
                tab.show();
            }
        }
        
        // Restaurer la position après un court délai
        restoreScrollPosition();
    });
    
    // Aussi essayer de restaurer après que la page soit complètement chargée
    window.addEventListener('load', function() {
        if (!scrollRestored) {
            restoreScrollPosition();
        }
    });

    // Store active tab when changed
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem('activeTab', '#' + e.target.id);
        });
    });

    // Gestion du rapprochement automatique pour les suggestions uniques
    document.addEventListener('click', function(e) {
        // Gestion du clic sur les badges verts pour supprimer le rapprochement (onglet GVV)
        if (e.target.classList.contains('supprimer-rapprochement-badge')) {
            e.preventDefault();
            e.stopPropagation();

            const badge = e.target;
            const ecritureId = badge.getAttribute('data-ecriture-id');

            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir supprimer le rapprochement de l\'écriture ' + ecritureId + ' ?')) {
                return;
            }

            // Désactiver temporairement le badge
            const originalText = badge.textContent;
            badge.textContent = '...';
            badge.style.pointerEvents = 'none';

            // Effectuer la requête AJAX
            fetch(window.APP_BASE_URL + 'rapprochements/supprimer_rapprochement_ecriture', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'ecriture_id=' + encodeURIComponent(ecritureId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succès - recharger la page pour mettre à jour l'affichage
                        redirectWithScrollPosition(getReturnUrl());
                    } else {
                        // Erreur - remettre le badge dans son état original
                        badge.textContent = originalText;
                        badge.style.pointerEvents = 'auto';
                        alert('Erreur lors de la suppression du rapprochement: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    badge.textContent = originalText;
                    badge.style.pointerEvents = 'auto';
                    alert('Erreur de communication avec le serveur');
                });
        }

        if (e.target.classList.contains('auto-reconcile-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const ecritureId = button.getAttribute('data-ecriture-id');
            const line = button.getAttribute('data-line');

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'En cours...';

            // Effectuer la requête AJAX
            fetch(window.APP_BASE_URL + 'rapprochements/rapprocher_unique', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'string_releve=' + encodeURIComponent(stringReleve) +
                        '&ecriture_id=' + encodeURIComponent(ecritureId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succès - recharger la page pour que les impacts sur les autres opérations soient pris en compte
                        redirectWithScrollPosition(getReturnUrl());
                    } else {
                        // Erreur - remettre le bouton dans son état initial
                        button.disabled = false;
                        button.textContent = 'Rapprocher';
                        alert('Erreur lors du rapprochement: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    button.disabled = false;
                    button.textContent = 'Rapprocher';
                    alert('Erreur de communication avec le serveur');
                });
        }

        // Gestion de la suppression du rapprochement
        if (e.target.classList.contains('auto-unreconcile-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');

            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce rapprochement ?')) {
                return;
            }

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'Suppression...';

            // Effectuer la requête AJAX de suppression
            fetch(window.APP_BASE_URL + 'rapprochements/supprimer_rapprochement_unique', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'string_releve=' + encodeURIComponent(stringReleve)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour que les impacts sur les autres opérations soient pris en compte
                        redirectWithScrollPosition(getReturnUrl());
                    } else {
                        // Erreur - remettre le bouton dans son état rapproché
                        button.disabled = false;
                        button.textContent = 'Rapproché';
                        alert('Erreur lors de la suppression: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    button.disabled = false;
                    button.textContent = 'Rapproché';
                    alert('Erreur de communication avec le serveur');
                });
        }

        // Gestion du rapprochement pour les choix multiples
        if (e.target.classList.contains('auto-reconcile-multiple-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');

            // Récupérer la valeur sélectionnée dans le dropdown OU dans les radio buttons
            let ecritureId;
            const dropdown = document.querySelector('select[name="op_' + line + '"]');
            const radioSelected = document.querySelector('input[name="op_' + line + '"]:checked');
            
            if (dropdown && dropdown.value) {
                ecritureId = dropdown.value;
            } else if (radioSelected && radioSelected.value) {
                ecritureId = radioSelected.value;
            } else {
                alert('Veuillez sélectionner une écriture dans les options proposées');
                return;
            }

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'En cours...';

            // Effectuer la requête AJAX
            fetch(window.APP_BASE_URL + 'rapprochements/rapprocher_unique', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'string_releve=' + encodeURIComponent(stringReleve) +
                        '&ecriture_id=' + encodeURIComponent(ecritureId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succès - rediriger vers import_releve_from_file pour recharger et propager les effets
                        redirectWithScrollPosition(getReturnUrl());
                    } else {
                        // Erreur - remettre le bouton dans son état initial
                        button.disabled = false;
                        button.textContent = 'Rapprocher';
                        alert('Erreur lors du rapprochement: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    button.disabled = false;
                    button.textContent = 'Rapprocher';
                    alert('Erreur de communication avec le serveur');
                });
        }

        // Gestion du rapprochement pour les combinaisons multiples
        if (e.target.classList.contains('auto-reconcile-combination-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');
            const ecritureIdsJson = button.getAttribute('data-ecriture-ids');
            
            let ecritureIds;
            try {
                ecritureIds = JSON.parse(ecritureIdsJson);
            } catch (error) {
                alert('Erreur lors de la lecture des IDs des écritures');
                return;
            }

            if (!ecritureIds || ecritureIds.length === 0) {
                alert('Aucune écriture à rapprocher');
                return;
            }

            // Désactiver le bouton pendant le traitement
            button.disabled = true;
            button.textContent = 'En cours...';

            // Effectuer la requête AJAX avec tous les IDs d'écritures
            fetch(window.APP_BASE_URL + 'rapprochements/rapprocher_multiple', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'string_releve=' + encodeURIComponent(stringReleve) + 
                      '&ecriture_ids=' + encodeURIComponent(ecritureIdsJson)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - recharger la page
                    redirectWithScrollPosition(getReturnUrl());
                } else {
                    // Erreur - remettre le bouton dans son état initial
                    button.disabled = false;
                    button.textContent = 'Non rapproché';
                    let errorMessage = 'Erreur lors du rapprochement: ' + data.message;
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += '\\n\\nDétails:\\n' + data.errors.join('\\n');
                    }
                    alert(errorMessage);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                button.disabled = false;
                button.textContent = 'Non rapproché';
                alert('Erreur de communication avec le serveur');
            });
        }

        // Gestion du rapprochement manuel
        if (e.target.classList.contains('manual-reconcile-btn')) {
            e.preventDefault();

            const button = e.target;
            const stringReleve = button.getAttribute('data-string-releve');
            const line = button.getAttribute('data-line');

            // Rediriger vers la page de rapprochement manuel
            const url = window.APP_BASE_URL + 'rapprochements/rapprochement_manuel?' +
                'line=' + encodeURIComponent(line);
            
            redirectWithScrollPosition(url);
        }
    });
}

// Initialize when document is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReconciliation);
} else {
    initReconciliation();
}
