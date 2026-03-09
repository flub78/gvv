<script>
// Auto-generate missing PDF thumbnails on page load
$(document).ready(function() {
    $('.pdf-needs-thumbnail').each(function() {
        var $span = $(this);
        var pdfPath = $span.data('pdf-path');
        if (!pdfPath) return;

        $.ajax({
            url: '<?= site_url('archived_documents/generate_thumbnail') ?>',
            type: 'POST',
            data: { file_path: pdfPath },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.thumbnail_url) {
                    $span.replaceWith('<img class="doc-thumbnail" src="' + response.thumbnail_url + '"/>');
                }
            }
        });
    });
});
</script>
