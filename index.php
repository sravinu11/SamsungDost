<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";
require_login();
$isAdmin = $_SESSION['role'] === 'ALL';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SAMSUNG DOST OJT</title>
<style>
  :root {
    color-scheme: light;
    --page:        #f9f9f7;
    --surface:     #fcfcfb;
    --surface-2:   #f3f2ee;
    --text-primary:#0b0b0b;
    --text-secondary:#52514e;
    --text-muted:  #898781;
    --grid:        #e1e0d9;
    --axis:        #c3c2b7;
    --border:      rgba(11,11,11,0.10);
    --accent:      #2a78d6;
    --accent2:     #eb6834;
    --slot3:       #1baf7a;
    --slot4:       #eda100;
    --slot5:       #e87ba4;
    --good:        #0ca30c;
    --warning:     #fab219;
    --critical:    #d03b3b;
    --tooltip-bg:  #0b0b0b;
    --tooltip-fg:  #ffffff;
    --glass-bg:      rgba(252,252,251,0.68);
    --glass-bg-2:    rgba(243,242,238,0.62);
    --glass-border:  rgba(255,255,255,0.55);
    --glass-shadow:  0 10px 34px rgba(20,20,20,0.10), inset 0 1px 0 rgba(255,255,255,0.45);
    --glass-shadow-hover: 0 18px 46px rgba(20,20,20,0.16), inset 0 1px 0 rgba(255,255,255,0.5);
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      color-scheme: dark;
      --page:        #0d0d0d;
      --surface:     #1a1a19;
      --surface-2:   #232322;
      --text-primary:#ffffff;
      --text-secondary:#c3c2b7;
      --text-muted:  #898781;
      --grid:        #2c2c2a;
      --axis:        #383835;
      --border:      rgba(255,255,255,0.10);
      --accent:      #3987e5;
      --accent2:     #d95926;
      --slot3:       #199e70;
      --slot4:       #c98500;
      --slot5:       #d55181;
      --tooltip-bg:  #fcfcfb;
      --tooltip-fg:  #0b0b0b;
      --glass-bg:      rgba(26,26,25,0.58);
      --glass-bg-2:    rgba(35,35,34,0.5);
      --glass-border:  rgba(255,255,255,0.10);
      --glass-shadow:  0 10px 34px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.06);
      --glass-shadow-hover: 0 18px 46px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.08);
    }
  }
  :root[data-theme="dark"] {
    color-scheme: dark;
    --page:        #0d0d0d;
    --surface:     #1a1a19;
    --surface-2:   #232322;
    --text-primary:#ffffff;
    --text-secondary:#c3c2b7;
    --text-muted:  #898781;
    --grid:        #2c2c2a;
    --axis:        #383835;
    --border:      rgba(255,255,255,0.10);
    --accent:      #3987e5;
    --accent2:     #d95926;
    --slot3:       #199e70;
    --slot4:       #c98500;
    --slot5:       #d55181;
    --tooltip-bg:  #fcfcfb;
    --tooltip-fg:  #0b0b0b;
    --glass-bg:      rgba(26,26,25,0.58);
    --glass-bg-2:    rgba(35,35,34,0.5);
    --glass-border:  rgba(255,255,255,0.10);
    --glass-shadow:  0 10px 34px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.06);
    --glass-shadow-hover: 0 18px 46px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.08);
  }

  * { box-sizing: border-box; }
  html, body { height: 100%; }
  body {
    margin: 0;
    background: var(--page);
    color: var(--text-primary);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    padding: 28px 24px 60px;
    position: relative;
  }
  .mesh {
    position: fixed; inset: -20%; z-index: 0; pointer-events: none; filter: blur(70px); opacity: 0.4;
    background:
      radial-gradient(circle at 15% 20%, var(--accent) 0%, transparent 42%),
      radial-gradient(circle at 85% 15%, var(--accent2) 0%, transparent 42%),
      radial-gradient(circle at 30% 85%, var(--slot3) 0%, transparent 42%),
      radial-gradient(circle at 80% 80%, var(--slot5) 0%, transparent 40%);
    animation: drift 30s ease-in-out infinite alternate;
  }
  @keyframes drift {
    0%   { transform: translate(0, 0) scale(1); }
    100% { transform: translate(-2.5%, 2.5%) scale(1.06); }
  }
  @media (prefers-reduced-motion: reduce) { .mesh { animation: none; } }
  .wrap { position: relative; z-index: 1; max-width: 1280px; margin: 0 auto; display: flex; flex-direction: column; gap: 22px; }

  .hdr { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; border-bottom: 1px solid var(--border); padding-bottom: 18px; }
  .hdr-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--accent); margin: 0 0 6px; }
  .hdr h1 { font-size: 28px; font-weight: 750; letter-spacing: -0.01em; margin: 0; }
  .hdr-meta { text-align: right; font-size: 12.5px; color: var(--text-muted); display: flex; align-items: center; gap: 14px; }
  .hdr-meta strong { color: var(--text-primary); font-variant-numeric: tabular-nums; }
  .hdr-user { text-align: right; }
  .hdr-user .who { font-size: 12.5px; color: var(--text-primary); font-weight: 650; }
  .hdr-user .role-pill {
    display: inline-block; font-size: 9.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
    color: var(--accent); background: rgba(42,120,214,0.12); border-radius: 999px; padding: 2px 8px; margin-left: 6px;
  }
  .hdr-user a { font-size: 11.5px; color: var(--text-muted); text-decoration: none; }
  .hdr-user a:hover { color: var(--critical); text-decoration: underline; }

  .logo-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
  .logo-chip {
    background: #ffffff; border-radius: 10px; padding: 7px 12px; display: inline-flex; align-items: center;
    border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 14px rgba(0,0,0,0.10);
  }
  .logo-chip img { display: block; height: 32px; width: auto; }
  .logo-chip.dost img { height: 42px; }

  .filter-bar {
    display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; position: relative; z-index: 15;
    background: var(--glass-bg); backdrop-filter: blur(18px) saturate(160%); -webkit-backdrop-filter: blur(18px) saturate(160%);
    border: 1px solid var(--glass-border); border-radius: 16px; padding: 14px 16px; box-shadow: var(--glass-shadow);
  }
  .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 148px; flex: 1 1 148px; }
  .filter-group label { font-size: 10px; font-weight: 650; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); }
  .filter-group select {
    font: inherit; font-size: 12.5px; color: var(--text-primary); background: var(--glass-bg-2);
    border: 1px solid var(--border); border-radius: 7px; padding: 7px 9px; appearance: none;
    background-image: linear-gradient(45deg, transparent 50%, var(--text-muted) 50%), linear-gradient(135deg, var(--text-muted) 50%, transparent 50%);
    background-position: calc(100% - 15px) center, calc(100% - 10px) center; background-size: 5px 5px, 5px 5px; background-repeat: no-repeat;
    padding-right: 26px; cursor: pointer;
  }
  .filter-group select:focus-visible { outline: 2px solid var(--accent); outline-offset: 1px; }
  .filter-group select:disabled { opacity: 0.65; cursor: default; background-image: none; padding-right: 9px; }

  .multiselect { position: relative; }
  .multiselect-trigger {
    width: 100%; text-align: left; font: inherit; font-size: 12.5px; color: var(--text-primary); background: var(--glass-bg-2);
    border: 1px solid var(--border); border-radius: 7px; padding: 7px 26px 7px 9px; cursor: pointer;
    background-image: linear-gradient(45deg, transparent 50%, var(--text-muted) 50%), linear-gradient(135deg, var(--text-muted) 50%, transparent 50%);
    background-position: calc(100% - 15px) center, calc(100% - 10px) center; background-size: 5px 5px, 5px 5px; background-repeat: no-repeat;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .multiselect-trigger:focus-visible { outline: 2px solid var(--accent); outline-offset: 1px; }
  .multiselect.open .multiselect-trigger { border-color: var(--accent); }
  .multiselect-panel {
    position: absolute; top: calc(100% + 6px); left: 0; min-width: 100%; z-index: 20;
    background: var(--glass-bg); backdrop-filter: blur(20px) saturate(160%); -webkit-backdrop-filter: blur(20px) saturate(160%);
    border: 1px solid var(--glass-border); border-radius: 10px; box-shadow: var(--glass-shadow-hover);
    padding: 6px; max-height: 240px; overflow-y: auto;
  }
  .multiselect-option {
    display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-primary);
    padding: 6px 8px; border-radius: 6px; cursor: pointer; white-space: nowrap;
  }
  .multiselect-option:hover { background: rgba(128,128,128,0.12); }
  .multiselect-option input { accent-color: var(--accent); cursor: pointer; }
  .multiselect-option .cnt { margin-left: auto; color: var(--text-muted); font-size: 11px; font-variant-numeric: tabular-nums; }
  .filter-reset {
    font: inherit; font-size: 12px; font-weight: 650; color: var(--text-secondary); background: transparent;
    border: 1px solid var(--border); border-radius: 7px; padding: 7.5px 12px; cursor: pointer; flex: none;
    transition: color 0.15s, border-color 0.15s, transform 0.15s;
  }
  .filter-reset:hover { color: var(--text-primary); border-color: var(--axis); transform: translateY(-1px); }
  .filter-count { font-size: 11.5px; color: var(--text-muted); flex: none; padding-bottom: 8px; }
  .filter-count strong { color: var(--accent); font-variant-numeric: tabular-nums; }

  .kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
  .kpi {
    background: var(--glass-bg); backdrop-filter: blur(18px) saturate(160%); -webkit-backdrop-filter: blur(18px) saturate(160%);
    border: 1px solid var(--glass-border); border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; gap: 6px;
    box-shadow: var(--glass-shadow); transition: transform 0.22s ease, box-shadow 0.22s ease;
  }
  .kpi:hover { transform: translateY(-3px); box-shadow: var(--glass-shadow-hover); }
  .kpi-label { font-size: 10.5px; font-weight: 650; letter-spacing: 0.07em; text-transform: uppercase; color: var(--text-muted); }
  .kpi-value { font-size: 25px; font-weight: 750; font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }
  .kpi-sub { font-size: 11.5px; color: var(--text-secondary); }
  .kpi-sub b { color: var(--text-primary); }

  .section-title { display: flex; align-items: baseline; gap: 10px; margin: 4px 0 0; }
  .section-title h2 { font-size: 16px; font-weight: 700; margin: 0; }
  .section-title span { font-size: 12px; color: var(--text-muted); }
  .grid-2 { display: grid; grid-template-columns: 1.3fr 1fr; gap: 14px; }
  .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .card {
    background: var(--glass-bg); backdrop-filter: blur(20px) saturate(160%); -webkit-backdrop-filter: blur(20px) saturate(160%);
    border: 1px solid var(--glass-border); border-radius: 16px; padding: 18px 20px 14px; display: flex; flex-direction: column; gap: 10px; min-width: 0;
    box-shadow: var(--glass-shadow); transition: transform 0.25s ease, box-shadow 0.25s ease;
  }
  .card:hover { transform: translateY(-4px); box-shadow: var(--glass-shadow-hover); }
  @media (prefers-reduced-motion: reduce) { .card, .kpi, .filter-reset { transition: none; } .card:hover, .kpi:hover { transform: none; } }
  .card-title { font-size: 13px; font-weight: 650; color: var(--text-primary); }
  .card-note { font-size: 11px; color: var(--text-muted); margin-top: -6px; }
  .chart-wrap { overflow-x: auto; }
  .chart-wrap svg { display: block; overflow: visible; }
  .empty-note { font-size: 12px; color: var(--text-muted); padding: 24px 0; text-align: center; }

  .legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: 11.5px; color: var(--text-secondary); }
  .legend-item { display: flex; align-items: center; gap: 6px; }
  .legend-item b { color: var(--text-primary); font-variant-numeric: tabular-nums; }
  .legend-swatch { width: 9px; height: 9px; border-radius: 2px; flex: none; }

  .viz-tooltip {
    position: fixed; pointer-events: none; z-index: 50;
    background: var(--tooltip-bg); color: var(--tooltip-fg);
    font-size: 11.5px; padding: 7px 10px; border-radius: 7px;
    line-height: 1.45; box-shadow: 0 6px 18px rgba(0,0,0,0.22);
    opacity: 0; transform: translate(-50%, -100%); transition: opacity 0.08s;
    white-space: nowrap; font-variant-numeric: tabular-nums;
  }
  .viz-tooltip b { font-weight: 700; }
  .viz-tooltip.show { opacity: 1; }

  .bar-hit { cursor: pointer; }
  .bar-hit:hover rect.mark, .bar-hit:hover path.mark { filter: brightness(1.08); }
  .axis-label { fill: var(--text-muted); font-size: 10.5px; }
  .axis-label.strong { fill: var(--text-secondary); font-weight: 600; }
  .value-label { fill: var(--text-primary); font-size: 10.5px; font-weight: 650; font-variant-numeric: tabular-nums; }
  .gridline { stroke: var(--grid); stroke-width: 1; }
  .baseline { stroke: var(--axis); stroke-width: 1; }

  .note-banner {
    font-size: 11.5px; color: var(--text-muted); background: var(--glass-bg-2);
    backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
    border: 1px solid var(--glass-border); border-radius: 10px; padding: 8px 12px;
  }

  .table-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .btn-export {
    font: inherit; font-size: 12.5px; font-weight: 650; color: #ffffff; background: var(--good);
    border: none; border-radius: 7px; padding: 8px 14px; cursor: pointer; flex: none;
    display: inline-flex; align-items: center; gap: 6px;
    box-shadow: 0 6px 16px rgba(12,163,12,0.3); transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
  }
  .btn-export:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 10px 22px rgba(12,163,12,0.38); }
  .btn-export:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
  .btn-export:disabled { opacity: 0.5; cursor: default; transform: none; }

  .table-scroll {
    overflow-x: auto; overflow-y: auto; max-height: 560px;
    border: 1px solid var(--glass-border); border-radius: 12px;
    background: var(--glass-bg-2); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
  }
  .data-table { border-collapse: collapse; width: max-content; min-width: 100%; font-size: 12px; }
  .data-table th, .data-table td {
    padding: 7px 10px; text-align: left; white-space: nowrap; border-bottom: 1px solid var(--border);
  }
  .data-table th {
    position: sticky; top: 0; background: var(--glass-bg); backdrop-filter: blur(10px); color: var(--text-secondary);
    font-weight: 650; font-size: 10.5px; letter-spacing: 0.03em; text-transform: uppercase; z-index: 1;
  }
  .data-table td { color: var(--text-primary); font-variant-numeric: tabular-nums; }
  .data-table tbody tr:hover { background: rgba(128,128,128,0.08); }

  .pagination { display: flex; align-items: center; justify-content: flex-end; gap: 10px; font-size: 12px; color: var(--text-secondary); }
  .pagination button {
    font: inherit; font-size: 12px; font-weight: 600; color: var(--text-secondary); background: var(--glass-bg-2);
    border: 1px solid var(--border); border-radius: 6px; padding: 5px 10px; cursor: pointer; transition: transform 0.15s ease;
  }
  .pagination button:disabled { opacity: 0.4; cursor: default; }
  .pagination button:not(:disabled):hover { color: var(--text-primary); border-color: var(--axis); transform: translateY(-1px); }
  .pagination span { font-variant-numeric: tabular-nums; }

  @media (max-width: 980px) {
    .kpi-row { grid-template-columns: repeat(3, 1fr); }
    .grid-2, .grid-3 { grid-template-columns: 1fr; }
  }
  @media (max-width: 560px) {
    .kpi-row { grid-template-columns: repeat(2, 1fr); }
  }
</style>
</head>
<body>
  <div class="mesh"></div>
  <div class="wrap">

    <div class="hdr">
      <div>
        <div class="logo-row">
          <span class="logo-chip"><img src="assets/quess-logo.png" alt="Quess"></span>
          <span class="logo-chip dost"><img src="assets/samsung-dost-logo.jpg" alt="Samsung Dost"></span>
        </div>
        <p class="hdr-eyebrow">CSR &middot; Workforce Onboarding</p>
        <h1>SAMSUNG DOST OJT</h1>
      </div>
      <div class="hdr-meta">
        <div>
          <div><strong id="metaTotal">&mdash;</strong> Candidates Tracked</div>
          <div>Live  &middot; <code>Samsung Dost</code></div>
        </div>
        <div class="hdr-user">
          <div class="who"><?= htmlspecialchars($_SESSION['display_name']) ?><span class="role-pill"><?= htmlspecialchars($_SESSION['role']) ?></span></div>
          <a href="logout.php">Sign out</a>
        </div>
      </div>
    </div>

    <div class="filter-bar" id="filterBar">
      <div class="filter-group">
        <label id="fBusinessLabel">Business</label>
        <div class="multiselect" id="fBusiness">
          <button type="button" class="multiselect-trigger" id="fBusinessTrigger" aria-haspopup="listbox" aria-expanded="false">All businesses</button>
          <div class="multiselect-panel" id="fBusinessPanel" role="listbox" aria-multiselectable="true" hidden></div>
        </div>
      </div>
      <div class="filter-group"><label for="fPartner">Implementation Partner</label><select id="fPartner"></select></div>
      <div class="filter-group"><label for="fVertical">Vertical Type</label><select id="fVertical"></select></div>
      <div class="filter-group"><label for="fRegion">Region</label><select id="fRegion"></select></div>
      <div class="filter-group"><label for="fState">State Name</label><select id="fState"></select></div>
      <div class="filter-group"><label for="fQual">Qualification</label><select id="fQual"></select></div>
      <button class="filter-reset" id="fReset" type="button">Reset filters</button>
      <div class="filter-count" id="fCount"></div>
    </div>

    <div class="kpi-row" id="kpiRow"></div>

    <div class="note-banner">--.</div>

    <div class="section-title"><h2>OJT Intake</h2><span>Daily candidate additions and partner coverage</span></div>
    <div class="grid-2">
      <div class="card">
        <div class="card-title">Daily OJT intake by business</div>
        <div class="card-note">Candidates added per OJT start date, split by business type</div>
        <div class="chart-wrap"><div id="chartIntake"></div></div>
      </div>
      <div class="card">
        <div class="card-title">Candidates by partner</div>
        <div class="card-note">OJT candidates managed by each implementation partner</div>
        <div class="chart-wrap"><div id="chartPartner"></div></div>
      </div>
    </div>

    <div class="section-title"><h2>OJT Duration &amp; Business Mix</h2><span>Training length and business-line split</span></div>
    <div class="grid-2">
      <div class="card">
        <div class="card-title">OJT duration (days)</div>
        <div class="chart-wrap"><div id="chartDuration"></div></div>
      </div>
      <div class="card">
        <div class="card-title">Business vertical mix</div>
        <div class="card-note">--</div>
        <div class="chart-wrap"><div id="chartBusiness"></div></div>
      </div>
    </div>

    <div class="section-title"><h2>Demographics</h2><span>Who the candidate pool is made up of</span></div>
    <div class="grid-3">
      <div class="card"><div class="card-title">Gender split</div><div class="chart-wrap"><div id="chartGender"></div></div></div>
      <div class="card"><div class="card-title">Age distribution</div><div class="chart-wrap"><div id="chartAge"></div></div></div>
      <div class="card"><div class="card-title">Qualification</div><div class="chart-wrap"><div id="chartQual"></div></div></div>
    </div>

    <div class="section-title"><h2>Region &amp; Partner Coverage</h2><span>Where candidates are being onboarded</span></div>
    <div class="grid-2">
      <div class="card"><div class="card-title">Candidates by region</div><div class="chart-wrap"><div id="chartRegion"></div></div></div>
      <div class="card">
        <div class="card-title">Top regions by partner</div>
        <div class="card-note">Candidate volume by implementation partner, top 8 regions</div>
        <div class="chart-wrap"><div id="chartRegionPartner"></div></div>
      </div>
    </div>
    <div class="card"><div class="card-title">Top states</div><div class="chart-wrap"><div id="chartState"></div></div></div>

    <div class="section-title"><h2>Candidate Records</h2><span>Full row-level data for the current filter selection</span></div>
    <div class="card">
      <div class="table-toolbar">
        <div class="card-note" id="tableCount" style="margin-top:0;">&nbsp;</div>
        <button class="btn-export" id="btnExport" type="button">Export to Excel</button>
      </div>
      <div class="table-scroll">
        <table class="data-table" id="dataTable">
          <thead><tr id="tableHead"></tr></thead>
          <tbody id="tableBody"></tbody>
        </table>
      </div>
      <div class="pagination" id="pagination"></div>
    </div>

  </div>

  <div class="viz-tooltip" id="tt"></div>

<script>
const cs = getComputedStyle(document.documentElement);
const V = name => cs.getPropertyValue(name).trim();
const COLORS = () => ({
  accent: V('--accent'), accent2: V('--accent2'), slot3: V('--slot3'), slot4: V('--slot4'),
  slot5: V('--slot5'), good: V('--good'), warning: V('--warning'), critical: V('--critical'),
  grid: V('--grid'), axis: V('--axis'), muted: V('--text-muted'), secondary: V('--text-secondary'),
  primary: V('--text-primary'), surface2: V('--surface-2')
});

const tt = document.getElementById('tt');
function showTip(evt, html) { tt.innerHTML = html; tt.classList.add('show'); moveTip(evt); }
function moveTip(evt) { tt.style.left = evt.clientX + 'px'; tt.style.top = (evt.clientY - 12) + 'px'; }
function hideTip() { tt.classList.remove('show'); }
document.addEventListener('mousemove', e => { if (tt.classList.contains('show')) moveTip(e); });

function svgEl(tag, attrs) {
  const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
  for (const k in attrs) el.setAttribute(k, attrs[k]);
  return el;
}
function fmt(n) { return Number(n).toLocaleString('en-IN'); }
function clear(el) { el.innerHTML = ''; }
function emptyState(el) { const p = document.createElement('div'); p.className = 'empty-note'; p.textContent = 'No candidates match the current filters.'; el.appendChild(p); }

function hBarChart(mount, data, opts = {}) {
  if (!data.length) return emptyState(mount);
  const color = opts.color || COLORS().accent;
  const W = opts.width || 560, rowH = opts.rowH || 26, gap = 8;
  const labelW = opts.labelW || 128;
  const max = Math.max(...data.map(d => d.count), 1);
  const plotW = W - labelW - 46;
  const H = data.length * (rowH + gap) - gap + 8;
  const svg = svgEl('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', height: H });
  data.forEach((d, i) => {
    const y = i * (rowH + gap);
    const w = Math.max(2, (d.count / max) * plotW);
    const g = svgEl('g', { class: 'bar-hit' });
    const lbl = svgEl('text', { x: labelW - 8, y: y + rowH / 2 + 4, 'text-anchor': 'end', class: 'axis-label strong' });
    lbl.textContent = d.name; g.appendChild(lbl);
    g.appendChild(svgEl('rect', { class: 'mark', x: labelW, y, width: w, height: rowH, rx: 4, fill: color }));
    const vt = svgEl('text', { x: labelW + w + 8, y: y + rowH / 2 + 4, class: 'value-label' });
    vt.textContent = fmt(d.count); g.appendChild(vt);
    g.addEventListener('mousemove', e => showTip(e, `<b>${d.name}</b><br>${fmt(d.count)} candidates`));
    g.addEventListener('mouseleave', hideTip);
    svg.appendChild(g);
  });
  mount.appendChild(svg);
}

function vBarChart(mount, data, opts = {}) {
  if (!data.length) return emptyState(mount);
  const color = opts.color || COLORS().accent;
  const W = opts.width || 560;
  const rotate = !!opts.rotateLabels;
  const padL = 34, padB = rotate ? 62 : 30, padT = 22, padR = 8;
  const H = opts.height || 240;
  const plotW = W - padL - padR, plotH = H - padB - padT;
  const max = Math.max(...data.map(d => d.count), 1) * 1.12;
  const bw = plotW / data.length;
  const svg = svgEl('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', height: H });
  [0.25, 0.5, 0.75, 1].forEach(f => {
    const y = padT + plotH * (1 - f);
    svg.appendChild(svgEl('line', { class: 'gridline', x1: padL, x2: W - padR, y1: y, y2: y }));
  });
  svg.appendChild(svgEl('line', { class: 'baseline', x1: padL, x2: W - padR, y1: padT + plotH, y2: padT + plotH }));
  data.forEach((d, i) => {
    const h = (d.count / max) * plotH;
    const x = padL + i * bw + bw * 0.18;
    const w = bw * 0.64;
    const y = padT + plotH - h;
    const g = svgEl('g', { class: 'bar-hit' });
    g.appendChild(svgEl('rect', { class: 'mark', x, y, width: w, height: Math.max(2, h), rx: 4, fill: color }));
    if (d.count > 0) {
      const val = svgEl('text', { x: x + w / 2, y: y - 6, 'text-anchor': 'middle', class: 'value-label' });
      val.textContent = fmt(d.count); g.appendChild(val);
    }
    const lblAttrs = rotate
      ? { x: x + w / 2, y: padT + plotH + 10, 'text-anchor': 'end', class: 'axis-label',
          transform: `rotate(-40 ${x + w / 2} ${padT + plotH + 10})` }
      : { x: x + w / 2, y: padT + plotH + 16, 'text-anchor': 'middle', class: 'axis-label' };
    const lbl = svgEl('text', lblAttrs);
    lbl.textContent = d.name; g.appendChild(lbl);
    g.addEventListener('mousemove', e => showTip(e, `<b>${d.name}</b><br>${fmt(d.count)} candidates`));
    g.addEventListener('mouseleave', hideTip);
    svg.appendChild(g);
  });
  mount.appendChild(svg);
}

function groupedBarChart(mount, data, series, opts = {}) {
  if (!data.length) return emptyState(mount);
  const W = opts.width || 560, H = opts.height || 260;
  const padL = 40, padB = 34, padT = 22, padR = 8;
  const plotW = W - padL - padR, plotH = H - padB - padT;
  const max = Math.max(...data.flatMap(d => series.map(s => d[s.key])), 1) * 1.15;
  const groupW = plotW / data.length;
  const barW = Math.min(22, groupW / (series.length + 1.4));
  const svg = svgEl('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', height: H });
  [0.25, 0.5, 0.75, 1].forEach(f => {
    const y = padT + plotH * (1 - f);
    svg.appendChild(svgEl('line', { class: 'gridline', x1: padL, x2: W - padR, y1: y, y2: y }));
  });
  svg.appendChild(svgEl('line', { class: 'baseline', x1: padL, x2: W - padR, y1: padT + plotH, y2: padT + plotH }));
  data.forEach((d, i) => {
    const gx = padL + i * groupW + groupW / 2 - (series.length * barW) / 2;
    series.forEach((s, si) => {
      const val = d[s.key] || 0;
      const h = (val / max) * plotH;
      const x = gx + si * barW + 2;
      const y = padT + plotH - h;
      const g = svgEl('g', { class: 'bar-hit' });
      g.appendChild(svgEl('rect', { class: 'mark', x, y, width: barW - 3, height: Math.max(2, h), rx: 3, fill: s.color }));
      if (val > 0 && barW >= 14) {
        const valLbl = svgEl('text', { x: x + (barW - 3) / 2, y: y - 4, 'text-anchor': 'middle', class: 'value-label', style: 'font-size:8.5px' });
        valLbl.textContent = fmt(val); g.appendChild(valLbl);
      }
      g.addEventListener('mousemove', e => showTip(e, `<b>${d.label}</b><br>${s.name}: ${fmt(val)}`));
      g.addEventListener('mouseleave', hideTip);
      svg.appendChild(g);
    });
    const lbl = svgEl('text', { x: padL + i * groupW + groupW / 2, y: padT + plotH + 16, 'text-anchor': 'middle', class: 'axis-label' });
    lbl.textContent = d.label; svg.appendChild(lbl);
  });
  mount.appendChild(svg);
}

function donutChart(mount, data, opts = {}) {
  const total = data.reduce((a, d) => a + d.count, 0);
  if (!total) return emptyState(mount);
  const size = opts.size || 190, r = size / 2 - 14, cx = size / 2, cy = size / 2, sw = opts.strokeWidth || 26;
  const svg = svgEl('svg', { viewBox: `0 0 ${size} ${size}`, width: size, height: size });
  const circumference = 2 * Math.PI * r;
  let offset = 0;
  const gapDeg = 2;
  data.forEach(d => {
    const frac = d.count / total;
    const len = circumference * frac - gapDeg;
    const g = svgEl('g', { class: 'bar-hit' });
    const circle = svgEl('circle', {
      class: 'mark', cx, cy, r, fill: 'none', stroke: d.color, 'stroke-width': sw,
      'stroke-dasharray': `${Math.max(0, len)} ${circumference - Math.max(0, len)}`,
      'stroke-dashoffset': -offset, transform: `rotate(-90 ${cx} ${cy})`, 'stroke-linecap': 'butt'
    });
    g.appendChild(circle);
    g.addEventListener('mousemove', e => showTip(e, `<b>${d.name}</b><br>${fmt(d.count)} (${(frac * 100).toFixed(1)}%)`));
    g.addEventListener('mouseleave', hideTip);
    svg.appendChild(g);
    if (frac >= 0.12) {
      const startFrac = offset / circumference;
      const midAngle = (-90 + startFrac * 360 + (frac * 360) / 2) * Math.PI / 180;
      const lx = cx + r * Math.cos(midAngle);
      const ly = cy + r * Math.sin(midAngle);
      const valText = svgEl('text', {
        x: lx, y: ly + 4, 'text-anchor': 'middle',
        style: 'font-size:11px;font-weight:700;fill:#ffffff;paint-order:stroke;stroke:rgba(0,0,0,0.4);stroke-width:2.5px;stroke-linejoin:round;',
      });
      valText.textContent = fmt(d.count);
      svg.appendChild(valText);
    }
    offset += circumference * frac;
  });
  const label = svgEl('text', { x: cx, y: cy - 3, 'text-anchor': 'middle', class: 'value-label', style: 'font-size:19px' });
  label.textContent = fmt(total);
  const sub = svgEl('text', { x: cx, y: cy + 14, 'text-anchor': 'middle', class: 'axis-label' });
  sub.textContent = 'total';
  svg.appendChild(label); svg.appendChild(sub);
  mount.appendChild(svg);
}

function legend(mount, items) {
  const el = document.createElement('div');
  el.className = 'legend';
  items.forEach(it => {
    const item = document.createElement('div');
    item.className = 'legend-item';
    const valuePart = it.count != null ? ` <b>${fmt(it.count)}</b>` : '';
    item.innerHTML = `<span class="legend-swatch" style="background:${it.color}"></span>${it.name}${valuePart}`;
    el.appendChild(item);
  });
  mount.appendChild(el);
}

/* ---------------- filters + data fetch ---------------- */
const filterDefs = [
  { id: 'fBusiness', field: 'business', label: 'All businesses', multi: true },
  { id: 'fPartner', field: 'partner', label: 'All partners' },
  { id: 'fVertical', field: 'vertical', label: 'All verticals' },
  { id: 'fRegion', field: 'region', label: 'All regions' },
  { id: 'fState', field: 'state', label: 'All states' },
  { id: 'fQual', field: 'qualification', label: 'All qualifications' },
];
const state = {};
filterDefs.forEach(fd => { state[fd.field] = []; });

function buildQuery() {
  const params = new URLSearchParams();
  filterDefs.forEach(fd => { if (state[fd.field].length) params.set(fd.field, state[fd.field].join(',')); });
  return params.toString();
}

async function loadAndRender() {
  tablePage = 1;
  const q = buildQuery();
  const [apiData, tableData] = await Promise.all([
    fetch('api.php?' + q).then(r => r.json()),
    fetchTableData(1, q),
  ]);
  renderFilterOptions(apiData.filterOptions);
  renderAll(apiData);
  renderTable(tableData);
}

function renderFilterOptions(filterOptions) {
  filterDefs.forEach(fd => {
    const opts = filterOptions[fd.field] || [];
    if (fd.multi) { renderMultiSelect(fd, opts); return; }

    const sel = document.getElementById(fd.id);
    const prevValue = state[fd.field][0] ?? '';
    clear(sel);
    const optAll = document.createElement('option');
    optAll.value = ''; optAll.textContent = fd.label;
    sel.appendChild(optAll);
    opts.forEach(opt => {
      const o = document.createElement('option');
      o.value = opt.label; o.textContent = `${opt.label} (${fmt(opt.c)})`;
      sel.appendChild(o);
    });
    const stillValid = opts.some(o => o.label === prevValue);
    if (fd.field === 'partner' && opts.length === 1) {
      sel.value = opts[0].label;
      state.partner = [opts[0].label];
      sel.disabled = true;
    } else if (prevValue !== '' && stillValid) {
      sel.value = prevValue;
      sel.disabled = false;
    } else {
      sel.value = '';
      state[fd.field] = [];
      sel.disabled = false;
    }
  });
}

function multiSelectTriggerLabel(fd, opts) {
  const sel = state[fd.field];
  if (!sel.length) return fd.label;
  if (sel.length <= 2) return sel.join(', ');
  return `${sel.length} selected`;
}

function renderMultiSelect(fd, opts) {
  state[fd.field] = state[fd.field].filter(v => opts.some(o => o.label === v));
  const trigger = document.getElementById(fd.id + 'Trigger');
  const panel = document.getElementById(fd.id + 'Panel');
  trigger.textContent = multiSelectTriggerLabel(fd, opts);
  clear(panel);
  opts.forEach(opt => {
    const row = document.createElement('label');
    row.className = 'multiselect-option';
    row.setAttribute('role', 'option');
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = state[fd.field].includes(opt.label);
    cb.addEventListener('change', () => {
      if (cb.checked) state[fd.field] = [...state[fd.field], opt.label];
      else state[fd.field] = state[fd.field].filter(v => v !== opt.label);
      trigger.textContent = multiSelectTriggerLabel(fd, opts);
      loadAndRender();
    });
    const txt = document.createElement('span');
    txt.textContent = opt.label;
    const cnt = document.createElement('span');
    cnt.className = 'cnt';
    cnt.textContent = fmt(opt.c);
    row.appendChild(cb); row.appendChild(txt); row.appendChild(cnt);
    panel.appendChild(row);
  });
}

function closeAllMultiSelects() {
  document.querySelectorAll('.multiselect.open').forEach(el => {
    el.classList.remove('open');
    el.querySelector('.multiselect-panel').hidden = true;
    el.querySelector('.multiselect-trigger').setAttribute('aria-expanded', 'false');
  });
}

function initFilters() {
  filterDefs.forEach(fd => {
    if (fd.multi) {
      const root = document.getElementById(fd.id);
      const trigger = document.getElementById(fd.id + 'Trigger');
      const panel = document.getElementById(fd.id + 'Panel');
      trigger.addEventListener('click', e => {
        e.stopPropagation();
        const willOpen = panel.hidden;
        closeAllMultiSelects();
        if (willOpen) {
          panel.hidden = false;
          root.classList.add('open');
          trigger.setAttribute('aria-expanded', 'true');
        }
      });
      panel.addEventListener('click', e => e.stopPropagation());
      return;
    }
    const sel = document.getElementById(fd.id);
    sel.addEventListener('change', () => {
      state[fd.field] = sel.value ? [sel.value] : [];
      loadAndRender();
    });
  });
  document.addEventListener('click', closeAllMultiSelects);

  document.getElementById('fReset').addEventListener('click', () => {
    filterDefs.forEach(fd => { state[fd.field] = []; });
    loadAndRender();
  });
}

function renderAll(data) {
  const c = COLORS();

  document.getElementById('metaTotal').textContent = fmt(data.filtered);
  const countEl = document.getElementById('fCount');
  countEl.innerHTML = data.filtered === data.total
    ? `Showing all <strong>${fmt(data.total)}</strong> candidates`
    : `Showing <strong>${fmt(data.filtered)}</strong> of ${fmt(data.total)} candidates`;

  const kpiRow = document.getElementById('kpiRow');
  clear(kpiRow);
  const kpis = [
    { label: 'Total candidates', value: fmt(data.filtered), sub: `${data.partner.length} partner(s) &middot; ${data.business.length} vertical(s)` },
    { label: 'NHIT test taken', value: `${data.kpis.nhit_pct}%`, sub: 'Of filtered candidates' },
    { label: 'Avg. age', value: data.filtered ? data.kpis.avg_age : '—', sub: 'years, across pool' },
    { label: 'Avg. OJT duration', value: data.filtered ? `${data.kpis.avg_duration}d` : '—', sub: 'days, across pool' },
    { label: 'Gender mix', value: data.filtered ? `${data.kpis.male_pct}% / ${data.kpis.female_pct}%` : '—', sub: 'Male / Female' },
  ];
  kpis.forEach(k => {
    const el = document.createElement('div');
    el.className = 'kpi';
    el.innerHTML = `<div class="kpi-label">${k.label}</div><div class="kpi-value">${k.value}</div><div class="kpi-sub">${k.sub}</div>`;
    kpiRow.appendChild(el);
  });

  const bizColors = { MX: c.accent, DA: c.accent2, VD: c.slot3 };
  const bizFallback = [c.accent, c.accent2, c.slot3, c.slot4];
  const intakeSeries = data.businessNames.map((name, i) => ({ key: name, name, color: bizColors[name] || bizFallback[i % bizFallback.length] }));
  const chartIntake = document.getElementById('chartIntake'); clear(chartIntake);
  groupedBarChart(chartIntake, data.dailyIntake, intakeSeries, { width: 700, height: 260 });
  if (data.dailyIntake.length) legend(chartIntake, intakeSeries.map(s => ({ name: s.name, color: s.color })));

  const chartPartner = document.getElementById('chartPartner'); clear(chartPartner);
  hBarChart(chartPartner, data.partner, { width: 480, labelW: 90, rowH: 30, color: c.accent });

  const chartDuration = document.getElementById('chartDuration'); clear(chartDuration);
  vBarChart(chartDuration, data.duration, { width: 560, height: 240, color: c.accent });

  const chartBusiness = document.getElementById('chartBusiness'); clear(chartBusiness);
  const bizData = data.business.map(d => ({ ...d, color: bizColors[d.name] || c.axis }));
  donutChart(chartBusiness, bizData, { size: 176 });
  if (data.filtered) legend(chartBusiness, bizData);

  const genderColors = { Male: c.accent, Female: c.slot5, Unknown: c.axis };
  const chartGender = document.getElementById('chartGender'); clear(chartGender);
  const genderData = data.gender.map(d => ({ ...d, color: genderColors[d.name] || c.axis }));
  donutChart(chartGender, genderData, { size: 176 });
  if (data.filtered) legend(chartGender, genderData);

  const chartAge = document.getElementById('chartAge'); clear(chartAge);
  vBarChart(chartAge, data.ageBuckets, { width: 340, height: 220, color: c.accent });

  const chartQual = document.getElementById('chartQual'); clear(chartQual);
  vBarChart(chartQual, data.qualification, { width: 340, height: 250, color: c.accent, rotateLabels: true });

  const chartRegion = document.getElementById('chartRegion'); clear(chartRegion);
  vBarChart(chartRegion, data.region, { width: 640, height: 280, color: c.accent, rotateLabels: true });

  const partnerColors = { TSSC: c.accent, ESSCI: c.accent2 };
  const fallbackColors = [c.accent, c.accent2, c.slot3, c.slot4];
  const partnerSeries = data.partnerNames.map((name, i) => ({ key: name, name, color: partnerColors[name] || fallbackColors[i % fallbackColors.length] }));
  const chartRegionPartner = document.getElementById('chartRegionPartner'); clear(chartRegionPartner);
  groupedBarChart(chartRegionPartner, data.regionPartner, partnerSeries, { width: 560, height: 250 });
  if (data.regionPartner.length) legend(chartRegionPartner, partnerSeries.map(s => ({ name: s.name, color: s.color })));

  const chartState = document.getElementById('chartState'); clear(chartState);
  vBarChart(chartState, data.state, { width: 1000, height: 280, color: c.accent, rotateLabels: true });
}

/* ---------------- candidate records table ---------------- */
let tablePage = 1;
const PAGE_SIZE = 50;

async function fetchTableData(page, query) {
  const params = new URLSearchParams(query);
  params.set('page', page);
  params.set('pageSize', PAGE_SIZE);
  const res = await fetch('rows.php?' + params.toString());
  return res.json();
}

async function loadTable(page) {
  tablePage = page;
  const data = await fetchTableData(page, buildQuery());
  renderTable(data);
}

function renderTable(data) {
  const head = document.getElementById('tableHead');
  const body = document.getElementById('tableBody');
  clear(head); clear(body);

  data.columns.forEach(col => {
    const th = document.createElement('th');
    th.textContent = col.replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
    head.appendChild(th);
  });

  if (!data.rows.length) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = data.columns.length || 1;
    td.className = 'empty-note';
    td.textContent = 'No candidates match the current filters.';
    tr.appendChild(td);
    body.appendChild(tr);
  } else {
    data.rows.forEach(row => {
      const tr = document.createElement('tr');
      row.forEach(val => {
        const td = document.createElement('td');
        td.textContent = val === null ? '' : val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });
  }

  const start = data.total ? (data.page - 1) * data.pageSize + 1 : 0;
  const end = Math.min(data.total, data.page * data.pageSize);
  document.getElementById('tableCount').textContent =
    data.total ? `Rows ${fmt(start)}–${fmt(end)} of ${fmt(data.total)}` : 'No rows';

  const pager = document.getElementById('pagination');
  clear(pager);
  const prevBtn = document.createElement('button');
  prevBtn.textContent = '← Prev';
  prevBtn.disabled = data.page <= 1;
  prevBtn.addEventListener('click', () => loadTable(data.page - 1));
  const nextBtn = document.createElement('button');
  nextBtn.textContent = 'Next →';
  nextBtn.disabled = data.page >= data.pages;
  nextBtn.addEventListener('click', () => loadTable(data.page + 1));
  const info = document.createElement('span');
  info.textContent = `Page ${data.page} of ${data.pages}`;
  pager.appendChild(prevBtn); pager.appendChild(info); pager.appendChild(nextBtn);
}

document.getElementById('btnExport').addEventListener('click', () => {
  window.location = 'export.php?' + buildQuery();
});

initFilters();
loadAndRender();
</script>
</body>
</html>
