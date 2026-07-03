<!-- VIEW: application/views/bs_footer.php -->
<?php
$CI =& get_instance();
$CI->config->load('program');
$banner_color = trim((string) $CI->config->item('banner_color'));
if ($banner_color === '') {
    $banner_color = 'green';
}
?>
<footer class="container-fluid p-3 mt-3 text-white text-center" style="background-color: <?= htmlspecialchars($banner_color, ENT_QUOTES, 'UTF-8') ?>;">
    <p>
        <?= $this->lang->line("gvv_copyright") ?>
        <?= $this->lang->line("gvv_generated") ?>
    </p>
</footer>

<script type="text/javascript">
    <!--
    $(document).ready(function() {
        // Prevent double-submission on slow networks: disable submit buttons after first click.
        // setTimeout defers the disable until after the browser has collected form data,
        // so named submit buttons (name="button") are still included in the POST payload.
        // We check isDefaultPrevented() inside the timeout so that client-side validation
        // (which calls e.preventDefault()) keeps the button enabled on error.
        $('form').on('submit', function(e) {
            var $btns = $(this).find('button[type="submit"], input[type="submit"]');
            setTimeout(function() {
                var prevented = e.isDefaultPrevented() ||
                    (e.originalEvent && e.originalEvent.defaultPrevented);
                if (!prevented) {
                    $btns.prop('disabled', true);
                }
            }, 0);
        });

        // notre code ici
        $(".jbutton").button();
        $("#tabs").tabs();

        var execute = function() {
            //$("#dialog").parent().hide();
            $("#dialog").dialog("close");
        };
        var cancel = function() {
            alert("Cancel")
        };
        var dialogOpts = {
            modal: true,
            buttons: {
                "Ok": execute
            }
        };
        $("#dialog").dialog(dialogOpts);

        $(".datepicker").datepicker({
            changeYear: true,
            yearRange: "1930:2030"
        });

$('.datatable').dataTable({
            "bFilter": true,
            "bPaginate": true,
            "iDisplayLength": 100,
            "bStateSave": true,
            "fnStateSave": function(oSettings, oState) {
                try {
                    localStorage.setItem('DT_' + oSettings.sInstance, JSON.stringify(oState));
                } catch(e) {}
            },
            "fnStateLoad": function(oSettings) {
                try {
                    var data = localStorage.getItem('DT_' + oSettings.sInstance);
                    return data ? JSON.parse(data) : null;
                } catch(e) {
                    return null;
                }
            },
            "bSort": true,
            "bInfo": true,
            "bJQueryUI": true,
            "bRetrieve": false,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "search": {
                "caseInsensitive": true
            },
            "oLanguage": olanguage,

            // Add the page length menu options
            "aLengthMenu": [
                [10, 25, 50, 100, 500, 1000, -1],
                [10, 25, 50, 100, 500, 1000, "Tous les"]
            ],
            "fnDrawCallback": highlightSearchCallback
        });

        // "bFilter": true,
        // "iDisplayLength": 100,
        // "bStateSave": false,
        // "bSort": true,
        // "bInfo": true,
        // "bJQueryUI": true,
        // "bRetrieve": false,
        // "bAutoWidth": true,
        // "scrollY": "500px", // Add this for vertical scrolling
        // "scrollCollapse": true, // Add this to collapse when less data
        // "search": {
        //     "caseInsensitive": true
        // },
        // "oLanguage": olanguage,

        $('.datatable_nopaging').dataTable({
            "bFilter": true,
            "bPaginate": false,
            "iDisplayLength": -1,
            "bStateSave": true,
            "bSort": true,
            "bInfo": true,
            "bJQueryUI": true,
            "bRetrieve": false,
            "bAutoWidth": true,
            "search": {
                "caseInsensitive": true
            },
            "oLanguage": olanguage,
            "fnDrawCallback": highlightSearchCallback
        });

        $('.datatable_500').dataTable({
            "bFilter": true,
            "bPaginate": false,
            "scrollY": "500px", // Add this for vertical scrolling
            "scrollCollapse": true, // Add this to collapse when less data
            "iDisplayLength": 100,
            "bStateSave": true,  // Sauvegarde l'état (recherche, scroll) dans localStorage
            "bSort": true,
            "bInfo": true,
            "bJQueryUI": true,
            "bRetrieve": false,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "search": {
                "caseInsensitive": true
            },
            "oLanguage": olanguage,
            "fnDrawCallback": highlightSearchCallback
        });


        $('.fixed_datatable').dataTable({
            "bFilter": false,
            "bPaginate": false,
            "bStateSave": false,
            "bSort": false,
            "bInfo": false,
            "bAutoWidth": true,
            "bJQueryUI": false,
        });

        $('.searchable_nosort_datatable').dataTable({
            "bFilter": true,
            "bPaginate": true,
            "iDisplayLength": 100,
            "bStateSave": true,  // Sauvegarde l'état (pagination, recherche) dans localStorage
            "bSort": false,
            "bInfo": true,
            "bAutoWidth": true,
            "bJQueryUI": true,
            "ordering": false,
            "sPaginationType": "full_numbers",
            "search": {
                "caseInsensitive": true
            },
            "oLanguage": olanguage,
            "aLengthMenu": [
                [10, 25, 50, 100, 500, 1000, -1],
                [10, 25, 50, 100, 500, 1000, "Tous les"]
            ],
            "columnDefs": [{
                "orderable": false,
                "targets": "_all"
            }],
            "fnDrawCallback": highlightSearchCallback
        });

        // DataTable pour balance hiérarchique: recherche SANS pagination
        $('.balance_searchable_datatable').dataTable({
            "bFilter": true,
            "bPaginate": false,  // PAS de pagination
            "bStateSave": true,  // Sauvegarde l'état (recherche) dans localStorage
            "bSort": false,
            "bInfo": false,      // Pas d'info "Affichage de 1 à 100 sur 200"
            "bAutoWidth": true,
            "bJQueryUI": true,
            "ordering": false,
            "search": {
                "caseInsensitive": true
            },
            "oLanguage": olanguage,
            "columnDefs": [{
                "orderable": false,
                "targets": "_all"
            }],
            "fnDrawCallback": highlightSearchCallback
        });

        // to replace the select by an input that select values in the dropdown
        $('.big_select').select2({
            placeholder: 'Filtre...',
            width: '300px',
            allowClear: true
        });

        // When the user clicks × on the compte selector, Select2 fires `select2:clearing`
        // BEFORE firing `change` (with the stale old value). We set a flag here so that
        // compte_selection() can detect the clear and navigate to the grand journal page
        // instead of reading the outdated native selectedIndex value.
        $(document).on('select2:clearing', '#selector', function () {
            window._s2_compte_clearing = true;
        });

        // to replace the select by an input that select values in the dropdown
        $('.big_select_large').select2({
            placeholder: 'Filtre...',
            width: '100%',
            allowClear: false
        });

    });

                $('.datatable_500').closest('.dataTables_wrapper').css({
                'max-height': '500px',
                'overflow-y': 'auto'
            });

    // PDF Thumbnail async generation
    // Callable from any context (page load or AJAX-injected content).
    // $container : jQuery object to search within, or null for the whole page.
    function triggerPdfThumbnails($container) {
        var pdfElements = $container
            ? $container.find('.pdf-needs-thumbnail')
            : $('.pdf-needs-thumbnail');
        if (pdfElements.length === 0) return;

        var processQueue = [];
        pdfElements.each(function() { processQueue.push($(this)); });

        function processNextPdf() {
            if (processQueue.length === 0) return;

            var $element = processQueue.shift();
            var pdfPath = $element.data('pdf-path');

            $.ajax({
                url: '<?= site_url("attachments/generate_thumbnail") ?>',
                type: 'POST',
                data: { file_path: pdfPath },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.thumbnail_url) {
                        var img = '<img class="doc-thumbnail" src="' + response.thumbnail_url + '" title="' + pdfPath + '"/>';
                        $element.replaceWith(img);
                    }
                    setTimeout(processNextPdf, 100);
                },
                error: function() {
                    setTimeout(processNextPdf, 100);
                }
            });
        }

        setTimeout(processNextPdf, 200);
    }

    // Process elements already in the page at load time
    $(document).ready(function() { triggerPdfThumbnails(null); });
    //
    -->
</script>

</body>

</html>