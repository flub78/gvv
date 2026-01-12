<!-- VIEW: application/views/reservations/bs_reservations_v6.php -->
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
 * Aircraft Reservations - FullCalendar v6
 */

$this->load->view('bs_header', array('new_layout' => true));
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-9">
            <h2>Aircraft Reservations - FullCalendar v6</h2>
            <div id='calendar' style="height: 100%"></div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>FullCalendar Events Log</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    <div id="event-log">
                        <p class="text-muted">Waiting for events...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reservation Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer" id="eventModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelEventBtn">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveEventBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar v6 Standard Bundle -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js"></script>

<?php
// Map CodeIgniter language to FullCalendar locale
$ci_language = $this->config->item('language');
$locale_map = array(
    'french' => 'fr',
    'english' => 'en',
    'dutch' => 'nl'
);
$fullcalendar_locale = isset($locale_map[$ci_language]) ? $locale_map[$ci_language] : 'en';
?>

<!-- FullCalendar Locale Scripts -->
<?php if ($fullcalendar_locale !== 'en'): ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/locales/<?= $fullcalendar_locale ?>.global.min.js"></script>
<?php endif; ?>

<style>
    #calendar {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        font-size: 14px;
    }
    
    .event-log-item {
        padding: 8px;
        margin-bottom: 8px;
        border-left: 3px solid #007bff;
        background-color: #f8f9fa;
        border-radius: 3px;
        font-size: 12px;
        font-family: monospace;
    }
    
    .event-log-item.error {
        border-left-color: #dc3545;
        background-color: #f8d7da;
    }
    
    .event-log-item.warning {
        border-left-color: #ffc107;
        background-color: #fff3cd;
    }
    
    .event-log-item.success {
        border-left-color: #28a745;
        background-color: #d4edda;
    }
    
    .log-timestamp {
        color: #666;
        font-weight: bold;
    }
    
    .log-content {
        margin-top: 2px;
    }
</style>

<script>
    // Data from PHP
    const OPTIONS = {
        aircraft: <?php echo json_encode($aircraft_list); ?>,
        pilots: <?php echo json_encode($pilots_list); ?>,
        instructors: <?php echo json_encode($instructors_list); ?>
    };

    const TRANSLATIONS = <?php echo json_encode($translations); ?>;
    const BASE_URL = '<?php echo base_url(); ?>';

    let eventLog = [];
    const MAX_LOG_ENTRIES = 50;
    let currentEditingEvent = null;
    
    /**
     * Add an event to the log display
     */
    function addLog(category, message, data = null, type = 'info') {
        const timestamp = new Date().toLocaleTimeString('fr-FR', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        const logEntry = {
            timestamp,
            category,
            message,
            data,
            type
        };
        
        eventLog.unshift(logEntry);
        if (eventLog.length > MAX_LOG_ENTRIES) {
            eventLog.pop();
        }
        
        renderLog();
        
        // Also log to console
        console.log(`[${timestamp}] [${category}] ${message}`, data);
    }
    
    /**
     * Render the event log to the UI
     */
    function renderLog() {
        const logContainer = document.getElementById('event-log');
        logContainer.innerHTML = '';
        
        eventLog.forEach(entry => {
            const div = document.createElement('div');
            div.className = `event-log-item ${entry.type}`;
            
            let html = `<div class="log-timestamp">${entry.timestamp}</div>`;
            html += `<div class="log-content"><strong>[${entry.category}]</strong> ${entry.message}`;
            
            if (entry.data) {
                html += `<pre>${JSON.stringify(entry.data, null, 2)}</pre>`;
            }
            
            html += '</div>';
            div.innerHTML = html;
            logContainer.appendChild(div);
        });
    }

    /**
     * Display modal for creating or editing a reservation
     */
    function displayEventModal(event, startDate = null, endDate = null) {
        try {
            const modalEl = document.getElementById('eventModal');
            if (!modalEl) {
                throw new Error('Modal element not found in DOM');
            }

            const modal = new bootstrap.Modal(modalEl);
            const titleEl = document.getElementById('eventModalTitle');
            const bodyEl = document.getElementById('eventModalBody');
            const saveBtn = document.getElementById('saveEventBtn');
            const cancelBtn = document.getElementById('cancelEventBtn');

            if (!titleEl || !bodyEl) {
                throw new Error('Modal elements not found');
            }

            // Set title based on create vs edit mode
            const isCreate = !event || event.id === null || event.id === undefined;
            titleEl.textContent = isCreate ? TRANSLATIONS.modal_new : TRANSLATIONS.modal_edit;
            saveBtn.textContent = isCreate ? TRANSLATIONS.btn_create : TRANSLATIONS.btn_save;
            cancelBtn.textContent = TRANSLATIONS.btn_cancel;

            // Store current editing event
            currentEditingEvent = event;

            // Prepare dates
            let startStr = '';
            let endStr = '';

            if (isCreate && startDate && endDate) {
                // Creating new event from select
                startStr = formatDateTimeLocal(startDate);
                endStr = formatDateTimeLocal(endDate);
            } else if (event) {
                // Editing existing event
                if (event.start instanceof Date) {
                    startStr = formatDateTimeLocal(event.start);
                } else if (typeof event.start === 'string') {
                    startStr = event.start.slice(0, 16);
                }

                if (event.end instanceof Date) {
                    endStr = formatDateTimeLocal(event.end);
                } else if (typeof event.end === 'string') {
                    endStr = event.end.slice(0, 16);
                }
            }

            const props = (event && event.extendedProps) ? event.extendedProps : {};
            const aircraftId = props.aircraft_id || props.aircraft || '';
            const pilotId = props.pilot_member_id || '';
            const instructorId = props.instructor_member_id || '';
            const purpose = props.purpose || '';
            const notes = props.notes || '';
            const status = props.status || 'confirmed';

            // Build aircraft select
            let aircraftSelect = '<select class="form-control" id="eventAircraft">';
            aircraftSelect += `<option value="">${TRANSLATIONS.select_aircraft}</option>`;
            if (OPTIONS.aircraft) {
                for (const [id, label] of Object.entries(OPTIONS.aircraft)) {
                    const selected = String(id) === String(aircraftId) ? 'selected' : '';
                    aircraftSelect += `<option value="${id}" ${selected}>${escapeHtml(label)}</option>`;
                }
            }
            aircraftSelect += '</select>';

            // Build pilot select
            let pilotSelect = '<select class="form-control" id="eventPilot">';
            pilotSelect += `<option value="">${TRANSLATIONS.select_pilot}</option>`;
            if (OPTIONS.pilots) {
                for (const [id, label] of Object.entries(OPTIONS.pilots)) {
                    const selected = String(id) === String(pilotId) ? 'selected' : '';
                    pilotSelect += `<option value="${id}" ${selected}>${escapeHtml(label)}</option>`;
                }
            }
            pilotSelect += '</select>';

            // Build instructor select
            let instructorSelect = '<select class="form-control" id="eventInstructor">';
            instructorSelect += `<option value="">${TRANSLATIONS.select_instructor_none}</option>`;
            if (OPTIONS.instructors) {
                for (const [id, label] of Object.entries(OPTIONS.instructors)) {
                    const selected = String(id) === String(instructorId) ? 'selected' : '';
                    instructorSelect += `<option value="${id}" ${selected}>${escapeHtml(label)}</option>`;
                }
            }
            instructorSelect += '</select>';

            // Build form HTML
            const formHtml = `<form id="eventEditForm">
                <div class="mb-3">
                    <label for="eventAircraft" class="form-label"><strong>${TRANSLATIONS.form_aircraft}:</strong></label>
                    ${aircraftSelect}
                </div>

                <div class="mb-3">
                    <label for="eventPilot" class="form-label"><strong>${TRANSLATIONS.form_pilot}:</strong></label>
                    ${pilotSelect}
                </div>

                <div class="mb-3">
                    <label for="eventInstructor" class="form-label"><strong>${TRANSLATIONS.form_instructor}:</strong> <span class="text-muted">${TRANSLATIONS.form_instructor_optional}</span></label>
                    ${instructorSelect}
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="eventStart" class="form-label"><strong>${TRANSLATIONS.form_start_time}:</strong></label>
                        <input type="datetime-local" class="form-control" id="eventStart" value="${startStr}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="eventEnd" class="form-label"><strong>${TRANSLATIONS.form_end_time}:</strong></label>
                        <input type="datetime-local" class="form-control" id="eventEnd" value="${endStr}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="eventPurpose" class="form-label"><strong>${TRANSLATIONS.form_purpose}:</strong></label>
                    <input type="text" class="form-control" id="eventPurpose" value="${escapeHtml(purpose)}">
                </div>

                <div class="mb-3">
                    <label for="eventNotes" class="form-label"><strong>${TRANSLATIONS.form_notes}:</strong></label>
                    <textarea class="form-control" id="eventNotes" rows="2">${escapeHtml(notes)}</textarea>
                </div>

                <div class="mb-3">
                    <label for="eventStatus" class="form-label"><strong>${TRANSLATIONS.form_status}:</strong></label>
                    <select class="form-control" id="eventStatus">
                        <option value="confirmed" ${status === 'confirmed' ? 'selected' : ''}>${TRANSLATIONS.status_confirmed}</option>
                        <option value="pending" ${status === 'pending' ? 'selected' : ''}>${TRANSLATIONS.status_pending}</option>
                        <option value="completed" ${status === 'completed' ? 'selected' : ''}>${TRANSLATIONS.status_completed}</option>
                        <option value="no_show" ${status === 'no_show' ? 'selected' : ''}>${TRANSLATIONS.status_no_show}</option>
                    </select>
                </div>
            </form>`;

            bodyEl.innerHTML = formHtml;
            modal.show();
        } catch (error) {
            console.error('Error in displayEventModal:', error);
            alert('Error displaying reservation form: ' + error.message);
        }
    }

    /**
     * Save event changes to server
     */
    function saveEventChanges(calendar) {
        const startStr = document.getElementById('eventStart').value.replace('T', ' ') + ':00';
        const endStr = document.getElementById('eventEnd').value.replace('T', ' ') + ':00';
        const purpose = document.getElementById('eventPurpose').value;
        const notes = document.getElementById('eventNotes').value;
        const status = document.getElementById('eventStatus').value;
        const aircraftId = document.getElementById('eventAircraft').value;
        const pilotId = document.getElementById('eventPilot').value;
        const instructorId = document.getElementById('eventInstructor').value;

        // Validation
        if (!aircraftId) {
            alert(TRANSLATIONS.error_no_aircraft);
            return;
        }
        if (!pilotId) {
            alert(TRANSLATIONS.error_no_pilot);
            return;
        }

        const isCreate = !currentEditingEvent || currentEditingEvent.id === null || currentEditingEvent.id === undefined;

        // Build request body
        let requestBody = 'start_datetime=' + encodeURIComponent(startStr) +
                         '&end_datetime=' + encodeURIComponent(endStr) +
                         '&purpose=' + encodeURIComponent(purpose) +
                         '&notes=' + encodeURIComponent(notes) +
                         '&status=' + encodeURIComponent(status) +
                         '&aircraft_id=' + encodeURIComponent(aircraftId) +
                         '&pilot_member_id=' + encodeURIComponent(pilotId) +
                         '&instructor_member_id=' + encodeURIComponent(instructorId);

        if (!isCreate) {
            requestBody = 'reservation_id=' + currentEditingEvent.id + '&' + requestBody;
        }

        // Send to server
        fetch(BASE_URL + 'index.php/reservations/update_reservation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addLog('SAVE', isCreate ? 'Reservation created successfully' : 'Reservation updated successfully', null, 'success');
                // Close modal and reload calendar
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                calendar.refetchEvents();
                // Show success message
                alert(TRANSLATIONS.success_saved);
            } else {
                addLog('SAVE', 'Failed to save reservation', { error: data.error }, 'error');
                alert(TRANSLATIONS.error_prefix + ': ' + (data.error || TRANSLATIONS.error_unknown));
            }
        })
        .catch(error => {
            console.error('Error saving reservation:', error);
            addLog('SAVE', 'Error saving reservation', { error: error.message }, 'error');
            alert(TRANSLATIONS.error_saving + ': ' + error.message);
        });
    }

    /**
     * Format Date to datetime-local input format
     */
    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Check Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded!');
            alert('Error: Bootstrap is not loaded. Cannot create reservations.');
            return;
        }

        addLog('INIT', 'Initializing FullCalendar v6');
        
        // Button text translations based on locale
        var buttonTextMap = {
            'fr': {
                today: 'Aujourd\'hui',
                month: 'Mois',
                week: 'Semaine',
                day: 'Jour',
                list: 'Liste'
            },
            'nl': {
                today: 'Vandaag',
                month: 'Maand',
                week: 'Week',
                day: 'Dag',
                list: 'Lijst'
            },
            'en': {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day',
                list: 'List'
            }
        };
        
        var buttonTexts = buttonTextMap['<?= $fullcalendar_locale ?>'] || buttonTextMap['en'];
        
        // Get saved view from localStorage, default to dayGridMonth
        var savedView = localStorage.getItem('reservationsCalendarView') || 'dayGridMonth';
        
        // Get timeline increment from config (default 15 minutes)
        var timelineIncrement = <?php echo isset($timeline_increment) ? $timeline_increment : 15; ?>;
        
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: '<?= $fullcalendar_locale ?>',
            initialView: savedView,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: buttonTexts.today,
                month: buttonTexts.month,
                week: buttonTexts.week,
                day: buttonTexts.day,
                list: buttonTexts.list
            },
            height: 'auto',
            contentHeight: 'auto',
            slotDuration: '00:' + String(timelineIncrement).padStart(2, '0') + ':00',
            snapDuration: '00:' + String(timelineIncrement).padStart(2, '0') + ':00',
            slotMinTime: '06:00:00',
            events: {
                url: '<?= base_url('index.php/reservations/get_events') ?>',
                failure: function() {
                    addLog('EVENTS', 'Error loading events from server', null, 'error');
                }
            },
            editable: true,
            selectable: true,
            selectConstraint: 'businessHours',
            eventClick: function(info) {
                addLog('EVENT_CLICK', 'Event clicked', {
                    eventId: info.event.id,
                    title: info.event.title,
                    start: info.event.start,
                    end: info.event.end,
                    extendedProps: info.event.extendedProps
                }, 'info');

                // Open edit modal
                try {
                    displayEventModal(info.event);
                } catch (error) {
                    console.error('Error in displayEventModal:', error);
                    alert('Error opening modal: ' + error.message);
                }
            },
            eventMouseEnter: function(info) {
                addLog('EVENT_MOUSE_ENTER', 'Mouse over event', {
                    eventId: info.event.id,
                    title: info.event.title
                }, 'info');
            },
            eventMouseLeave: function(info) {
                addLog('EVENT_MOUSE_LEAVE', 'Mouse left event', {
                    eventId: info.event.id,
                    title: info.event.title
                }, 'info');
            },
            select: function(info) {
                addLog('SELECT', 'Date/time range selected', {
                    start: info.start,
                    end: info.end,
                    allDay: info.allDay,
                    jsEvent: {
                        type: info.jsEvent.type,
                        button: info.jsEvent.button
                    }
                }, 'success');

                // Open create modal with selected time range
                try {
                    displayEventModal(null, info.start, info.end);
                } catch (error) {
                    console.error('Error in displayEventModal:', error);
                    alert('Error opening modal: ' + error.message);
                }

                // Deselect after showing the modal
                calendar.unselect();
            },
            dateClick: function(info) {
                addLog('DATE_CLICK', 'Date clicked', {
                    date: info.date,
                    allDay: info.allDay,
                    jsEvent: {
                        type: info.jsEvent.type,
                        button: info.jsEvent.button
                    }
                }, 'info');

                // If clicking on a time slot (not all day), open create modal with 1-hour duration
                if (!info.allDay) {
                    const startDate = info.date;
                    const endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // Add 1 hour

                    try {
                        displayEventModal(null, startDate, endDate);
                    } catch (error) {
                        console.error('Error in displayEventModal:', error);
                        alert('Error opening modal: ' + error.message);
                    }
                }
            },
            datesSet: function(info) {
                addLog('DATES_SET', 'Calendar view changed', {
                    start: info.start,
                    end: info.end,
                    startStr: info.startStr,
                    endStr: info.endStr
                }, 'warning');
            },
            eventChange: function(info) {
                addLog('EVENT_CHANGE', 'Event modified (drag/resize)', {
                    eventId: info.event.id,
                    title: info.event.title,
                    oldStart: info.oldEvent.start,
                    newStart: info.event.start,
                    oldEnd: info.oldEvent.end,
                    newEnd: info.event.end
                }, 'warning');
                
                // Persist changes to server
                // Convert to local datetime string (not UTC)
                function toLocalDatetimeString(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const seconds = String(date.getSeconds()).padStart(2, '0');
                    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                }
                
                const startStr = toLocalDatetimeString(info.event.start);
                const endStr = info.event.end ? toLocalDatetimeString(info.event.end) : startStr;
                const resourceId = info.event.extendedProps.aircraft || '';
                
                fetch('<?= base_url('index.php/reservations/on_event_drop') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'event_id=' + info.event.id + 
                          '&start_datetime=' + encodeURIComponent(startStr) +
                          '&end_datetime=' + encodeURIComponent(endStr) +
                          '&resource_id=' + encodeURIComponent(resourceId) +
                          '&action=move'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addLog('EVENT_CHANGE_SAVED', 'Event changes saved to server', {
                            eventId: info.event.id,
                            start: startStr,
                            end: endStr
                        }, 'success');
                    } else {
                        addLog('EVENT_CHANGE_FAILED', 'Failed to save event changes', {
                            eventId: info.event.id,
                            error: data.error
                        }, 'error');
                        // Show error message to user
                        alert(TRANSLATIONS.error_prefix + ': ' + (data.error || TRANSLATIONS.error_unknown));
                        // Revert the change
                        info.revert();
                    }
                })
                .catch(error => {
                    addLog('EVENT_CHANGE_ERROR', 'Error saving event changes', {
                        eventId: info.event.id,
                        error: error.message
                    }, 'error');
                    // Show error message to user
                    alert(TRANSLATIONS.error_prefix + ': ' + error.message);
                    // Revert the change
                    info.revert();
                });
            },
            eventDidMount: function(info) {
                addLog('EVENT_DID_MOUNT', 'Event rendered to DOM', {
                    eventId: info.event.id,
                    title: info.event.title
                }, 'info');
            },
            eventAdd: function(info) {
                addLog('EVENT_ADD', 'Event added to calendar', {
                    eventId: info.event.id,
                    title: info.event.title,
                    start: info.event.start,
                    end: info.event.end
                }, 'success');
            },
            eventRemove: function(info) {
                addLog('EVENT_REMOVE', 'Event removed from calendar', {
                    eventId: info.event.id,
                    title: info.event.title
                }, 'warning');
            },
            loading: function(isLoading) {
                addLog('LOADING', isLoading ? 'Calendar loading started' : 'Calendar loading finished', {
                    isLoading: isLoading
                }, isLoading ? 'warning' : 'success');
            },
            datesDestroy: function() {
                addLog('DATES_DESTROY', 'Dates destroyed (view switching)', null, 'warning');
            },
            windowResize: function(view) {
                addLog('WINDOW_RESIZE', 'Window resized', {
                    viewType: view.type,
                    viewTitle: view.title
                }, 'info');
            },
            viewDidMount: function(info) {
                // Save view preference to localStorage
                localStorage.setItem('reservationsCalendarView', info.view.type);
                addLog('VIEW_DID_MOUNT', 'View mounted to DOM', {
                    viewType: info.view.type,
                    viewTitle: info.view.title
                }, 'success');
            },
            viewWillUnmount: function(info) {
                addLog('VIEW_WILL_UNMOUNT', 'View will be unmounted', {
                    viewType: info.view.type,
                    viewTitle: info.view.title
                }, 'warning');
            },
            eventDid: function(info) {
                addLog('EVENT_DID', 'Generic event callback', {
                    eventId: info.event?.id,
                    title: info.event?.title
                }, 'info');
            },
            moreLinkClick: function(info) {
                addLog('MORE_LINK_CLICK', 'More link clicked (day with too many events)', {
                    date: info.date,
                    allDay: info.allDay,
                    numEvents: info.num,
                    hiddenEventCount: info.hiddenSegs?.length || 0
                }, 'info');
            },
            drop: function(info) {
                addLog('DROP', 'External element dropped on calendar', {
                    draggedEl: info.draggedEl?.id,
                    start: info.date,
                    allDay: info.allDay
                }, 'warning');
            },
            receive: function(info) {
                addLog('RECEIVE', 'Event received from external source', {
                    event: {
                        id: info.event?.id,
                        title: info.event?.title
                    },
                    date: info.date
                }, 'success');
            },
            eventError: function(info) {
                addLog('EVENT_ERROR', 'Error loading events', {
                    errorMessage: info.message,
                    error: info.error?.message || info.error
                }, 'error');
            }
        });
        
        addLog('INIT', 'FullCalendar v6 instance created');
        calendar.render();
        addLog('INIT', 'FullCalendar rendered successfully', null, 'success');

        // Add event listener for save button
        document.getElementById('saveEventBtn').addEventListener('click', function() {
            saveEventChanges(calendar);
        });
    });
</script>
