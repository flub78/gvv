<?php
/**
 * Reservations - FullCalendar v6 View
 *
 * Displays aircraft reservations calendar with FullCalendar v6.
 * Loads events from the reservations/get_events API endpoint.
 */
?>
<!DOCTYPE html>
<html lang="<?php echo $this->lang->line('lang'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->lang->line('reservations_title') ?: 'Aircraft Reservations'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FullCalendar v6 -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .calendar-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #007bff;
        }
        
        .calendar-header h1 {
            color: #333;
            font-size: 28px;
            margin: 0;
        }
        
        .calendar-header p {
            color: #666;
            margin: 5px 0 0 0;
        }
        
        #calendar {
            font-size: 14px;
        }
        
        .fc .fc-button-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .fc .fc-button-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        .fc .fc-button-primary.fc-button-active {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        /* Status colors */
        .fc-event[data-status="confirmed"] {
            background-color: #28A745 !important;
            border-color: #28A745 !important;
        }
        
        .fc-event[data-status="pending"] {
            background-color: #FFC107 !important;
            border-color: #FFC107 !important;
            color: #333 !important;
        }
        
        .fc-event[data-status="completed"] {
            background-color: #6C757D !important;
            border-color: #6C757D !important;
        }
        
        .fc-event[data-status="no_show"] {
            background-color: #E83E8C !important;
            border-color: #E83E8C !important;
        }
        
        /* Event info popup */
        .event-info-popup {
            display: none;
            position: fixed;
            background: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            min-width: 300px;
        }
        
        .event-info-popup.show {
            display: block;
        }
        
        .event-info-popup h5 {
            margin: 0 0 10px 0;
            color: #333;
            font-weight: bold;
        }
        
        .event-info-popup .info-row {
            margin: 8px 0;
            font-size: 13px;
        }
        
        .event-info-popup .info-label {
            font-weight: 600;
            color: #666;
            display: inline-block;
            min-width: 90px;
        }
        
        .event-info-popup .info-value {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .status-badge.pending {
            background-color: #FFC107;
            color: #333;
        }
        
        .status-badge.confirmed {
            background-color: #28A745;
        }
        
        .status-badge.completed {
            background-color: #6C757D;
        }
        
        .status-badge.no_show {
            background-color: #E83E8C;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="calendar-header">
            <h1><?php echo $this->lang->line('reservations_title') ?: 'Aircraft Reservations'; ?></h1>
            <p><?php echo $this->lang->line('reservations_description') ?: 'View and manage aircraft reservations'; ?></p>
        </div>
        
        <div id="calendar"></div>
    </div>
    
    <!-- Event Info Popup -->
    <div id="eventInfoPopup" class="event-info-popup">
        <h5 id="eventTitle"></h5>
        <div class="info-row">
            <span class="info-label"><?php echo $this->lang->line('aircraft') ?: 'Aircraft'; ?>:</span>
            <span class="info-value" id="eventAircraft"></span>
        </div>
        <div class="info-row">
            <span class="info-label"><?php echo $this->lang->line('pilot') ?: 'Pilot'; ?>:</span>
            <span class="info-value" id="eventPilot"></span>
        </div>
        <div class="info-row">
            <span class="info-label"><?php echo $this->lang->line('instructor') ?: 'Instructor'; ?>:</span>
            <span class="info-value" id="eventInstructor"></span>
        </div>
        <div class="info-row">
            <span class="info-label"><?php echo $this->lang->line('time') ?: 'Time'; ?>:</span>
            <span class="info-value" id="eventTime"></span>
        </div>
        <div class="info-row">
            <span class="info-label"><?php echo $this->lang->line('purpose') ?: 'Purpose'; ?>:</span>
            <span class="info-value" id="eventPurpose"></span>
        </div>
        <div class="info-row">
            <span class="info-label"><?php echo $this->lang->line('status') ?: 'Status'; ?>:</span>
            <span class="info-value" id="eventStatus"></span>
        </div>
        <div class="info-row" id="eventNotesRow" style="display:none;">
            <span class="info-label"><?php echo $this->lang->line('notes') ?: 'Notes'; ?>:</span>
            <span class="info-value" id="eventNotes"></span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                height: 'auto',
                contentHeight: 'auto',
                editable: false,
                selectable: true,
                eventDisplay: 'block',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                },
                slotLabelFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: false
                },
                locale: '<?php echo $this->lang->line('lang') ?: 'en'; ?>',
                events: {
                    url: '<?php echo base_url('reservations/get_events'); ?>',
                    failure: function() {
                        alert('<?php echo $this->lang->line('error_loading_events') ?: 'Error loading events'; ?>');
                    }
                },
                eventClick: function(info) {
                    showEventInfo(info.event);
                },
                dateClick: function(info) {
                    // Hide popup when clicking on empty date
                    document.getElementById('eventInfoPopup').classList.remove('show');
                }
            });
            
            calendar.render();
            
            // Close popup when clicking outside
            document.addEventListener('click', function(event) {
                var popup = document.getElementById('eventInfoPopup');
                if (!popup.contains(event.target) && event.target.className.indexOf('fc-event') === -1) {
                    popup.classList.remove('show');
                }
            });
        });
        
        function showEventInfo(event) {
            var popup = document.getElementById('eventInfoPopup');
            var props = event.extendedProps;
            
            document.getElementById('eventTitle').textContent = event.title;
            document.getElementById('eventAircraft').textContent = props.aircraft_model || '-';
            document.getElementById('eventPilot').textContent = props.pilot || '-';
            document.getElementById('eventInstructor').textContent = props.instructor || '-';
            document.getElementById('eventTime').textContent = formatDateTime(event.start) + ' - ' + formatDateTime(event.end);
            document.getElementById('eventPurpose').textContent = props.purpose || '-';
            
            var statusBadge = '<span class="status-badge ' + props.status + '">' + props.status.toUpperCase() + '</span>';
            document.getElementById('eventStatus').innerHTML = statusBadge;
            
            if (props.notes) {
                document.getElementById('eventNotesRow').style.display = 'block';
                document.getElementById('eventNotes').textContent = props.notes;
            } else {
                document.getElementById('eventNotesRow').style.display = 'none';
            }
            
            // Position popup at mouse location
            popup.style.left = event.jsEvent.pageX + 'px';
            popup.style.top = (event.jsEvent.pageY - 200) + 'px';
            popup.classList.add('show');
        }
        
        function formatDateTime(date) {
            if (!date) return '-';
            var options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleString('<?php echo $this->lang->line('lang') ?: 'en'; ?>', options);
        }
    </script>
</body>
</html>
