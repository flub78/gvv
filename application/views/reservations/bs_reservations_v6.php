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
    <h2>Aircraft Reservations - FullCalendar v6</h2>
    <div id='calendar' style="height: 100%"></div>
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
                <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display: none;">Supprimer</button>
                <div class="flex-grow-1"></div>
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
</style>

<script>
    // Data from PHP
    const OPTIONS = {
        aircraft: <?php echo json_encode($aircraft_list); ?>,
        pilots: <?php echo json_encode($pilots_list); ?>,
        instructors: <?php echo json_encode($instructors_list); ?>
    };

    const TRANSLATIONS = <?php echo json_encode($translations); ?>;
    const BASE_URL = '<?php echo site_url(); ?>';

    let currentEditingEvent = null;

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
            const deleteBtn = document.getElementById('deleteEventBtn');

            if (!titleEl || !bodyEl) {
                throw new Error('Modal elements not found');
            }

            // Set title based on create vs edit mode
            const isCreate = !event || event.id === null || event.id === undefined;
            titleEl.textContent = isCreate ? TRANSLATIONS.modal_new : TRANSLATIONS.modal_edit;
            saveBtn.textContent = isCreate ? TRANSLATIONS.btn_create : TRANSLATIONS.btn_save;
            cancelBtn.textContent = TRANSLATIONS.btn_cancel;

            // Show/hide delete button based on mode
            if (deleteBtn) {
                if (isCreate) {
                    deleteBtn.style.display = 'none';
                } else {
                    deleteBtn.style.display = 'inline-block';
                    deleteBtn.textContent = TRANSLATIONS.btn_delete;
                }
            }

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
            const notes = props.notes || '';
            const status = props.status || 'reservation';

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
                    <label for="eventNotes" class="form-label"><strong>${TRANSLATIONS.form_notes}:</strong></label>
                    <textarea class="form-control" id="eventNotes" rows="2">${escapeHtml(notes)}</textarea>
                </div>

                <div class="mb-3">
                    <label for="eventStatus" class="form-label"><strong>${TRANSLATIONS.form_status}:</strong></label>
                    <select class="form-control" id="eventStatus">
                        <option value="reservation" ${status === 'reservation' ? 'selected' : ''}>${TRANSLATIONS.status_reservation}</option>
                        <option value="maintenance" ${status === 'maintenance' ? 'selected' : ''}>${TRANSLATIONS.status_maintenance}</option>
                        <option value="unavailable" ${status === 'unavailable' ? 'selected' : ''}>${TRANSLATIONS.status_unavailable}</option>
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
        // Pilot is required only for regular reservations, not for maintenance/unavailable
        if (!pilotId && status === 'reservation') {
            alert(TRANSLATIONS.error_no_pilot);
            return;
        }

        const isCreate = !currentEditingEvent || currentEditingEvent.id === null || currentEditingEvent.id === undefined;

        // Build request body
        let requestBody = 'start_datetime=' + encodeURIComponent(startStr) +
                         '&end_datetime=' + encodeURIComponent(endStr) +
                         '&notes=' + encodeURIComponent(notes) +
                         '&status=' + encodeURIComponent(status) +
                         '&aircraft_id=' + encodeURIComponent(aircraftId) +
                         '&pilot_member_id=' + encodeURIComponent(pilotId) +
                         '&instructor_member_id=' + encodeURIComponent(instructorId);

        if (!isCreate) {
            requestBody = 'reservation_id=' + currentEditingEvent.id + '&' + requestBody;
        }

        // Send to server
        fetch(BASE_URL + 'reservations/update_reservation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reload calendar
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                calendar.refetchEvents();
            } else {
                alert(TRANSLATIONS.error_prefix + ': ' + (data.error || TRANSLATIONS.error_unknown));
            }
        })
        .catch(error => {
            console.error('Error saving reservation:', error);
            alert(TRANSLATIONS.error_saving + ': ' + error.message);
        });
    }

    /**
     * Delete event reservation
     */
    function deleteEventReservation(calendar) {
        if (!currentEditingEvent || !currentEditingEvent.id) {
            alert(TRANSLATIONS.error_unknown);
            return;
        }

        // Confirm deletion
        if (!confirm(TRANSLATIONS.confirm_delete)) {
            return;
        }

        const reservationId = currentEditingEvent.id;

        // Send delete request to server
        fetch(BASE_URL + 'reservations/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'reservation_id=' + encodeURIComponent(reservationId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reload calendar
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                calendar.refetchEvents();
            } else {
                alert(TRANSLATIONS.error_prefix + ': ' + (data.error || TRANSLATIONS.error_unknown));
            }
        })
        .catch(error => {
            console.error('Error deleting reservation:', error);
            alert(TRANSLATIONS.error_deleting + ': ' + error.message);
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
                url: '<?= site_url('reservations/get_events') ?>',
                failure: function() {
                }
            },
            editable: true,
            selectable: true,
            selectConstraint: 'businessHours',
            eventClick: function(info) {
                // Open edit modal
                try {
                    displayEventModal(info.event);
                } catch (error) {
                    console.error('Error in displayEventModal:', error);
                    alert('Error opening modal: ' + error.message);
                }
            },
            select: function(info) {
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

                // Open create modal with 1-hour duration
                let startDate, endDate;

                if (info.allDay) {
                    // If clicking on a day (month view), set default time to 9:00-10:00
                    startDate = new Date(info.date);
                    startDate.setHours(9, 0, 0, 0);
                    endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // Add 1 hour
                } else {
                    // If clicking on a time slot (week/day view), use clicked time
                    startDate = info.date;
                    endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // Add 1 hour
                }

                try {
                    displayEventModal(null, startDate, endDate);
                } catch (error) {
                    console.error('Error in displayEventModal:', error);
                    alert('Error opening modal: ' + error.message);
                }
            },
            eventChange: function(info) {
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
                
                fetch('<?= site_url('reservations/on_event_drop') ?>', {
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
                    if (!data.success) {
                        // Show error message to user
                        alert(TRANSLATIONS.error_prefix + ': ' + (data.error || TRANSLATIONS.error_unknown));
                        // Revert the change
                        info.revert();
                    }
                })
                .catch(error => {
                    // Show error message to user
                    alert(TRANSLATIONS.error_prefix + ': ' + error.message);
                    // Revert the change
                    info.revert();
                });
            },
            viewDidMount: function(info) {
                // Save view preference to localStorage
                localStorage.setItem('reservationsCalendarView', info.view.type);
            }
        });
        
        calendar.render();

        // Add event listener for save button
        document.getElementById('saveEventBtn').addEventListener('click', function() {
            saveEventChanges(calendar);
        });

        // Add event listener for delete button
        document.getElementById('deleteEventBtn').addEventListener('click', function() {
            deleteEventReservation(calendar);
        });
    });
</script>
