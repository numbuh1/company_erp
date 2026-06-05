/**
 * Leave request form JS.
 *
 * Single-day leave:
 *   total hours = end_time − start_time (raw diff, user can override)
 *
 * Multi-day leave:
 *   Shows two editable inputs (start_day_hours, end_day_hours).
 *   total hours = start_day_hours + 8 × middle_days + end_day_hours
 *
 * Default partial-day values are computed from the datetime fields
 * (work day edges: 08:00 – 17:00) but the user can freely edit them.
 */

document.addEventListener('DOMContentLoaded', () => {
    const startEl        = document.getElementById('start_at');
    const endEl          = document.getElementById('end_at');
    const hoursEl        = document.getElementById('hours');
    const startDayEl     = document.getElementById('start_day_hours');
    const endDayEl       = document.getElementById('end_day_hours');
    const sectionEl      = document.getElementById('partial-day-section');
    const breakdownEl    = document.getElementById('leave-hours-breakdown');
    const startLabelEl   = document.getElementById('partial-start-label');
    const endLabelEl     = document.getElementById('partial-end-label');
    const middleInfoEl   = document.getElementById('middle-days-info');

    if (!startEl || !endEl || !hoursEl) return;

    const WORK_START = 8;   // 08:00
    const WORK_END   = 17;  // 17:00
    const FULL_DAY   = 8;

    function pad(n)      { return String(n).padStart(2, '0'); }
    function fmtTime(dt) { return pad(dt.getHours()) + ':' + pad(dt.getMinutes()); }
    function fmtDate(dt) { return pad(dt.getDate()) + '/' + pad(dt.getMonth() + 1); }

    function daysBetween(a, b) {
        const d1 = new Date(a); d1.setHours(0, 0, 0, 0);
        const d2 = new Date(b); b = new Date(b); b.setHours(0, 0, 0, 0);
        return Math.round((b - d1) / 86_400_000);
    }

    /** Compute default partial-day hours from the datetime fields. */
    function defaultStartDayH(startDt) {
        const mins = startDt.getHours() * 60 + startDt.getMinutes();
        return Math.max(0, WORK_END * 60 - mins) / 60;
    }
    function defaultEndDayH(endDt) {
        const mins = endDt.getHours() * 60 + endDt.getMinutes();
        return Math.max(0, mins - WORK_START * 60) / 60;
    }

    // Track whether the *total hours* field has been manually overridden
    let totalManual = false;
    hoursEl.addEventListener('input', () => { totalManual = true; });

    function update() {
        if (!startEl.value || !endEl.value) return;

        const startDt = new Date(startEl.value);
        const endDt   = new Date(endEl.value);
        if (endDt < startDt) { hoursEl.value = ''; return; }

        const diff = daysBetween(startDt, endDt);

        if (diff === 0) {
            // ── Single-day ───────────────────────────────────────────────────
            if (sectionEl) sectionEl.classList.add('hidden');
            if (breakdownEl) breakdownEl.classList.add('hidden');
            if (!totalManual) {
                const h = (endDt - startDt) / 3_600_000;
                hoursEl.value = h.toFixed(2);
            }
            return;
        }

        // ── Multi-day ─────────────────────────────────────────────────────────
        if (sectionEl) sectionEl.classList.remove('hidden');

        const midDays = Math.max(0, diff - 1);

        // Pre-fill partial-day inputs with defaults if they're still empty
        if (startDayEl && startDayEl.value === '') {
            startDayEl.value = defaultStartDayH(startDt).toFixed(2);
        }
        if (endDayEl && endDayEl.value === '') {
            endDayEl.value = defaultEndDayH(endDt).toFixed(2);
        }

        // Update date labels on the inputs
        if (startLabelEl) startLabelEl.textContent = '(' + fmtDate(startDt) + ')';
        if (endLabelEl)   endLabelEl.textContent   = '(' + fmtDate(endDt)   + ')';

        // Middle days info
        if (middleInfoEl) {
            middleInfoEl.textContent = midDays > 0
                ? `📋 ${midDays} ngày giữa × ${FULL_DAY}h = ${midDays * FULL_DAY}h`
                : '';
        }

        // Recalculate total from the editable inputs
        const sdH = parseFloat(startDayEl?.value) || 0;
        const edH = parseFloat(endDayEl?.value)   || 0;
        const total = sdH + midDays * FULL_DAY + edH;

        if (!totalManual) hoursEl.value = total.toFixed(2);

        // Breakdown display
        if (breakdownEl) {
            let html = `<div>📅 <strong>Ngày ${fmtDate(startDt)}</strong>: ${sdH.toFixed(1)}h</div>`;
            if (midDays > 0) html += `<div>📅 <strong>${midDays} ngày giữa</strong>: ${midDays * FULL_DAY}h (8h/ngày)</div>`;
            html += `<div>📅 <strong>Ngày ${fmtDate(endDt)}</strong>: ${edH.toFixed(1)}h</div>`;
            html += `<div class="font-semibold pt-1 mt-1 border-t border-blue-200 dark:border-blue-700">Tổng: ${total.toFixed(1)}h</div>`;
            breakdownEl.innerHTML = html;
            breakdownEl.classList.remove('hidden');
        }
    }

    // When the datetime fields change, reset partial-day defaults
    startEl.addEventListener('change', () => {
        if (startDayEl) startDayEl.value = ''; // re-derive default
        totalManual = false;
        update();
    });
    endEl.addEventListener('change', () => {
        if (endDayEl) endDayEl.value = ''; // re-derive default
        totalManual = false;
        update();
    });

    // When partial-day inputs change, just recalculate (don't reset to defaults)
    if (startDayEl) startDayEl.addEventListener('input', () => { totalManual = false; update(); });
    if (endDayEl)   endDayEl.addEventListener('input',   () => { totalManual = false; update(); });

    // Run once on page load (handles edit mode with pre-filled values)
    if (startEl.value && endEl.value) update();
});
