/**
 * GVV - Synchronise la largeur de la page avec un tableau DataTables plus large
 * que l'écran, pour éviter qu'un tableau large déborde de façon incohérente
 * (menu/bannière restant à la largeur de l'écran pendant que seul le tableau
 * déborde) ou, à l'inverse, reste figé à une largeur trop étroite sur grand
 * écran quand "bAutoWidth" fige une largeur en pixels.
 *
 * À appeler depuis le "fnDrawCallback" de toute table DataTables susceptible
 * de déborder d'un écran étroit, avec l'élément <table> comme contexte "this" :
 *
 *   "fnDrawCallback": function() { gvvSyncWideTableLayout(this); }
 *
 * Effets :
 * - #body (filtre, accordéons, contenu) et header.container-fluid (bannière,
 *   décorative) sont étirés à la largeur réelle du tableau.
 * - Le menu (nav.navbar) reste volontairement à la largeur de l'écran, pour
 *   que ses contrôles (bouton "Quitter", sélecteur de section) restent
 *   accessibles sans scroll horizontal. Un calque décoratif est placé
 *   derrière lui (même hauteur/couleur, étiré) pour éviter un décrochement
 *   de couleur visible quand on scrolle vers la droite.
 */
function gvvSyncWideTableLayout(tableEl) {
    var pageWidth = $(tableEl).outerWidth() + 'px';

    $('#body').css('min-width', pageWidth);
    $('header.container-fluid').css('min-width', pageWidth);

    var $nav = $('nav.navbar').first();
    if ($nav.length) {
        var $backdrop = $nav.children('.nav-width-backdrop');
        if ($backdrop.length === 0) {
            $backdrop = $('<div class="nav-width-backdrop"></div>').prependTo($nav);
            $backdrop.css({
                position: 'absolute',
                top: 0,
                left: 0,
                height: '100%',
                zIndex: -1,
                pointerEvents: 'none'
            });
        }
        $backdrop.css({
            minWidth: pageWidth,
            backgroundColor: $nav.css('background-color')
        });
    }

    // Sur certaines tables (rendu client, sans "bServerSide"), la largeur finale du
    // tableau se stabilise juste après ce callback (le navigateur termine son propre
    // calcul de layout de tableau une fois toutes les cellules en place). Un second
    // passage juste après laisse ce calcul se terminer avant de re-mesurer.
    setTimeout(function() {
        var settledWidth = $(tableEl).outerWidth() + 'px';
        if (settledWidth !== pageWidth) {
            $('#body').css('min-width', settledWidth);
            $('header.container-fluid').css('min-width', settledWidth);
            $('nav.navbar').first().children('.nav-width-backdrop').css('min-width', settledWidth);
        }
    }, 50);
}

/**
 * Conséquence de gvvSyncWideTableLayout() sur smartphone : la page étant plus
 * large que l'écran, le navigateur mobile agrandit le "layout viewport" à la
 * largeur du tableau. Les modales Bootstrap (position: fixed) s'affichent alors
 * en haut à gauche de la page élargie, hors de la zone réellement visible
 * (visualViewport) quand l'utilisateur a scrollé vers la colonne Actions :
 * le bouton semble ne rien faire.
 *
 * On recale donc chaque modale (et son fond grisé) sur la zone visible à
 * l'ouverture, uniquement quand celle-ci ne coïncide pas avec la fenêtre
 * (page élargie ou zoomée) ; sur PC le comportement Bootstrap est inchangé.
 */
(function() {
    var vv = window.visualViewport;
    if (!vv) {
        return;
    }

    function isShifted() {
        return vv.offsetLeft > 0 || vv.offsetTop > 0
            || vv.width < document.documentElement.clientWidth - 1;
    }

    function fitToVisualViewport(el) {
        el.style.left = vv.offsetLeft + 'px';
        el.style.top = vv.offsetTop + 'px';
        el.style.width = vv.width + 'px';
        el.style.height = vv.height + 'px';
    }

    function resetPosition(el) {
        el.style.left = '';
        el.style.top = '';
        el.style.width = '';
        el.style.height = '';
    }

    function fitModal(modal) {
        if (isShifted()) {
            fitToVisualViewport(modal);
            modal.dataset.gvvFitted = '1';
        }
    }

    document.addEventListener('show.bs.modal', function(e) {
        fitModal(e.target);
    });

    document.addEventListener('shown.bs.modal', function(e) {
        // Seconde passe : le visualViewport peut n'être mis à jour qu'après un
        // défilement récent, donc après l'événement "show".
        fitModal(e.target);
        if (!e.target.dataset.gvvFitted) {
            return;
        }
        // Bootstrap compense une "barre de défilement" égale à la partie de page
        // hors écran (ex. 509px), ce qui réduirait la boîte de dialogue à 0px.
        e.target.style.paddingRight = '0px';
        var backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            fitToVisualViewport(backdrop);
        }
    });

    document.addEventListener('hidden.bs.modal', function(e) {
        if (e.target.dataset.gvvFitted) {
            delete e.target.dataset.gvvFitted;
            resetPosition(e.target);
        }
    });
})();
