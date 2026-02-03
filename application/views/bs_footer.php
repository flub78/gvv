<!-- VIEW: application/views/bs_footer.php -->
<footer class="container-fluid p-3 mt-3 bg-success text-white text-center">
    <p>
        <?= $this->lang->line("gvv_copyright") ?>
        <?= $this->lang->line("gvv_generated") ?>
    </p>
</footer>

<script type="text/javascript">
    <!--
    $(document).ready(function() {
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
            "bStateSave": true,  // Sauvegarde l'état (pagination, recherche) dans localStorage
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
            "bJQueryUI": true,
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
    // Generate thumbnails for PDFs that don't have one yet
    $(document).ready(function() {
        var pdfElements = $('.pdf-needs-thumbnail');
        if (pdfElements.length === 0) return;

        // Process PDFs one at a time to avoid overwhelming the server
        var processQueue = [];
        pdfElements.each(function() {
            processQueue.push($(this));
        });

        function processNextPdf() {
            if (processQueue.length === 0) return;

            var $element = processQueue.shift();
            var pdfPath = $element.data('pdf-path');
            var elementId = $element.attr('id');

            $.ajax({
                url: '<?= site_url("attachments/generate_thumbnail") ?>',
                type: 'POST',
                data: { file_path: pdfPath },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.thumbnail_url) {
                        // Replace icon with thumbnail image
                        var $link = $element.parent('a');
                        var href = $link.attr('href');
                        var img = '<img class="doc-thumbnail" src="' + response.thumbnail_url + '" title="' + pdfPath + '"/>';
                        $element.replaceWith(img);
                    }
                    // Process next PDF after a small delay
                    setTimeout(processNextPdf, 100);
                },
                error: function() {
                    // Keep the icon, process next PDF
                    setTimeout(processNextPdf, 100);
                }
            });
        }

        // Start processing after page load with a small delay
        setTimeout(processNextPdf, 500);
    });
    //
    -->
</script>

</body>

</html>