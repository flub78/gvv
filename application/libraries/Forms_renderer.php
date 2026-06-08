<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Forms renderer library
 *
 * Prepares dynamic field definitions for public rendering and generates
 * interactive widgets (e.g. signature) from declarative HTML attributes.
 */
class Forms_renderer {

    private static $signature_assets_emitted  = false;
    private static $validation_script_emitted = false;

    public function normalize_fields_for_view(array $fields, array $old_values = array()) {
        $normalized = array();

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? (int) $field['id'] : 0;
            $type = isset($field['field_type']) ? (string) $field['field_type'] : 'text';
            $name = 'field_' . $field_id;

            $options = array();
            if (!empty($field['options_json'])) {
                $decoded = json_decode($field['options_json'], true);
                if (is_array($decoded)) {
                    $options = array_values($decoded);
                }
            }

            $old = array_key_exists($field_id, $old_values) ? $old_values[$field_id] : '';
            if ($type === 'checkbox') {
                $old = is_array($old) ? $old : array();
            }

            $html_type = 'text';
            if (in_array($type, array('email', 'date', 'number', 'file'), true)) {
                $html_type = $type;
            }

            $normalized[] = array(
                'id'         => $field_id,
                'name'       => $name,
                'type'       => $type,
                'label'      => isset($field['label']) ? $field['label'] : '',
                'required'   => !empty($field['is_required']),
                'options'    => $options,
                'old_value'  => $old,
                'html_type'  => $html_type,
            );
        }

        return $normalized;
    }

    /**
     * Replace <div data-gvv-type="signature"> elements in page HTML with the
     * interactive signature widget.  Returns the modified HTML string and sets
     * $has_signature_widget to true when at least one widget was injected.
     */
    public function inject_signature_widgets($html, &$has_signature_widget = false) {
        if (strpos($html, 'data-gvv-type') === false) {
            return $html;
        }

        $result = preg_replace_callback(
            '/<div([^>]*)\bdata-gvv-type=["\']signature["\']([^>]*)>(.*?)<\/div>/is',
            function ($matches) use (&$has_signature_widget) {
                $all_attrs = $matches[1] . $matches[2];

                preg_match('/data-gvv-name=["\']([^"\']+)["\']/', $all_attrs, $name_m);
                $field_name = isset($name_m[1]) ? trim($name_m[1]) : '';
                if ($field_name === '') {
                    return $matches[0]; // keep original if no name
                }

                preg_match('/data-gvv-required=["\']?true["\']?/i', $all_attrs, $req_m);
                $required = !empty($req_m);

                // Extract label from div text content
                $label = trim(strip_tags($matches[3]));
                if ($label === '') {
                    $label = $field_name;
                }

                $has_signature_widget = true;
                return $this->render_signature_widget($field_name, $label, $required);
            },
            $html
        );

        return ($result !== null) ? $result : $html;
    }

    /**
     * Render the HTML for a composite signature widget.
     *
     * Three tabs: Draw (canvas + SignaturePad), Upload (image file), Type (handwriting font canvas).
     * Two hidden inputs transmit value and type (canvas|file|text) to the server.
     * The first call also emits the shared CSS/JS assets (once per page).
     */
    public function render_signature_widget($field_name, $label = '', $required = false) {
        $fn = htmlspecialchars($field_name, ENT_QUOTES, 'UTF-8');
        $lbl = htmlspecialchars($label !== '' ? $label : $field_name, ENT_QUOTES, 'UTF-8');
        $req_attr = $required ? ' required' : '';
        $req_star = $required ? ' <span class="text-danger">*</span>' : '';

        $id_draw   = 'gvv-sig-draw-'   . $fn;
        $id_upload = 'gvv-sig-upload-' . $fn;
        $id_type   = 'gvv-sig-type-'   . $fn;
        $id_canvas = 'gvv-sig-canvas-' . $fn;
        $id_tcanv  = 'gvv-sig-tcanv-'  . $fn;

        $req_data = $required ? ' data-gvv-required="true"' : '';
        $html  = '<div class="gvv-signature-widget mb-3" data-sig-name="' . $fn . '"' . $req_data . '>' . "\n";
        $html .= '  <label class="form-label fw-semibold">' . $lbl . $req_star . '</label>' . "\n";

        // Tabs
        $html .= '  <ul class="nav nav-tabs gvv-sig-tabs" role="tablist">' . "\n";
        $html .= '    <li class="nav-item"><button class="nav-link active" type="button" data-sig-tab="canvas">'
               . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/></svg>'
               . 'Dessiner</button></li>' . "\n";
        $html .= '    <li class="nav-item"><button class="nav-link" type="button" data-sig-tab="file">'
               . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"/></svg>'
               . 'Importer</button></li>' . "\n";
        $html .= '    <li class="nav-item"><button class="nav-link" type="button" data-sig-tab="text">'
               . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M0 4s0-2 2-2 2 2 2 2v8s0 2-2 2-2-2-2-2V4zm5 0v8a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2zm9 0s0-2 2-2 2 2 2 2v8s0 2-2 2-2-2-2-2V4z"/></svg>'
               . 'Taper</button></li>' . "\n";
        $html .= '  </ul>' . "\n";

        // Draw panel
        $html .= '  <div class="gvv-sig-panel border border-top-0 rounded-bottom p-3" data-sig-panel="canvas">' . "\n";
        $html .= '    <canvas id="' . $id_canvas . '" class="gvv-sig-draw-canvas d-block"'
               . ' style="border:1px solid #dee2e6;border-radius:4px;width:100%;max-width:600px;height:150px;touch-action:none;background:#fff;"></canvas>' . "\n";
        $html .= '    <button type="button" class="btn btn-sm btn-outline-secondary mt-2 gvv-sig-clear-btn">'
               . '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854z"/></svg>'
               . 'Effacer</button>' . "\n";
        $html .= '  </div>' . "\n";

        // Upload panel
        $html .= '  <div class="gvv-sig-panel border border-top-0 rounded-bottom p-3 d-none" data-sig-panel="file">' . "\n";
        $html .= '    <input type="file" name="' . $fn . '_file" class="form-control gvv-sig-file-input" accept="image/*">' . "\n";
        $html .= '    <div class="gvv-sig-upload-preview mt-2"></div>' . "\n";
        $html .= '  </div>' . "\n";

        // Type panel
        $html .= '  <div class="gvv-sig-panel border border-top-0 rounded-bottom p-3 d-none" data-sig-panel="text">' . "\n";
        $html .= '    <input type="text" class="form-control mb-2 gvv-sig-text-input" placeholder="Tapez votre signature..." autocomplete="off">' . "\n";
        $html .= '    <canvas id="' . $id_tcanv . '" class="gvv-sig-text-canvas d-block"'
               . ' style="border:1px solid #dee2e6;border-radius:4px;width:100%;max-width:600px;height:80px;background:#fff;"></canvas>' . "\n";
        $html .= '  </div>' . "\n";

        // Hidden inputs
        $html .= '  <input type="hidden" name="' . $fn . '" class="gvv-sig-value">' . "\n";
        $html .= '  <input type="hidden" name="' . $fn . '_type" class="gvv-sig-type-hidden" value="canvas">' . "\n";
        $html .= '</div>' . "\n";

        if (!self::$signature_assets_emitted) {
            $html .= $this->build_signature_assets();
            self::$signature_assets_emitted = true;
        }

        return $html;
    }

    /**
     * Append client-side validation script to page HTML (emitted once per request).
     *
     * The script disables HTML5 native validation bubbles and replaces them with
     * Bootstrap is-invalid highlighting + an inline invalid-feedback message per
     * field + a summary alert at the top of the form listing all missing fields.
     * It handles regular required inputs, checkboxes, and GVV signature widgets.
     */
    public function inject_validation_script($html) {
        if (self::$validation_script_emitted) {
            return $html;
        }
        self::$validation_script_emitted = true;
        return $html . $this->build_validation_script();
    }

    private function build_validation_script() {
        return <<<'VALJS'
<style>
input.is-invalid,textarea.is-invalid,select.is-invalid{border-color:#dc3545!important;border-width:1px;border-style:solid;outline:none;}
.gvv-invalid-feedback{display:block;color:#dc3545;font-size:.875em;margin-top:.25rem;}
.gvv-sig-invalid>.gvv-sig-panel,
.gvv-sig-invalid .gvv-sig-tabs{outline:2px solid #dc3545;border-radius:4px;}
.gvv-sig-error{display:block;color:#dc3545;font-size:.875em;margin-top:.25rem;}
</style>
<script>
(function () {
    'use strict';

    function init() {
        var form = document.querySelector('form[action*="forms/submit"]');
        if (!form) return;

        /* Disable HTML5 native validation bubbles — we handle display ourselves */
        form.setAttribute('novalidate', 'novalidate');

        form.addEventListener('submit', function (e) {
            clearErrors(form);
            var missing = [];

            /* 1. Standard required inputs / selects / textareas */
            form.querySelectorAll('input[required],textarea[required],select[required]').forEach(function (el) {
                if (el.closest('.gvv-signature-widget')) return; /* handled below */
                if (isEmpty(el)) {
                    markFieldInvalid(el);
                    missing.push(getLabel(el));
                }
            });

            /* 2. Required signature widgets
             * The widget's own submit listener (registered earlier) has already
             * populated the hidden .gvv-sig-value input before this runs. */
            form.querySelectorAll('.gvv-signature-widget[data-gvv-required="true"]').forEach(function (widget) {
                var typeInput  = widget.querySelector('.gvv-sig-type-hidden');
                var valueInput = widget.querySelector('.gvv-sig-value');
                var mode = typeInput ? typeInput.value : 'canvas';
                var empty = false;
                if (mode === 'file') {
                    var fi = widget.querySelector('.gvv-sig-file-input');
                    empty = !fi || !fi.files || fi.files.length === 0;
                } else {
                    empty = !valueInput || valueInput.value.trim() === '';
                }
                if (empty) {
                    markSigInvalid(widget);
                    var lbl = widget.querySelector('.form-label');
                    missing.push(lbl ? lbl.textContent.trim().replace(/\s*\*\s*$/, '').trim() : 'Signature');
                }
            });

            if (missing.length > 0) {
                e.preventDefault();
                showSummary(form, missing);
                var first = form.querySelector('.is-invalid, .gvv-sig-invalid');
                if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    function isEmpty(el) {
        if (el.type === 'checkbox') return !el.checked;
        if (el.type === 'radio')    return !document.querySelector('input[name="' + esc(el.name) + '"]:checked');
        return el.value.trim() === '';
    }

    function getLabel(el) {
        var field = el.closest('.field');
        if (field) {
            var lbl = field.querySelector('label');
            if (lbl) return lbl.textContent.trim().replace(/\s*\*\s*$/, '').trim();
        }
        return el.name || 'Champ';
    }

    function markFieldInvalid(el) {
        el.classList.add('is-invalid');
        var parent = el.parentNode;
        if (!parent.querySelector('.gvv-invalid-feedback')) {
            var fb = document.createElement('div');
            fb.className = 'gvv-invalid-feedback';
            fb.textContent = 'Ce champ est obligatoire.';
            parent.insertBefore(fb, el.nextSibling);
        }
        if (!el.dataset.gvvValidationBound) {
            el.dataset.gvvValidationBound = '1';
            var clear = function () {
                if (!isEmpty(el)) {
                    el.classList.remove('is-invalid');
                    var fb = el.parentNode.querySelector('.gvv-invalid-feedback');
                    if (fb) fb.remove();
                }
            };
            el.addEventListener('input',  clear);
            el.addEventListener('change', clear);
        }
    }

    function markSigInvalid(widget) {
        widget.classList.add('gvv-sig-invalid');
        if (!widget.querySelector('.gvv-sig-error')) {
            var fb = document.createElement('div');
            fb.className = 'gvv-sig-error';
            fb.textContent = 'La signature est obligatoire.';
            widget.appendChild(fb);
        }
        if (!widget.dataset.gvvSigBound) {
            widget.dataset.gvvSigBound = '1';
            var canvas = widget.querySelector('.gvv-sig-draw-canvas');
            if (canvas) {
                ['pointerup', 'mouseup', 'touchend'].forEach(function (ev) {
                    canvas.addEventListener(ev, function () { clearSigError(widget); });
                });
            }
            var fileInput = widget.querySelector('.gvv-sig-file-input');
            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    if (fileInput.files && fileInput.files.length > 0) clearSigError(widget);
                });
            }
            var textInput = widget.querySelector('.gvv-sig-text-input');
            if (textInput) {
                textInput.addEventListener('input', function () {
                    if (textInput.value.trim() !== '') clearSigError(widget);
                });
            }
        }
    }

    function clearSigError(widget) {
        widget.classList.remove('gvv-sig-invalid');
        var fb = widget.querySelector('.gvv-sig-error');
        if (fb) fb.remove();
    }

    function showSummary(form, labels) {
        var alert = document.createElement('div');
        alert.className = 'alert alert-danger gvv-validation-summary';
        alert.setAttribute('role', 'alert');
        var items = labels.map(function (l) {
            return '<li>' + esc(l) + '</li>';
        }).join('');
        alert.innerHTML = '<strong>Veuillez renseigner les champs obligatoires :</strong>'
            + '<ul class="mb-0 mt-1">' + items + '</ul>';
        form.insertBefore(alert, form.firstChild);
        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.gvv-invalid-feedback').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.gvv-sig-invalid').forEach(function (el) { el.classList.remove('gvv-sig-invalid'); });
        form.querySelectorAll('.gvv-sig-error').forEach(function (el) { el.remove(); });
        var s = form.querySelector('.gvv-validation-summary');
        if (s) s.remove();
    }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
VALJS;
    }

    private function build_signature_assets() {
        // Google Font for handwriting (Caveat) + CSS isolation for the widget
        $out = '<style>' . "\n"
             . '@import url(\'https://fonts.googleapis.com/css2?family=Caveat:wght@600&display=swap\');' . "\n"
             // Isolate the widget from unscoped form CSS (e.g. bare `input[type="text"]` rules)
             . '.gvv-signature-widget { all: initial; display: block; font-family: inherit; }' . "\n"
             . '.gvv-signature-widget * { box-sizing: border-box; }' . "\n"
             . '.gvv-signature-widget .form-label { display: block; margin-bottom: 0.5rem; font-size: 1rem; font-weight: 600; color: #212529; }' . "\n"
             . '.gvv-signature-widget .text-danger { color: #dc3545; }' . "\n"
             . '.gvv-signature-widget .nav { display: flex; flex-wrap: wrap; padding-left: 0; margin-bottom: 0; list-style: none; }' . "\n"
             . '.gvv-signature-widget .nav-tabs { border-bottom: 1px solid #dee2e6; }' . "\n"
             . '.gvv-signature-widget .nav-item { display: list-item; }' . "\n"
             . '.gvv-signature-widget .nav-link { display: block; padding: 0.5rem 1rem; color: #0d6efd; text-decoration: none; background: none; border: 1px solid transparent; border-radius: 0.375rem 0.375rem 0 0; cursor: pointer; font-size: 0.875rem; }' . "\n"
             . '.gvv-signature-widget .nav-link.active { color: #495057; background-color: #fff; border-color: #dee2e6 #dee2e6 #fff; }' . "\n"
             . '.gvv-signature-widget .gvv-sig-panel { display: block; padding: 1rem; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 0.375rem 0.375rem; background: #fff; }' . "\n"
             . '.gvv-signature-widget .gvv-sig-panel.d-none { display: none !important; }' . "\n"
             . '.gvv-signature-widget input[type="text"] { display: block; width: 100%; padding: 0.375rem 0.75rem; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529; background-color: #fff; background-clip: padding-box; border: 1px solid #dee2e6; border-radius: 0.375rem; outline: revert; flex: unset; margin: 0 0 0.5rem 0; }' . "\n"
             . '.gvv-signature-widget input[type="file"] { display: block; width: 100%; font-size: 0.875rem; }' . "\n"
             . '.gvv-signature-widget .btn { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.875rem; border-radius: 0.25rem; cursor: pointer; border: 1px solid transparent; background: none; }' . "\n"
             . '.gvv-signature-widget .btn-outline-secondary { color: #6c757d; border-color: #6c757d; }' . "\n"
             . '.gvv-signature-widget .btn-outline-secondary:hover { background-color: #6c757d; color: #fff; }' . "\n"
             . '.gvv-signature-widget .mt-2 { margin-top: 0.5rem !important; }' . "\n"
             . '.gvv-signature-widget .mb-3 { margin-bottom: 1rem !important; }' . "\n"
             . '.gvv-signature-widget .fw-semibold { font-weight: 600 !important; }' . "\n"
             . '</style>' . "\n";

        $out .= <<<'JS'
<script>
(function () {
    'use strict';

    function initWidget(widget) {
        var name = widget.getAttribute('data-sig-name');
        if (!name || widget.dataset.sigInited) return;
        widget.dataset.sigInited = '1';

        var drawPanel   = widget.querySelector('[data-sig-panel="canvas"]');
        var uploadPanel = widget.querySelector('[data-sig-panel="file"]');
        var typePanel   = widget.querySelector('[data-sig-panel="text"]');
        var drawCanvas  = drawPanel  ? drawPanel.querySelector('.gvv-sig-draw-canvas') : null;
        var textCanvas  = typePanel  ? typePanel.querySelector('.gvv-sig-text-canvas') : null;
        var fileInput   = uploadPanel ? uploadPanel.querySelector('.gvv-sig-file-input') : null;
        var valueInput  = widget.querySelector('.gvv-sig-value');
        var typeInput   = widget.querySelector('.gvv-sig-type-hidden');
        var clearBtn    = drawPanel  ? drawPanel.querySelector('.gvv-sig-clear-btn') : null;
        var textInput   = typePanel  ? typePanel.querySelector('.gvv-sig-text-input') : null;
        var preview     = uploadPanel ? uploadPanel.querySelector('.gvv-sig-upload-preview') : null;

        var pad       = null;
        var activeTab = 'canvas';

        // --- Init SignaturePad ---
        if (drawCanvas && typeof SignaturePad !== 'undefined') {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            drawCanvas.width  = drawCanvas.offsetWidth  * ratio;
            drawCanvas.height = drawCanvas.offsetHeight * ratio;
            drawCanvas.getContext('2d').scale(ratio, ratio);
            pad = new SignaturePad(drawCanvas, { backgroundColor: 'rgb(255,255,255)' });
        }

        // --- Tab switching ---
        widget.querySelectorAll('[data-sig-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = this.getAttribute('data-sig-tab');
                activeTab = tab;
                widget.querySelectorAll('[data-sig-tab]').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                [drawPanel, uploadPanel, typePanel].forEach(function (p) { if (p) p.classList.add('d-none'); });
                var map = { canvas: drawPanel, file: uploadPanel, text: typePanel };
                if (map[tab]) map[tab].classList.remove('d-none');
                typeInput.value = tab;
            });
        });

        // --- Clear draw canvas ---
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (pad) pad.clear();
                valueInput.value = '';
            });
        }

        // --- File upload preview ---
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (!this.files || !this.files[0]) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    if (preview) {
                        preview.innerHTML = '<img src="' + e.target.result
                            + '" style="max-width:300px;max-height:150px;border:1px solid #dee2e6;border-radius:4px;" alt="">';
                    }
                };
                reader.readAsDataURL(this.files[0]);
            });
        }

        // --- Typed signature: render on canvas ---
        var sigFont = 'Caveat';
        var sigFontLoaded = false;

        function renderTypedSig(text) {
            if (!textCanvas) return;
            var w = textCanvas.offsetWidth  || 600;
            var h = textCanvas.offsetHeight || 80;
            // Only reset intrinsic dimensions when they differ (avoids clearing a valid drawing)
            if (textCanvas.width !== w)  textCanvas.width  = w;
            if (textCanvas.height !== h) textCanvas.height = h;
            var ctx = textCanvas.getContext('2d');
            ctx.fillStyle = 'rgb(255,255,255)';
            ctx.fillRect(0, 0, w, h);
            if (text) {
                ctx.fillStyle = '#1a1a1a';
                ctx.font = Math.round(h * 0.65) + 'px "' + sigFont + '", cursive';
                ctx.textBaseline = 'middle';
                ctx.fillText(text, 16, h / 2);
            }
        }

        function drawTypedSig(text) {
            renderTypedSig(text);
            // If the web font is not yet ready, schedule a redraw after it loads
            if (!sigFontLoaded && document.fonts && document.fonts.load) {
                var size = Math.round((textCanvas.offsetHeight || 80) * 0.65);
                document.fonts.load(size + 'px "' + sigFont + '"').then(function () {
                    sigFontLoaded = true;
                    if (textInput && textInput.value) renderTypedSig(textInput.value);
                });
            }
        }
        if (textInput) {
            textInput.addEventListener('input', function () { drawTypedSig(this.value); });
        }

        // --- Prepare hidden value before form submit ---
        var form = widget.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (activeTab === 'canvas' || activeTab === 'text') {
                    var src = activeTab === 'canvas' ? drawCanvas : textCanvas;
                    if (!src) return;
                    // Skip if canvas is blank
                    if (activeTab === 'canvas' && pad && pad.isEmpty()) {
                        valueInput.value = '';
                        return;
                    }
                    if (activeTab === 'text' && (!textInput || textInput.value.trim() === '')) {
                        valueInput.value = '';
                        return;
                    }
                    // Ensure the typed canvas reflects the current text before capturing
                    if (activeTab === 'text' && textInput) {
                        drawTypedSig(textInput.value);
                    }
                    // Normalise to 600×200
                    var norm = document.createElement('canvas');
                    norm.width = 600; norm.height = 200;
                    var ctx = norm.getContext('2d');
                    ctx.fillStyle = 'rgb(255,255,255)';
                    ctx.fillRect(0, 0, 600, 200);
                    ctx.drawImage(src, 0, 0, 600, 200);
                    var dataUrl = norm.toDataURL('image/png');
                    // Strip prefix — CI2 XSS filter strips "data:...base64,..." patterns
                    valueInput.value = dataUrl.substring('data:image/png;base64,'.length);
                }
                // file mode: the file input submits itself, no extra action needed
            });
        }
    }

    function initAll() {
        document.querySelectorAll('.gvv-signature-widget').forEach(initWidget);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
</script>
JS;

        return $out;
    }
}
