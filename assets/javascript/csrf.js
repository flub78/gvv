/**
 * Protection CSRF : ajoute le jeton à toutes les requêtes POST vers GVV.
 *
 * Le jeton est lu dans le cookie posé par le serveur (MY_Security). Il est
 * ajouté :
 *   - aux formulaires POST de la page, en champ caché (à l'affichage, à la
 *     soumission et lors d'un form.submit() en JavaScript) ;
 *   - aux requêtes XMLHttpRequest (donc jQuery) et fetch, dans l'en-tête
 *     X-CSRF-Token.
 * Seules les requêtes vers le même site reçoivent le jeton : il n'est jamais
 * transmis à un site tiers.
 *
 * Ce script est injecté automatiquement dans chaque page HTML (MY_Output).
 */
(function () {
    'use strict';

    var FIELD = 'gvv_csrf_token';
    var HEADER = 'X-CSRF-Token';
    var COOKIE_SUFFIX = 'gvv_csrf_cookie';

    if (window.gvvCsrfInstalled) {
        return;
    }
    window.gvvCsrfInstalled = true;

    function token() {
        var cookies = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < cookies.length; i++) {
            var eq = cookies[i].indexOf('=');
            var name = cookies[i].substring(0, eq);
            if (name.length >= COOKIE_SUFFIX.length
                && name.substring(name.length - COOKIE_SUFFIX.length) === COOKIE_SUFFIX) {
                return decodeURIComponent(cookies[i].substring(eq + 1));
            }
        }
        return '';
    }

    function sameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function addToForm(form) {
        if (!form || form.nodeName !== 'FORM') {
            return;
        }
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post' || !sameOrigin(form.getAttribute('action') || window.location.href)) {
            return;
        }
        var value = token();
        if (!value) {
            return;
        }
        var input = form.querySelector('input[name="' + FIELD + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = FIELD;
            form.appendChild(input);
        }
        input.value = value;
    }

    function addToAllForms() {
        var forms = document.getElementsByTagName('form');
        for (var i = 0; i < forms.length; i++) {
            addToForm(forms[i]);
        }
    }

    // Formulaires : soumission par l'utilisateur, requestSubmit(), jQuery .submit()
    document.addEventListener('submit', function (event) {
        addToForm(event.target);
    }, true);

    // Formulaires : form.submit() en JavaScript ne déclenche pas l'événement submit
    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        addToForm(this);
        return nativeSubmit.apply(this, arguments);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addToAllForms);
    } else {
        addToAllForms();
    }

    // XMLHttpRequest (et jQuery.ajax, $.post...)
    var nativeOpen = XMLHttpRequest.prototype.open;
    var nativeSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) {
        this.gvvCsrfNeeded = String(method).toUpperCase() === 'POST' && sameOrigin(url);
        return nativeOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
        if (this.gvvCsrfNeeded) {
            // Toujours envoyé, même vide (cookie expiré) : il signale au serveur
            // un appel de script, qui reçoit alors un refus en JSON
            this.setRequestHeader(HEADER, token());
        }
        return nativeSend.apply(this, arguments);
    };

    // fetch
    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            var url = (typeof input === 'string' || input instanceof URL) ? String(input) : input.url;
            var method = (init && init.method) || (input && input.method) || 'GET';
            if (String(method).toUpperCase() === 'POST' && sameOrigin(url)) {
                // Toujours envoyé, même vide : voir XMLHttpRequest ci-dessus
                init = init ? Object.assign({}, init) : {};
                var headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
                headers.set(HEADER, token());
                init.headers = headers;
            }
            return nativeFetch.call(window, input, init);
        };
    }
})();
