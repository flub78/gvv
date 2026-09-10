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
 * Création rapide d'un aérodrome depuis les formulaires de saisie de vol.
 *
 * Le bouton "+" à côté d'un sélecteur d'aérodrome ouvre la fenêtre modale
 * #terrainModal (application/views/terrains/bs_ajax_modal.php). À la
 * validation, l'aérodrome est créé via AJAX (terrains/ajax_create) puis
 * ajouté à tous les sélecteurs d'aérodrome de la page et sélectionné dans
 * celui d'où provient la demande.
 *
 * Configuration fournie par la vue via la variable globale terrain_modal_config.
 */
(function ($) {
    'use strict';

    if (typeof terrain_modal_config === 'undefined') {
        return;
    }

    var cfg = terrain_modal_config;
    var $modalEl = $('#terrainModal');
    if ($modalEl.length === 0) {
        return;
    }

    var modal = new bootstrap.Modal($modalEl[0]);
    var targetSelectId = null;

    var FIELDS = {
        oaci: { input: '#terrainModalOaci', error: '#terrainModalOaciError' },
        nom: { input: '#terrainModalNom', error: '#terrainModalNomError' },
        freq1: { input: '#terrainModalFreq1', error: '#terrainModalFreq1Error' },
        freq2: { input: '#terrainModalFreq2', error: '#terrainModalFreq2Error' },
        comment: { input: '#terrainModalComment', error: null }
    };

    function clearErrors() {
        $('#terrainModalGlobalError').addClass('d-none').text('');
        $.each(FIELDS, function (name, sel) {
            $(sel.input).removeClass('is-invalid');
            if (sel.error) {
                $(sel.error).text('');
            }
        });
    }

    function showFieldErrors(errors) {
        var handled = {};
        $.each(FIELDS, function (name, sel) {
            if (errors[name]) {
                handled[name] = true;
                $(sel.input).addClass('is-invalid');
                if (sel.error) {
                    $(sel.error).text(errors[name]);
                } else {
                    showGlobalError(errors[name]);
                }
            }
        });
        // Erreurs non rattachées à un champ connu
        var leftover = [];
        $.each(errors, function (name, msg) {
            if (!handled[name]) {
                leftover.push(msg);
            }
        });
        if (leftover.length) {
            showGlobalError(leftover.join(' '));
        }
    }

    function showGlobalError(msg) {
        var $g = $('#terrainModalGlobalError');
        var current = $g.text();
        $g.text(current ? current + ' ' + msg : msg).removeClass('d-none');
    }

    function resetForm() {
        clearErrors();
        $.each(FIELDS, function (name, sel) {
            $(sel.input).val('');
        });
    }

    /**
     * Ajoute l'option à tous les sélecteurs d'aérodrome de la page,
     * en respectant l'ordre alphabétique du libellé, puis sélectionne
     * la nouvelle valeur dans le sélecteur d'origine.
     */
    function addOptionEverywhere(oaci, label) {
        $.each(cfg.selects, function (i, id) {
            var $select = $('#' + id);
            if ($select.length === 0) {
                return;
            }
            if ($select.find('option[value="' + oaci.replace(/"/g, '\\"') + '"]').length > 0) {
                return;
            }
            var inserted = false;
            $select.find('option').each(function () {
                if (this.value === '') {
                    return; // garde l'entrée vide en tête
                }
                if (label.localeCompare($(this).text(), undefined, { sensitivity: 'base' }) < 0) {
                    $(new Option(label, oaci, false, false)).insertBefore(this);
                    inserted = true;
                    return false;
                }
            });
            if (!inserted) {
                $select.append(new Option(label, oaci, false, false));
            }
        });

        var $target = $('#' + targetSelectId);
        if ($target.length) {
            $target.val(oaci).trigger('change');
        }
    }

    $(document).on('click', '.js-add-terrain', function () {
        targetSelectId = $(this).data('target');
        resetForm();
        modal.show();
        window.setTimeout(function () {
            $('#terrainModalOaci').trigger('focus');
        }, 300);
    });

    $('#terrainModalSubmit').on('click', function () {
        var $btn = $(this);
        clearErrors();

        var payload = {
            oaci: $('#terrainModalOaci').val(),
            nom: $('#terrainModalNom').val(),
            freq1: $('#terrainModalFreq1').val(),
            freq2: $('#terrainModalFreq2').val(),
            comment: $('#terrainModalComment').val()
        };

        $btn.prop('disabled', true);

        $.ajax({
            url: cfg.url,
            type: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (resp) {
            if (resp && resp.success) {
                addOptionEverywhere(resp.oaci, resp.label);
                modal.hide();
            } else if (resp && resp.errors) {
                showFieldErrors(resp.errors);
            } else {
                showGlobalError(cfg.messages.save);
            }
        }).fail(function (xhr) {
            // jQuery 1.8 n'expose pas xhr.responseJSON : parser le corps à la main.
            var resp = xhr.responseJSON;
            if (!resp && xhr.responseText) {
                try {
                    resp = JSON.parse(xhr.responseText);
                } catch (e) {
                    resp = null;
                }
            }
            if (resp && resp.errors) {
                showFieldErrors(resp.errors);
            } else {
                showGlobalError(cfg.messages.network);
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

})(jQuery);
