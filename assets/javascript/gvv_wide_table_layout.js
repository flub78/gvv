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
