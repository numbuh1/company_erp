// Client-side mirror of App\Support\WorkHours::breakdown (the server recalculates and is authoritative).
// Config (lunch break, day edges, holidays, labels) is set by the app layout in window.WorkHoursConfig.

const pad   = (n) => String(n).padStart(2, '0');
const iso   = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const short = (d) => `${pad(d.getDate())}/${pad(d.getMonth() + 1)}`;
const mins  = (hm) => { const [h, m] = String(hm || '00:00').split(':').map(Number); return h * 60 + (m || 0); };
const ofDay = (d) => d.getHours() * 60 + d.getMinutes();
const fmtH  = (h) => `${Math.round(h * 100) / 100}h`;
const esc   = (t) => { const s = document.createElement('span'); s.textContent = t; return s.innerHTML; };

// Full class names (not built from parts) so Tailwind keeps them in the CSS build.
const TONES = {
    blue: { dot: 'text-blue-400', title: 'text-blue-700 dark:text-blue-400' },
    sky:  { dot: 'text-sky-400',  title: 'text-sky-700 dark:text-sky-400' },
};

function config() {
    return Object.assign({
        lunchStart: '12:00', lunchEnd: '13:00', dayStart: '08:00', dayEnd: '17:00', holidays: [], labels: {},
    }, window.WorkHoursConfig || {});
}

window.WorkHours = {
    /** Returns null for an empty/invalid range, otherwise { multiDay, total, first, last, mid, midHours }. */
    breakdown(start, end) {
        if (!(start instanceof Date) || !(end instanceof Date) || !(end > start)) return null;
        const c = config();
        const lS = mins(c.lunchStart), lE = mins(c.lunchEnd);
        const net = (from, to) => Math.max(0, (to - from) - Math.max(0, Math.min(to, lE) - Math.max(from, lS))) / 60;

        if (iso(start) === iso(end)) {
            const h = net(ofDay(start), ofDay(end));
            return { multiDay: false, total: h, first: h, last: h, mid: 0, midHours: 0 };
        }

        const workDay = (d) => d.getDay() !== 0 && d.getDay() !== 6 && !c.holidays.includes(iso(d));
        const first = workDay(start) ? net(ofDay(start), mins(c.dayEnd)) : 0;
        const last  = workDay(end) ? net(mins(c.dayStart), ofDay(end)) : 0;

        let mid = 0;
        const d = new Date(start); d.setHours(12, 0, 0, 0); d.setDate(d.getDate() + 1);
        const stop = new Date(end); stop.setHours(0, 0, 0, 0);
        while (d < stop) { if (workDay(d)) mid++; d.setDate(d.getDate() + 1); }
        const midHours = mid * net(mins(c.dayStart), mins(c.dayEnd));

        return { multiDay: true, total: first + midHours + last, first, last, mid, midHours };
    },

    /** Breakdown box contents for a multi-day result. `tone` is 'blue' (leave) or 'sky' (WFH). */
    html(r, start, end, tone = 'blue') {
        const L = config().labels;
        const T = TONES[tone] || TONES.blue;
        const dot = `<span class="${T.dot} mr-1">•</span>`;
        let html = `<p class="font-semibold ${T.title} mb-1.5">${esc(L.title || 'Expected total hours')}</p><div class="space-y-0.5">`;
        html += `${dot}<strong>${esc(L.day || 'Day')} ${short(start)}</strong>: ${fmtH(r.first)}<br>`;
        if (r.mid > 0) {
            html += `${dot}<strong>${r.mid} ${esc(L.workDays || 'working days')}</strong>: ${fmtH(r.midHours)} <span class="text-gray-400">(${esc(L.excl || '')})</span><br>`;
        }
        html += `${dot}<strong>${esc(L.day || 'Day')} ${short(end)}</strong>: ${fmtH(r.last)}</div>`;
        return html;
    },
};
