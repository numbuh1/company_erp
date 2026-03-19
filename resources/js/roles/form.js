document.addEventListener('DOMContentLoaded', () => {

    // Toggle all permissions in a module
    document.querySelectorAll('.module-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const container = this.closest('.border');
            const checkboxes = container.querySelectorAll('.permission-checkbox');

            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    });

    // Update "Select All" when individual checkbox changes
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            const container = this.closest('.border');
            const all = container.querySelectorAll('.permission-checkbox');
            const checked = container.querySelectorAll('.permission-checkbox:checked');
            const toggle = container.querySelector('.module-toggle');

            toggle.checked = all.length === checked.length;
        });
    });

});