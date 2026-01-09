<!-- VIEW: application/views/reservations/timeline.php -->
<?php
/**
 * Timeline View for Aircraft Reservations
 *
 * Displays reservations organized by aircraft (resources) in a timeline format.
 * Supports drag-drop, click events, and empty slot clicks with callback logging.
 *
 * Design: Future-proof for FullCalendar Premium Timeline API migration
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<style>
    .timeline-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 40px);
        }
        
        .timeline-header {
            padding: 20px;
            border-bottom: 2px solid #007bff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .timeline-title h1 {
            margin: 0;
            font-size: 28px;
            color: #333;
        }
        
        .timeline-title p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .timeline-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .timeline-controls button {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .current-date-display {
            min-width: 200px;
            text-align: center;
            padding: 8px 16px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-weight: 600;
            color: #333;
        }
        
        .timeline-body {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        /* Resource column (aircraft names) */
        .timeline-resources {
            width: 200px;
            border-right: 2px solid #ddd;
            overflow-y: auto;
            background-color: #fafafa;
        }
        
        .timeline-resources-header {
            padding: 12px;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #ddd;
            background-color: #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .resource-row {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 13px;
            height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .resource-row:hover {
            background-color: #e8f4fd;
        }
        
        .resource-row-title {
            font-weight: 600;
            color: #333;
        }
        
        .resource-row-model {
            font-size: 12px;
            color: #999;
            margin-top: 2px;
        }
        
        /* Timeline grid */
        .timeline-grid {
            flex: 1;
            overflow-x: auto;
            overflow-y: auto;
            position: relative;
        }
        
        .timeline-content {
            display: flex;
            min-width: 100%;
            height: 100%;
        }
        
        /* Time slots */
        .timeline-time-header {
            display: flex;
            position: sticky;
            top: 0;
            z-index: 11;
            background-color: white;
            border-bottom: 2px solid #ddd;
            height: 50px;
        }
        
        .time-slot-header {
            min-width: 60px;
            padding: 8px 4px;
            text-align: center;
            border-right: 1px solid #eee;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Resources timeline */
        .timeline-events-wrapper {
            display: flex;
            width: 100%;
            min-height: 100%;
        }
        
        .resource-timeline {
            min-width: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .resource-row-timeline {
            display: flex;
            border-bottom: 3px solid #999;
            height: 60px;
            position: relative;
        }
        
        .resource-row-timeline:last-child {
            border-bottom: 2px solid #333;
        }
        
        .time-slot {
            min-width: 60px;
            border-right: 1px solid #ddd;
            flex: 0 0 auto;
            position: relative;
            cursor: pointer;
            transition: background-color 0.1s;
        }
        
        .time-slot:hover {
            background-color: #f0f8ff;
        }
        
        /* Events */
        .reservation-event {
            position: absolute;
            top: 5px;
            height: calc(100% - 10px);
            background-color: #28A745;
            border: 2px solid #20c997;
            border-radius: 4px;
            padding: 5px;
            cursor: move;
            touch-action: none;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
            z-index: 5;
            overflow: hidden;
            white-space: nowrap;
            transition: box-shadow 0.2s;
        }
        
        .reservation-event:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 6;
        }
        
        .reservation-event.pending {
            background-color: #FFC107;
            border-color: #ffb300;
            color: #333;
        }
        
        .reservation-event.confirmed {
            background-color: #28A745;
            border-color: #20c997;
            color: white;
        }
        
        .reservation-event.completed {
            background-color: #6C757D;
            border-color: #5a6268;
            color: white;
        }
        
        .reservation-event.no_show {
            background-color: #E83E8C;
            border-color: #c6113b;
            color: white;
        }
        
        .reservation-event.dragging {
            opacity: 0.7;
            z-index: 100;
        }
        
        /* Event tooltip */
        .event-tooltip {
            position: absolute;
            background-color: rgba(0,0,0,0.9);
            color: white;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 200;
            max-width: 250px;
            display: none;
            pointer-events: none;
            white-space: normal;
        }
        
        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .status-badge.pending {
            background-color: #FFC107;
            color: #333;
        }
        
        .status-badge.confirmed {
            background-color: #28A745;
            color: white;
        }
        
        .status-badge.completed {
            background-color: #6C757D;
            color: white;
        }
        
        .status-badge.no_show {
            background-color: #E83E8C;
            color: white;
        }
        
        /* Now indicator */
        .now-indicator {
            position: absolute;
            background-color: #dc3545;
            width: 2px;
            height: 100%;
            z-index: 10;
            top: 0;
        }
        
        @media (max-width: 768px) {
            .timeline-resources {
                width: 120px;
            }
            
            .time-slot-header {
                min-width: 50px;
            }
            
            .time-slot {
                min-width: 50px;
            }
            
            .timeline-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .timeline-controls {
                justify-content: center;
            }
        }
    </style>

<div id="body" class="body container-fluid">
    <h3><?php echo $this->lang->line('reservations_timeline') ?: 'Timeline Réservations'; ?></h3>
    
    <div class="timeline-container">
        <!-- Header -->
        <div class="timeline-header">
            <div class="timeline-title">
                <h4><?php echo $this->lang->line('reservations_timeline_desc') ?: 'Disponibilité des aéronefs par jour'; ?></h4>
            </div>
            <div class="timeline-controls">
                <div class="btn-group btn-group-sm me-3" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btnViewDay" title="Vue jour">
                        <i class="fas fa-calendar-day"></i> <?php echo $this->lang->line('day') ?: 'Jour'; ?>
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btnViewWeek" title="Vue semaine">
                        <i class="fas fa-calendar-week"></i> <?php echo $this->lang->line('week') ?: 'Semaine'; ?>
                    </button>
                </div>
                <button class="btn btn-outline-secondary btn-sm" id="btnPrevious" title="Previous day">
                    <i class="fas fa-chevron-left"></i> <?php echo $this->lang->line('previous') ?: 'Précédent'; ?>
                </button>
                <input type="date" class="form-control form-control-sm mx-2" id="datePicker" style="width: auto; display: inline-block;" value="<?php echo $current_date; ?>" title="Sélectionner une date">
                <div class="current-date-display" id="currentDateDisplay">
                    <?php echo $current_date_formatted; ?>
                </div>
                <button class="btn btn-outline-secondary btn-sm" id="btnToday" title="Go to today">
                    <i class="fas fa-calendar-day"></i> <?php echo $this->lang->line('today') ?: "Aujourd'hui"; ?>
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btnNext" title="Next day">
                    <?php echo $this->lang->line('next') ?: 'Suivant'; ?> <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Timeline Body -->
        <div class="timeline-body">
            <!-- Resources Column (Aircraft) -->
            <div class="timeline-resources">
                <div class="timeline-resources-header">
                    <?php echo $this->lang->line('aircraft') ?: 'Aircraft'; ?>
                </div>
                <div id="resourcesList">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
            
            <!-- Timeline Grid -->
            <div class="timeline-grid">
                <!-- Time header will be inserted here -->
                <div id="timelineContent">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
    </div> <!-- end timeline-container -->
    
    <!-- Event Info Modal (optional, for details) -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Détails de la réservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const CONFIG = {
            baseUrl: '<?php echo base_url(); ?>',
            currentDate: '<?php echo $current_date; ?>',
            pixelsPerHour: 60,
            slotWidthPx: 60,
            startHour: 6  // Timeline starts at 6:00
        };
        
        // State
        let state = {
            currentDate: CONFIG.currentDate,
            viewMode: 'day',  // 'day' or 'week'
            timelineData: null,
            draggingEvent: null,
            dragMode: null,  // 'move' or 'resize'
            dragStartX: 0,
            dragStartLeft: 0,
            dragStartWidth: 0,
            isDragging: false,
            dragDistance: 0
        };
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadTimelineData();
            setupDateNavigation();
        });
        
        /**
         * Load timeline data from server
         */
        function loadTimelineData() {
            fetch(CONFIG.baseUrl + 'reservations/get_timeline_data?date=' + state.currentDate)
                .then(response => response.json())
                .then(data => {
                    state.timelineData = data;
                    renderTimeline();
                })
                .catch(error => {
                    console.error('Error loading timeline:', error);
                    alert('Error loading timeline data');
                });
        }
        
        /**
         * Render the complete timeline
         */
        function renderTimeline() {
            renderTimeHeader();
            renderResources();
            renderEvents();
            updateDateDisplay();
        }
        
        /**
         * Render time header (hours)
         */
        function renderTimeHeader() {
            let html = '<div class="timeline-time-header">';
            for (let hour = 6; hour < 24; hour++) {
                const timeStr = String(hour).padStart(2, '0') + ':00';
                html += `<div class="time-slot-header">${timeStr}</div>`;
            }
            html += '</div>';
            
            document.getElementById('timelineContent').innerHTML = html;
        }
        
        /**
         * Render resources (aircraft) list
         */
        function renderResources() {
            const html = state.timelineData.resources
                .map(resource => `
                    <div class="resource-row" data-resource-id="${resource.id}">
                        <div class="resource-row-title">${escapeHtml(resource.title)}</div>
                    </div>
                `)
                .join('');
            
            document.getElementById('resourcesList').innerHTML = html;
            
            // Add click handlers
            document.querySelectorAll('.resource-row').forEach(el => {
                el.addEventListener('click', function() {
                    const resourceId = this.getAttribute('data-resource-id');
                    console.log('Resource clicked:', resourceId);
                });
            });
        }
        
        /**
         * Render events (reservations) on timeline
         */
        function renderEvents() {
            let html = '<div class="resource-timeline">';
            
            state.timelineData.resources.forEach(resource => {
                html += `<div class="resource-row-timeline" data-resource-id="${resource.id}">`;
                
                // Time slots
                for (let hour = 6; hour < 24; hour++) {
                    html += `<div class="time-slot" data-hour="${hour}" data-resource-id="${resource.id}"></div>`;
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            
            const container = document.getElementById('timelineContent');
            container.innerHTML += html;
            
            // Render events on top
            state.timelineData.events.forEach(event => {
                renderEvent(event);
            });
            
            // Add click handlers for empty slots
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.addEventListener('click', function(e) {
                    if (e.target === this) {
                        handleSlotClick(this);
                    }
                });
            });
        }
        
        /**
         * Render a single event
         */
        function renderEvent(event) {
            const resourceRow = document.querySelector(
                `.resource-row-timeline[data-resource-id="${event.resourceId}"]`
            );
            
            if (!resourceRow) return;
            
            const startTime = new Date(event.start);
            const endTime = new Date(event.end);
            const startHour = startTime.getHours() + startTime.getMinutes() / 60;
            const endHour = endTime.getHours() + endTime.getMinutes() / 60;
            const duration = endHour - startHour;
            
            const left = (startHour - CONFIG.startHour) * CONFIG.slotWidthPx;
            const width = Math.max(duration * CONFIG.slotWidthPx, 40);
            
            const eventEl = document.createElement('div');
            eventEl.className = `reservation-event ${event.status}`;
            eventEl.style.left = left + 'px';
            eventEl.style.width = width + 'px';
            eventEl.setAttribute('data-event-id', event.id);
            eventEl.setAttribute('data-resource-id', event.resourceId);
            eventEl.setAttribute('data-start', event.start);
            eventEl.setAttribute('data-end', event.end);
            eventEl.textContent = event.title;
            eventEl.title = `${event.title} (${event.status})`;
            
            // Add resize handle
            const resizeHandle = document.createElement('div');
            resizeHandle.className = 'resize-handle';
            resizeHandle.style.cssText = 'position: absolute; right: 0; top: 0; bottom: 0; width: 8px; cursor: ew-resize; background: rgba(0,0,0,0.1);';
            eventEl.appendChild(resizeHandle);
            
            // Add event handlers
            let clickStartX = 0;
            eventEl.addEventListener('mousedown', (e) => {
                clickStartX = e.clientX;
            });
            
            eventEl.addEventListener('click', (e) => {
                if (e.target.classList.contains('resize-handle')) return;
                // Check if this was actually a drag (moved > 5px)
                const clickDistance = Math.abs(e.clientX - clickStartX);
                if (clickDistance > 5) {
                    e.stopPropagation();
                    return; // This was a drag, not a click
                }
                e.stopPropagation();
                handleEventClick(event);
            });
            
            eventEl.addEventListener('mousedown', (e) => {
                if (e.button === 0 && !e.target.classList.contains('resize-handle')) {
                    startDragging(e, event, eventEl, 'move');
                }
            });
            
            resizeHandle.addEventListener('mousedown', (e) => {
                if (e.button === 0) {
                    e.stopPropagation();
                    startDragging(e, event, eventEl, 'resize');
                }
            });
            
            // Show tooltip on hover
            eventEl.addEventListener('mouseenter', (e) => {
                showEventTooltip(e, event);
            });
            
            eventEl.addEventListener('mouseleave', (e) => {
                hideEventTooltip();
            });
            
            resourceRow.appendChild(eventEl);
        }
        
        /**
         * Handle event click
         */
        function handleEventClick(event) {
            console.log('Event clicked:', event.id);
            
            // Send trace to server
            fetch(CONFIG.baseUrl + 'reservations/on_event_click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'event_id=' + event.id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Event click traced');
                    // Optionally show event details modal
                    showEventDetails(event);
                }
            })
            .catch(error => console.error('Error tracing click:', error));
        }
        
        /**
         * Start dragging an event
         */
        function startDragging(e, event, eventEl, mode) {
            e.preventDefault();
            state.draggingEvent = event;
            state.dragMode = mode;
            state.dragStartX = e.clientX;
            state.dragStartLeft = parseInt(eventEl.style.left);
            state.dragStartWidth = parseInt(eventEl.style.width);
            state.draggingElement = eventEl;
            state.isDragging = true;
            state.dragDistance = 0;
            
            eventEl.classList.add('dragging');
            
            document.addEventListener('mousemove', onDragMove);
            document.addEventListener('mouseup', onDragEnd);
        }
        
        /**
         * Handle drag movement
         */
        function onDragMove(e) {
            if (!state.draggingEvent || !state.draggingElement) return;
            
            const delta = e.clientX - state.dragStartX;
            state.dragDistance = Math.abs(delta);
            const eventEl = state.draggingElement;
            
            if (state.dragMode === 'move') {
                // Move the event
                const newLeft = Math.max(0, state.dragStartLeft + delta);
                eventEl.style.left = newLeft + 'px';
            } else if (state.dragMode === 'resize') {
                // Resize the event
                const newWidth = Math.max(30, state.dragStartWidth + delta);
                eventEl.style.width = newWidth + 'px';
            }
        }
        
        /**
         * Handle drag end
         */
        function onDragEnd(e) {
            if (!state.draggingEvent || !state.draggingElement) return;
            
            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('mouseup', onDragEnd);
            
            const event = state.draggingEvent;
            const eventEl = state.draggingElement;
            eventEl.classList.remove('dragging');
            
            // Calculate new times based on position
            const left = parseInt(eventEl.style.left);
            const width = parseInt(eventEl.style.width);
            
            const startHourDecimal = (left / CONFIG.slotWidthPx) + CONFIG.startHour;
            const durationHours = width / CONFIG.slotWidthPx;
            const endHourDecimal = startHourDecimal + durationHours;
            
            // Convert to datetime strings
            const startHour = Math.floor(startHourDecimal);
            const startMinute = Math.round((startHourDecimal - startHour) * 60);
            const endHour = Math.floor(endHourDecimal);
            const endMinute = Math.round((endHourDecimal - endHour) * 60);
            
            const dateStr = state.currentDate;
            const newStart = `${dateStr} ${String(startHour).padStart(2, '0')}:${String(startMinute).padStart(2, '0')}:00`;
            const newEnd = `${dateStr} ${String(endHour).padStart(2, '0')}:${String(endMinute).padStart(2, '0')}:00`;
            
            console.log(`Event ${state.dragMode}d:`, event.id, 'from', event.start, '-', event.end, 'to', newStart, '-', newEnd);
            
            // Update the element's data attributes
            eventEl.setAttribute('data-start', newStart);
            eventEl.setAttribute('data-end', newEnd);
            
            // Send to server
            fetch(CONFIG.baseUrl + 'reservations/on_event_drop', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'event_id=' + event.id + 
                      '&start_datetime=' + encodeURIComponent(newStart) +
                      '&end_datetime=' + encodeURIComponent(newEnd) +
                      '&resource_id=' + event.resourceId +
                      '&action=' + state.dragMode
            })
            .then(response => {
                console.log('Drop response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Drop response data:', data);
                if (data.success) {
                    console.log('Event updated successfully, keeping new position');
                    // Update the event object to reflect new times
                    event.start = newStart;
                    event.end = newEnd;
                    // DO NOT reload - keep the visual changes
                } else {
                    console.error('Server returned error:', data.error);
                    // Reload to revert changes on error
                    setTimeout(() => loadTimelineData(), 500);
                }
            })
            .catch(error => {
                console.error('Error updating event:', error);
                // Reload to revert changes on error
                setTimeout(() => loadTimelineData(), 500);
            });
            
            state.draggingEvent = null;
            state.draggingElement = null;
            state.dragMode = null;
            state.isDragging = false;
            state.dragDistance = 0;
        }
        
        /**
         * Handle empty slot click
         */
        function handleSlotClick(slotEl) {
            const hour = parseInt(slotEl.getAttribute('data-hour'));
            const resourceId = slotEl.getAttribute('data-resource-id');
            const clickedTime = String(hour).padStart(2, '0') + ':00:00';
            
            console.log('Slot clicked:', resourceId, clickedTime);
            
            // Send trace to server
            fetch(CONFIG.baseUrl + 'reservations/on_slot_click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'resource_id=' + resourceId + 
                      '&clicked_time=' + clickedTime
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Slot click traced');
                }
            })
            .catch(error => console.error('Error tracing click:', error));
        }
        
        /**
         * Show event details
         */
        function showEventDetails(event) {
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            document.getElementById('eventModalTitle').textContent = event.title;
            
            const body = document.getElementById('eventModalBody');
            body.innerHTML = `
                <p><strong>Aircraft:</strong> ${escapeHtml(event.extendedProps.aircraft_model)}</p>
                <p><strong>Time:</strong> ${formatTime(event.start)} - ${formatTime(event.end)}</p>
                <p><strong>Purpose:</strong> ${escapeHtml(event.extendedProps.purpose || '-')}</p>
                <p><strong>Status:</strong> <span class="status-badge ${event.status}">${event.status.toUpperCase()}</span></p>
                ${event.extendedProps.instructor_member_id ? 
                    `<p><strong>Instructor:</strong> ${escapeHtml(event.extendedProps.instructor_member_id)}</p>` : ''}
                ${event.extendedProps.notes ? 
                    `<p><strong>Notes:</strong> ${escapeHtml(event.extendedProps.notes)}</p>` : ''}
            `;
            
            modal.show();
        }
        
        /**
         * Show event tooltip
         */
        function showEventTooltip(e, event) {
            // Could implement tooltip if needed
        }
        
        /**
         * Hide event tooltip
         */
        function hideEventTooltip() {
            // Could implement tooltip if needed
        }
        
        /**
         * Setup date navigation buttons
         */
        function setupDateNavigation() {
            // View mode buttons
            document.getElementById('btnViewDay').addEventListener('click', function() {
                state.viewMode = 'day';
                updateViewModeButtons();
                loadTimelineData();
            });
            
            document.getElementById('btnViewWeek').addEventListener('click', function() {
                state.viewMode = 'week';
                updateViewModeButtons();
                loadTimelineData();
            });
            
            // Navigation buttons
            document.getElementById('btnPrevious').addEventListener('click', function() {
                const date = new Date(state.currentDate);
                if (state.viewMode === 'day') {
                    date.setDate(date.getDate() - 1);
                } else {
                    date.setDate(date.getDate() - 7);
                }
                navigateToDate(date);
            });
            
            document.getElementById('btnToday').addEventListener('click', function() {
                navigateToDate(new Date());
            });
            
            document.getElementById('btnNext').addEventListener('click', function() {
                const date = new Date(state.currentDate);
                if (state.viewMode === 'day') {
                    date.setDate(date.getDate() + 1);
                } else {
                    date.setDate(date.getDate() + 7);
                }
                navigateToDate(date);
            });
            
            document.getElementById('datePicker').addEventListener('change', function(e) {
                const selectedDate = new Date(e.target.value + 'T12:00:00');
                navigateToDate(selectedDate);
            });
        }
        
        /**
         * Update view mode button states
         */
        function updateViewModeButtons() {
            document.getElementById('btnViewDay').classList.toggle('active', state.viewMode === 'day');
            document.getElementById('btnViewWeek').classList.toggle('active', state.viewMode === 'week');
        }
        
        /**
         * Navigate to a specific date
         */
        function navigateToDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            state.currentDate = `${year}-${month}-${day}`;
            
            loadTimelineData();
        }
        
        /**
         * Update date display
         */
        function updateDateDisplay() {
            const date = new Date(state.currentDate);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formatted = date.toLocaleDateString('<?php echo $this->lang->line("lang") ?: "en"; ?>', options);
            document.getElementById('currentDateDisplay').textContent = formatted;
            
            // Update date picker value
            document.getElementById('datePicker').value = state.currentDate;
        }
        
        /**
         * Helper: Format time
         */
        function formatTime(datetime) {
            const date = new Date(datetime);
            return String(date.getHours()).padStart(2, '0') + ':' + 
                   String(date.getMinutes()).padStart(2, '0');
        }
        
        /**
         * Helper: Escape HTML
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</div> <!-- end body container -->
