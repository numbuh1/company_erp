/**
 * Leave request form JS.
 *
 * Single-day:
 *   total = end_time − start_time (raw diff)
 *
 * Multi-day:
 *   Shows editable start_day_hours + end_day_hours inputs.
 *   Middle days = working days between start and end (skip weekends + holidays).
 *   total = start_day_hours + 8 × working_middle_days + end_day_hours
 *
 * Holiday dates are injected via window._leaveHolidays (array of 'YYYY-MM-DD').
 */

document.addEventListener('DOMContentLoaded', () => {
    const startEl      = document.getElementById('start_at');
    const endEl        = document.getElementById('end_at');
    const hoursEl      = document.getElementById('hours');
    const startDayEl   = document.getElementById('start_day_hours');
    const endDayEl     = document.getElementById('end_day_hours');
    const sectionEl    = document.getElementById('partial-day-section');
    const breakdownEl  = document.getElementById('leave-hours-breakdown');
    const startLabelEl = document.getElementById('partial-start-label');
    const endLabelEl   = document.getElementById('partial-end-label');

    if (!startEl || !endEl || !hoursEl) return;

    const WORK_START = 8.5;   // 08:30
    const WORK_END   = 17.5;  // 17:30
    const FULL_DAY   = 8;

    function pad(n)      { return String(n).padStart(2, '0'); }
    function fmtTime(dt) { return pad(dt.getHours()) + ':' + pad(dt.getMinutes()); }
    function fmtDate(dt) { return pad(dt.getDate()) + '/' + pad(dt.getMonth() + 1); }
    function isoDate(dt) {
        return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate());
    }

    /** Calendar days between two Date objects (date part only). */
    function calDaysBetween(a, b) {
        const d1 = new Date(a); d1.setHours(0, 0, 0, 0);
        const d2 = new Date(b); d2.setHours(0, 0, 0, 0);
        return Math.round((d2 - d1) / 86_400_000);
    }

    /**
     * Count working days STRICTLY between startDate and endDate
     * (i.e. excluding both the start day and end day).
     * Skips weekends (Sat/Sun) and public holidays from window._leaveHolidays.
     */
    function countWorkingMiddleDays(startDt, endDt) {
        const holidays = window._leaveHolidays || [];
        let count = 0;
        // start from day after startDt, stop before endDt
        const d = new Date(startDt);
        d.setHours(12, 0, 0, 0); // noon avoids DST edge cases
        d.setDate(d.getDate() + 1);
        const stop = new Date(endDt);
        stop.setHours(0, 0, 0, 0);

        while (d < stop) {
            const dow = d.getDay(); // 0=Sun, 6=Sat
            if (dow !== 0 && dow !== 6 && !holidays.includes(isoDate(d))) {
                count++;
            }
            d.setDate(d.getDate() + 1);
        }
        return count;
    }

    /** Default partial hours from work-day edges. */
    function defaultStartDayH(dt) {
        return Math.max(0, WORK_END * 60 - (dt.getHours() * 60 + dt.getMinutes())) / 60;
    }
    function defaultEndDayH(dt) {
        return Math.max(0, (dt.getHours() * 60 + dt.getMinutes()) - WORK_START * 60) / 60;
    }

    // Track whether the total-hours input was manually overridden
    let totalManual = false;
    hoursEl.addEventListener('input', () => { totalManual = true; });

    function update() {
        if (!startEl.value || !endEl.value) return;

        const startDt = new Date(startEl.value);
        const endDt   = new Date(endEl.value);
        if (endDt < startDt) { hoursEl.value = ''; return; }

        const calDiff = calDaysBetween(startDt, endDt);

        // ── Single-day ────────────────────────────────────────────────────────
        if (calDiff === 0) {
            if (sectionEl)   sectionEl.classList.add('hidden');
            if (breakdownEl) breakdownEl.classList.add('hidden');
            if (!totalManual) {
                hoursEl.value = ((endDt - startDt) / 3_600_000).toFixed(2);
            }
            return;
        }

        // ── Multi-day ─────────────────────────────────────────────────────────
        if (sectionEl) sectionEl.classList.remove('hidden');

        // Pre-fill partial inputs with computed defaults the first time
        if (startDayEl && startDayEl.value === '')
            startDayEl.value = defaultStartDayH(startDt).toFixed(2);
        if (endDayEl && endDayEl.value === '')
            endDayEl.value = defaultEndDayH(endDt).toFixed(2);

        // Update date labels inside the partial-day section
        if (startLabelEl) startLabelEl.textContent = '(' + fmtDate(startDt) + ')';
        if (endLabelEl)   endLabelEl.textContent   = '(' + fmtDate(endDt)   + ')';

        const midCount = countWorkingMiddleDays(startDt, endDt);
        const sdH      = parseFloat(startDayEl?.value) || 0;
        const edH      = parseFloat(endDayEl?.value)   || 0;
        const midH     = midCount * FULL_DAY;
        const total    = sdH + midH + edH;

        if (!totalManual) hoursEl.value = total.toFixed(2);

        // ── Breakdown panel ───────────────────────────────────────────────────
        if (breakdownEl) {
            const bullet = '<span class="text-blue-400 mr-1">•</span>';

            let html = '<p class="font-semibold text-blue-700 dark:text-blue-400 mb-2">Tổng giờ dự kiến</p>';
            html += '<div class="space-y-0.5">';
            html += `<div>${bullet}<strong>Ngày ${fmtDate(startDt)}</strong>: ${sdH.toFixed(1)}h`
                  + ` <span class="text-gray-400">(${fmtTime(startDt)} → 17:30)</span></div>`;

            if (midCount > 0) {
                html += `<div>${bullet}<strong>${midCount} ngày làm việc</strong>: ${midH}h`
                      + ` <span class="text-gray-400">(8h/ngày, bỏ qua cuối tuần &amp; ngày lễ)</span></div>`;
            }

            html += `<div>${bullet}<strong>Ngày ${fmtDate(endDt)}</strong>: ${edH.toFixed(1)}h`
                  + ` <span class="text-gray-400">(08:30 → ${fmtTime(endDt)})</span></div>`;
            html += '</div>';

            breakdownEl.innerHTML = html;
            breakdownEl.classList.remove('hidden');
        }
    }

    // Datetime fields change → reset partial-day defaults, recalculate
    startEl.addEventListener('change', () => {
        if (startDayEl) startDayEl.value = '';
        totalManual = false;
        update();
    });
    endEl.addEventListener('change', () => {
        if (endDayEl) endDayEl.value = '';
        totalManual = false;
        update();
    });

    // Partial-day inputs change → recalculate total only
    if (startDayEl) startDayEl.addEventListener('input', () => { totalManual = false; update(); });
    if (endDayEl)   endDayEl.addEventListener('input',   () => { totalManual = false; update(); });

    // Restore state on page load (edit mode with pre-filled values)
    if (startEl.value && endEl.value) update();
});
