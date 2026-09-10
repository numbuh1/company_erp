<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FrameCAD Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { margin: 0; font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background: #f8fafc; color: #1e293b; }
        .dark body { background: #0f172a; color: #e2e8f0; }

        .viewer-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 1rem; }
        .dark .viewer-header { background: #1e293b; border-color: #334155; }

        .viewer-body { display: flex; height: calc(100vh - 57px); }

        .sidebar { width: 320px; min-width: 320px; background: #fff; border-right: 1px solid #e2e8f0; overflow-y: auto; display: flex; flex-direction: column; }
        .dark .sidebar { background: #1e293b; border-color: #334155; }

        .sidebar-section { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        .dark .sidebar-section { border-color: #334155; }
        .sidebar-section h3 { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.5rem; }

        .frame-list { flex: 1; overflow-y: auto; padding: 0.5rem; }
        .frame-item { padding: 0.5rem 0.75rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; display: flex; justify-content: space-between; align-items: center; }
        .frame-item:hover { background: #f1f5f9; }
        .dark .frame-item:hover { background: #334155; }
        .frame-item.active { background: #dbeafe; color: #1d4ed8; font-weight: 600; }
        .dark .frame-item.active { background: #1e3a5f; color: #93c5fd; }
        .frame-item .type-badge { font-size: 0.7rem; padding: 0.125rem 0.375rem; border-radius: 9999px; background: #e2e8f0; color: #64748b; }
        .dark .frame-item .type-badge { background: #334155; color: #94a3b8; }

        .canvas-area { flex: 1; position: relative; overflow: hidden; background: #f1f5f9; }
        .dark .canvas-area { background: #0f172a; }
        canvas { display: block; width: 100%; height: 100%; }

        .canvas-toolbar { position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; background: #fff; padding: 0.375rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
        .dark .canvas-toolbar { background: #1e293b; }
        .canvas-toolbar button { padding: 0.375rem 0.75rem; border-radius: 0.375rem; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; font-size: 0.8rem; color: #475569; }
        .dark .canvas-toolbar button { border-color: #475569; background: #334155; color: #cbd5e1; }
        .canvas-toolbar button:hover { background: #f1f5f9; }
        .dark .canvas-toolbar button:hover { background: #475569; }

        .canvas-info { position: absolute; top: 0.75rem; left: 0.75rem; font-size: 0.75rem; color: #94a3b8; pointer-events: none; }

        .upload-zone { border: 2px dashed #cbd5e1; border-radius: 0.75rem; padding: 3rem; text-align: center; cursor: pointer; transition: all 0.15s; margin: 1rem; }
        .upload-zone:hover, .upload-zone.dragover { border-color: #3b82f6; background: #eff6ff; }
        .dark .upload-zone { border-color: #475569; }
        .dark .upload-zone:hover, .dark .upload-zone.dragover { border-color: #60a5fa; background: #1e3a5f; }
        .upload-zone svg { width: 3rem; height: 3rem; margin: 0 auto 0.75rem; color: #94a3b8; }
        .upload-zone p { color: #64748b; font-size: 0.875rem; }

        .legend { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem 1rem; }
        .legend-item { display: flex; align-items: center; gap: 0.25rem; font-size: 0.7rem; color: #64748b; }
        .legend-dot { width: 12px; height: 4px; border-radius: 1px; }

        .detail-table { width: 100%; font-size: 0.8rem; }
        .detail-table td { padding: 0.25rem 0; }
        .detail-table td:first-child { color: #94a3b8; width: 40%; }

        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8; }

        .toggle-group { display: flex; gap: 0.25rem; }
        .toggle-btn { padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 0.25rem; border: 1px solid #e2e8f0; background: transparent; cursor: pointer; color: #64748b; }
        .dark .toggle-btn { border-color: #475569; color: #94a3b8; }
        .toggle-btn.on { background: #dbeafe; border-color: #93c5fd; color: #1d4ed8; }
        .dark .toggle-btn.on { background: #1e3a5f; border-color: #3b82f6; color: #93c5fd; }
    </style>
</head>
<body>
<div id="app">
    <div class="viewer-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.5rem;height:1.5rem;color:#3b82f6">
            <path d="M3 12h18M3 6h18M3 18h18"/>
        </svg>
        <div>
            <div style="font-weight:600;font-size:1.1rem">FrameCAD Viewer</div>
            <div id="job-info" style="font-size:0.75rem;color:#94a3b8"></div>
        </div>
        <div style="margin-left:auto">
            <label style="cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;font-size:0.8rem;padding:0.375rem 0.75rem;border-radius:0.375rem;border:1px solid #e2e8f0;background:#fff;color:#475569"
                   class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1rem;height:1rem">
                    <path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z"/>
                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/>
                </svg>
                Load XML
                <input type="file" id="file-input" accept=".xml" hidden>
            </label>
        </div>
    </div>

    <div class="viewer-body">
        <div class="sidebar" id="sidebar">
            <div id="upload-panel">
                <div class="upload-zone" id="drop-zone">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <p style="font-weight:600;margin-bottom:0.25rem">Drop FrameCAD XML here</p>
                    <p>or click to browse</p>
                </div>
            </div>

            <div id="frames-panel" style="display:none">
                <div class="sidebar-section">
                    <h3>Display</h3>
                    <div class="toggle-group">
                        <button class="toggle-btn on" data-layer="sticks" onclick="toggleLayer(this)">Members</button>
                        <button class="toggle-btn on" data-layer="fasteners" onclick="toggleLayer(this)">Fasteners</button>
                        <button class="toggle-btn on" data-layer="tools" onclick="toggleLayer(this)">Tool Actions</button>
                        <button class="toggle-btn on" data-layer="labels" onclick="toggleLayer(this)">Labels</button>
                        <button class="toggle-btn on" data-layer="dims" onclick="toggleLayer(this)">Dims</button>
                    </div>
                </div>
                <div class="sidebar-section" style="padding-bottom:0">
                    <h3>Frames</h3>
                </div>
                <div class="frame-list" id="frame-list"></div>
                <div class="sidebar-section" id="frame-detail" style="display:none">
                    <h3>Frame Details</h3>
                    <table class="detail-table" id="detail-table"></table>
                </div>
                <div class="sidebar-section" style="padding:0">
                    <div class="legend" id="legend"></div>
                </div>
            </div>
        </div>

        <div class="canvas-area" id="canvas-area">
            <div class="empty-state" id="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:4rem;height:4rem;margin-bottom:1rem;opacity:0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/>
                </svg>
                <p>Load a FrameCAD XML file to preview</p>
            </div>
            <canvas id="canvas" style="display:none"></canvas>
            <div class="canvas-toolbar" id="toolbar" style="display:none">
                <button onclick="zoomIn()">+ Zoom</button>
                <button onclick="zoomOut()">- Zoom</button>
                <button onclick="fitView()">Fit</button>
                <button onclick="prevFrame()">&larr; Prev</button>
                <button onclick="nextFrame()">Next &rarr;</button>
            </div>
            <div class="canvas-info" id="canvas-info"></div>
        </div>
    </div>
</div>

<script>
const COLORS = {
    TopPlate:    '#ef4444',
    BottomPlate: '#3b82f6',
    Stud:        '#22c55e',
    TrimStud:    '#16a34a',
    Brace:       '#f59e0b',
    Nog:         '#a855f7',
    HeadPlate:   '#ec4899',
    Sill:        '#06b6d4',
    default:     '#94a3b8',
};

const USAGE_LABELS = {
    TopPlate: 'Top Plate', BottomPlate: 'Bottom Plate', Stud: 'Stud',
    TrimStud: 'Trim Stud', Brace: 'Brace', Nog: 'Nogging',
    HeadPlate: 'Head Plate', Sill: 'Sill',
};

let allFrames = [];
let currentFrameIdx = -1;
let layers = { sticks: true, fasteners: true, tools: true, labels: true, dims: true };
let cam = { x: 0, y: 0, scale: 1 };
let isDragging = false, dragStart = { x: 0, y: 0 }, camStart = { x: 0, y: 0 };

const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

// File handling
document.getElementById('file-input').addEventListener('change', e => {
    if (e.target.files[0]) loadFile(e.target.files[0]);
});

const dropZone = document.getElementById('drop-zone');
dropZone.addEventListener('click', () => document.getElementById('file-input').click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) loadFile(e.dataTransfer.files[0]);
});

function loadFile(file) {
    const reader = new FileReader();
    reader.onload = e => parseXML(e.target.result);
    reader.readAsText(file);
}

function parsePoint(text) {
    const parts = text.trim().split(',').map(Number);
    return { x: parts[0], y: parts[1], z: parts[2] };
}

function parseXML(xmlText) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(xmlText, 'text/xml');
    const root = doc.querySelector('framecad_import');
    if (!root) { alert('Not a valid FrameCAD XML file.'); return; }

    const jobNum = root.querySelector('jobnum')?.textContent.trim().replace(/"/g, '') || '';
    const client = root.querySelector('client')?.textContent.trim().replace(/"/g, '') || '';
    const dateDrawn = root.querySelector('datedrawn')?.textContent.trim().replace(/"/g, '') || '';
    const planName = root.querySelector('plan')?.getAttribute('name') || '';
    const units = root.querySelector('drawing_info')?.getAttribute('units') || 'Metric';

    document.getElementById('job-info').textContent =
        [client, planName, dateDrawn].filter(Boolean).join(' • ');

    allFrames = [];
    root.querySelectorAll('frame').forEach(frameEl => {
        const frame = {
            name: frameEl.getAttribute('name'),
            type: frameEl.getAttribute('type'),
            elevation: parseFloat(frameEl.querySelector('elevation')?.textContent || '0'),
            envelope: [],
            sticks: [],
            fasteners: [],
            toolActions: [],
        };

        frameEl.querySelectorAll('envelope vertex').forEach(v => {
            frame.envelope.push(parsePoint(v.textContent));
        });

        frameEl.querySelectorAll('stick').forEach(s => {
            const profileEl = s.querySelector('profile');
            frame.sticks.push({
                name: s.getAttribute('name'),
                type: s.getAttribute('type'),
                usage: s.getAttribute('usage'),
                gauge: parseFloat(s.getAttribute('gauge') || '0'),
                start: parsePoint(s.querySelector('start').textContent),
                end: parsePoint(s.querySelector('end').textContent),
                profile: profileEl ? {
                    web: parseFloat(profileEl.getAttribute('web')),
                    shape: profileEl.getAttribute('shape'),
                } : null,
                flipped: s.querySelector('flipped')?.textContent.trim() === 'true',
            });
        });

        frameEl.querySelectorAll('fastener').forEach(f => {
            frame.fasteners.push({
                name: f.getAttribute('name'),
                count: parseInt(f.getAttribute('count') || '1'),
                point: parsePoint(f.querySelector('point').textContent),
            });
        });

        frameEl.querySelectorAll('tool_action').forEach(t => {
            frame.toolActions.push({
                name: t.getAttribute('name'),
                start: parsePoint(t.querySelector('start').textContent),
                end: parsePoint(t.querySelector('end').textContent),
            });
        });

        allFrames.push(frame);
    });

    renderFrameList();
    if (allFrames.length > 0) selectFrame(0);

    document.getElementById('upload-panel').style.display = 'none';
    document.getElementById('frames-panel').style.display = '';
    document.getElementById('empty-state').style.display = 'none';
    canvas.style.display = '';
    document.getElementById('toolbar').style.display = '';
}

function renderFrameList() {
    const list = document.getElementById('frame-list');
    list.innerHTML = allFrames.map((f, i) =>
        `<div class="frame-item${i === currentFrameIdx ? ' active' : ''}" onclick="selectFrame(${i})">
            <span>${f.name}</span>
            <span class="type-badge">${f.type.replace(/([A-Z])/g, ' $1').trim()}</span>
        </div>`
    ).join('');
}

function selectFrame(idx) {
    currentFrameIdx = idx;
    renderFrameList();
    showFrameDetail(allFrames[idx]);
    fitView();
}

function showFrameDetail(frame) {
    const detail = document.getElementById('frame-detail');
    const table = document.getElementById('detail-table');
    detail.style.display = '';

    const usages = {};
    frame.sticks.forEach(s => { usages[s.usage] = (usages[s.usage] || 0) + 1; });

    const env = getProjectedEnvelope(frame);
    const width = env ? Math.round(env.maxH - env.minH) : 0;
    const height = env ? Math.round(env.maxV - env.minV) : 0;

    table.innerHTML = `
        <tr><td>Name</td><td style="font-weight:600">${frame.name}</td></tr>
        <tr><td>Type</td><td>${frame.type.replace(/([A-Z])/g, ' $1').trim()}</td></tr>
        <tr><td>Size</td><td>${width} x ${height} mm</td></tr>
        <tr><td>Members</td><td>${frame.sticks.length}</td></tr>
        <tr><td>Fasteners</td><td>${frame.fasteners.length}</td></tr>
        ${Object.entries(usages).map(([k,v]) => `<tr><td style="padding-left:0.5rem">${USAGE_LABELS[k] || k}</td><td>${v}</td></tr>`).join('')}
    `;

    const legendEl = document.getElementById('legend');
    const usedColors = {};
    frame.sticks.forEach(s => { usedColors[s.usage] = COLORS[s.usage] || COLORS.default; });
    legendEl.innerHTML = Object.entries(usedColors).map(([usage, color]) =>
        `<span class="legend-item"><span class="legend-dot" style="background:${color}"></span>${USAGE_LABELS[usage] || usage}</span>`
    ).join('');
}

function getProjectedEnvelope(frame) {
    if (!frame.envelope.length) return null;
    const allPts = [...frame.envelope];
    frame.sticks.forEach(s => { allPts.push(s.start, s.end); });

    const xs = allPts.map(p => p.x);
    const ys = allPts.map(p => p.y);
    const xRange = Math.max(...xs) - Math.min(...xs);
    const yRange = Math.max(...ys) - Math.min(...ys);

    const useX = xRange > yRange;
    return {
        horizontal: useX ? 'x' : 'y',
        minH: useX ? Math.min(...xs) : Math.min(...ys),
        maxH: useX ? Math.max(...xs) : Math.max(...ys),
        minV: Math.min(...allPts.map(p => p.z)),
        maxV: Math.max(...allPts.map(p => p.z)),
    };
}

function project(point, env) {
    const h = env.horizontal === 'x' ? point.x : point.y;
    return { h: h - env.minH, v: point.z - env.minV };
}

// Canvas rendering
function resizeCanvas() {
    const area = document.getElementById('canvas-area');
    const dpr = window.devicePixelRatio || 1;
    canvas.width = area.clientWidth * dpr;
    canvas.height = area.clientHeight * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    draw();
}

function fitView() {
    if (currentFrameIdx < 0) return;
    const frame = allFrames[currentFrameIdx];
    const env = getProjectedEnvelope(frame);
    if (!env) return;

    const area = document.getElementById('canvas-area');
    const w = area.clientWidth;
    const h = area.clientHeight;
    const fw = env.maxH - env.minH;
    const fh = env.maxV - env.minV;
    const padding = 80;

    cam.scale = Math.min((w - padding * 2) / fw, (h - padding * 2) / fh);
    cam.x = (w - fw * cam.scale) / 2;
    cam.y = (h - fh * cam.scale) / 2;
    draw();
}

function toScreen(h, v) {
    const frame = allFrames[currentFrameIdx];
    const env = getProjectedEnvelope(frame);
    const fh = env.maxV - env.minV;
    return {
        x: cam.x + h * cam.scale,
        y: cam.y + (fh - v) * cam.scale,
    };
}

function draw() {
    if (currentFrameIdx < 0) return;
    const frame = allFrames[currentFrameIdx];
    const env = getProjectedEnvelope(frame);
    if (!env) return;

    const area = document.getElementById('canvas-area');
    const w = area.clientWidth;
    const h = area.clientHeight;

    ctx.clearRect(0, 0, w, h);

    // Draw envelope
    if (frame.envelope.length >= 4) {
        ctx.save();
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = '#94a3b844';
        ctx.lineWidth = 1;
        ctx.beginPath();
        const pts = frame.envelope.map(p => {
            const proj = project(p, env);
            return toScreen(proj.h, proj.v);
        });
        ctx.moveTo(pts[0].x, pts[0].y);
        for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i].x, pts[i].y);
        ctx.closePath();
        ctx.stroke();
        ctx.restore();
    }

    // Draw tool actions
    if (layers.tools) {
        frame.toolActions.forEach(t => {
            const s = project(t.start, env);
            const e = project(t.end, env);
            const ss = toScreen(s.h, s.v);
            const se = toScreen(e.h, e.v);

            ctx.save();
            if (t.name === 'Bolt') {
                ctx.strokeStyle = '#f97316';
                ctx.setLineDash([2, 3]);
                ctx.lineWidth = 1.5;
            } else {
                ctx.strokeStyle = '#06b6d488';
                ctx.setLineDash([6, 4]);
                ctx.lineWidth = 1;
            }
            ctx.beginPath();
            ctx.moveTo(ss.x, ss.y);
            ctx.lineTo(se.x, se.y);
            ctx.stroke();
            ctx.restore();

            if (t.name === 'Bolt') {
                ctx.fillStyle = '#f97316';
                ctx.beginPath();
                ctx.arc(ss.x, ss.y, 3, 0, Math.PI * 2);
                ctx.fill();
            }
        });
    }

    // Draw sticks
    if (layers.sticks) {
        frame.sticks.forEach(stick => {
            const s = project(stick.start, env);
            const e = project(stick.end, env);
            const ss = toScreen(s.h, s.v);
            const se = toScreen(e.h, e.v);
            const color = COLORS[stick.usage] || COLORS.default;

            ctx.strokeStyle = color;
            ctx.lineWidth = stick.usage === 'Brace' ? 2 : 3;
            ctx.lineCap = 'round';
            ctx.setLineDash([]);
            ctx.beginPath();
            ctx.moveTo(ss.x, ss.y);
            ctx.lineTo(se.x, se.y);
            ctx.stroke();

            // Labels
            if (layers.labels) {
                const mx = (ss.x + se.x) / 2;
                const my = (ss.y + se.y) / 2;
                const fontSize = Math.max(9, Math.min(11, 10 * cam.scale / 0.3));
                ctx.font = `600 ${fontSize}px Figtree, sans-serif`;
                ctx.fillStyle = color;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';

                const dx = se.x - ss.x;
                const dy = se.y - ss.y;
                const len = Math.sqrt(dx*dx + dy*dy);
                const nx = len > 0 ? -dy/len : 0;
                const ny = len > 0 ? dx/len : -1;
                const offset = 8;
                ctx.fillText(stick.name, mx + nx * offset, my + ny * offset);
            }
        });
    }

    // Draw fasteners
    if (layers.fasteners) {
        frame.fasteners.forEach(f => {
            const p = project(f.point, env);
            const sp = toScreen(p.h, p.v);
            ctx.fillStyle = '#f59e0b';
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.arc(sp.x, sp.y, 3.5, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
        });
    }

    // Draw dimensions
    if (layers.dims) {
        drawDimensions(frame, env);
    }

    // Canvas info
    document.getElementById('canvas-info').textContent =
        `${frame.name} • ${frame.type.replace(/([A-Z])/g, ' $1').trim()} • ${Math.round(cam.scale * 100)}%`;
}

function drawDimensions(frame, env) {
    const fh = env.maxV - env.minV;
    const fw = env.maxH - env.minH;

    ctx.save();
    ctx.strokeStyle = '#64748b88';
    ctx.fillStyle = '#64748b';
    ctx.lineWidth = 0.5;
    ctx.setLineDash([]);
    const fontSize = Math.max(8, Math.min(10, 9 * cam.scale / 0.3));
    ctx.font = `${fontSize}px Figtree, sans-serif`;

    // Overall width
    const bottomY = toScreen(0, -1).y;
    const dimY = bottomY + 30;
    const left = toScreen(0, 0);
    const right = toScreen(fw, 0);

    ctx.beginPath();
    ctx.moveTo(left.x, dimY);
    ctx.lineTo(right.x, dimY);
    ctx.stroke();

    // Ticks
    ctx.beginPath();
    ctx.moveTo(left.x, dimY - 4);
    ctx.lineTo(left.x, dimY + 4);
    ctx.moveTo(right.x, dimY - 4);
    ctx.lineTo(right.x, dimY + 4);
    ctx.stroke();

    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    ctx.fillText(`${Math.round(fw)} mm`, (left.x + right.x) / 2, dimY + 6);

    // Overall height
    const leftX = toScreen(-1, 0).x;
    const dimX = leftX - 30;
    const bottom = toScreen(0, 0);
    const top = toScreen(0, fh);

    ctx.beginPath();
    ctx.moveTo(dimX, bottom.y);
    ctx.lineTo(dimX, top.y);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(dimX - 4, bottom.y);
    ctx.lineTo(dimX + 4, bottom.y);
    ctx.moveTo(dimX - 4, top.y);
    ctx.lineTo(dimX + 4, top.y);
    ctx.stroke();

    ctx.save();
    ctx.translate(dimX - 8, (bottom.y + top.y) / 2);
    ctx.rotate(-Math.PI / 2);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'bottom';
    ctx.fillText(`${Math.round(fh)} mm`, 0, 0);
    ctx.restore();

    ctx.restore();
}

function toggleLayer(btn) {
    const layer = btn.dataset.layer;
    layers[layer] = !layers[layer];
    btn.classList.toggle('on', layers[layer]);
    draw();
}

function zoomIn() { cam.scale *= 1.3; draw(); }
function zoomOut() { cam.scale /= 1.3; draw(); }
function prevFrame() { if (currentFrameIdx > 0) selectFrame(currentFrameIdx - 1); }
function nextFrame() { if (currentFrameIdx < allFrames.length - 1) selectFrame(currentFrameIdx + 1); }

// Pan / zoom
canvas.addEventListener('mousedown', e => {
    isDragging = true;
    dragStart = { x: e.clientX, y: e.clientY };
    camStart = { x: cam.x, y: cam.y };
    canvas.style.cursor = 'grabbing';
});
canvas.addEventListener('mousemove', e => {
    if (!isDragging) return;
    cam.x = camStart.x + (e.clientX - dragStart.x);
    cam.y = camStart.y + (e.clientY - dragStart.y);
    draw();
});
canvas.addEventListener('mouseup', () => { isDragging = false; canvas.style.cursor = 'grab'; });
canvas.addEventListener('mouseleave', () => { isDragging = false; canvas.style.cursor = 'grab'; });
canvas.addEventListener('wheel', e => {
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;
    const factor = e.deltaY < 0 ? 1.1 : 0.9;
    const newScale = cam.scale * factor;

    cam.x = mx - (mx - cam.x) * (newScale / cam.scale);
    cam.y = my - (my - cam.y) * (newScale / cam.scale);
    cam.scale = newScale;
    draw();
}, { passive: false });

// Keyboard shortcuts
document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft') prevFrame();
    if (e.key === 'ArrowRight') nextFrame();
    if (e.key === 'f' || e.key === 'F') fitView();
    if (e.key === '+' || e.key === '=') zoomIn();
    if (e.key === '-') zoomOut();
});

window.addEventListener('resize', () => { resizeCanvas(); });
resizeCanvas();
canvas.style.cursor = 'grab';
</script>
</body>
</html>
