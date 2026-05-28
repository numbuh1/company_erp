document.addEventListener('DOMContentLoaded', () => {
    const dateEl  = document.getElementById('ot_date');
    const startEl = document.getElementById('start_time');
    const endEl   = document.getElementById('end_time');
    const hoursEl = document.getElementById('hours');

    // Not an editable form (readonly view has none of these)
    if (!dateEl || !startEl || !endEl) return;

    const holidayDates = window._otHolidayDates || [];
    let manualHours    = false;

    // ── Type helpers ────────────────────────────────────────────────
    function getOtType(dateStr) {
        if (!dateStr) return 'OT x1.5';
        if (holidayDates.includes(dateStr)) return 'OT x3';
        const day = new Date(dateStr + 'T00:00:00').getDay(); // 0 = Sunday
        return day === 0 ? 'OT x2' : 'OT x1.5';
    }

    function typeBadgeCls(type) {
        if (type === 'OT x3') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        if (type === 'OT x2') return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
        return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
    }

    function updateType() {
        const type      = getOtType(dateEl.value);
        const hiddenEl  = document.getElementById('ot_type_hidden');
        const displayEl = document.getElementById('ot_type_display');
        if (hiddenEl)  hiddenEl.value = type;
        if (displayEl) {
            displayEl.textContent = type;
            displayEl.className   =
                'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ' +
                typeBadgeCls(type);
        }
    }

    // ── Hours auto-calc ─────────────────────────────────────────────
    function calcHours() {
        if (manualHours || !startEl.value || !endEl.value || !hoursEl) return;
        const [sh, sm] = startEl.value.split(':').map(Number);
        const [eh, em] = endEl.value.split(':').map(Number);
        const diff = (eh * 60 + em) - (sh * 60 + sm);
        if (diff > 0) {
            hoursEl.value = (diff / 60).toFixed(2).replace(/\.?0+$/, '');
        }
    }

    // ── Listeners ───────────────────────────────────────────────────
    hoursEl?.addEventListener('input', () => { manualHours = true; });
    dateEl.addEventListener('change',  updateType);
    startEl.addEventListener('change', calcHours);
    endEl.addEventListener('change',   calcHours);

    // Init on page load (handles edit form pre-filled values)
    updateType();
    calcHours();
});
