<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FrameCAD Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { margin:0; font-family:'Figtree',ui-sans-serif,system-ui,sans-serif; background:#f8fafc; color:#1e293b; }
        .viewer-header { background:#fff; border-bottom:1px solid #e2e8f0; padding:0.5rem 1.25rem; display:flex; align-items:center; gap:0.75rem; }
        .viewer-body { display:flex; height:calc(100vh - 49px); }
        .sidebar { width:380px; min-width:380px; background:#fff; border-right:1px solid #e2e8f0; display:flex; flex-direction:column; overflow:hidden; }
        .sidebar-section { padding:0.75rem 1rem; border-bottom:1px solid #e2e8f0; }
        .sidebar-section h3 { font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:0.375rem; }
        .canvas-area { flex:1; position:relative; overflow:hidden; background:#f1f5f9; }
        canvas { display:block; width:100%; height:100%; }
        .canvas-toolbar { position:absolute; bottom:1rem; left:50%; transform:translateX(-50%); display:flex; gap:0.375rem; background:#fff; padding:0.25rem; border-radius:0.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.15); }
        .canvas-toolbar button { padding:0.25rem 0.625rem; border-radius:0.375rem; border:1px solid #e2e8f0; background:#fff; cursor:pointer; font-size:0.75rem; color:#475569; }
        .canvas-toolbar button:hover { background:#f1f5f9; }
        .canvas-info { position:absolute; top:0.5rem; left:0.5rem; font-size:0.7rem; color:#94a3b8; pointer-events:none; }

        .upload-zone { border:2px dashed #cbd5e1; border-radius:0.75rem; padding:3rem; text-align:center; cursor:pointer; transition:all 0.15s; margin:1rem; }
        .upload-zone:hover,.upload-zone.dragover { border-color:#3b82f6; background:#eff6ff; }
        .upload-zone svg { width:3rem; height:3rem; margin:0 auto 0.75rem; color:#94a3b8; }
        .upload-zone p { color:#64748b; font-size:0.875rem; }
        .empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8; }

        .toggle-group { display:flex; gap:0.25rem; flex-wrap:wrap; }
        .toggle-btn { padding:0.2rem 0.4rem; font-size:0.7rem; border-radius:0.25rem; border:1px solid #e2e8f0; background:transparent; cursor:pointer; color:#64748b; }
        .toggle-btn.on { background:#dbeafe; border-color:#93c5fd; color:#1d4ed8; }

        .header-btn { display:inline-flex; align-items:center; gap:0.375rem; font-size:0.75rem; padding:0.3rem 0.625rem; border-radius:0.375rem; border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer; }
        .header-btn:hover { background:#f1f5f9; }
        .header-btn:disabled { opacity:0.4; cursor:default; }
        .header-btn.primary { background:#3b82f6; color:#fff; border-color:#3b82f6; }
        .header-btn.primary:hover { background:#2563eb; }

        .sidebar-tabs { display:flex; border-bottom:1px solid #e2e8f0; }
        .tab-btn { flex:1; padding:0.5rem; text-align:center; font-size:0.75rem; font-weight:600; border:none; background:transparent; cursor:pointer; color:#94a3b8; border-bottom:2px solid transparent; }
        .tab-btn.active { color:#3b82f6; border-bottom-color:#3b82f6; }

        .frame-list { overflow-y:auto; flex:1; padding:0.25rem; }
        .frame-item { padding:0.375rem 0.625rem; border-radius:0.375rem; cursor:pointer; font-size:0.8rem; display:flex; justify-content:space-between; align-items:center; }
        .frame-item:hover { background:#f1f5f9; }
        .frame-item.active { background:#dbeafe; color:#1d4ed8; font-weight:600; }
        .frame-item .type-badge { font-size:0.65rem; padding:0.1rem 0.3rem; border-radius:9999px; background:#e2e8f0; color:#64748b; }

        .detail-table { width:100%; font-size:0.75rem; }
        .detail-table td { padding:0.15rem 0; }
        .detail-table td:first-child { color:#94a3b8; width:35%; }

        .legend { display:flex; flex-wrap:wrap; gap:0.375rem; padding:0.5rem 1rem; border-top:1px solid #e2e8f0; }
        .legend-item { display:flex; align-items:center; gap:0.2rem; font-size:0.65rem; color:#64748b; }
        .legend-dot { width:10px; height:3px; border-radius:1px; }

        /* Components tab */
        .comp-table { width:100%; font-size:0.7rem; border-collapse:collapse; }
        .comp-table thead th { position:sticky; top:0; background:#f8fafc; padding:0.3rem 0.25rem; text-align:left; font-weight:600; color:#94a3b8; font-size:0.65rem; text-transform:uppercase; border-bottom:1px solid #e2e8f0; z-index:1; }
        .comp-table thead th.r { text-align:right; }
        .comp-table tbody tr { cursor:pointer; border-bottom:1px solid #f1f5f9; }
        .comp-table tbody tr:hover { background:#f8fafc; }
        .comp-table tbody tr.selected { background:#eff6ff; }
        .comp-table td { padding:0.3rem 0.25rem; vertical-align:middle; }
        .comp-table td.r { text-align:right; font-family:ui-monospace,monospace; }
        .comp-table .color-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
        .comp-table input[type=number] { width:3.5rem; padding:0.1rem 0.2rem; font-size:0.7rem; border:1px solid #e2e8f0; border-radius:0.2rem; text-align:right; font-family:ui-monospace,monospace; background:#fff; }
        .comp-table input[type=number]:focus { outline:none; border-color:#93c5fd; }
        .delta-neg { color:#ef4444; font-weight:600; }
        .delta-pos { color:#22c55e; font-weight:600; }
        .delta-zero { color:#cbd5e1; }

        .comp-detail { background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.375rem; margin:0.5rem 0.75rem; padding:0.625rem; }
        .comp-detail h4 { font-size:0.75rem; font-weight:600; margin-bottom:0.375rem; display:flex; align-items:center; gap:0.375rem; }
        .comp-detail .profile-info { font-size:0.65rem; color:#94a3b8; margin-bottom:0.5rem; }
        .compare-table { width:100%; font-size:0.7rem; font-family:ui-monospace,monospace; border-collapse:collapse; }
        .compare-table th { text-align:right; font-weight:500; color:#94a3b8; padding:0.2rem 0.375rem; font-size:0.65rem; }
        .compare-table th:first-child { text-align:left; }
        .compare-table td { text-align:right; padding:0.2rem 0.375rem; }
        .compare-table td:first-child { text-align:left; color:#64748b; font-family:inherit; }
        .compare-table .delta-col { color:#3b82f6; }
        .compare-table tr.len-row { border-top:1px solid #e2e8f0; font-weight:600; }
        .compare-table input[type=number] { width:5rem; padding:0.15rem 0.25rem; font-size:0.7rem; border:1px solid #cbd5e1; border-radius:0.2rem; text-align:right; font-family:ui-monospace,monospace; background:#fff; }
        .compare-table input[type=number]:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,0.15); }
        .compare-table input[type=number].overridden { border-color:#f59e0b; background:#fffbeb; }
        .coord-reset-btn { font-size:0.6rem; padding:0.05rem 0.3rem; border:1px solid #e2e8f0; border-radius:0.2rem; background:#fff; color:#94a3b8; cursor:pointer; margin-left:0.2rem; vertical-align:middle; }
        .coord-reset-btn:hover { background:#fee2e2; color:#ef4444; border-color:#fca5a5; }

        .batch-row { display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap; }
        .batch-row select,.batch-row input { font-size:0.7rem; padding:0.2rem 0.3rem; border:1px solid #e2e8f0; border-radius:0.25rem; }
        .batch-row input { width:3.5rem; text-align:right; font-family:ui-monospace,monospace; }
        .batch-row .apply-btn { font-size:0.7rem; padding:0.2rem 0.5rem; background:#3b82f6; color:#fff; border:none; border-radius:0.25rem; cursor:pointer; }
        .batch-row .apply-btn:hover { background:#2563eb; }
        .batch-row label { font-size:0.65rem; color:#64748b; display:flex; align-items:center; gap:0.2rem; }
        .batch-check { display:flex; align-items:center; gap:0.25rem; font-size:0.65rem; color:#64748b; margin-top:0.375rem; }

        /* Compare modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:100; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(2px); }
        .modal-box { background:#fff; border-radius:0.75rem; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); width:90vw; max-width:1200px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1.25rem; border-bottom:1px solid #e2e8f0; }
        .modal-header h2 { font-size:1rem; font-weight:700; }
        .modal-close { width:2rem; height:2rem; display:flex; align-items:center; justify-content:center; border:none; background:transparent; cursor:pointer; border-radius:0.375rem; font-size:1.25rem; color:#64748b; }
        .modal-close:hover { background:#f1f5f9; }
        .modal-body { flex:1; display:flex; gap:1px; background:#e2e8f0; overflow:hidden; min-height:0; }
        .compare-pane { flex:1; display:flex; flex-direction:column; background:#f8fafc; min-width:0; }
        .compare-pane-header { padding:0.5rem 1rem; background:#fff; border-bottom:1px solid #e2e8f0; font-size:0.8rem; font-weight:600; text-align:center; }
        .compare-pane-header.before { color:#ef4444; }
        .compare-pane-header.after { color:#22c55e; }
        .compare-pane canvas { flex:1; display:block; width:100%; }
        .modal-footer { padding:0.5rem 1.25rem; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:0.5rem; }
        .modal-nav-btn { padding:0.3rem 0.75rem; border-radius:0.375rem; border:1px solid #e2e8f0; background:#fff; cursor:pointer; font-size:0.75rem; color:#475569; }
        .modal-nav-btn:hover { background:#f1f5f9; }
    </style>
</head>
<body>
<div id="app">
    <div class="viewer-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1.25rem;height:1.25rem;color:#3b82f6"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        <div>
            <div style="font-weight:600;font-size:1rem">FrameCAD Viewer</div>
            <div id="job-info" style="font-size:0.7rem;color:#94a3b8"></div>
        </div>
        <div style="margin-left:auto;display:flex;gap:0.5rem">
            <label class="header-btn" style="cursor:pointer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:0.875rem;height:0.875rem"><path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
                Load XML
                <input type="file" id="file-input" accept=".xml" hidden>
            </label>
            <button class="header-btn" id="compare-btn" onclick="showCompareModal()" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:0.875rem;height:0.875rem"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.06-7.983a.75.75 0 00-1.06 0l-.312.311A7 7 0 003.288 6.89a.75.75 0 101.449.39A5.5 5.5 0 0113.938 4.89l.312-.311H11.817a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V1.694a.75.75 0 00-.75-.75z" clip-rule="evenodd"/></svg>
                Compare
            </button>
            <button class="header-btn primary" id="export-btn" onclick="exportXML()" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:0.875rem;height:0.875rem"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
                Export XML
            </button>
        </div>
    </div>

    <div class="viewer-body">
        <div class="sidebar" id="sidebar">
            <div id="upload-panel">
                <div class="upload-zone" id="drop-zone">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <p style="font-weight:600;margin-bottom:0.25rem">Drop FrameCAD XML here</p>
                    <p>or click to browse</p>
                </div>
            </div>

            <div id="main-panel" style="display:none;flex:1;display:flex;flex-direction:column;overflow:hidden">
                <div class="sidebar-section" style="padding:0.5rem 1rem">
                    <div class="toggle-group">
                        <button class="toggle-btn on" data-layer="sticks" onclick="toggleLayer(this)">Members</button>
                        <button class="toggle-btn on" data-layer="fasteners" onclick="toggleLayer(this)">Fasteners</button>
                        <button class="toggle-btn on" data-layer="tools" onclick="toggleLayer(this)">Tools</button>
                        <button class="toggle-btn on" data-layer="labels" onclick="toggleLayer(this)">Labels</button>
                        <button class="toggle-btn on" data-layer="dims" onclick="toggleLayer(this)">Dims</button>
                        <button class="toggle-btn" data-layer="ghost" onclick="toggleLayer(this)">Ghost</button>
                    </div>
                </div>

                <div class="sidebar-section" style="padding:0.5rem 1rem">
                    <h3>Transform</h3>
                    <div style="display:flex;gap:0.375rem;align-items:center;flex-wrap:wrap">
                        <label style="display:flex;align-items:center;gap:0.2rem;font-size:0.7rem;cursor:pointer">
                            <input type="checkbox" id="reset-origin" checked onchange="onTransformChange()"> Reset Origin
                        </label>
                        <button class="toggle-btn" id="flip-h-btn" onclick="toggleFlipH()">Flip H</button>
                        <button class="toggle-btn" id="flip-v-btn" onclick="toggleFlipV()">Flip V</button>
                    </div>
                </div>

                <div class="sidebar-tabs">
                    <button class="tab-btn active" id="tab-frames-btn" onclick="switchTab('frames')">Frames</button>
                    <button class="tab-btn" id="tab-comp-btn" onclick="switchTab('components')">Components</button>
                </div>

                <!-- Frames tab -->
                <div id="frames-tab" style="flex:1;display:flex;flex-direction:column;overflow:hidden">
                    <div class="frame-list" id="frame-list"></div>
                    <div class="sidebar-section" id="frame-detail" style="display:none">
                        <h3>Frame Details</h3>
                        <table class="detail-table" id="detail-table"></table>
                    </div>
                </div>

                <!-- Components tab -->
                <div id="components-tab" style="display:none;flex:1;flex-direction:column;overflow:hidden">
                    <div class="sidebar-section" style="padding:0.5rem 0.75rem">
                        <h3>Batch Trim</h3>
                        <div class="batch-row">
                            <select id="batch-type">
                                <option value="all">All Types</option>
                                <option value="Stud">Stud</option>
                                <option value="TrimStud">Trim Stud</option>
                                <option value="TopPlate">Top Plate</option>
                                <option value="BottomPlate">Bottom Plate</option>
                                <option value="Brace">Brace</option>
                                <option value="Nog">Nogging</option>
                                <option value="HeadPlate">Head Plate</option>
                                <option value="Sill">Sill</option>
                            </select>
                            <label>S:<input type="number" id="batch-start" step="0.1" min="0" value="0"></label>
                            <label>E:<input type="number" id="batch-end" step="0.1" min="0" value="0"></label>
                            <button class="apply-btn" onclick="applyBatchTrim()">Apply</button>
                        </div>
                        <label class="batch-check">
                            <input type="checkbox" id="batch-all-frames"> Apply to all frames
                        </label>
                    </div>
                    <div style="overflow-y:auto;flex:1">
                        <table class="comp-table" id="comp-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th class="r">S.Trim</th>
                                    <th class="r">E.Trim</th>
                                    <th class="r">Length</th>
                                    <th class="r">New</th>
                                    <th class="r">&Delta;</th>
                                </tr>
                            </thead>
                            <tbody id="comp-tbody"></tbody>
                        </table>
                        <div id="comp-detail-area"></div>
                    </div>
                </div>

                <div class="legend" id="legend"></div>
            </div>
        </div>

        <div class="canvas-area" id="canvas-area">
            <div class="empty-state" id="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:4rem;height:4rem;margin-bottom:1rem;opacity:0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/></svg>
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

<!-- Compare Modal -->
<div class="modal-overlay" id="compare-modal" style="display:none" onclick="if(event.target===this)closeCompareModal()">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="compare-title">Compare: Before &amp; After</h2>
            <button class="modal-close" onclick="closeCompareModal()">&times;</button>
        </div>
        <div class="modal-body" style="height:60vh">
            <div class="compare-pane">
                <div class="compare-pane-header before">Before (Original)</div>
                <canvas id="compare-before"></canvas>
            </div>
            <div class="compare-pane">
                <div class="compare-pane-header after">After (Modified)</div>
                <canvas id="compare-after"></canvas>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-nav-btn" onclick="compareNav(-1)">&larr; Prev Frame</button>
            <span id="compare-frame-label" style="font-size:0.8rem;color:#64748b;padding:0.3rem 0.5rem"></span>
            <button class="modal-nav-btn" onclick="compareNav(1)">Next Frame &rarr;</button>
        </div>
    </div>
</div>

<script>
// === CONSTANTS ===
const COLORS = {
    TopPlate:'#ef4444', BottomPlate:'#3b82f6', Stud:'#22c55e', TrimStud:'#16a34a',
    Brace:'#f59e0b', Nog:'#a855f7', HeadPlate:'#ec4899', Sill:'#06b6d4', default:'#94a3b8',
};
const USAGE_LABELS = {
    TopPlate:'Top Plate', BottomPlate:'Bottom Plate', Stud:'Stud', TrimStud:'Trim Stud',
    Brace:'Brace', Nog:'Nogging', HeadPlate:'Head Plate', Sill:'Sill',
};

// === STATE ===
let allFrames = [];
let currentFrameIdx = -1;
let selectedStickIdx = -1;
let sidebarTab = 'frames';
let layers = { sticks:true, fasteners:true, tools:true, labels:true, dims:true, ghost:false };
let cam = { x:0, y:0, scale:1 };
let isDragging = false, dragStart = {x:0,y:0}, camStart = {x:0,y:0};
let frameMods = {};
let originalXmlDoc = null;
let cachedEnv = null;
let compareFrameIdx = 0;

const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

// === FRAME MODIFICATIONS ===
function getFrameMod(idx) {
    if (!frameMods[idx]) frameMods[idx] = { resetOrigin:true, flipH:false, flipV:false, stickTrims:{}, stickOverrides:{} };
    return frameMods[idx];
}
function getStickTrim(frameIdx, stickName) {
    const mod = getFrameMod(frameIdx);
    if (!mod.stickTrims[stickName]) mod.stickTrims[stickName] = { start:0, end:0 };
    return mod.stickTrims[stickName];
}
function getStickOverride(frameIdx, stickName) {
    const mod = getFrameMod(frameIdx);
    if (!mod.stickOverrides[stickName]) mod.stickOverrides[stickName] = { start:null, end:null };
    return mod.stickOverrides[stickName];
}

// === COORDINATE TRANSFORM ===
function getEnvelopeBounds3D(frame) {
    const pts = frame.envelope;
    if (!pts.length) return null;
    return {
        minX:Math.min(...pts.map(p=>p.x)), maxX:Math.max(...pts.map(p=>p.x)),
        minY:Math.min(...pts.map(p=>p.y)), maxY:Math.max(...pts.map(p=>p.y)),
        minZ:Math.min(...pts.map(p=>p.z)), maxZ:Math.max(...pts.map(p=>p.z)),
    };
}

function transformPoint3D(point, frame, frameIdx) {
    const mod = getFrameMod(frameIdx);
    if (!mod.resetOrigin) return { x:point.x, y:point.y, z:point.z };
    const b = getEnvelopeBounds3D(frame);
    if (!b) return { x:point.x, y:point.y, z:point.z };

    let x = point.x - b.minX;
    let y = point.y - b.minY;
    let z = point.z - b.minZ;
    const xR = b.maxX - b.minX, yR = b.maxY - b.minY, zR = b.maxZ - b.minZ;

    if (mod.flipH) {
        if (xR > yR) x = xR - x; else y = yR - y;
    }
    if (mod.flipV) z = zR - z;
    return { x, y, z };
}

function dist3D(a, b) {
    const dx=b.x-a.x, dy=b.y-a.y, dz=b.z-a.z;
    return Math.sqrt(dx*dx+dy*dy+dz*dz);
}

function applyTrimToStick(stick, frameIdx, frame) {
    const trim = getStickTrim(frameIdx, stick.name);
    const tS = transformPoint3D(stick.start, frame, frameIdx);
    const tE = transformPoint3D(stick.end, frame, frameIdx);
    if (trim.start === 0 && trim.end === 0) return { start:tS, end:tE };

    const dx=tE.x-tS.x, dy=tE.y-tS.y, dz=tE.z-tS.z;
    const len = Math.sqrt(dx*dx+dy*dy+dz*dz);
    if (len === 0) return { start:tS, end:tE };
    const ux=dx/len, uy=dy/len, uz=dz/len;
    return {
        start:{ x:tS.x+ux*trim.start, y:tS.y+uy*trim.start, z:tS.z+uz*trim.start },
        end:{ x:tE.x-ux*trim.end, y:tE.y-uy*trim.end, z:tE.z-uz*trim.end },
    };
}

function getStickFinal3D(stick, frameIdx, frame) {
    const trimmed = applyTrimToStick(stick, frameIdx, frame);
    const ovr = getStickOverride(frameIdx, stick.name);
    return {
        start: ovr.start ? { ...ovr.start } : trimmed.start,
        end: ovr.end ? { ...ovr.end } : trimmed.end,
    };
}

function reconstruct3DFromHV(h, v, env) {
    if (env.horizontal === 'x') {
        return { x: h + env.minH, y: env.minH, z: v + env.minV };
    } else {
        return { x: env.minH, y: h + env.minH, z: v + env.minV };
    }
}

function setStickCoord(si, which, axis, val) {
    const frame = allFrames[currentFrameIdx];
    const stick = frame.sticks[si];
    const env = getProjectedEnvelope(frame, currentFrameIdx);
    const ovr = getStickOverride(currentFrameIdx, stick.name);
    const trimmed = applyTrimToStick(stick, currentFrameIdx, frame);

    let point3d = ovr[which] ? { ...ovr[which] } : { ...(which === 'start' ? trimmed.start : trimmed.end) };

    if (axis === 'h') {
        const absH = val + env.minH;
        if (env.horizontal === 'x') point3d.x = absH;
        else point3d.y = absH;
    } else {
        point3d.z = val + env.minV;
    }

    ovr[which] = point3d;
    renderComponentList();
    draw();
}

function resetStickCoord(si, which) {
    const frame = allFrames[currentFrameIdx];
    const stick = frame.sticks[si];
    const ovr = getStickOverride(currentFrameIdx, stick.name);
    ovr[which] = null;
    renderComponentList();
    draw();
}

// === 2D PROJECTION ===
function getProjectedEnvelope(frame, frameIdx) {
    if (!frame.envelope.length) return null;
    const tEnv = frame.envelope.map(p => transformPoint3D(p, frame, frameIdx));
    const allPts = [...tEnv];
    frame.sticks.forEach(s => {
        allPts.push(transformPoint3D(s.start, frame, frameIdx));
        allPts.push(transformPoint3D(s.end, frame, frameIdx));
    });
    const xs=allPts.map(p=>p.x), ys=allPts.map(p=>p.y);
    const xR=Math.max(...xs)-Math.min(...xs), yR=Math.max(...ys)-Math.min(...ys);
    const useX = xR > yR;
    return {
        horizontal: useX ? 'x' : 'y',
        minH: useX ? Math.min(...xs) : Math.min(...ys),
        maxH: useX ? Math.max(...xs) : Math.max(...ys),
        minV: Math.min(...allPts.map(p=>p.z)),
        maxV: Math.max(...allPts.map(p=>p.z)),
    };
}

function getOriginalProjectedEnvelope(frame) {
    if (!frame.envelope.length) return null;
    const allPts = [...frame.envelope];
    frame.sticks.forEach(s => { allPts.push(s.start); allPts.push(s.end); });
    const xs=allPts.map(p=>p.x), ys=allPts.map(p=>p.y);
    const xR=Math.max(...xs)-Math.min(...xs), yR=Math.max(...ys)-Math.min(...ys);
    const useX = xR > yR;
    return {
        horizontal: useX ? 'x' : 'y',
        minH: useX ? Math.min(...xs) : Math.min(...ys),
        maxH: useX ? Math.max(...xs) : Math.max(...ys),
        minV: Math.min(...allPts.map(p=>p.z)),
        maxV: Math.max(...allPts.map(p=>p.z)),
    };
}

function project(point3d, env) {
    const h = env.horizontal === 'x' ? point3d.x : point3d.y;
    return { h: h - env.minH, v: point3d.z - env.minV };
}

// === FILE HANDLING ===
document.getElementById('file-input').addEventListener('change', e => { if (e.target.files[0]) loadFile(e.target.files[0]); });
const dropZone = document.getElementById('drop-zone');
dropZone.addEventListener('click', () => document.getElementById('file-input').click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('dragover'); if (e.dataTransfer.files[0]) loadFile(e.dataTransfer.files[0]); });

function loadFile(file) {
    const reader = new FileReader();
    reader.onload = e => parseXML(e.target.result);
    reader.readAsText(file);
}

function parsePoint(text) {
    const p = text.trim().split(',').map(Number);
    return { x:p[0], y:p[1], z:p[2] };
}

function parseXML(xmlText) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(xmlText, 'text/xml');
    const root = doc.querySelector('framecad_import');
    if (!root) { alert('Not a valid FrameCAD XML file.'); return; }

    originalXmlDoc = doc;

    const client = root.querySelector('client')?.textContent.trim().replace(/"/g,'') || '';
    const dateDrawn = root.querySelector('datedrawn')?.textContent.trim().replace(/"/g,'') || '';
    const planName = root.querySelector('plan')?.getAttribute('name') || '';
    document.getElementById('job-info').textContent = [client, planName, dateDrawn].filter(Boolean).join(' • ');

    allFrames = [];
    frameMods = {};
    root.querySelectorAll('frame').forEach(frameEl => {
        const frame = { name:frameEl.getAttribute('name'), type:frameEl.getAttribute('type'),
            elevation:parseFloat(frameEl.querySelector('elevation')?.textContent||'0'),
            envelope:[], sticks:[], fasteners:[], toolActions:[] };

        frameEl.querySelectorAll('envelope vertex').forEach(v => frame.envelope.push(parsePoint(v.textContent)));

        frameEl.querySelectorAll('stick').forEach(s => {
            const pr = s.querySelector('profile');
            frame.sticks.push({
                name:s.getAttribute('name'), type:s.getAttribute('type'), usage:s.getAttribute('usage'),
                gauge:parseFloat(s.getAttribute('gauge')||'0'),
                yield:s.getAttribute('yield')||'', tensile:s.getAttribute('tensile')||'', coating:s.getAttribute('coating')||'',
                start:parsePoint(s.querySelector('start').textContent),
                end:parsePoint(s.querySelector('end').textContent),
                profile: pr ? { web:+pr.getAttribute('web'), lFlange:+pr.getAttribute('l_flange'),
                    rFlange:+pr.getAttribute('r_flange'), lLip:+pr.getAttribute('l_lip'),
                    rLip:+pr.getAttribute('r_lip'), shape:pr.getAttribute('shape') } : null,
                flipped: s.querySelector('flipped')?.textContent.trim() === 'true',
            });
        });

        frameEl.querySelectorAll('fastener').forEach(f => {
            frame.fasteners.push({ name:f.getAttribute('name'), count:parseInt(f.getAttribute('count')||'1'),
                point:parsePoint(f.querySelector('point').textContent) });
        });

        frameEl.querySelectorAll('tool_action').forEach(t => {
            frame.toolActions.push({ name:t.getAttribute('name'),
                start:parsePoint(t.querySelector('start').textContent),
                end:parsePoint(t.querySelector('end').textContent) });
        });

        allFrames.push(frame);
    });

    renderFrameList();
    if (allFrames.length > 0) selectFrame(0);

    document.getElementById('upload-panel').style.display = 'none';
    document.getElementById('main-panel').style.display = 'flex';
    document.getElementById('empty-state').style.display = 'none';
    canvas.style.display = '';
    document.getElementById('toolbar').style.display = '';
    document.getElementById('export-btn').disabled = false;
    document.getElementById('compare-btn').disabled = false;
}

// === UI: TABS ===
function switchTab(tab) {
    sidebarTab = tab;
    document.getElementById('tab-frames-btn').classList.toggle('active', tab==='frames');
    document.getElementById('tab-comp-btn').classList.toggle('active', tab==='components');
    document.getElementById('frames-tab').style.display = tab==='frames' ? 'flex' : 'none';
    const ct = document.getElementById('components-tab');
    ct.style.display = tab==='components' ? 'flex' : 'none';
    if (tab === 'components') renderComponentList();
}

// === UI: TRANSFORM ===
function onTransformChange() {
    if (currentFrameIdx < 0) return;
    const mod = getFrameMod(currentFrameIdx);
    mod.resetOrigin = document.getElementById('reset-origin').checked;
    mod.stickOverrides = {};
    refreshAll();
}
function toggleFlipH() {
    if (currentFrameIdx < 0) return;
    const mod = getFrameMod(currentFrameIdx);
    mod.flipH = !mod.flipH;
    mod.stickOverrides = {};
    document.getElementById('flip-h-btn').classList.toggle('on', mod.flipH);
    refreshAll();
}
function toggleFlipV() {
    if (currentFrameIdx < 0) return;
    const mod = getFrameMod(currentFrameIdx);
    mod.flipV = !mod.flipV;
    mod.stickOverrides = {};
    document.getElementById('flip-v-btn').classList.toggle('on', mod.flipV);
    refreshAll();
}
function syncTransformUI() {
    if (currentFrameIdx < 0) return;
    const mod = getFrameMod(currentFrameIdx);
    document.getElementById('reset-origin').checked = mod.resetOrigin;
    document.getElementById('flip-h-btn').classList.toggle('on', mod.flipH);
    document.getElementById('flip-v-btn').classList.toggle('on', mod.flipV);
}
function refreshAll() {
    fitView();
    if (sidebarTab === 'components') renderComponentList();
    if (sidebarTab === 'frames') showFrameDetail(allFrames[currentFrameIdx]);
}

// === UI: FRAME LIST ===
function renderFrameList() {
    document.getElementById('frame-list').innerHTML = allFrames.map((f, i) =>
        `<div class="frame-item${i===currentFrameIdx?' active':''}" onclick="selectFrame(${i})">
            <span>${f.name}</span>
            <span class="type-badge">${f.type.replace(/([A-Z])/g,' $1').trim()}</span>
        </div>`
    ).join('');
}

function selectFrame(idx) {
    currentFrameIdx = idx;
    selectedStickIdx = -1;
    syncTransformUI();
    renderFrameList();
    showFrameDetail(allFrames[idx]);
    if (sidebarTab === 'components') renderComponentList();
    fitView();
}

function showFrameDetail(frame) {
    document.getElementById('frame-detail').style.display = '';
    const env = getProjectedEnvelope(frame, currentFrameIdx);
    const w = env ? Math.round(env.maxH - env.minH) : 0;
    const h = env ? Math.round(env.maxV - env.minV) : 0;
    const usages = {};
    frame.sticks.forEach(s => { usages[s.usage] = (usages[s.usage]||0)+1; });
    document.getElementById('detail-table').innerHTML = `
        <tr><td>Name</td><td style="font-weight:600">${frame.name}</td></tr>
        <tr><td>Type</td><td>${frame.type.replace(/([A-Z])/g,' $1').trim()}</td></tr>
        <tr><td>Size</td><td>${w} x ${h} mm</td></tr>
        <tr><td>Members</td><td>${frame.sticks.length}</td></tr>
        <tr><td>Fasteners</td><td>${frame.fasteners.length}</td></tr>
        ${Object.entries(usages).map(([k,v])=>`<tr><td style="padding-left:0.5rem">${USAGE_LABELS[k]||k}</td><td>${v}</td></tr>`).join('')}`;

    const usedColors = {};
    frame.sticks.forEach(s => { usedColors[s.usage] = COLORS[s.usage]||COLORS.default; });
    document.getElementById('legend').innerHTML = Object.entries(usedColors).map(([u,c]) =>
        `<span class="legend-item"><span class="legend-dot" style="background:${c}"></span>${USAGE_LABELS[u]||u}</span>`
    ).join('');
}

// === UI: COMPONENT LIST ===
function renderComponentList() {
    if (currentFrameIdx < 0) return;
    const frame = allFrames[currentFrameIdx];
    const idx = currentFrameIdx;
    const env = getProjectedEnvelope(frame, idx);

    const tbody = document.getElementById('comp-tbody');
    tbody.innerHTML = frame.sticks.map((stick, si) => {
        const tS = transformPoint3D(stick.start, frame, idx);
        const tE = transformPoint3D(stick.end, frame, idx);
        const origLen = dist3D(tS, tE);
        const final3d = getStickFinal3D(stick, idx, frame);
        const newLen = dist3D(final3d.start, final3d.end);
        const delta = newLen - origLen;
        const color = COLORS[stick.usage]||COLORS.default;
        const cls = si === selectedStickIdx ? ' selected' : '';
        const dCls = delta < -0.001 ? 'delta-neg' : delta > 0.001 ? 'delta-pos' : 'delta-zero';
        const trim = getStickTrim(idx, stick.name);
        const ovr = getStickOverride(idx, stick.name);
        const hasOverride = ovr.start || ovr.end;
        const ovrMark = hasOverride ? ' *' : '';

        return `<tr class="${cls}" onclick="selectStick(${si})">
            <td><span class="color-dot" style="background:${color}"></span></td>
            <td style="font-weight:600">${stick.name}${ovrMark}</td>
            <td style="color:#94a3b8">${USAGE_LABELS[stick.usage]||stick.usage}</td>
            <td class="r"><input type="number" step="0.1" min="0" value="${trim.start}" onchange="setTrim(${si},'start',this.value)" onclick="event.stopPropagation()"></td>
            <td class="r"><input type="number" step="0.1" min="0" value="${trim.end}" onchange="setTrim(${si},'end',this.value)" onclick="event.stopPropagation()"></td>
            <td class="r">${origLen.toFixed(1)}</td>
            <td class="r">${newLen.toFixed(1)}</td>
            <td class="r ${dCls}">${Math.abs(delta) > 0.001 ? (delta>0?'+':'') + delta.toFixed(1) : '-'}</td>
        </tr>`;
    }).join('');

    renderComparisonDetail(env);
}

function selectStick(si) {
    selectedStickIdx = selectedStickIdx === si ? -1 : si;
    renderComponentList();
    draw();
}

function setTrim(si, which, val) {
    const frame = allFrames[currentFrameIdx];
    const stick = frame.sticks[si];
    const trim = getStickTrim(currentFrameIdx, stick.name);
    trim[which] = Math.max(0, parseFloat(val) || 0);
    const ovr = getStickOverride(currentFrameIdx, stick.name);
    ovr.start = null;
    ovr.end = null;
    renderComponentList();
    draw();
}

function applyBatchTrim() {
    const type = document.getElementById('batch-type').value;
    const startV = Math.max(0, parseFloat(document.getElementById('batch-start').value) || 0);
    const endV = Math.max(0, parseFloat(document.getElementById('batch-end').value) || 0);
    const allF = document.getElementById('batch-all-frames').checked;

    const indices = allF ? allFrames.map((_,i)=>i) : [currentFrameIdx];
    indices.forEach(fi => {
        allFrames[fi].sticks.forEach(stick => {
            if (type === 'all' || stick.usage === type) {
                const trim = getStickTrim(fi, stick.name);
                trim.start = startV;
                trim.end = endV;
                const ovr = getStickOverride(fi, stick.name);
                ovr.start = null;
                ovr.end = null;
            }
        });
    });
    renderComponentList();
    draw();
}

function renderComparisonDetail(env) {
    const area = document.getElementById('comp-detail-area');
    if (selectedStickIdx < 0 || currentFrameIdx < 0) { area.innerHTML = ''; return; }

    const frame = allFrames[currentFrameIdx];
    const idx = currentFrameIdx;
    const si = selectedStickIdx;
    const stick = frame.sticks[si];
    const color = COLORS[stick.usage]||COLORS.default;

    const tS = transformPoint3D(stick.start, frame, idx);
    const tE = transformPoint3D(stick.end, frame, idx);
    const trimmed = applyTrimToStick(stick, idx, frame);
    const final3d = getStickFinal3D(stick, idx, frame);
    const ovr = getStickOverride(idx, stick.name);

    const oS = project(tS, env);
    const oE = project(tE, env);
    const fS = project(final3d.start, env);
    const fE = project(final3d.end, env);
    const origLen = dist3D(tS, tE);
    const newLen = dist3D(final3d.start, final3d.end);
    const dLen = newLen - origLen;

    const profStr = stick.profile
        ? `${stick.profile.shape}${stick.profile.web} · ${stick.gauge}mm · G${stick.yield} · ${stick.coating}`
        : '';

    function fmt(v) { return v.toFixed(3); }
    function fmtD(v) { return (v > 0.0005 ? '+' : '') + v.toFixed(3); }
    function dCls(v) { return Math.abs(v) < 0.001 ? 'delta-zero' : 'delta-col'; }
    function ovrCls(which) { return ovr[which] ? ' overridden' : ''; }
    function rstBtn(which) {
        return ovr[which] ? `<button class="coord-reset-btn" onclick="event.stopPropagation();resetStickCoord(${si},'${which}')" title="Reset to computed value">×</button>` : '';
    }

    area.innerHTML = `
    <div class="comp-detail">
        <h4><span class="color-dot" style="background:${color};width:10px;height:10px;border-radius:50%;display:inline-block"></span>
            ${stick.name} &mdash; ${USAGE_LABELS[stick.usage]||stick.usage}
            ${ovr.start || ovr.end ? '<span style="font-size:0.6rem;color:#f59e0b;font-weight:400;margin-left:0.25rem">(manually edited)</span>' : ''}</h4>
        <div class="profile-info">${profStr}</div>
        <table class="compare-table">
            <tr><th></th><th>Original</th><th>Modified</th><th>&Delta;</th><th></th></tr>
            <tr>
                <td>Start H</td>
                <td>${fmt(oS.h)}</td>
                <td><input type="number" step="0.001" value="${fS.h.toFixed(3)}" class="${ovrCls('start')}" onchange="setStickCoord(${si},'start','h',parseFloat(this.value))" onclick="event.stopPropagation()"></td>
                <td class="${dCls(fS.h-oS.h)}">${fmtD(fS.h-oS.h)}</td>
                <td>${rstBtn('start')}</td>
            </tr>
            <tr>
                <td>Start V</td>
                <td>${fmt(oS.v)}</td>
                <td><input type="number" step="0.001" value="${fS.v.toFixed(3)}" class="${ovrCls('start')}" onchange="setStickCoord(${si},'start','v',parseFloat(this.value))" onclick="event.stopPropagation()"></td>
                <td class="${dCls(fS.v-oS.v)}">${fmtD(fS.v-oS.v)}</td>
                <td></td>
            </tr>
            <tr>
                <td>End H</td>
                <td>${fmt(oE.h)}</td>
                <td><input type="number" step="0.001" value="${fE.h.toFixed(3)}" class="${ovrCls('end')}" onchange="setStickCoord(${si},'end','h',parseFloat(this.value))" onclick="event.stopPropagation()"></td>
                <td class="${dCls(fE.h-oE.h)}">${fmtD(fE.h-oE.h)}</td>
                <td>${rstBtn('end')}</td>
            </tr>
            <tr>
                <td>End V</td>
                <td>${fmt(oE.v)}</td>
                <td><input type="number" step="0.001" value="${fE.v.toFixed(3)}" class="${ovrCls('end')}" onchange="setStickCoord(${si},'end','v',parseFloat(this.value))" onclick="event.stopPropagation()"></td>
                <td class="${dCls(fE.v-oE.v)}">${fmtD(fE.v-oE.v)}</td>
                <td></td>
            </tr>
            <tr class="len-row"><td>Length</td><td>${fmt(origLen)}</td><td>${fmt(newLen)}</td>
                <td class="${dLen < -0.001 ? 'delta-neg' : dLen > 0.001 ? 'delta-pos' : 'delta-zero'}">${fmtD(dLen)}</td><td></td></tr>
        </table>
    </div>`;
}

// === CANVAS: SETUP ===
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
    const env = getProjectedEnvelope(allFrames[currentFrameIdx], currentFrameIdx);
    if (!env) return;
    const area = document.getElementById('canvas-area');
    const w = area.clientWidth, h = area.clientHeight;
    const fw = env.maxH - env.minH, fh = env.maxV - env.minV;
    const pad = 80;
    cam.scale = Math.min((w-pad*2)/fw, (h-pad*2)/fh);
    cam.x = (w - fw*cam.scale)/2;
    cam.y = (h - fh*cam.scale)/2;
    draw();
}

function toScreen(h, v) {
    const fh = cachedEnv.maxV - cachedEnv.minV;
    return { x: cam.x + h*cam.scale, y: cam.y + (fh-v)*cam.scale };
}

// === CANVAS: DRAWING ===
function draw() {
    if (currentFrameIdx < 0) return;
    const frame = allFrames[currentFrameIdx];
    const idx = currentFrameIdx;
    cachedEnv = getProjectedEnvelope(frame, idx);
    if (!cachedEnv) return;
    const env = cachedEnv;

    const area = document.getElementById('canvas-area');
    const w = area.clientWidth, h = area.clientHeight;
    ctx.clearRect(0, 0, w, h);

    if (frame.envelope.length >= 4) {
        ctx.save(); ctx.setLineDash([4,4]); ctx.strokeStyle='#94a3b844'; ctx.lineWidth=1;
        ctx.beginPath();
        const pts = frame.envelope.map(p => { const tp=transformPoint3D(p,frame,idx); const pr=project(tp,env); return toScreen(pr.h,pr.v); });
        ctx.moveTo(pts[0].x,pts[0].y);
        for (let i=1;i<pts.length;i++) ctx.lineTo(pts[i].x,pts[i].y);
        ctx.closePath(); ctx.stroke(); ctx.restore();
    }

    if (layers.tools) {
        frame.toolActions.forEach(t => {
            const ts=transformPoint3D(t.start,frame,idx), te=transformPoint3D(t.end,frame,idx);
            const s=project(ts,env), e=project(te,env);
            const ss=toScreen(s.h,s.v), se=toScreen(e.h,e.v);
            ctx.save();
            if (t.name==='Bolt') { ctx.strokeStyle='#f97316'; ctx.setLineDash([2,3]); ctx.lineWidth=1.5; }
            else { ctx.strokeStyle='#06b6d488'; ctx.setLineDash([6,4]); ctx.lineWidth=1; }
            ctx.beginPath(); ctx.moveTo(ss.x,ss.y); ctx.lineTo(se.x,se.y); ctx.stroke(); ctx.restore();
            if (t.name==='Bolt') { ctx.fillStyle='#f97316'; ctx.beginPath(); ctx.arc(ss.x,ss.y,3,0,Math.PI*2); ctx.fill(); }
        });
    }

    if (layers.sticks) {
        frame.sticks.forEach((stick, si) => {
            const final3d = getStickFinal3D(stick, idx, frame);
            const color = COLORS[stick.usage]||COLORS.default;
            const isSelected = si === selectedStickIdx;
            const trim = getStickTrim(idx, stick.name);
            const ovr = getStickOverride(idx, stick.name);
            const hasMod = trim.start > 0 || trim.end > 0 || ovr.start || ovr.end;

            if (layers.ghost && hasMod) {
                const oS = transformPoint3D(stick.start, frame, idx);
                const oE = transformPoint3D(stick.end, frame, idx);
                const gs = project(oS, env), ge = project(oE, env);
                const gss = toScreen(gs.h, gs.v), gse = toScreen(ge.h, ge.v);
                ctx.save(); ctx.strokeStyle = color + '33'; ctx.lineWidth = 5; ctx.lineCap='round'; ctx.setLineDash([]);
                ctx.beginPath(); ctx.moveTo(gss.x,gss.y); ctx.lineTo(gse.x,gse.y); ctx.stroke(); ctx.restore();
            }

            const s=project(final3d.start,env), e=project(final3d.end,env);
            const ss=toScreen(s.h,s.v), se=toScreen(e.h,e.v);

            if (isSelected) {
                ctx.save(); ctx.strokeStyle=color+'44'; ctx.lineWidth=10; ctx.lineCap='round'; ctx.setLineDash([]);
                ctx.beginPath(); ctx.moveTo(ss.x,ss.y); ctx.lineTo(se.x,se.y); ctx.stroke(); ctx.restore();
            }

            ctx.strokeStyle=color; ctx.lineWidth=stick.usage==='Brace'?2:3; ctx.lineCap='round'; ctx.setLineDash([]);
            ctx.beginPath(); ctx.moveTo(ss.x,ss.y); ctx.lineTo(se.x,se.y); ctx.stroke();

            if (layers.labels) {
                const mx=(ss.x+se.x)/2, my=(ss.y+se.y)/2;
                const fontSize=Math.max(9,Math.min(11,10*cam.scale/0.3));
                ctx.font=`600 ${fontSize}px Figtree,sans-serif`; ctx.fillStyle=color;
                ctx.textAlign='center'; ctx.textBaseline='bottom';
                const dx=se.x-ss.x, dy=se.y-ss.y, len=Math.sqrt(dx*dx+dy*dy);
                const nx=len>0?-dy/len:0, ny=len>0?dx/len:-1;
                ctx.fillText(stick.name, mx+nx*8, my+ny*8);
            }
        });
    }

    if (layers.fasteners) {
        frame.fasteners.forEach(f => {
            const tp=transformPoint3D(f.point,frame,idx);
            const p=project(tp,env); const sp=toScreen(p.h,p.v);
            ctx.fillStyle='#f59e0b'; ctx.strokeStyle='#fff'; ctx.lineWidth=1.5;
            ctx.beginPath(); ctx.arc(sp.x,sp.y,3.5,0,Math.PI*2); ctx.fill(); ctx.stroke();
        });
    }

    if (layers.dims) drawDimensions(env);

    document.getElementById('canvas-info').textContent =
        `${frame.name} • ${frame.type.replace(/([A-Z])/g,' $1').trim()} • ${Math.round(cam.scale*100)}%`;
}

function drawDimensions(env) {
    const fh=env.maxV-env.minV, fw=env.maxH-env.minH;
    ctx.save(); ctx.strokeStyle='#64748b88'; ctx.fillStyle='#64748b'; ctx.lineWidth=0.5; ctx.setLineDash([]);
    const fs=Math.max(8,Math.min(10,9*cam.scale/0.3));
    ctx.font=`${fs}px Figtree,sans-serif`;

    const dimY=toScreen(0,-1).y+30, left=toScreen(0,0), right=toScreen(fw,0);
    ctx.beginPath(); ctx.moveTo(left.x,dimY); ctx.lineTo(right.x,dimY); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(left.x,dimY-4); ctx.lineTo(left.x,dimY+4); ctx.moveTo(right.x,dimY-4); ctx.lineTo(right.x,dimY+4); ctx.stroke();
    ctx.textAlign='center'; ctx.textBaseline='top';
    ctx.fillText(`${Math.round(fw)} mm`, (left.x+right.x)/2, dimY+6);

    const dimX=toScreen(-1,0).x-30, bottom=toScreen(0,0), top=toScreen(0,fh);
    ctx.beginPath(); ctx.moveTo(dimX,bottom.y); ctx.lineTo(dimX,top.y); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(dimX-4,bottom.y); ctx.lineTo(dimX+4,bottom.y); ctx.moveTo(dimX-4,top.y); ctx.lineTo(dimX+4,top.y); ctx.stroke();
    ctx.save(); ctx.translate(dimX-8,(bottom.y+top.y)/2); ctx.rotate(-Math.PI/2);
    ctx.textAlign='center'; ctx.textBaseline='bottom'; ctx.fillText(`${Math.round(fh)} mm`,0,0); ctx.restore();
    ctx.restore();
}

// === GENERIC FRAME DRAWING (for compare modal) ===
function drawFrameOnCanvas(cvs, frame, frameIdx, useModifications) {
    const c = cvs.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = cvs.getBoundingClientRect();
    const w = rect.width, h = rect.height;
    cvs.width = w * dpr;
    cvs.height = h * dpr;
    c.setTransform(dpr, 0, 0, dpr, 0, 0);
    c.clearRect(0, 0, w, h);

    let env;
    if (useModifications) {
        env = getProjectedEnvelope(frame, frameIdx);
    } else {
        env = getOriginalProjectedEnvelope(frame);
    }
    if (!env) return;

    const fw = env.maxH - env.minH, fh = env.maxV - env.minV;
    const pad = 50;
    const scale = Math.min((w-pad*2)/fw, (h-pad*2)/fh);
    const ox = (w - fw*scale)/2;
    const oy = (h - fh*scale)/2;

    function ts(hv, vv) {
        return { x: ox + hv*scale, y: oy + (fh-vv)*scale };
    }
    function projPt(p3d) {
        const hVal = env.horizontal === 'x' ? p3d.x : p3d.y;
        return { h: hVal - env.minH, v: p3d.z - env.minV };
    }

    // Envelope
    if (frame.envelope.length >= 4) {
        c.save(); c.setLineDash([4,4]); c.strokeStyle='#94a3b844'; c.lineWidth=1;
        c.beginPath();
        const pts = frame.envelope.map(p => {
            const tp = useModifications ? transformPoint3D(p, frame, frameIdx) : p;
            const pr = projPt(tp);
            return ts(pr.h, pr.v);
        });
        c.moveTo(pts[0].x,pts[0].y);
        for (let i=1;i<pts.length;i++) c.lineTo(pts[i].x,pts[i].y);
        c.closePath(); c.stroke(); c.restore();
    }

    // Tool actions
    frame.toolActions.forEach(t => {
        const tpS = useModifications ? transformPoint3D(t.start, frame, frameIdx) : t.start;
        const tpE = useModifications ? transformPoint3D(t.end, frame, frameIdx) : t.end;
        const s = projPt(tpS), e = projPt(tpE);
        const ss = ts(s.h,s.v), se = ts(e.h,e.v);
        c.save();
        if (t.name==='Bolt') { c.strokeStyle='#f97316'; c.setLineDash([2,3]); c.lineWidth=1.5; }
        else { c.strokeStyle='#06b6d488'; c.setLineDash([6,4]); c.lineWidth=1; }
        c.beginPath(); c.moveTo(ss.x,ss.y); c.lineTo(se.x,se.y); c.stroke(); c.restore();
        if (t.name==='Bolt') { c.fillStyle='#f97316'; c.beginPath(); c.arc(ss.x,ss.y,3,0,Math.PI*2); c.fill(); }
    });

    // Sticks
    frame.sticks.forEach(stick => {
        let startPt, endPt;
        if (useModifications) {
            const f3d = getStickFinal3D(stick, frameIdx, frame);
            startPt = f3d.start;
            endPt = f3d.end;
        } else {
            startPt = stick.start;
            endPt = stick.end;
        }
        const color = COLORS[stick.usage]||COLORS.default;
        const s = projPt(startPt), e = projPt(endPt);
        const ss = ts(s.h,s.v), se = ts(e.h,e.v);

        c.strokeStyle = color;
        c.lineWidth = stick.usage==='Brace' ? 2 : 3;
        c.lineCap = 'round';
        c.setLineDash([]);
        c.beginPath(); c.moveTo(ss.x,ss.y); c.lineTo(se.x,se.y); c.stroke();

        const mx=(ss.x+se.x)/2, my=(ss.y+se.y)/2;
        const fontSize = Math.max(8, Math.min(10, 9*scale/0.3));
        c.font = `600 ${fontSize}px Figtree,sans-serif`;
        c.fillStyle = color;
        c.textAlign = 'center'; c.textBaseline = 'bottom';
        const dx=se.x-ss.x, dy=se.y-ss.y, len=Math.sqrt(dx*dx+dy*dy);
        const nx=len>0?-dy/len:0, ny=len>0?dx/len:-1;
        c.fillText(stick.name, mx+nx*7, my+ny*7);
    });

    // Fasteners
    frame.fasteners.forEach(f => {
        const tp = useModifications ? transformPoint3D(f.point, frame, frameIdx) : f.point;
        const p = projPt(tp);
        const sp = ts(p.h, p.v);
        c.fillStyle='#f59e0b'; c.strokeStyle='#fff'; c.lineWidth=1.5;
        c.beginPath(); c.arc(sp.x,sp.y,3,0,Math.PI*2); c.fill(); c.stroke();
    });

    // Dimensions
    const dimFs = Math.max(8, Math.min(10, 9*scale/0.3));
    c.save(); c.strokeStyle='#64748b88'; c.fillStyle='#64748b'; c.lineWidth=0.5; c.setLineDash([]);
    c.font = `${dimFs}px Figtree,sans-serif`;
    const dimY = ts(0,-1).y + 20;
    const lp = ts(0,0), rp = ts(fw,0);
    c.beginPath(); c.moveTo(lp.x,dimY); c.lineTo(rp.x,dimY); c.stroke();
    c.beginPath(); c.moveTo(lp.x,dimY-3); c.lineTo(lp.x,dimY+3); c.moveTo(rp.x,dimY-3); c.lineTo(rp.x,dimY+3); c.stroke();
    c.textAlign='center'; c.textBaseline='top';
    c.fillText(`${Math.round(fw)} mm`, (lp.x+rp.x)/2, dimY+4);

    const dimX = ts(-1,0).x - 20;
    const bp = ts(0,0), tp2 = ts(0,fh);
    c.beginPath(); c.moveTo(dimX,bp.y); c.lineTo(dimX,tp2.y); c.stroke();
    c.beginPath(); c.moveTo(dimX-3,bp.y); c.lineTo(dimX+3,bp.y); c.moveTo(dimX-3,tp2.y); c.lineTo(dimX+3,tp2.y); c.stroke();
    c.save(); c.translate(dimX-6,(bp.y+tp2.y)/2); c.rotate(-Math.PI/2);
    c.textAlign='center'; c.textBaseline='bottom';
    c.fillText(`${Math.round(fh)} mm`,0,0); c.restore();
    c.restore();
}

// === COMPARE MODAL ===
function showCompareModal() {
    if (allFrames.length === 0) return;
    compareFrameIdx = currentFrameIdx >= 0 ? currentFrameIdx : 0;
    document.getElementById('compare-modal').style.display = '';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => drawCompare());
}

function closeCompareModal() {
    document.getElementById('compare-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function compareNav(dir) {
    compareFrameIdx = Math.max(0, Math.min(allFrames.length-1, compareFrameIdx + dir));
    drawCompare();
}

function drawCompare() {
    const frame = allFrames[compareFrameIdx];
    document.getElementById('compare-title').textContent = `Compare: ${frame.name} — ${frame.type.replace(/([A-Z])/g,' $1').trim()}`;
    document.getElementById('compare-frame-label').textContent = `${frame.name} (${compareFrameIdx+1} / ${allFrames.length})`;

    const before = document.getElementById('compare-before');
    const after = document.getElementById('compare-after');
    drawFrameOnCanvas(before, frame, compareFrameIdx, false);
    drawFrameOnCanvas(after, frame, compareFrameIdx, true);
}

// === EXPORT ===
function exportXML() {
    if (!originalXmlDoc) return;
    const doc = originalXmlDoc.cloneNode(true);
    const frames = doc.querySelectorAll('frame');

    frames.forEach((frameEl, idx) => {
        const frame = allFrames[idx];

        frameEl.querySelectorAll('envelope vertex').forEach((v, vi) => {
            const tp = transformPoint3D(frame.envelope[vi], frame, idx);
            v.textContent = fmtCoord(tp);
        });

        frameEl.querySelectorAll('stick').forEach((s, si) => {
            const stick = frame.sticks[si];
            const final3d = getStickFinal3D(stick, idx, frame);
            s.querySelector('start').textContent = fmtCoord(final3d.start);
            s.querySelector('end').textContent = fmtCoord(final3d.end);
        });

        frameEl.querySelectorAll('fastener').forEach((f, fi) => {
            const tp = transformPoint3D(frame.fasteners[fi].point, frame, idx);
            f.querySelector('point').textContent = fmtCoord(tp);
        });

        frameEl.querySelectorAll('tool_action').forEach((t, ti) => {
            const ta = frame.toolActions[ti];
            const tpS = transformPoint3D(ta.start, frame, idx);
            const tpE = transformPoint3D(ta.end, frame, idx);
            t.querySelector('start').textContent = fmtCoord(tpS);
            t.querySelector('end').textContent = fmtCoord(tpE);
        });
    });

    const serializer = new XMLSerializer();
    let xmlStr = serializer.serializeToString(doc);
    if (!xmlStr.startsWith('<?xml')) xmlStr = '<?xml version="1.0" encoding="UTF-8"?>\n' + xmlStr;

    const blob = new Blob([xmlStr], { type:'application/xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'framecad-modified.xml';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function fmtCoord(p) { return `${p.x.toFixed(3)},${p.y.toFixed(3)},${p.z.toFixed(3)} `; }

// === CONTROLS ===
function toggleLayer(btn) { const l=btn.dataset.layer; layers[l]=!layers[l]; btn.classList.toggle('on',layers[l]); draw(); }
function zoomIn() { cam.scale*=1.3; draw(); }
function zoomOut() { cam.scale/=1.3; draw(); }
function prevFrame() { if (currentFrameIdx>0) selectFrame(currentFrameIdx-1); }
function nextFrame() { if (currentFrameIdx<allFrames.length-1) selectFrame(currentFrameIdx+1); }

canvas.addEventListener('mousedown', e => { isDragging=true; dragStart={x:e.clientX,y:e.clientY}; camStart={x:cam.x,y:cam.y}; canvas.style.cursor='grabbing'; });
canvas.addEventListener('mousemove', e => { if (!isDragging) return; cam.x=camStart.x+(e.clientX-dragStart.x); cam.y=camStart.y+(e.clientY-dragStart.y); draw(); });
canvas.addEventListener('mouseup', () => { isDragging=false; canvas.style.cursor='grab'; });
canvas.addEventListener('mouseleave', () => { isDragging=false; canvas.style.cursor='grab'; });
canvas.addEventListener('wheel', e => {
    e.preventDefault();
    const rect=canvas.getBoundingClientRect(), mx=e.clientX-rect.left, my=e.clientY-rect.top;
    const factor=e.deltaY<0?1.1:0.9, newScale=cam.scale*factor;
    cam.x=mx-(mx-cam.x)*(newScale/cam.scale); cam.y=my-(my-cam.y)*(newScale/cam.scale);
    cam.scale=newScale; draw();
}, { passive:false });

document.addEventListener('keydown', e => {
    if (e.target.tagName==='INPUT'||e.target.tagName==='SELECT') return;
    if (document.getElementById('compare-modal').style.display !== 'none') {
        if (e.key==='Escape') closeCompareModal();
        if (e.key==='ArrowLeft') compareNav(-1);
        if (e.key==='ArrowRight') compareNav(1);
        return;
    }
    if (e.key==='ArrowLeft') prevFrame();
    if (e.key==='ArrowRight') nextFrame();
    if (e.key==='f'||e.key==='F') fitView();
    if (e.key==='+'||e.key==='=') zoomIn();
    if (e.key==='-') zoomOut();
});

window.addEventListener('resize', () => {
    resizeCanvas();
    if (document.getElementById('compare-modal').style.display !== 'none') drawCompare();
});
resizeCanvas();
canvas.style.cursor = 'grab';
</script>
</body>
</html>
