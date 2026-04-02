let _tsAttendants = null;
let _tsLocation   = null;
let _usersLoaded  = false;
let _locsLoaded   = false;

async function _loadUsers() {
    if (_usersLoaded) return;
    try {
        const res  = await fetch('/events/users', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        _tsAttendants = new TomSelect('#event-attendants', {
            options: data,
            valueField: 'id',
            labelField: 'label',
            searchField: 'label',
            maxOptions: null,
            plugins: ['remove_button'],
        });
        _usersLoaded = true;
    } catch(e) { console.error('Failed to load users', e); }
}

async function _loadLocations() {
    if (_locsLoaded) return;
    try {
        const res  = await fetch('/events/locations', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        const opts = data.map(n => ({ value: n, text: n }));
        _tsLocation = new TomSelect('#event-location', {
            options: opts,
            create: true,
            createOnBlur: true,
            maxOptions: null,
            placeholder: '— None —',
            allowEmptyOption: true,
        });
        _locsLoaded = true;
    } catch(e) { console.error('Failed to load locations', e); }
}

window.openEventModal = async function(data = {}) {
    // Reset form
    const form = document.getElementById('event-modal-form');
    form.reset();
    form.action = '/events';
    document.getElementById('event-modal-method').value = 'POST';
    document.getElementById('event-modal-title').textContent = data.title || 'New Event';
    document.getElementById('event-modal-source').value = data.source || '';

    // Set applicant ID (single value — controller wraps in array via hidden input name="applicant_ids[]")
    const applicantInput = document.getElementById('event-applicant-id');
    applicantInput.value = data.applicantId || '';

    // Show/hide file section
    const fileSection = document.getElementById('event-file-section');
    if (data.hideFile) {
        fileSection.classList.add('hidden');
    } else {
        fileSection.classList.remove('hidden');
    }

    // Load Tom Select options lazily
    await Promise.all([_loadUsers(), _loadLocations()]);

    // Pre-fill fields
    if (data.name)        document.getElementById('event-name').value        = data.name;
    if (data.event_type)  document.getElementById('event-type').value        = data.event_type;
    if (data.description) document.getElementById('event-description').value = data.description;
    if (data.start_at)    document.getElementById('event-start').value       = data.start_at;
    if (data.end_at)      document.getElementById('event-end').value         = data.end_at;

    if (data.location && _tsLocation) {
        _tsLocation.clear();
        _tsLocation.createItem(data.location);
        _tsLocation.setValue(data.location);
    }

    if (data.attendants && _tsAttendants) {
        _tsAttendants.clear();
        _tsAttendants.setValue(data.attendants.map(String));
    }

    document.getElementById('event-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
};

window.closeEventModal = function() {
    document.getElementById('event-modal').classList.add('hidden');
    document.body.style.overflow = '';
};

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEventModal();
});
