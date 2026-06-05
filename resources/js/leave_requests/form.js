/**
 * Leave request form — auto-calculate total absence hours.
 *
 * Single-day leave  : hours = end_time − start_time (raw diff)
 * Multi-day leave   : hours = (work_end − start_time on day 1)
 *                           + 8 h × (full middle days)
 *                           + (end_time − work_start on last day)
 *
 * Default workday edges: 08:00 – 17:00.
 */

document.addEventListener('DOMContentLoaded', () => {
    const startEl     = document.getElementById('start_at');
    const endEl       = document.getElementById('end_at');
    const hoursEl     = document.getElementById('hours');
    const breakdownEl = document.getElementById('leave-hours-breakdown');

    if (!startEl || !endEl || !hoursEl) return;

    const WORK_START_H  = 8;   // 08:00
    const WORK_END_H    = 17;  // 17:00
    const HOURS_PER_DAY = 8;

    let manuallyEdited = false;
    hoursEl.addEventListener('input', () => { manuallyEdited = true; });

    function pad(n)       { return String(n).padStart(2, '0'); }
    function fmtTime(dt)  { return pad(dt.getHours()) + ':' + pad(dt.getMinutes()); }
    function fmtDate(dt)  { return pad(dt.getDate()) + '/' + pad(dt.getMonth() + 1); }

    /** Calendar days between two Date objects (ignoring time). */
    function daysBetween(a, b) {
        const d1 = new Date(a); d1.setHours(0, 0, 0, 0);
        const d2 = new Date(b); d2.setHours(0, 0, 0, 0);
        return Math.round((d2 - d1) / 86_400_000);
    }

    function recalculate() {
        if (!startEl.value || !endEl.value) return;

        const startDt = new Date(startEl.value);
        const endDt   = new Date(endEl.value);
        if (endDt < startDt) { hoursEl.value = ''; return; }

        const diff = daysBetween(startDt, endDt);

        if (diff === 0) {
            // ── Same-day leave ──────────────────────────────────────────────
            const h = (endDt - startDt) / 3_600_000;
            if (!manuallyEdited) hoursEl.value = h.toFixed(2);
            if (breakdownEl) breakdownEl.classList.add('hidden');
            return;
        }

        // ── Multi-day leave ──────────────────────────────────────────────────
        const startMins = startDt.getHours() * 60 + startDt.getMinutes();
        const endMins   = endDt.getHours()   * 60 + endDt.getMinutes();

        const firstH = Math.max(0, WORK_END_H * 60 - startMins) / 60;
        const lastH  = Math.max(0, endMins - WORK_START_H * 60)  / 60;
        // diff = 1 → 2 days (no middle); diff = 2 → 3 days (1 middle day); …
        const midDays = Math.max(0, diff - 1);
        const midH    = midDays * HOURS_PER_DAY;
        const total   = firstH + midH + lastH;

        if (!manuallyEdited) hoursEl.value = total.toFixed(2);

        // Build breakdown panel
        if (breakdownEl) {
            let html = '';
            html += `<div>📅 <strong>Ngày ${fmtDate(startDt)}</strong>: ${firstH.toFixed(1)}h &nbsp;(${fmtTime(startDt)} → 17:00)</div>`;
            if (midDays > 0) {
                html += `<div>📅 <strong>${midDays} ngày giữa</strong>: ${midH}h &nbsp;(8h/ngày)</div>`;
            }
            html += `<div>📅 <strong>Ngày ${fmtDate(endDt)}</strong>: ${lastH.toFixed(1)}h &nbsp;(08:00 → ${fmtTime(endDt)})</div>`;
            html += `<div class="font-semibold pt-1 mt-1 border-t border-blue-200 dark:border-blue-700">Tổng: ${total.toFixed(1)}h</div>`;
            breakdownEl.innerHTML = html;
            breakdownEl.classList.remove('hidden');
        }
    }

    startEl.addEventListener('change', () => { manuallyEdited = false; recalculate(); });
    endEl.addEventListener('change',   () => { manuallyEdited = false; recalculate(); });

    // Populate on load when editing an existing multi-day leave
    if (startEl.value && endEl.value) recalculate();
});
