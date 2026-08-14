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
 *    along with this program.  If not, see <http: *www.gnu.org/licenses/>.
 *
 * @package javascript
 *
 * Fonctions Javascript du formulaire "Messages du jour" (MOTD)
 */

/**
 * target_type a changé : n'affiche que le sélecteur pertinent (liste ou
 * utilisateur) et efface la valeur de l'autre pour ne pas soumettre une
 * cible obsolète et cachée.
 */
function motd_target_changed() {
	var target_type = $('input[name=target_type]:checked').val();

	if (target_type == 'list') {
		$('#motd_target_list_wrapper').show();
		$('#motd_target_user_wrapper').hide();
		$('#target_user_login').val('');
	} else if (target_type == 'user') {
		$('#motd_target_list_wrapper').hide();
		$('#motd_target_user_wrapper').show();
		$('#target_list_id').val('');
	} else {
		$('#motd_target_list_wrapper').hide();
		$('#motd_target_user_wrapper').hide();
		$('#target_list_id').val('');
		$('#target_user_login').val('');
	}
}

/**
 * Insère du texte à la position du curseur dans un textarea.
 */
function motd_insert_at_cursor(textarea, text) {
	if (textarea.selectionStart || textarea.selectionStart === 0) {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
		textarea.selectionStart = textarea.selectionEnd = start + text.length;
	} else {
		textarea.value += text;
	}
	textarea.focus();
}

/**
 * Câble le bouton d'upload d'image : envoie le fichier sélectionné, puis
 * insère la référence Markdown ![alt](url) dans le textarea #content.
 */
function motd_init_image_upload(uploadUrl, errorFallback) {
	$('#motd_image_insert').click(function() {
		var input = document.getElementById('motd_image_file');
		var msg = $('#motd_image_upload_message');
		msg.text('');

		if (!input.files || !input.files[0]) {
			return;
		}

		var data = new FormData();
		data.append('image_file', input.files[0]);

		$.ajax({
			url: uploadUrl,
			type: 'POST',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response && response.url) {
					motd_insert_at_cursor(document.getElementById('content'), '![](' + response.url + ')');
					input.value = '';
				} else if (response && response.error) {
					msg.text(response.error);
				}
			},
			error: function(xhr) {
				var response = xhr.responseJSON;
				msg.text(response && response.error ? response.error : errorFallback);
			}
		});
	});
}

/**
 * Câble la section repliable "Messages du jour" du dashboard : persiste
 * l'état replié/déplié et le critère de tri choisis par l'utilisateur.
 */
function motd_init_dashboard_section(toggleUrl, sortUrl) {
	var $section = $('#motdSectionBody');
	if ($section.length) {
		$section.on('shown.bs.collapse hidden.bs.collapse', function(e) {
			// jQuery exposes the bare event name here (e.g. "hidden"), not the
			// full "hidden.bs.collapse" string used to bind the handler.
			$.post(toggleUrl, { collapsed: e.type === 'hidden' ? 1 : 0 });
		});
	}

	$('#motdSortSelect').on('change', function() {
		$.post(sortUrl, { sort_by: $(this).val() }, function() {
			location.reload();
		});
	});
}

/**
 * Câble les actions utilisateur sur chaque message : masquer, masquer tous,
 * pris connaissance, répondre (et répondre à une réponse, admin seulement).
 *
 * @param opts { hideUrl, hideAllUrl, unhideAllUrl, toggleUrl, ackUrl, replyUrl, errorFallback, ackBadgeLabel, repliesTitle, activeCountLabel }
 */
function motd_init_dashboard_actions(opts) {
	function showError($container, message) {
		$container.find('.motd-action-error').first().text(message || opts.errorFallback);
	}

	// Le badge "non lus" du bandeau doit rester exact après un
	// masquage/acquittement sans recharger la page.
	function decrementUnreadBadge() {
		var $badge = $('#motdSectionUnreadBadge');
		if (!$badge.length) {
			return;
		}
		var next = (parseInt($badge.text(), 10) || 0) - 1;
		if (next <= 0) {
			$badge.remove();
		} else {
			$badge.text(next);
		}
	}

	// Le badge du bouton "Afficher tous les messages" doit lui aussi rester
	// exact après un masquage individuel sans recharger la page.
	function incrementHiddenBadge() {
		var $badge = $('#motdHiddenCountBadge');
		if ($badge.length) {
			$badge.text((parseInt($badge.text(), 10) || 0) + 1);
			return;
		}
		$('#motdShowHiddenBtn').append(
			$('<span class="badge bg-secondary" id="motdHiddenCountBadge"></span>').text(1)
		);
	}

	$('#motdHideAllBtn').on('click', function() {
		$.post(opts.hideAllUrl, {}, function() {
			location.reload();
		}).fail(function() {
			alert(opts.errorFallback);
		});
	});

	$('#motdShowHiddenBtn').on('click', function() {
		// Affiche aussi les messages masques que deplie la section, pour que
		// l'utilisateur les voie immediatement sans devoir cliquer l'accordeon.
		$.when(
			$.post(opts.unhideAllUrl, {}),
			$.post(opts.toggleUrl, { collapsed: 0 })
		).done(function() {
			location.reload();
		}).fail(function() {
			alert(opts.errorFallback);
		});
	});

	$(document).on('click', '.motd-hide-btn', function() {
		var $btn = $(this);
		var messageId = $btn.data('message-id');
		var $item = $btn.closest('.accordion-item');
		var $actions = $btn.closest('.motd-message-actions');

		$.post(opts.hideUrl + '/' + messageId, {})
			.done(function(response) {
				if (!response || !response.success) {
					showError($actions, opts.errorFallback);
					return;
				}
				incrementHiddenBadge();
				var wasUnread = $item.data('unread') == 1;
				var $accordion = $item.closest('.accordion');
				$item.fadeOut(200, function() {
					$(this).remove();
					var remaining = $accordion.find('.accordion-item').length;
					if (remaining === 0) {
						// Recharge pour afficher l'etat "aucun message actif" rendu
						// cote serveur (texte + bouton "Afficher tous les messages").
						location.reload();
						return;
					}
					var $activeCount = $('#motdSectionActiveCount');
					if ($activeCount.length && opts.activeCountLabel) {
						$activeCount.text(opts.activeCountLabel.replace('%d', remaining));
					}
				});
				if (wasUnread) {
					decrementUnreadBadge();
				}
			})
			.fail(function(xhr) {
				var response = xhr.responseJSON;
				showError($actions, response && response.error ? response.error : opts.errorFallback);
			});
	});

	$(document).on('click', '.motd-ack-btn', function() {
		var $btn = $(this);
		var messageId = $btn.data('message-id');
		var $actions = $btn.closest('.motd-message-actions');

		$.post(opts.ackUrl + '/' + messageId, {})
			.done(function(response) {
				if (!response || !response.success) {
					showError($actions, opts.errorFallback);
					return;
				}
				var $badge = $('<span class="badge bg-success"><i class="fas fa-check" aria-hidden="true"></i> </span>');
				$badge.append(document.createTextNode(opts.ackBadgeLabel));
				$btn.replaceWith($badge);
				$btn.closest('.accordion-item').data('unread', 0);
				decrementUnreadBadge();
			})
			.fail(function() {
				showError($actions, opts.errorFallback);
			});
	});

	$(document).on('click', '.motd-reply-to-btn', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var messageId = $btn.data('message-id');
		var replyId = $btn.data('reply-id');
		var author = $btn.data('author');
		var $form = $('.motd-reply-form[data-message-id="' + messageId + '"]');

		$form.data('parent-reply-id', replyId);
		$form.find('.motd-reply-replying-to-author').text(author);
		$form.find('.motd-reply-replying-to').show();
		$form.find('.motd-reply-textarea').focus();
	});

	$(document).on('click', '.motd-reply-cancel', function(e) {
		e.preventDefault();
		var $form = $(this).closest('.motd-reply-form');
		$form.removeData('parent-reply-id');
		$form.find('.motd-reply-replying-to').hide();
	});

	$(document).on('click', '.motd-reply-submit-btn', function() {
		var $btn = $(this);
		var messageId = $btn.data('message-id');
		var $form = $btn.closest('.motd-reply-form');
		var $textarea = $form.find('.motd-reply-textarea');
		var $actions = $form.closest('.accordion-body').find('.motd-message-actions');
		var content = $textarea.val().trim();
		var parentReplyId = $form.data('parent-reply-id');

		if (!content) {
			return;
		}

		$.post(opts.replyUrl + '/' + messageId, { content: content, parent_reply_id: parentReplyId || '' })
			.done(function(response) {
				if (!response || !response.success) {
					showError($actions, response && response.error ? response.error : opts.errorFallback);
					return;
				}
				var reply = response.reply;
				var $repliesList = $('#motdReplies' + messageId);
				if ($repliesList.find('h6').length === 0) {
					$repliesList.append($('<hr>'));
					$repliesList.append($('<h6></h6>').text(opts.repliesTitle));
				}
				var $block = $('<div class="border rounded p-2 mb-2 bg-light"></div>').attr('id', 'motdReply' + reply.id);
				var $head = $('<div class="d-flex justify-content-between"></div>');
				$head.append($('<strong></strong>').text(reply.author_login));
				$head.append($('<small class="text-muted"></small>').text(reply.created_at));
				$block.append($head);
				$block.append($('<div class="markdown-content"></div>').html(reply.content_html));
				$repliesList.append($block);

				$textarea.val('');
				$form.removeData('parent-reply-id');
				$form.find('.motd-reply-replying-to').hide();
			})
			.fail(function(xhr) {
				var response = xhr.responseJSON;
				showError($actions, response && response.error ? response.error : opts.errorFallback);
			});
	});
}
