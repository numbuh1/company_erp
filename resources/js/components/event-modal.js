let _tsAttendants = null;
let _tsLocation   = null;
let _usersLoaded  = false;
let _locsLoaded   = false;

const _eventCache = new Map();

// Holds the data of the event currently shown in the read-only view modal,
// so the Edit / Send Email buttons can act on it without re-fetching.
let _currentViewData = null;

function _formatDateLabel(isoDate) {
    if (!isoDate) return '';
    const [y, m, d] = isoDate.split('-');
    return `${d}/${m}/${y}`;
}

function _formatDuration(mins) {
    if (!mins || mins <= 0) return '';
    if (mins >= 60) {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return m > 0 ? `${h}h ${m}m` : `${h}h`;
    }
    return `${mins}m`;
}

async function _loadUsers() {
    if (_usersLoaded) return;
    try {
        const res  = await fetch(window.eventRoutes.users, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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
        const res  = await fetch(window.eventRoutes.locations, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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

async function _fetchEvent(id) {
    if (_eventCache.has(id)) return _eventCache.get(id);
    try {
        const res  = await fetch(`${window.eventRoutes.base}/${id}/data`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        _eventCache.set(id, data);
        return data;
    } catch(e) {
        console.error('Failed to fetch event', e);
        return null;
    }
}

// Delegate click on any element with data-event-id — open the read-only view modal
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-event-id]');
    if (!btn) return;
    const id   = btn.dataset.eventId;
    const data = await _fetchEvent(id);
    if (data) openViewEventModal(data);
});

window.openEventModal = async function(data = {}) {
    const form = document.getElementById('event-modal-form');
    form.reset();

    if (data.id) {
        form.action = window.eventRoutes.base + '/' + data.id;
        document.getElementById('event-modal-method').value = 'PUT';
        document.getElementById('event-modal-title').textContent = data.title || 'Edit Event';
    } else {
        form.action = window.eventRoutes.store;
        document.getElementById('event-modal-method').value = 'POST';
        document.getElementById('event-modal-title').textContent = data.title || 'New Event';
    }

    // Show/hide delete button
    const deleteForm = document.getElementById('event-delete-form');
    if (data.id) {
        deleteForm.action = window.eventRoutes.base + '/' + data.id;
        deleteForm.classList.remove('hidden');
    } else {
        deleteForm.classList.add('hidden');
    }

    document.getElementById('event-modal-source').value = data.source || '';

    const applicantInput = document.getElementById('event-applicant-id');
    if (applicantInput) applicantInput.value = data.applicantId || '';

    // Applicant link row
    const linkRow = document.getElementById('event-applicant-link-row');
    const linkEl  = document.getElementById('event-applicant-link');
    if (linkRow && linkEl) {
        if (data.applicantUrl) {
            linkEl.href        = data.applicantUrl;
            linkEl.textContent = data.applicantName ? 'Applicant: ' + data.applicantName : 'View Applicant';
            linkRow.classList.remove('hidden');
        } else {
            linkRow.classList.add('hidden');
        }
    }

    const fileSection = document.getElementById('event-file-section');
    if (data.hideFile) {
        fileSection.classList.add('hidden');
    } else {
        fileSection.classList.remove('hidden');
    }

    await Promise.all([_loadUsers(), _loadLocations()]);

    if (data.name)        document.getElementById('event-name').value        = data.name;
    if (data.event_type)  document.getElementById('event-type').value        = data.event_type;
    if (data.description) document.getElementById('event-description').value = data.description;

    if (data.start_at) {
        const [datePart, timePart] = data.start_at.split('T');
        document.getElementById('event-date').value = datePart || '';
        document.getElementById('event-time').value = timePart || '';

        if (data.end_at) {
            const start    = new Date(data.start_at);
            const end      = new Date(data.end_at);
            const diffMins = Math.round((end - start) / 60000);
            document.getElementById('event-duration').value = diffMins > 0 ? diffMins : '';
        }
    }

    if (_tsLocation) {
        _tsLocation.clear();
        if (data.location) {
            if (!_tsLocation.getOption(data.location)) {
                _tsLocation.addOption({ value: data.location, text: data.location });
            }
            _tsLocation.setValue(data.location);
        }
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

// ─────────────────────────────────────────────────────────────────────────
// Read-only "view" modal — shown when clicking an event. Offers Edit and
// (for interview events linked to an applicant) Send Email actions.
// ─────────────────────────────────────────────────────────────────────────
window.openViewEventModal = function(data) {
    _currentViewData = data;

    document.getElementById('view-event-title').textContent = data.name || 'Event';
    document.getElementById('view-event-type').textContent  = data.event_type_label || data.event_type || '';

    const locationRow = document.getElementById('view-event-location-row');
    if (data.location) {
        document.getElementById('view-event-location').textContent = data.location;
        locationRow.classList.remove('hidden');
    } else {
        locationRow.classList.add('hidden');
    }

    let diffMins = 0;
    if (data.start_at) {
        const [datePart, timePart] = data.start_at.split('T');
        document.getElementById('view-event-date').textContent = _formatDateLabel(datePart);
        document.getElementById('view-event-time').textContent = timePart || '';

        if (data.end_at) {
            const start = new Date(data.start_at);
            const end   = new Date(data.end_at);
            diffMins    = Math.round((end - start) / 60000);
        }
    }
    document.getElementById('view-event-duration').textContent = _formatDuration(diffMins);

    const descRow = document.getElementById('view-event-description-row');
    if (data.description) {
        document.getElementById('view-event-description').textContent = data.description;
        descRow.classList.remove('hidden');
    } else {
        descRow.classList.add('hidden');
    }

    const attendantsRow = document.getElementById('view-event-attendants-row');
    const attendantsEl  = document.getElementById('view-event-attendants');
    attendantsEl.innerHTML = '';
    if (data.attendants_detail && data.attendants_detail.length) {
        data.attendants_detail.forEach(u => {
            const span = document.createElement('span');
            span.className = 'text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
            span.textContent = u.name;
            attendantsEl.appendChild(span);
        });
        attendantsRow.classList.remove('hidden');
    } else {
        attendantsRow.classList.add('hidden');
    }

    const applicantRow  = document.getElementById('view-event-applicant-row');
    const applicantLink = document.getElementById('view-event-applicant-link');
    if (data.applicants && data.applicants.length) {
        applicantLink.href        = data.applicants[0].url;
        applicantLink.textContent = data.applicants.map(a => a.name).join(', ');
        applicantRow.classList.remove('hidden');
    } else {
        applicantRow.classList.add('hidden');
    }

    const fileRow  = document.getElementById('view-event-file-row');
    const fileLink = document.getElementById('view-event-file-link');
    if (data.file_url) {
        fileLink.href        = data.file_url;
        fileLink.textContent = data.file_name || 'Tải tệp đính kèm';
        fileRow.classList.remove('hidden');
    } else {
        fileRow.classList.add('hidden');
    }

    document.getElementById('view-event-edit-btn').classList.toggle('hidden', !data.can_edit);

    const hasApplicantEmail = !!(data.applicants && data.applicants.some(a => a.email));
    document.getElementById('view-event-email-btn').classList.toggle('hidden', !hasApplicantEmail);

    document.getElementById('event-view-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
};

window.closeViewEventModal = function() {
    document.getElementById('event-view-modal').classList.add('hidden');
    document.body.style.overflow = '';
};

window.editFromView = function() {
    if (!_currentViewData) return;
    closeViewEventModal();
    openEventModal({ ..._currentViewData, title: 'Edit Event' });
};

// ─────────────────────────────────────────────────────────────────────────
// Email modal — composes an interview-invite email using the event data
// and the Company option settings, then hands off to the user's mail client.
// ─────────────────────────────────────────────────────────────────────────
window.openEmailFromView = function() {
    if (!_currentViewData) return;
    const data = _currentViewData;
    const cfg  = window.appConfig || {};

    const applicants = data.applicants || [];
    const to = applicants.map(a => a.email).filter(Boolean).join(', ');

    const cc = (data.attendants_detail || [])
        .filter(u => u.email && u.id !== cfg.currentUserId)
        .filter(u => !applicants.some(a => a.email === u.email))
        .map(u => u.email)
        .join(', ');

    const position      = applicants[0]?.position || '';
    const applicantName = applicants[0]?.name || '';
    const company       = cfg.companyName || '';

    let dateLabel = '', timeLabel = '', endLabel = '';
    if (data.start_at) {
        const [datePart, timePart] = data.start_at.split('T');
        dateLabel = _formatDateLabel(datePart);
        timeLabel = timePart || '';
    }
    if (data.end_at) {
        endLabel = data.end_at.split('T')[1] || '';
    }

    const subject = `Thư mời phỏng vấn${position ? ' - ' + position : ''}${company ? ' tại ' + company : ''}`;

    const lines = [];
    lines.push(`Kính gửi ${applicantName || 'Anh/Chị'},`);
    lines.push('');
    lines.push(`${company || 'Chúng tôi'} xin trân trọng mời bạn tham gia buổi phỏng vấn${position ? ' cho vị trí ' + position : ''} với thông tin chi tiết như sau:`);
    lines.push('');
    lines.push(`- Thời gian: ${dateLabel} lúc ${timeLabel}${endLabel ? ' - ' + endLabel : ''}`);
    if (data.location) lines.push(`- Địa điểm: ${data.location}`);
    if (cfg.companyAddress) lines.push(`- Địa chỉ công ty: ${cfg.companyAddress}`);
    if (cfg.companyPhone) lines.push(`- Điện thoại liên hệ: ${cfg.companyPhone}`);
    lines.push('');
    lines.push('Vui lòng phản hồi email này để xác nhận lịch hẹn. Nếu có bất kỳ thay đổi nào, xin vui lòng liên hệ với chúng tôi sớm nhất có thể.');
    lines.push('');
    lines.push('Trân trọng,');
    if (cfg.currentUserName) lines.push(cfg.currentUserName);
    if (company) lines.push(company);

    closeViewEventModal();
    openEmailModal({ to, cc, subject, body: lines.join('\n') });
};

window.openEmailModal = function(data = {}) {
    document.getElementById('email-to').value      = data.to || '';
    document.getElementById('email-cc').value      = data.cc || '';
    document.getElementById('email-subject').value = data.subject || '';
    document.getElementById('email-body').value    = data.body || '';

    document.getElementById('email-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
};

window.closeEmailModal = function() {
    document.getElementById('email-modal').classList.add('hidden');
    document.body.style.overflow = '';
};

// Builds a mailto: link from the modal fields and hands off to the
// user's default mail client (e.g. Outlook).
window.sendEmailViaClient = function() {
    const to      = document.getElementById('email-to').value.trim();
    const cc      = document.getElementById('email-cc').value.trim();
    const subject = document.getElementById('email-subject').value;
    const body    = document.getElementById('email-body').value;

    let mailto = `mailto:${encodeURIComponent(to)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

    if (cc) {
        const ccEncoded = cc.split(',').map(s => encodeURIComponent(s.trim())).filter(Boolean).join(',');
        if (ccEncoded) mailto += `&cc=${ccEncoded}`;
    }

    window.location.href = mailto;
};

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;

    const emailModal = document.getElementById('email-modal');
    const viewModal  = document.getElementById('event-view-modal');

    if (emailModal && !emailModal.classList.contains('hidden')) {
        closeEmailModal();
    } else if (viewModal && !viewModal.classList.contains('hidden')) {
        closeViewEventModal();
    } else {
        closeEventModal();
    }
});
