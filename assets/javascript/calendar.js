/*
 * Javascript associated to the calendar management
 */

var width = '80%';
var height = 360;

/*
 * Called on form validation
 */
function add_event() {
	var date_ajout = $("#date_ajout").val();
	var role = $("#role").val();
	var commentaire = $("#commentaire").val();

	var str = "date=" + date_ajout + ", role=" + role + ", commentaire="
			+ commentaire;
	console.log(str);

	var url = $("[name='base_url']").val() + 'index.php/presences/ajout/json';

	console.log('url=' + url);
	console.log("data=" + $('#event_add_form').serialize());
	
	$.ajax({
		type : "POST",
		url : url,
		data : $('#event_add_form').serialize(),
		success : function(code_html, statut) {
			$('#calendar').fullCalendar('refetchEvents');
			$('#calendar').fullCalendar('rerenderEvents');
		},

		error : function(resultat, statut, erreur) {
			alert("error");
		},

		complete : function(resultat, statut) {
		}
	});

	$("#calendar_form").dialog("close");
}

/*
 * Function activated when the user click on a day
 * 
 * Input of a new event
 */
function select_day(start, end) {

	$("#date_ajout").val(start.format('l'));
	$("#date_ajout").prop('disabled', true);
	$("[name='event_id']").val('');

	var gvv_user = $('[name="gvv_user"]').val();
	$("#mlogin").val(gvv_user);
	
	$("#commentaire").val("");

	$("#calendar_form").dialog({
		height : height,
		width : width,
		modal : true,
		draggable : true,
		resizable : false,

		buttons : [ {
			text : button_save,
			click : add_event
		}, {
			text : button_cancel,
			click : function() {
				$("#calendar_form").dialog("close");
			}
		} ]
	});

	$('#calendar_form:input').css('background-color', 'lemonchiffon')
	$('#calendar').fullCalendar('unselect');
	$("#date_ajout").prop('disabled', false);
}

function close_form() {
	$("#calendar_form").dialog("close");
}

/*
 * Display the calendar
 */
function renderCalendar() {
	
	var cal_id = $("[name='cal_id']").val();

	$('#calendar').fullCalendar({
		theme : true,
		header : {
			left : 'prev,next today',
			center : 'title',
			right : 'month,agendaWeek,agendaDay'
		},
		firstDay : 1,
//		height : 700,
//		width : 800,
		selectable : true,
		selectHelper : true,
		select : select_day,
		eventClick : event_click,
		editable : true,
		eventStartEditable : true,
		eventDurationEditable : true,
		eventDrop : event_drop,
		eventResize : event_resize,
		eventLimit : true, // allow "more" link when too many events
		googleCalendarApiKey : 'AIzaSyCxniEtFXxdtX6gcMr0I9RZEBH2f5Mpm5c',
		events : {
			googleCalendarId : cal_id
		}

	});

}

/*
 * Called when user confirm delete
 */
function confirmDelete() {
	var url = $("[name='base_url']").val() + 'index.php/presences/delete/'
			+ id + '/json';
	// id = 4v2lh776fmis7no9r43usl48uc
	// http://localhost/gvv2/index.php/presences/delete/4v2lh776fmis7no9r43usl48uc

	$.ajax({
		url : url,
		type : 'GET',
		success : function(code_html, statut) {
			$('#calendar').fullCalendar('refetchEvents');
			$('#calendar').fullCalendar('rerenderEvents');
		},

		error : function(resultat, statut, erreur) {
			alert("error");
		},

		complete : function(resultat, statut) {
		}
	});

	$("#calendar_form").dialog("close");
}

/*
 * Scan a select and return the value that matches a given text
 */
function value_from_text(selector, text) {
	var result = "";
	$(selector).each(function(i, elt) {
		var value = $(this).attr("value");
		var txt = $(this).text();
		console.log("i=" + i + ", value=" + value + ", text=" + txt);
		if (text == txt) {
			result = value;
//			break;
		}
	});
	return result;
}

/*
 * Activated when an event is selected
 * 
 * Modifications, delete or cancel
 */
function event_click(calEvent, jsEvent, view) {

	id = calEvent.id;

	console.log(calEvent);
	
	$("#date_ajout").val(calEvent.start.format('l'));
	$("[name='event_id']").val(id);
	
	var title = calEvent.title;
	var title_elts = title.split(",");
	
	var pilot_value;
	var role_value;
	if (title_elts.length < 1) {
		// no title, it is an error
		// scalar value
		pilot_value = '';
		role_value = '';
		$("#commentaire").val("");
	} else if (title_elts.length == 1) {
		// scalar value
		pilot_value = '';
		role_value = '';
		$("#commentaire").val(title_elts[0].trim());
	} else {
		pilot_value = value_from_text("#mlogin option", title_elts[0].trim());
		role_value = value_from_text("#role option", title_elts[1].trim());		
		$("#commentaire").val(calEvent.description);
	}
	console.log("membre='" + pilot_value + "', role='" + role_value  + "'");
	
	$("#mlogin").val(pilot_value);
	$("#role").val(role_value);

	$("#calendar_form").dialog({
		height : height,
		width : width,
		modal : true,
		draggable : true,
		resizable : false,
		title : title,

		buttons : [ {
			text : button_save,
			click : add_event
		}, {
//			text : button_change,
//			click : confirmDelete
//		}, {
			text : button_delete,
			click : confirmDelete
		}, {
			text : button_cancel,
			click : function() {
				$(this).dialog("close");
			}
		} ]
	});

	
	return false;
}

/*
 * Return a & separated key value sequence
 */
function serialize (event) {
	var str = "";
	
	str += "id=" + event.id;
	str += "&allDay=" + event.allDay;
	str += "&start=" + event.start.format();
	if (event.end) {
		str += "&end=" + event.end.format();
	}
	if (event.title) {
		str += "&title=" + encodeURIComponent(event.title);
	}
	if (event.description) {
		str += "&description=" + encodeURIComponent(event.description);
	}
	return str;
}

/*
 * Revert an error on illegal modification
 */
function revert_on_error (revertFunc, msg) {
	alert(msg);
	revertFunc();
	$('#calendar').fullCalendar('refetchEvents');
	$('#calendar').fullCalendar('rerenderEvents');	
}

/*
 * Update an existing event through an ajax request.
 * 
 * Return an empty string on success, or an error message on failure 
 */
function update_event (event, revertFunc) {

	var url = $("[name='base_url']").val() + 'index.php/presences/update';
	var result = "";
	var serialized_event = serialize(event);
	
	console.log("updating event: " + serialized_event)
	console.debug(event);
	
	$.ajax({
		type : "POST",
		url : url,
		data : serialized_event,
		success : function(code_html, statut) {
			
			var reply = JSON.parse(code_html);
			if (reply.status != "OK") {
				revert_on_error(revertFunc, reply.error);
			} else {
				console.log("update success");
			}
		},

		error : function(resultat, statut, erreur) {
			result = "update error " + event.title 
				+ ", resultat=" + resultat
				+ ", statut=" + statut 
				+ ", erreur" + erreur;
			revert_on_error(revertFunc, result);
		},

		complete : function(resultat, statut) {
		}
	});
}

/*
 * Activated when an event is dropped
 * 
 */
function event_drop(event, delta, revertFunc, jsEvent, ui, view) {
	update_event(event, revertFunc);		
}

function event_resize(event, delta, revertFunc) {
	update_event(event, revertFunc);
}

/*
 * Called when MOD is closed
 */
function close_mod() {

	if ($("#no_mod").prop('checked')) {

		var url = $("[name='base_url']").val()
				+ 'index.php/calendar/set_cookie/';

		$.ajax({
			url : url,
			type : 'GET',
			success : function(code_html, statut) {
			},

			error : function(resultat, statut, erreur) {
				alert("error setting no_mod cookie");
			},

			complete : function(resultat, statut) {
			}
		});

	}
	$(this).dialog("close");
}

$(document).ready(function() {
	renderCalendar();
	
	$("#mod").dialog({
		width: '90%',
		modal : true,
		title : $('[name="mod_title"]').val(),
		draggable : true,
		resizable : true,
		buttons : [ {
			text : "OK",
			id : "close_mod_dialog",
			click : close_mod
		}]

	});

});
