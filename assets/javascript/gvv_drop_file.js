/*
 * Affecte un fichier glissé-déposé à un <input type="file"> en passant par
 * une copie en mémoire.
 *
 * Sous Linux, les navigateurs Chromium (Chrome, Brave) ne savent pas lire un
 * fichier déposé depuis Nemo 6.6, qui le transmet via le portail de documents
 * (/run/user/<uid>/doc) : le nom s'affiche mais l'envoi du formulaire échoue
 * avec ERR_FILE_NOT_FOUND. Lire le contenu au moment du dépôt rend l'envoi
 * indépendant du disque quand c'est possible, et sinon permet de signaler
 * immédiatement le problème.
 *
 * Retourne une Promise résolue avec le fichier copié, ou null en cas d'échec
 * (une alerte Bootstrap est alors affichée avant la zone de dépôt).
 */
function gvvDropFile(file, input, zone, errorMessage) {
    var previous = zone.parentNode.querySelector('.gvv-drop-file-error');
    if (previous) previous.remove();

    return file.arrayBuffer().then(function (buf) {
        var copy = new File([buf], file.name, { type: file.type, lastModified: file.lastModified });
        var dt = new DataTransfer();
        dt.items.add(copy);
        input.files = dt.files;
        return copy;
    }, function () {
        input.value = '';
        var alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show gvv-drop-file-error';
        alert.setAttribute('role', 'alert');
        alert.textContent = errorMessage + ' (' + file.name + ')';
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.setAttribute('data-bs-dismiss', 'alert');
        alert.appendChild(close);
        zone.parentNode.insertBefore(alert, zone);
        return null;
    });
}
