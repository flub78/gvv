<!-- VIEW: application/views/deadlines_calendar/calendar.php -->
<?php
/**
 * GVV Gestion vol à voile
 * Read-only FullCalendar v6 view for document expiration deadlines.
 * Includes a custom year view (3 rows × 4 months on desktop, responsive).
 */

$this->load->view('bs_header', array('new_layout' => true));
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div class="container-fluid p-4">
    <div class="row mb-3">
        <div class="col-12">
            <h2><?= htmlspecialchars($translations['title']) ?></h2>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-12">
            <span class="badge me-2" style="background-color:#dc3545"><?= htmlspecialchars($translations['legend_expired']) ?></span>
            <span class="badge me-2" style="background-color:#fd7e14"><?= htmlspecialchars($translations['legend_expiring']) ?></span>
            <span class="badge me-2" style="background-color:#198754"><?= htmlspecialchars($translations['legend_active']) ?></span>
        </div>
    </div>

    <!-- Standard FullCalendar views -->
    <div class="row">
        <div class="col-12">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Custom year view (hidden by default) -->
    <div id="year-view" style="display:none">
        <div class="d-flex align-items-center gap-2 mb-3">
            <button class="btn btn-outline-secondary btn-sm" id="year-prev-btn" onclick="navigateYear(-1)">&#8249;</button>
            <button class="btn btn-outline-secondary btn-sm" id="year-next-btn" onclick="navigateYear(1)">&#8250;</button>
            <button class="btn btn-outline-secondary btn-sm" id="year-today-btn" onclick="navigateYear(0, true)">Today</button>
            <h5 id="year-view-title" class="mb-0 ms-2"></h5>
        </div>
        <div id="year-view-content">
            <div class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar v6 Standard Bundle -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js"></script>

<?php if ($fullcalendar_locale !== 'en'): ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/locales/<?= $fullcalendar_locale ?>.global.min.js"></script>
<?php endif; ?>

<style>
    #calendar {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        font-size: 14px;
    }

    /* ── Year view ──────────────────────────────────────────────── */
    .year-month-card {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0.5rem 0.4rem;
        background: #fff;
        height: 100%;
    }
    .year-month-title {
        font-weight: 600;
        text-align: center;
        text-transform: capitalize;
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
        color: #212529;
    }
    .year-day-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0;
    }
    .year-day-header {
        font-size: 0.6rem;
        text-align: center;
        color: #6c757d;
        font-weight: 700;
        padding: 1px 0 2px;
        text-transform: uppercase;
    }
    .year-day {
        font-size: 0.65rem;
        text-align: center;
        padding: 1px 0;
        min-height: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        cursor: default;
        border-radius: 3px;
    }
    .year-day.has-events {
        cursor: pointer;
    }
    .year-day.has-events:hover .year-day-num {
        background-color: #e9ecef;
        border-radius: 50%;
    }
    .year-day-num {
        width: 1.5em;
        height: 1.5em;
        line-height: 1.5em;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    .year-day.today .year-day-num {
        background-color: #0d6efd;
        color: #fff;
        font-weight: 700;
    }
    .year-event-dots {
        display: flex;
        gap: 1px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 1px;
    }
    .year-event-dot {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    /* Saturday/Sunday subtle tint */
    .year-day.weekend .year-day-num {
        color: #6c757d;
    }
</style>

<script>
    const TRANSLATIONS = <?php echo json_encode($translations); ?>;
    const EVENTS_URL   = '<?php echo site_url('deadlines_calendar/get_events'); ?>';
    const LOCALE       = '<?php echo $fullcalendar_locale; ?>';

    // Maps JS locale code → Intl locale string
    const INTL_LOCALE_MAP = { fr: 'fr-FR', nl: 'nl-NL', en: 'en-US' };
    const intlLocale = INTL_LOCALE_MAP[LOCALE] || 'en-US';

    let calendar;
    let yearViewYear   = new Date().getFullYear();
    let yearEventsByDate = {};
    let fcReady = false;   // prevents viewDidMount from hiding year view on initial render

    // ── FullCalendar init ────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const savedView  = localStorage.getItem('deadlinesCalendarView') || 'dayGridMonth';
        const initialView = (savedView === 'yearView') ? 'dayGridMonth' : savedView;

        // Localise the "Today" button in the year nav bar
        const todayLabel = new Intl.DateTimeFormat(intlLocale, {}).resolvedOptions
            ? new FullCalendar.Calendar(document.createElement('div'), { locale: LOCALE }).getOption('buttonText')?.today || 'Today'
            : 'Today';
        document.getElementById('year-today-btn').textContent = todayLabel;

        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: LOCALE,
            initialView: initialView,
            customButtons: {
                yearView: {
                    text: TRANSLATIONS.btn_year_view,
                    click: function () {
                        showYearView(calendar.getDate().getFullYear());
                    }
                }
            },
            headerToolbar: {
                left:   'prev,next today',
                center: 'title',
                right:  'dayGridMonth,timeGridWeek,timeGridDay,listMonth,yearView'
            },
            height: 'auto',
            firstDay: 1,
            weekNumbers: true,
            dayMaxEvents: true,
            editable: false,
            selectable: false,
            events: {
                url: EVENTS_URL,
                failure: function () {
                    alert('Erreur lors du chargement des événements.');
                }
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            },
            datesSet: function (info) {
                if (fcReady) {
                    localStorage.setItem('deadlinesCalendarView', info.view.type);
                }
            },
            viewDidMount: function () {
                // Any regular FullCalendar view mounting → leave year view
                if (fcReady) {
                    hideYearViewOnly();
                }
            }
        });

        calendar.render();
        fcReady = true;

        if (savedView === 'yearView') {
            showYearView(new Date().getFullYear());
        }
    });

    // ── Year view orchestration ──────────────────────────────────────────────
    function showYearView(year) {
        yearViewYear = year;

        // Swap visibility
        document.getElementById('calendar').style.display   = 'none';
        document.getElementById('year-view').style.display  = '';
        document.getElementById('year-view-title').textContent = year;

        // Mark year button active in FullCalendar toolbar
        setYearBtnActive(true);
        localStorage.setItem('deadlinesCalendarView', 'yearView');

        // Show spinner while fetching
        document.getElementById('year-view-content').innerHTML =
            '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div></div>';

        fetch(EVENTS_URL + '?start=' + year + '-01-01&end=' + year + '-12-31')
            .then(function (r) { return r.json(); })
            .then(function (events) { renderYearView(year, events); })
            .catch(function (err) {
                console.error('Year view fetch error:', err);
                document.getElementById('year-view-content').innerHTML =
                    '<div class="alert alert-danger">Erreur lors du chargement des événements.</div>';
            });
    }

    function hideYearViewOnly() {
        document.getElementById('year-view').style.display  = 'none';
        document.getElementById('calendar').style.display   = '';
        setYearBtnActive(false);
    }

    function navigateYear(delta, toToday) {
        if (toToday) {
            showYearView(new Date().getFullYear());
        } else {
            showYearView(yearViewYear + delta);
        }
    }

    function setYearBtnActive(active) {
        var btn = document.querySelector('.fc-yearView-button');
        if (!btn) return;
        if (active) {
            btn.classList.add('fc-button-active');
        } else {
            btn.classList.remove('fc-button-active');
        }
    }

    // ── Year grid rendering ──────────────────────────────────────────────────
    function renderYearView(year, events) {
        // Build date → events map
        yearEventsByDate = {};
        events.forEach(function (ev) {
            var date = ev.start;
            if (!yearEventsByDate[date]) yearEventsByDate[date] = [];
            yearEventsByDate[date].push(ev);
        });

        var today = new Date();
        var todayStr = isoDate(today.getFullYear(), today.getMonth() + 1, today.getDate());

        // Month names (capitalised)
        var monthFmt = new Intl.DateTimeFormat(intlLocale, { month: 'long' });

        // Abbreviated day headers (2 chars), Monday-first
        var dayFmt = new Intl.DateTimeFormat(intlLocale, { weekday: 'short' });
        var dayNames = [];
        for (var i = 0; i < 7; i++) {
            // 2000-01-03 was a Monday
            var d = new Date(2000, 0, 3 + i);
            dayNames.push(dayFmt.format(d).slice(0, 2));
        }

        // Build HTML: Bootstrap row with 12 month cols
        var html = '<div class="row g-3">';
        for (var m = 0; m < 12; m++) {
            html += '<div class="col-12 col-sm-6 col-md-4 col-lg-3">';
            html += buildMonthHtml(year, m, todayStr, monthFmt, dayNames);
            html += '</div>';
        }
        html += '</div>';

        document.getElementById('year-view-content').innerHTML = html;
    }

    function buildMonthHtml(year, month, todayStr, monthFmt, dayNames) {
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        // Day-of-week of 1st, shifted so Monday = 0
        var firstDow = (new Date(year, month, 1).getDay() + 6) % 7;

        var html = '<div class="year-month-card">';
        html += '<div class="year-month-title">' +
                    capitalize(monthFmt.format(new Date(year, month, 1))) +
                '</div>';
        html += '<div class="year-day-grid">';

        // Day-name header row
        dayNames.forEach(function (n) {
            html += '<div class="year-day-header">' + escHtml(n) + '</div>';
        });

        // Empty cells before 1st
        for (var i = 0; i < firstDow; i++) {
            html += '<div class="year-day empty"></div>';
        }

        // Day cells
        for (var day = 1; day <= daysInMonth; day++) {
            var dateStr = isoDate(year, month + 1, day);
            var evs     = yearEventsByDate[dateStr] || [];
            var dow     = (firstDow + day - 1) % 7; // 0=Mon … 6=Sun
            var isToday = (dateStr === todayStr);

            var cls = 'year-day';
            if (evs.length > 0) cls += ' has-events';
            if (isToday)        cls += ' today';
            if (dow >= 5)       cls += ' weekend';  // Sa=5, Su=6

            var onclick = evs.length > 0
                ? ' onclick="yearDayClick(\'' + dateStr + '\')"'
                : '';

            html += '<div class="' + cls + '"' + onclick + ' title="' + dateStr + '">';
            html += '<span class="year-day-num">' + day + '</span>';

            if (evs.length > 0) {
                html += '<div class="year-event-dots">';
                evs.slice(0, 3).forEach(function (ev) {
                    html += '<span class="year-event-dot" style="background-color:' +
                            escHtml(ev.color || '#3788d8') + '"></span>';
                });
                html += '</div>';
            }

            html += '</div>';
        }

        html += '</div>'; // .year-day-grid
        html += '</div>'; // .year-month-card
        return html;
    }

    // ── Day click: navigate FullCalendar to that month ───────────────────────
    function yearDayClick(dateStr) {
        var evs = yearEventsByDate[dateStr] || [];
        if (evs.length === 0) return;
        hideYearViewOnly();
        calendar.changeView('dayGridMonth', dateStr);
        // datesSet will fire and save 'dayGridMonth' to localStorage
    }

    // ── Utilities ────────────────────────────────────────────────────────────
    function isoDate(y, m, d) {
        return y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
