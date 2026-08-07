document.addEventListener('DOMContentLoaded', () => {
    const dateEl    = document.getElementById('ot_date');
    const startEl   = document.getElementById('start_time');
    const endEl     = document.getElementById('end_time');
    const hoursEl   = document.getElementById('hours');
    const typeSel   = document.getElementById('ot_type_select');
    const warningEl = document.getElementById('hours_warning');

    // Not an editable form (readonly view has none of these)
    if (!dateEl || !startEl || !endEl) return;

    const holidayDates = window._otHolidayDates || [];
    let manualHours     = false;

    // ── Type helpers ────────────────────────────────────────────────
    function getOtType(dateStr) {
        if (!dateStr) return 'OT x1.5';
        if (holidayDates.includes(dateStr)) return 'OT x3';
        const day = new Date(dateStr + 'T00:00:00').getDay(); // 0 = Sunday
        return day === 0 ? 'OT x2' : 'OT x1.5';
    }

    function updateType() {
        if (typeSel) typeSel.value = getOtType(dateEl.value);
    }

    // ── Hours auto-calc (supports overnight rollover) ──────────────
    function toMins(t) {
        const p = t.split(':').map(Number);
        return p[0] * 60 + (p[1] || 0);
    }

    function computeHours(s, e) {
        if (!s || !e) return 0;
        let sm = toMins(s);
        let em = toMins(e);
        if (em <= sm) em += 1440; // overnight: end time rolls into the next day
        return (em - sm) / 60;
    }

    function checkWarning() {
        if (!warningEl || !hoursEl) return;
        const h = parseFloat(hoursEl.value) || 0;
        warningEl.classList.toggle('hidden', h <= 8);
    }

    function calcHours() {
        if (manualHours || !startEl.value || !endEl.value || !hoursEl) return;
        const hrs = computeHours(startEl.value, endEl.value);
        hoursEl.value = hrs.toFixed(2).replace(/\.?0+$/, '');
        checkWarning();
    }

    // ── Listeners ───────────────────────────────────────────────────
    hoursEl?.addEventListener('input', () => { manualHours = true; checkWarning(); });
    dateEl.addEventListener('change',  updateType);
    startEl.addEventListener('change', calcHours);
    endEl.addEventListener('change',   calcHours);

    // Init on page load (handles edit form pre-filled values)
    if (typeSel && !typeSel.value) updateType();
    calcHours();
    checkWarning();
});
