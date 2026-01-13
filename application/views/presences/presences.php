<!-- VIEW: application/views/presences/presences.php -->
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
 * Pilot Presences Management - FullCalendar v6
 *
 * @author Claude Sonnet 4.5
 */

$this->load->view('bs_header', array('new_layout' => true));
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <h2><?= $translations['title'] ?></h2>
            <div id='calendar' style="height: 100%"></div>
        </div>
    </div>
</div>

<!-- Presence Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle"><?= $translations['modal_new'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer" id="eventModalFooter">
                <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display: none;">
                    <?= $translations['btn_delete'] ?>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelEventBtn">
                    <?= $translations['btn_cancel'] ?>
                </button>
                <button type="button" class="btn btn-primary" id="saveEventBtn">
                    <?= $translations['btn_create'] ?>
                </button>
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
        pilots: <?php echo json_encode($pilots_list); ?>,
        roles: <?php echo json_encode($roles_options); ?>
    };

    const TRANSLATIONS = <?php echo json_encode($translations); ?>;
    const BASE_URL = '<?php echo base_url(); ?>';
    const CURRENT_USER = '<?php echo $current_user; ?>';
    const IS_CA = <?php echo $is_ca ? 'true' : 'false'; ?>;

    let currentEditingEvent = null;

    /**
     * Display modal for creating or editing a presence
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

            // Show delete button only for existing events
            deleteBtn.style.display = isCreate ? 'none' : 'inline-block';

            // Store current editing event
            currentEditingEvent = event;

            // Prepare dates (YYYY-MM-DD format for date inputs)
            let startStr = '';
            let endStr = '';

            if (isCreate && startDate && endDate) {
                // Creating new event from select
                startStr = formatDateOnly(startDate);
                // FullCalendar gives us end date as exclusive, so subtract 1 day
                const inclusiveEnd = new Date(endDate);
                inclusiveEnd.setDate(inclusiveEnd.getDate() - 1);
                endStr = formatDateOnly(inclusiveEnd);
            } else if (event) {
                // Editing existing event
                if (event.start instanceof Date) {
                    startStr = formatDateOnly(event.start);
                } else if (typeof event.start === 'string') {
                    startStr = event.start.slice(0, 10);
                }

                if (event.end instanceof Date) {
                    // FullCalendar gives exclusive end, subtract 1 day for inclusive
                    const inclusiveEnd = new Date(event.end);
                    inclusiveEnd.setDate(inclusiveEnd.getDate() - 1);
                    endStr = formatDateOnly(inclusiveEnd);
                } else if (typeof event.end === 'string') {
                    // Parse and subtract 1 day
                    const endDate = new Date(event.end);
                    endDate.setDate(endDate.getDate() - 1);
                    endStr = formatDateOnly(endDate);
                }
            }

            const props = (event && event.extendedProps) ? event.extendedProps : {};
            const mlogin = props.mlogin || CURRENT_USER;
            const role = props.role || '';
            const commentaire = props.commentaire || '';

            // Build pilot select
            let pilotSelect = '<select class="form-control" id="eventPilot">';
            pilotSelect += `<option value="">${TRANSLATIONS.select_pilot}</option>`;
            if (OPTIONS.pilots) {
                for (const [id, label] of Object.entries(OPTIONS.pilots)) {
                    const selected = String(id) === String(mlogin) ? 'selected' : '';
                    pilotSelect += `<option value="${escapeHtml(id)}" ${selected}>${escapeHtml(label)}</option>`;
                }
            }
            pilotSelect += '</select>';

            // Build role select
            let roleSelect = '<select class="form-control" id="eventRole">';
            roleSelect += `<option value="">${TRANSLATIONS.select_role}</option>`;
            if (OPTIONS.roles) {
                for (const [key, label] of Object.entries(OPTIONS.roles)) {
                    const selected = String(key) === String(role) ? 'selected' : '';
                    roleSelect += `<option value="${escapeHtml(key)}" ${selected}>${escapeHtml(label)}</option>`;
                }
            }
            roleSelect += '</select>';

            // Build form HTML
            const formHtml = `<form id="eventEditForm">
                <div class="mb-3">
                    <label for="eventPilot" class="form-label"><strong>${TRANSLATIONS.form_pilot}:</strong></label>
                    ${pilotSelect}
                </div>

                <div class="mb-3">
                    <label for="eventRole" class="form-label"><strong>${TRANSLATIONS.form_role}:</strong></label>
                    ${roleSelect}
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="eventStartDate" class="form-label"><strong>${TRANSLATIONS.form_start_date}:</strong></label>
                        <input type="date" class="form-control" id="eventStartDate" value="${startStr}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="eventEndDate" class="form-label"><strong>${TRANSLATIONS.form_end_date}:</strong></label>
                        <input type="date" class="form-control" id="eventEndDate" value="${endStr}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="eventComment" class="form-label"><strong>${TRANSLATIONS.form_comment}:</strong></label>
                    <textarea class="form-control" id="eventComment" rows="2">${escapeHtml(commentaire)}</textarea>
                </div>
            </form>`;

            bodyEl.innerHTML = formHtml;
            modal.show();
        } catch (error) {
            console.error('Error in displayEventModal:', error);
            alert('Error displaying presence form: ' + error.message);
        }
    }

    /**
     * Save event changes to server
     */
    function saveEventChanges(calendar) {
        const mlogin = document.getElementById('eventPilot').value;
        const role = document.getElementById('eventRole').value;
        const commentaire = document.getElementById('eventComment').value;
        const startDate = document.getElementById('eventStartDate').value;
        const endDate = document.getElementById('eventEndDate').value;

        // Validation
        if (!mlogin) {
            alert(TRANSLATIONS.error_no_pilot);
            return;
        }

        if (!startDate || !endDate) {
            alert(TRANSLATIONS.error_invalid_dates);
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            alert(TRANSLATIONS.error_invalid_dates);
            return;
        }

        const isCreate = !currentEditingEvent || currentEditingEvent.id === null || currentEditingEvent.id === undefined;

        // Build request body
        let requestBody = 'mlogin=' + encodeURIComponent(mlogin) +
                         '&role=' + encodeURIComponent(role) +
                         '&commentaire=' + encodeURIComponent(commentaire) +
                         '&start_date=' + encodeURIComponent(startDate) +
                         '&end_date=' + encodeURIComponent(endDate);

        if (!isCreate) {
            requestBody = 'id=' + currentEditingEvent.id + '&' + requestBody;
        }

        // Determine endpoint
        const endpoint = isCreate ? 'create_presence' : 'update_presence';

        // Send to server
        fetch(BASE_URL + 'index.php/presences/' + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('SUCCESS:', isCreate ? 'Presence created' : 'Presence updated', data);

                // Close modal and reload calendar
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                calendar.refetchEvents();

                // Show warning if conflict detected
                if (data.warning) {
                    alert(data.message + '\n\n' + data.warning);
                } else {
                    // Show brief success message
                    showToast(data.message, 'success');
                }
            } else {
                console.error('ERROR:', 'Failed to save presence', data);
                alert(TRANSLATIONS.error_unknown + ': ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving presence:', error);
            alert(TRANSLATIONS.error_unknown + ': ' + error.message);
        });
    }

    /**
     * Delete current event
     */
    function deleteCurrentEvent(calendar) {
        if (!currentEditingEvent || !currentEditingEvent.id) {
            return;
        }

        if (!confirm(TRANSLATIONS.confirm_delete)) {
            return;
        }

        // Send delete request
        fetch(BASE_URL + 'index.php/presences/delete_presence', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + encodeURIComponent(currentEditingEvent.id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('SUCCESS: Presence deleted', data);

                // Close modal and reload calendar
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                calendar.refetchEvents();

                showToast(data.message, 'success');
            } else {
                console.error('ERROR: Failed to delete presence', data);
                alert(TRANSLATIONS.error_unknown + ': ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error deleting presence:', error);
            alert(TRANSLATIONS.error_unknown + ': ' + error.message);
        });
    }

    /**
     * Format Date to YYYY-MM-DD
     */
    function formatDateOnly(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
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

    /**
     * Show a toast notification
     */
    function showToast(message, type = 'info') {
        // Simple console log for now (can be enhanced with actual toast UI later)
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Check Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded!');
            alert('Error: Bootstrap is not loaded. Cannot manage presences.');
            return;
        }

        console.log('Initializing FullCalendar v6 for Presences');

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
        var savedView = localStorage.getItem('presencesCalendarView') || 'dayGridMonth';

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
            firstDay: 1, // Monday
            weekNumbers: true,
            events: {
                url: '<?= base_url('index.php/presences/get_events') ?>',
                failure: function() {
                    console.error('Failed to load presences from server');
                    alert('Error loading presences from server');
                }
            },
            eventDidMount: function(info) {
                console.log('Event mounted:', info.event.title, {
                    id: info.event.id,
                    start: info.event.start,
                    end: info.event.end,
                    allDay: info.event.allDay
                });
            },
            eventClick: function(info) {
                console.log('Event clicked:', info.event);
                displayEventModal(info.event);
            },
            select: function(info) {
                console.log('Date range selected:', info.startStr, 'to', info.endStr);
                displayEventModal(null, info.start, info.end);
            },
            eventDrop: function(info) {
                console.log('Event dropped:', info.event.id, 'to', info.event.start);

                // Calculate new dates (inclusive end)
                const startDate = formatDateOnly(info.event.start);
                const endDate = new Date(info.event.end);
                endDate.setDate(endDate.getDate() - 1); // Make inclusive
                const endDateStr = formatDateOnly(endDate);

                // Send update to server
                fetch(BASE_URL + 'index.php/presences/on_event_drop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(info.event.id) +
                          '&start_date=' + encodeURIComponent(startDate) +
                          '&end_date=' + encodeURIComponent(endDateStr)
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to update presence on drop', data);
                        alert(data.error || TRANSLATIONS.error_unknown);
                        info.revert();
                    } else {
                        console.log('Presence moved successfully');
                    }
                })
                .catch(error => {
                    console.error('Error moving presence:', error);
                    alert(TRANSLATIONS.error_unknown);
                    info.revert();
                });
            },
            eventResize: function(info) {
                console.log('Event resized:', info.event.id, 'new end:', info.event.end);

                // Calculate new end date (inclusive)
                const endDate = new Date(info.event.end);
                endDate.setDate(endDate.getDate() - 1); // Make inclusive
                const endDateStr = formatDateOnly(endDate);

                // Send update to server
                fetch(BASE_URL + 'index.php/presences/on_event_resize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(info.event.id) +
                          '&end_date=' + encodeURIComponent(endDateStr)
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to update presence on resize', data);
                        alert(data.error || TRANSLATIONS.error_unknown);
                        info.revert();
                    } else {
                        console.log('Presence resized successfully');
                    }
                })
                .catch(error => {
                    console.error('Error resizing presence:', error);
                    alert(TRANSLATIONS.error_unknown);
                    info.revert();
                });
            },
            datesSet: function(info) {
                // Save current view to localStorage
                localStorage.setItem('presencesCalendarView', info.view.type);
                console.log('View changed to:', info.view.type);
            },
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true
        });

        calendar.render();
        console.log('FullCalendar rendered successfully');

        // Attach save button handler
        document.getElementById('saveEventBtn').addEventListener('click', function() {
            saveEventChanges(calendar);
        });

        // Attach delete button handler
        document.getElementById('deleteEventBtn').addEventListener('click', function() {
            deleteCurrentEvent(calendar);
        });
    });
</script>
