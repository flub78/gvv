<?php

# https://github.com/blueimp/jQuery-File-Upload

$this->load->view('header');

echo $error; ?>

<?php echo form_open_multipart('tests/do_upload'); ?>

<input type="file" name="userfile" size="20" />
<br /><br />

<input type="submit" value="upload" />
<input type="submit" value="Cancel" />

</form>
<form action="/upload_article_image" method="POST" enctype="multipart/form-data">
    <input type="file" name="article_image" accept="image/*" capture="camera">
    <button type="submit">Upload Photo</button>
</form>

<div class="progress">
    <div class="bar"></div>
    <div class="percent">0%</div>
</div>

<div id="status"></div></br></br>

<!--  script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script-->
<script src="http://malsup.github.com/jquery.form.js"></script>
<script>
    (function() {

        var bar = $('.bar');
        var percent = $('.percent');
        var status = $('#status');

        $('form').ajaxForm({

            beforeSend: function() {
                status.empty();
                var percentVal = '0%';
                bar.width(percentVal)
                percent.html(percentVal);
            },

            uploadProgress: function(event, position, total, percentComplete) {
                var percentVal = percentComplete + '%';
                bar.width(percentVal)
                percent.html(percentVal);
                //console.log(percentVal, position, total);
            },

            success: function() {
                var percentVal = '100%';
                bar.width(percentVal)
                percent.html(percentVal);
            },

            complete: function(xhr) {
                status.html(xhr.responseText);
            }
        });

    })();
</script>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript"></script>
<script type="text/javascript">
    _uacct = "UA-850242-2";
    urchinTracker();
</script>

</body>

</html>