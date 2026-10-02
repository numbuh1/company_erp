// Leave request page: hours use the same rules as the WFH form (window.WorkHours, see work-hours.js).
// A single day keeps an editable total; a multi-day total is calculated (the server recalculates it anyway).

document.addEventListener('DOMContentLoaded', () => {
    const startEl     = document.getElementById('start_at');
    const endEl       = document.getElementById('end_at');
    const hoursEl     = document.getElementById('hours');
    const breakdownEl = document.getElementById('leave-hours-breakdown');

    if (!startEl || !endEl || !hoursEl || !window.WorkHours) return;

    const readonly = hoursEl.disabled;
    let totalManual = false;
    hoursEl.addEventListener('input', () => { totalManual = true; });

    function update() {
        breakdownEl?.classList.add('hidden');
        if (!startEl.value || !endEl.value) return;

        const start = new Date(startEl.value);
        const end   = new Date(endEl.value);
        const b     = window.WorkHours.breakdown(start, end);
        if (!b) { if (!totalManual) hoursEl.value = ''; return; }

        if (!b.multiDay) {
            if (!readonly) hoursEl.disabled = false;
            if (!totalManual) hoursEl.value = b.total.toFixed(2).replace(/\.?0+$/, '');
            return;
        }

        totalManual = false;
        hoursEl.value = b.total.toFixed(2).replace(/\.?0+$/, '');
        hoursEl.disabled = true;
        if (breakdownEl) {
            breakdownEl.innerHTML = window.WorkHours.html(b, start, end, 'blue');
            breakdownEl.classList.remove('hidden');
        }
    }

    startEl.addEventListener('change', () => { totalManual = false; update(); });
    endEl.addEventListener('change', () => { totalManual = false; update(); });

    // Edit mode: show the breakdown, but keep a saved single-day total as it was entered
    if (startEl.value && endEl.value) {
        const saved = hoursEl.value;
        update();
        if (saved !== '' && !hoursEl.disabled) { hoursEl.value = saved; totalManual = true; }
    }
});
