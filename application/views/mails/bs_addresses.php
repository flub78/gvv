<!-- VIEW: application/views/mails/bs_addresses.php -->
<?php

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
 * Sélecteur d'adresses email - Interface pour copier les adresses ou lancer un client email
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('mails');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("mail_addresses_title", 3);

?>

<form id="addressForm" method="post">
    <div class="form-group row">
        <label for="selection" class="col-sm-3 col-form-label"><?= $this->lang->line('mail_selection_label') ?>:</label>
        <div class="col-sm-6">
            <select name="selection" id="selection" class="form-control">
                <option value="" <?= (!isset($selected_list) || $selected_list === '') ? 'selected' : '' ?>><?= $this->lang->line('mail_select_list') ?></option>
                <?php if (isset($selection)): ?>
                    <?php foreach ($selection as $key => $label): ?>
                        <option value="<?= $key ?>" <?= (isset($selected_list) && $selected_list !== '' && $selected_list == $key) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>    <div class="form-group row">
        <label for="addresses" class="col-sm-3 col-form-label"><?= $this->lang->line('mail_addresses_label') ?>:</label>
        <div class="col-sm-9">
            <textarea id="addresses" name="addresses" class="form-control" rows="6" readonly 
                placeholder="<?= $this->lang->line('mail_addresses_placeholder') ?>"><?= isset($email_addresses) ? $email_addresses : '' ?></textarea>
            <small class="form-text text-muted">
                <span id="addressCount">0</span> <?= $this->lang->line('mail_addresses_count') ?>
            </small>
        </div>
    </div>
    
    <div class="form-group row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">
            <button type="button" id="copyButton" class="btn btn-secondary" disabled>
                <i class="fa fa-copy"></i> <?= $this->lang->line('mail_copy_addresses') ?>
            </button>
        </div>
    </div>
    
    <hr>
    
    <div class="form-group row">
        <label for="subject" class="col-sm-3 col-form-label"><?= $this->lang->line('mail_subject_label') ?>:</label>
        <div class="col-sm-6">
            <input type="text" id="subject" name="subject" class="form-control" value="<?= isset($subject) ? $subject : '' ?>">
        </div>
    </div>
    
    <div class="form-group row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">
            <div class="form-check">
                <input type="checkbox" id="sendToSelf" name="send_to_self" class="form-check-input" <?= (isset($send_to_self) && $send_to_self) ? 'checked' : '' ?>>
                <label for="sendToSelf" class="form-check-label"><?= $this->lang->line('mail_send_to_self') ?></label>
                <small class="form-text text-muted"><?= $this->lang->line('mail_send_to_self_help') ?></small>
            </div>
        </div>
    </div>
    
    <div class="form-group row">
        <div class="col-sm-3"></div>
        <div class="col-sm-9">
            <button type="button" id="mailtoButton" class="btn btn-primary" disabled>
                <i class="fa fa-envelope"></i> <?= $this->lang->line('mail_launch_client') ?>
            </button>
        </div>
    </div>
</form>

<?php
echo '</div>';
?>

<script>
$(document).ready(function() {
    // Handle selection change
    $('#selection').change(function() {
        var selection = $(this).val();
        if (selection) {
            $.post('<?= controller_url("mails/ajax_get_addresses") ?>', {
                selection: selection
            }, function(data) {
                $('#addresses').val(data.addresses);
                $('#addressCount').text(data.count);
                updateButtons();
            }, 'json');
        } else {
            $('#addresses').val('');
            $('#addressCount').text('0');
            updateButtons();
        }
    });
    
    // Update button states
    function updateButtons() {
        var hasAddresses = $('#addresses').val().trim() !== '';
        $('#copyButton').prop('disabled', !hasAddresses);
        $('#mailtoButton').prop('disabled', !hasAddresses);
    }
    
    // Copy to clipboard
    $('#copyButton').click(function() {
        var addresses = $('#addresses').val();
        var button = $(this);
        if (addresses) {
            // Try modern clipboard API first, fallback to older method
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(addresses).then(function() {
                    showCopySuccess(button);
                }).catch(function() {
                    fallbackCopyTextToClipboard(addresses, button);
                });
            } else {
                fallbackCopyTextToClipboard(addresses, button);
            }
        }
    });
    
    // Fallback copy method for older browsers or non-HTTPS
    function fallbackCopyTextToClipboard(text, button) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(button);
            } else {
                showCopyError(button);
            }
        } catch (err) {
            showCopyError(button);
        }
        
        document.body.removeChild(textArea);
    }
    
    // Show success message
    function showCopySuccess(button) {
        button.html('<i class="fa fa-check"></i> <?= $this->lang->line('mail_copied') ?>');
        button.removeClass('btn-secondary').addClass('btn-success');
        setTimeout(function() {
            button.html('<i class="fa fa-copy"></i> <?= $this->lang->line('mail_copy_addresses') ?>');
            button.removeClass('btn-success').addClass('btn-secondary');
        }, 2000);
    }
    
    // Show error message
    function showCopyError(button) {
        button.html('<i class="fa fa-exclamation-triangle"></i> <?= $this->lang->line('mail_copy_error') ?>');
        button.removeClass('btn-secondary').addClass('btn-danger');
        setTimeout(function() {
            button.html('<i class="fa fa-copy"></i> <?= $this->lang->line('mail_copy_addresses') ?>');
            button.removeClass('btn-danger').addClass('btn-secondary');
        }, 2000);
    }
    
    // Launch mailto
    $('#mailtoButton').click(function() {
        var addresses = $('#addresses').val();
        var subject = $('#subject').val();
        var sendToSelf = $('#sendToSelf').is(':checked');
        
        if (addresses) {
            var mailtoUrl = 'mailto:';
            if (sendToSelf) {
                // Send to self with addresses as BCC
                mailtoUrl += '?bcc=' + encodeURIComponent(addresses);
            } else {
                // Send to addresses directly
                mailtoUrl += encodeURIComponent(addresses);
            }
            
            if (subject) {
                mailtoUrl += (sendToSelf ? '&' : '?') + 'subject=' + encodeURIComponent(subject);
            }
            
            window.location.href = mailtoUrl;
        }
    });
    
    // Initialize button states
    updateButtons();
});
</script>
