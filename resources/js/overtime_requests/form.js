document.addEventListener('DOMContentLoaded', () => {
    const start = document.getElementById('start_at');
    const end = document.getElementById('end_at');
    const hours = document.getElementById('hours');
    let manuallyEdited = false;

    hours.addEventListener('input', () => {
        manuallyEdited = true;
    });

    function calculateHours() {
        if (!start.value || !end.value || manuallyEdited) return;

        const startDate = new Date(start.value);
        const endDate = new Date(end.value);

        if (endDate < startDate) {
            hours.value = '';
            return;
        }

        const diffMs = endDate - startDate;
        const diffHours = diffMs / (1000 * 60 * 60);

        hours.value = diffHours.toFixed(2);
    }

    start.addEventListener('change', calculateHours);
    end.addEventListener('change', calculateHours);    
});