#!/bin/bash
# test-status.sh - GVV test status tracker and dashboard generator
#
# Usage: ./test-status.sh [--open]
#   --open   Open dashboard in Firefox after generation

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

mkdir -p build

OPEN_BROWSER=false
for arg in "$@"; do
    [ "$arg" = "--open" ] && OPEN_BROWSER=true
done

# Collect git state
export GVV_COMMIT GVV_COMMIT_MSG GVV_UNCOMMITTED GVV_UNCOMMITTED_RAW GVV_MIGRATION GVV_GIT_LOG

GVV_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
GVV_COMMIT_MSG=$(git log -1 --format=%s 2>/dev/null || echo "")
GVV_UNCOMMITTED_RAW=$(git status --short 2>/dev/null || echo "")
GVV_UNCOMMITTED=$(echo "$GVV_UNCOMMITTED_RAW" | grep -c '' || true)
GVV_MIGRATION=$(grep "migration_version" application/config/migration.php 2>/dev/null \
    | grep -oE '[0-9]+' || echo "?")
GVV_GIT_LOG=$(git log --format="%h|%ad|%s" --date=format:"%Y-%m-%d %H:%M" -n 300 2>/dev/null)

python3 << 'PYEOF'
import os, sys, json, glob, html as esc
import xml.etree.ElementTree as ET
from datetime import datetime

PROJECT_DIR    = os.getcwd()
HISTORY_FILE   = os.path.join(PROJECT_DIR, "build", "test-history.jsonl")
DASHBOARD_FILE = os.path.join(PROJECT_DIR, "build", "test-dashboard.html")
LOGS_DIR       = os.path.join(PROJECT_DIR, "build", "logs")
PLAYWRIGHT_XML = os.path.join(PROJECT_DIR, "playwright", "test-results", "playwright-junit.xml")

COMMIT          = os.environ.get('GVV_COMMIT', 'unknown')
COMMIT_MSG      = os.environ.get('GVV_COMMIT_MSG', '')
UNCOMMITTED     = int(os.environ.get('GVV_UNCOMMITTED', '0'))
UNCOMMITTED_RAW = os.environ.get('GVV_UNCOMMITTED_RAW', '')
MIGRATION       = os.environ.get('GVV_MIGRATION', '?')
GIT_LOG_RAW     = os.environ.get('GVV_GIT_LOG', '')

UNCOMMITTED_FILES = [l for l in UNCOMMITTED_RAW.splitlines() if l.strip()]

# ── XML parsing ───────────────────────────────────────────────────────────────

def parse_junit(filepath, display_name=None):
    if not os.path.exists(filepath):
        return None
    try:
        root = ET.parse(filepath).getroot()
        if root.tag == 'testsuites':
            if root.get('tests'):
                ts = root
            else:
                found = root.find('testsuite')
                ts = found if found is not None else root
        elif root.tag == 'testsuite':
            ts = root
        else:
            return None

        name   = display_name or ts.get('name', '') or os.path.basename(filepath)
        tests  = int(ts.get('tests',    0) or 0)
        fail   = int(ts.get('failures', 0) or 0)
        errors = int(ts.get('errors',   0) or 0)
        skip   = int(ts.get('skipped',  0) or 0)
        t_time = float(ts.get('time',   0) or 0)
        passed = max(0, tests - fail - errors - skip)
        mtime  = os.path.getmtime(filepath)
        return {
            "name":      name,
            "file":      os.path.basename(filepath),
            "tests":     tests,
            "failures":  fail,
            "errors":    errors,
            "skipped":   skip,
            "passed":    passed,
            "time":      round(t_time, 2),
            "file_date": datetime.fromtimestamp(mtime).strftime('%Y-%m-%d %H:%M'),
        }
    except Exception as e:
        print(f"Warning: cannot parse {filepath}: {e}", file=sys.stderr)
        return None

# ── Collect suites ────────────────────────────────────────────────────────────

suites = []
for xml_file in sorted(glob.glob(os.path.join(LOGS_DIR, "*junit*xml"))):
    r = parse_junit(xml_file)
    if r:
        suites.append(r)
r = parse_junit(PLAYWRIGHT_XML, display_name="Playwright")
if r:
    suites.append(r)
if not suites:
    print("Warning: no JUnit XML files found — nothing to record.", file=sys.stderr)

run_date = max((s['file_date'] for s in suites), default=datetime.now().strftime('%Y-%m-%d %H:%M'))

# ── Append to history ─────────────────────────────────────────────────────────

record = {
    "snapshot_date":     datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
    "run_date":          run_date,
    "commit":            COMMIT,
    "commit_message":    COMMIT_MSG,
    "uncommitted":       UNCOMMITTED,
    "uncommitted_files": UNCOMMITTED_FILES,
    "migration":         MIGRATION,
    "suites":            suites,
}
with open(HISTORY_FILE, 'a', encoding='utf-8') as f:
    f.write(json.dumps(record, ensure_ascii=False) + '\n')

# ── Console summary ───────────────────────────────────────────────────────────

total_t = sum(s['tests']               for s in suites)
total_f = sum(s['failures']+s['errors'] for s in suites)
total_s = sum(s['skipped']             for s in suites)
total_p = total_t - total_f - total_s
pct     = (total_p / total_t * 100) if total_t > 0 else 0

print(f"\nGVV Test Status — {run_date}")
print(f"  Commit: {COMMIT}  |  Migration: {MIGRATION}  |  Non-commités: {UNCOMMITTED}")
print(f"  Total : {total_p}/{total_t} ({pct:.1f}%)  skipped: {total_s}  failures: {total_f}")
print()
for s in suites:
    t, p  = s['tests'], s['passed']
    f     = s['failures'] + s['errors']
    pct_s = (p / t * 100) if t > 0 else 0
    mark  = "✓" if f == 0 else "✗"
    print(f"  {mark} {s['name']:<32} {p:>4}/{t:<4} ({pct_s:5.1f}%)  [{s['file_date']}]")

# ── Load history ──────────────────────────────────────────────────────────────

records = []
try:
    with open(HISTORY_FILE, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line:
                try:
                    records.append(json.loads(line))
                except Exception:
                    pass
except FileNotFoundError:
    pass

# Suite names ordered by first appearance
all_suite_names, seen = [], set()
for rec in records:
    for s in rec.get('suites', []):
        if s['name'] not in seen:
            seen.add(s['name'])
            all_suite_names.append(s['name'])

# ── Deduplicate suite run events ──────────────────────────────────────────────
# One event per (suite_name, file_date) — later records overwrite earlier ones.

def get_run_events(records):
    seen = {}
    for rec in records:
        ctx = {
            'commit':            rec.get('commit', ''),
            'migration':         rec.get('migration', '?'),
            'uncommitted':       rec.get('uncommitted', 0),
            'uncommitted_files': rec.get('uncommitted_files', []),
        }
        for s in rec.get('suites', []):
            key = (s['name'], s['file_date'])
            seen[key] = {
                'type':       'run',
                'date':       s['file_date'],
                'suite_name': s['name'],
                'tests':      s['tests'],
                'passed':     s['passed'],
                'fail':       s['failures'] + s.get('errors', 0),
                'skip':       s['skipped'],
                **ctx,
            }
    return list(seen.values())

all_run_events = get_run_events(records)

# ── Parse git log ─────────────────────────────────────────────────────────────

def parse_git_log(raw):
    result = []
    for line in raw.splitlines():
        parts = line.strip().split('|', 2)
        if len(parts) == 3:
            result.append({
                'type':    'commit',
                'hash':    parts[0].strip(),
                'date':    parts[1][:16].strip(),
                'message': parts[2].strip(),
            })
    return result

all_commits = parse_git_log(GIT_LOG_RAW)

# Filter commits to the range covered by the history
if records:
    min_date = min(rec['run_date'] for rec in records)[:10]
    commits = [c for c in all_commits if c['date'][:10] >= min_date]
else:
    commits = all_commits[:50]

# ── Shared helpers ────────────────────────────────────────────────────────────

LEGEND = (
    '<span class="ms-2 fw-normal" style="font-size:11px">'
    '<svg width="11" height="11"><rect width="11" height="11" fill="#28a745"/></svg>&nbsp;Réussis&ensp;'
    '<svg width="11" height="11"><rect width="11" height="11" fill="#ffc107"/></svg>&nbsp;Skipped&ensp;'
    '<svg width="11" height="11"><rect width="11" height="11" fill="#dc3545"/></svg>&nbsp;Échoués'
    '</span>'
)

def suite_stats(rec, suite_name=None):
    ss = rec.get('suites', [])
    if suite_name:
        ss = [s for s in ss if s['name'] == suite_name]
    total = sum(s['tests']               for s in ss)
    fail  = sum(s['failures']+s['errors'] for s in ss)
    skip  = sum(s['skipped']             for s in ss)
    return total, fail, skip, max(0, total - fail - skip)

def composite_latest(records, suite_names):
    total = fail = skip = passed = 0
    for name in suite_names:
        for rec in reversed(records):
            for s in rec.get('suites', []):
                if s['name'] == name:
                    total  += s['tests']
                    fail   += s['failures'] + s['errors']
                    skip   += s['skipped']
                    passed += s['passed']
                    break
            else:
                continue
            break
    return total, fail, skip, passed

def header_badges(total, fail, skip, passed):
    C1, C2, C3 = '155px', '80px', '80px'
    if total == 0:
        c1 = '<span class="badge bg-secondary">—</span>'
        c2 = c3 = '<span class="text-muted">—</span>'
    else:
        pct   = passed / total * 100
        color = 'danger' if fail > 0 else 'success'
        c1 = f'<span class="badge bg-{color}">{passed}/{total}&nbsp;({pct:.0f}%)</span>'
        c2 = (f'<span class="badge bg-warning text-dark">{skip}</span>'
              if skip > 0 else '<span class="text-muted">—</span>')
        c3 = (f'<span class="badge bg-danger">{fail}</span>'
              if fail > 0 else '<span class="text-muted">—</span>')
    return (
        f'<span class="col-badge" style="width:{C1}">{c1}</span>'
        f'<span class="col-badge" style="width:{C2}">{c2}</span>'
        f'<span class="col-badge" style="width:{C3}">{c3}</span>'
    )

def unc_cell(data):
    count = data.get('uncommitted', 0)
    files = data.get('uncommitted_files', [])
    if count == 0:
        return '<span class="badge bg-success">0</span>'
    if files:
        escaped = esc.escape('\n'.join(files))
        return (
            f'<details style="display:inline-block">'
            f'<summary style="cursor:pointer">'
            f'<span class="badge bg-warning text-dark">{count}</span></summary>'
            f'<pre style="font-size:10px;margin:4px 0 0">{escaped}</pre></details>'
        )
    return f'<span class="badge bg-warning text-dark">{count}</span>'

# ── SVG helpers ───────────────────────────────────────────────────────────────

BAR_W, BAR_GAP, CHART_H = 32, 4, 160
LEFT, TOP, LABEL_H = 10, 10, 55

def _svg_bars(bars, max_t):
    """Render a list of {date, total, fail, skip, passed, tip} dicts as SVG rects."""
    BOTTOM = TOP + CHART_H
    recent = bars[-50:]
    svg_w  = max(300, LEFT + len(recent) * (BAR_W + BAR_GAP) + 20)
    svg_h  = CHART_H + TOP + LABEL_H

    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{svg_w}" height="{svg_h}" '
        f'style="font-family:monospace;font-size:9px;display:block">',
        f'<line x1="{LEFT}" y1="{BOTTOM}" x2="{svg_w-5}" y2="{BOTTOM}" '
        f'stroke="#999" stroke-width="1"/>',
    ]
    for i, b in enumerate(recent):
        total, fail, skip, passed = b['total'], b['fail'], b['skip'], b['passed']
        if total == 0:
            continue
        scale = CHART_H / max_t
        h_p = max(1 if passed > 0 else 0, round(passed * scale))
        h_s = max(1 if skip   > 0 else 0, round(skip   * scale))
        h_f = max(1 if fail   > 0 else 0, round(fail   * scale))
        x   = LEFT + i * (BAR_W + BAR_GAP)
        tip = esc.escape(b.get('tip', b['date']))
        parts.append(f'<g><title>{tip}</title>')
        y = BOTTOM
        for h, color in [(h_p, '#28a745'), (h_s, '#ffc107'), (h_f, '#dc3545')]:
            if h > 0:
                y -= h
                parts.append(
                    f'<rect x="{x}" y="{y}" width="{BAR_W}" height="{h}" fill="{color}"/>'
                )
        lx, ly = x + BAR_W // 2, BOTTOM + 4
        parts.append(
            f'<text x="{lx}" y="{ly}" text-anchor="end" fill="#555" '
            f'transform="rotate(45 {lx} {ly})">{esc.escape(b["date"][:10])}</text>'
        )
        parts.append('</g>')
    parts.append('</svg>')
    return '\n'.join(parts)


def make_global_svg(commits, run_events):
    """Histogram where each bar = cumulated tests since last commit."""
    all_evs = sorted(
        [('c', c['date'], c) for c in commits] +
        [('r', r['date'], r) for r in run_events],
        key=lambda x: x[1]
    )
    bars = []
    accumulated = {}
    for ev_type, date, ev in all_evs:
        if ev_type == 'c':
            accumulated = {}
        else:
            accumulated[ev['suite_name']] = {
                'total': ev['tests'], 'fail': ev['fail'],
                'skip':  ev['skip'],  'passed': ev['passed'],
            }
            total  = sum(s['total']  for s in accumulated.values())
            fail   = sum(s['fail']   for s in accumulated.values())
            skip   = sum(s['skip']   for s in accumulated.values())
            passed = sum(s['passed'] for s in accumulated.values())
            pct    = (passed / total * 100) if total > 0 else 0
            bars.append({
                'date':   date,
                'total':  total, 'fail': fail,
                'skip':   skip,  'passed': passed,
                'tip':    f"{date} +{ev['suite_name']} | {passed}/{total} ({pct:.0f}%)",
            })
    if not bars:
        return '<p class="text-muted small">Pas de données</p>'
    max_t = max(b['total'] for b in bars) or 1
    return _svg_bars(bars, max_t)


def make_suite_svg(run_events, suite_name):
    """Histogram for a single suite: one bar per run."""
    runs = sorted([r for r in run_events if r['suite_name'] == suite_name],
                  key=lambda r: r['date'])
    if not runs:
        return '<p class="text-muted small">Pas de données</p>'
    bars = []
    for r in runs:
        pct = (r['passed'] / r['tests'] * 100) if r['tests'] > 0 else 0
        bars.append({
            'date':   r['date'],
            'total':  r['tests'], 'fail': r['fail'],
            'skip':   r['skip'],  'passed': r['passed'],
            'tip':    f"{r['date']} | {r['commit']} | {r['passed']}/{r['tests']} ({pct:.0f}%)",
        })
    max_t = max(b['total'] for b in bars) or 1
    return _svg_bars(bars, max_t)

# ── Timeline table ────────────────────────────────────────────────────────────

def make_timeline_table(commits, run_events, suite_name=None):
    """History table with commits and suite runs interleaved chronologically."""
    if suite_name:
        filtered = [r for r in run_events if r['suite_name'] == suite_name]
    else:
        filtered = run_events

    all_evs = sorted(
        [(c['date'], 'commit', c) for c in commits] +
        [(r['date'], 'run',    r) for r in filtered],
        key=lambda x: x[0], reverse=True
    )
    if not all_evs:
        return '<p class="text-muted small">Pas de données</p>'

    is_global  = suite_name is None
    n_cols     = 5 if is_global else 4
    suite_hdr  = '<th>Suite</th>' if is_global else ''
    rows = []

    for date, ev_type, ev in all_evs:
        if ev_type == 'commit':
            rows.append(
                f'<tr class="table-secondary" style="font-size:11px">'
                f'<td colspan="{n_cols}">'
                f'<span class="badge bg-dark me-1">commit</span>'
                f'<code>{esc.escape(ev["hash"])}</code>'
                f'<span class="ms-2">{esc.escape(ev["message"][:65])}</span>'
                f'<span class="float-end text-muted">{esc.escape(ev["date"])}</span>'
                f'</td></tr>'
            )
        else:
            tests, passed, fail, skip = ev['tests'], ev['passed'], ev['fail'], ev['skip']
            pct       = (passed / tests * 100) if tests > 0 else 0
            row_class = 'table-danger' if fail > 0 else ('table-warning' if skip > 0 else '')
            p_badge   = f'<span class="badge bg-success">{passed}</span>'
            s_badge   = (f'<span class="badge bg-warning text-dark ms-1">{skip}</span>'
                         if skip > 0 else '')
            f_badge   = (f'<span class="badge bg-danger ms-1">{fail}</span>'
                         if fail > 0 else '')
            pct_txt   = f'<small class="text-muted ms-1">({pct:.0f}%)</small>'
            suite_td  = (f'<td><small>{esc.escape(ev["suite_name"])}</small></td>'
                         if is_global else '')
            rows.append(
                f'<tr class="{row_class}">'
                f'<td style="white-space:nowrap"><small>{esc.escape(ev["date"])}</small></td>'
                f'<td><code>{esc.escape(ev["commit"])}</code>'
                f'<small class="text-muted ms-1">migr.{esc.escape(str(ev["migration"]))}</small></td>'
                f'<td class="text-center">{unc_cell(ev)}</td>'
                f'{suite_td}'
                f'<td class="text-center">{p_badge}{s_badge}{f_badge}{pct_txt}</td>'
                f'</tr>'
            )

    return (
        f'<div class="table-responsive">'
        f'<table class="table table-bordered table-sm mb-0">'
        f'<thead class="table-dark"><tr>'
        f'<th>Date</th><th>Commit / Migr.</th>'
        f'<th title="Fichiers non commités">Δ</th>'
        f'{suite_hdr}<th>Résultats</th>'
        f'</tr></thead>'
        f'<tbody>{"".join(rows)}</tbody>'
        f'</table></div>'
    )

# ── Accordion section builder ─────────────────────────────────────────────────

def make_section(sec_id, title, suite_name=None, is_open=False):
    shown     = 'show' if is_open else ''
    collapsed = ''    if is_open else 'collapsed'
    expanded  = 'true' if is_open else 'false'

    if suite_name is None:
        stats  = composite_latest(records, all_suite_names)
        badges = header_badges(*stats)
        svg    = make_global_svg(commits, all_run_events)
        table  = make_timeline_table(commits, all_run_events, suite_name=None)
    else:
        latest_run = next(
            (r for r in sorted(all_run_events, key=lambda r: r['date'], reverse=True)
             if r['suite_name'] == suite_name),
            None
        )
        badges = (header_badges(latest_run['tests'], latest_run['fail'],
                                latest_run['skip'],  latest_run['passed'])
                  if latest_run else '')
        svg    = make_suite_svg(all_run_events, suite_name)
        table  = make_timeline_table(commits, all_run_events, suite_name=suite_name)

    return f'''
  <div class="accordion-item">
    <h2 class="accordion-header" id="hdr-{sec_id}">
      <button class="accordion-button {collapsed}" type="button"
              data-bs-toggle="collapse" data-bs-target="#col-{sec_id}"
              aria-expanded="{expanded}" aria-controls="col-{sec_id}">
        <span class="acc-row"><strong class="acc-name">{esc.escape(title)}</strong>{badges}</span>
      </button>
    </h2>
    <div id="col-{sec_id}" class="accordion-collapse collapse {shown}"
         aria-labelledby="hdr-{sec_id}" data-bs-parent="#mainAccordion">
      <div class="accordion-body pt-2">
        <p class="mb-1 fw-semibold" style="font-size:12px">Historique {LEGEND}</p>
        <div class="mb-3 chart-scroll">{svg}</div>
        <p class="mb-1 fw-semibold" style="font-size:12px">Journal des exécutions</p>
        {table}
      </div>
    </div>
  </div>'''

# ── Build accordion ───────────────────────────────────────────────────────────

sections = [make_section('global', 'Synthèse globale', suite_name=None, is_open=True)]
for name in all_suite_names:
    safe_id = name.replace(' ', '-').replace('/', '-')
    sections.append(make_section(safe_id, name, suite_name=name))

accordion    = '\n'.join(sections)
generated_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

# ── Full HTML ─────────────────────────────────────────────────────────────────

html_out = f"""<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GVV Test Dashboard</title>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body  {{ font-size: 13px; }}
    code  {{ font-size: 12px; }}
    .table td, .table th {{ font-size: 12px; padding: 3px 6px; vertical-align: middle; }}
    .chart-scroll {{
      overflow-x: auto; background: #f8f9fa;
      border: 1px solid #dee2e6; border-radius: 4px; padding: 8px;
    }}
    /* Accordion column alignment */
    .accordion-button {{ padding: 8px 12px; }}
    .accordion-body   {{ padding: 12px; }}
    .acc-row  {{ display: flex; align-items: center; flex: 1; min-width: 0; }}
    .acc-name {{ flex: 1; min-width: 0; overflow: hidden;
                 text-overflow: ellipsis; white-space: nowrap; padding-right: 10px; }}
    .col-badge {{ display: inline-block; text-align: center;
                  flex-shrink: 0; padding: 0 2px; }}
    .acc-col-hdr {{
      display: flex; align-items: center;
      padding: 3px 12px; font-size: 11px; color: #6c757d;
      background: #e9ecef; border: 1px solid rgba(0,0,0,.125);
      border-bottom: 0; border-radius: .375rem .375rem 0 0;
    }}
    details summary   {{ list-style: none; }}
    details summary::-webkit-details-marker {{ display: none; }}
  </style>
</head>
<body class="p-3">

<h5 class="mb-1">GVV Test Dashboard</h5>
<p class="text-muted mb-3" style="font-size:11px">Généré le {generated_at}</p>

<div class="acc-col-hdr">
  <span class="acc-name" style="font-weight:600">Suite</span>
  <span class="col-badge" style="width:155px;font-weight:600">Réussis</span>
  <span class="col-badge" style="width:80px;font-weight:600">Skipped</span>
  <span class="col-badge" style="width:80px;font-weight:600">Échoués</span>
  <span style="min-width:28px"></span>
</div>
<div class="accordion" id="mainAccordion">
{accordion}
</div>

</body>
</html>"""

with open(DASHBOARD_FILE, 'w', encoding='utf-8') as f:
    f.write(html_out)

print(f"\nDashboard: file://{DASHBOARD_FILE}")
PYEOF

rc=$?
if [ $rc -ne 0 ]; then
    echo "Error: dashboard generation failed (exit $rc)" >&2
    exit $rc
fi

if [ "$OPEN_BROWSER" = true ]; then
    firefox "build/test-dashboard.html" &>/dev/null &
fi
