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
            "iDisplayLength": 25,
            "bStateSave": false,
            "bSort": false,
            "bInfo": true,
            "bJQueryUI": true,
            "bRetrieve": false,
            "bAutoWidth": true,
            "sPaginationType": "full_numbers",
            "search": {
                "caseInsensitive": true
            },
            "oLanguage": olanguage
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

        // to replace the select by an input that select values in the dropdown
        $('.big_select').select2({
            placeholder: 'Filtre...',
            width: '300px',
            allowClear: false
        });

    });
    //
    -->
</script>

</body>

</html>