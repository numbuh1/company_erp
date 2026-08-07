// Kanban board behaviour for the recruitment position "show" page, plus the
// shared applicant edit modal (also used on the applicant "show" page):
// - drag & drop applicant cards between status columns (persisted via AJAX)
// - drag & drop status columns to reorder them (persisted via AJAX)
// - drag & drop a CV file (from the OS) onto a column: immediately creates a
//   new applicant (default name = filename) with the uploaded CV, then opens
//   the applicant edit modal (with a real, full CV preview) to fill in details
// - drag & drop *multiple* CV files at once: creates one applicant per file
//   (name = filename, no modal), then shows a single combined notification
// - inline "add custom status" form
// - per-card dropdown menu: edit / delete applicant
// - click a card to navigate to the applicant's page
// - the applicant edit modal: full form (status, name, contact info, CV, HR
//   note, notes, evaluation, salary/availability, referer, skills, tags),
//   submitted via AJAX, with a "Xóa" (delete) button + confirmation
// - "duplicate applicant" pop-up: when saving an applicant whose email/phone
//   matches another applicant record, offers to import that record's data,
//   delete this applicant, or keep the new data (remembered per-applicant)
// - notifications: assigned users are notified when an applicant is added
//   manually, when a single CV-drop applicant is saved from the modal for
//   the first time, or as one combined notification after a multi-CV drop

let _draggingCard = null;
let _draggingColumn = null;

// id of the applicant currently loaded in the edit modal.
let _editApplicantId = null;

let _refererTomSelect = null;
let _tagsTomSelect = null;

// FormData for an applicant update that triggered the "duplicate
// applicant" pop-up — re-submitted once the user picks an action.
let _pendingApplicantFormData = null;

// True when the applicant currently loaded in the edit modal was just
// created from a single-CV drop and hasn't been saved from the modal yet —
// the next save should notify assigned users about the new applicant.
let _notifyOnNextSave = false;

function _csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function _extOf(filename) {
    const parts = String(filename).split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
}

// Render a CV preview from a real (server-hosted, internet-accessible) URL,
// styled to match the `_cv-preview.blade.php` panel used on the applicant
// show page. Unlike a blob: URL, this URL also supports the Office Online
// embed for doc/docx files.
function _renderCvPreview(url, filename) {
    const container = document.getElementById('am-cv-preview');
    if (!container) return;

    if (!url) {
        container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded">`
            + `<p>Chưa có file CV.</p>`
            + `</div>`;
        return;
    }

    const ext = _extOf(filename);
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    if (imageExts.includes(ext)) {
        container.innerHTML = `<img src="${url}" alt="CV Preview" class="max-w-full rounded border border-gray-200 dark:border-gray-700">`;
    } else if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 60vh;"></iframe>`;
    } else if (['doc', 'docx'].includes(ext)) {
        container.innerHTML = `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}" class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 60vh;"></iframe>`
            + `<p class="text-xs text-gray-400 mt-2">Bản xem trước file Word/Doc cần URL có thể truy cập được từ Internet. Nếu không hiển thị được, vui lòng tải xuống để xem.</p>`;
    } else {
        container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded text-center px-4">`
            + `<p>Không có bản xem trước cho loại file này.</p>`
            + `</div>`;
    }
}

// Live-preview a newly selected CV file before it's uploaded.
function _previewLocalCvFile(file) {
    const container = document.getElementById('am-cv-preview');
    if (!container || !file) return;

    const ext = _extOf(file.name);
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    const url = URL.createObjectURL(file);

    if (imageExts.includes(ext)) {
        container.innerHTML = `<img src="${url}" alt="CV Preview" class="max-w-full rounded border border-gray-200 dark:border-gray-700">`;
    } else if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 60vh;"></iframe>`;
    } else if (['doc', 'docx'].includes(ext)) {
        container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded text-center px-4">`
            + `<p>📄 ${file.name.replace(/[<>&]/g, '')}</p>`
            + `<p class="mt-1 text-xs">Bản xem trước Word sẽ khả dụng sau khi lưu.</p>`
            + `</div>`;
    } else {
        container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded">`
            + `<p>Không có bản xem trước cho loại file này.</p>`
            + `</div>`;
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Star rating (evaluation)
// ─────────────────────────────────────────────────────────────────────────
window.setRating = function (val) {
    const input = document.getElementById('evaluation-input');
    if (input) input.value = val;
    document.querySelectorAll('#am-star-rating .star-btn').forEach(function (btn) {
        const isFilled = parseInt(btn.dataset.star) <= val;
        btn.textContent = isFilled ? '★' : '☆';
        btn.style.color = isFilled ? '#f59e0b' : '';
    });
};

// ─────────────────────────────────────────────────────────────────────────
// Applicant edit modal
// ─────────────────────────────────────────────────────────────────────────
function _clearApplicantModalErrors() {
    document.querySelectorAll('#recruitment-applicant-modal [id^="am-error"]').forEach(function (el) {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

function _showApplicantModalErrors(errors) {
    _clearApplicantModalErrors();
    if (!errors) return;

    const generic = [];
    Object.entries(errors).forEach(function ([field, messages]) {
        const baseField = field.split('.')[0];
        const el = document.getElementById('am-error-' + baseField);
        const msg = Array.isArray(messages) ? messages[0] : messages;
        if (el) {
            el.textContent = msg;
            el.classList.remove('hidden');
        } else {
            generic.push(msg);
        }
    });

    if (generic.length) {
        const el = document.getElementById('am-error');
        if (el) {
            el.textContent = generic.join(' ');
            el.classList.remove('hidden');
        }
    }
}

function _setVal(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value ?? '';
}

// Fill every field in the modal from an applicant-like object (either the
// `_applicantToJson()` payload from the store/update/show endpoints, or a
// blank placeholder used when creating a brand new applicant).
function _fillApplicantFields(applicant, cvUrl) {
    // Status
    const statusSelect = document.getElementById('am-status');
    if (statusSelect) statusSelect.value = applicant.status || '';

    // Basic fields
    _setVal('am-name', applicant.name);
    _setVal('am-notes', applicant.notes);
    _setVal('am-hr-note', applicant.hr_note);
    _setVal('am-email', applicant.email);
    _setVal('am-phone', applicant.phone);
    _setVal('am-profile-url', applicant.profile_url);
    _setVal('am-salary-expectation', applicant.salary_expectation);
    _setVal('am-available-date', applicant.available_date);

    // Evaluation
    window.setRating(applicant.evaluation || 0);

    // CV
    const cvInput = document.getElementById('am-cv');
    if (cvInput) cvInput.value = '';

    const currentCv     = document.getElementById('am-current-cv');
    const currentCvLink = document.getElementById('am-current-cv-link');
    if (applicant.cv_path && cvUrl) {
        if (currentCvLink) currentCvLink.href = cvUrl;
        if (currentCv) currentCv.classList.remove('hidden');
    } else if (currentCv) {
        currentCv.classList.add('hidden');
    }
    _renderCvPreview(cvUrl, applicant.cv_path || '');

    // Referer
    if (_refererTomSelect) {
        _refererTomSelect.setValue(applicant.referer_user_id ? String(applicant.referer_user_id) : '');
    }

    // Tags
    if (_tagsTomSelect) {
        _tagsTomSelect.setValue((applicant.tags || []).map(String));
    }

    // Skills
    const existingSkills = {};
    (applicant.skills || []).forEach(function (s) {
        existingSkills[s.id] = s.level;
    });
    if (typeof window.initSkillPicker === 'function') {
        window.initSkillPicker(window.recruitmentSkillsByCategory || {}, existingSkills);
    }
}

function _showApplicantModal() {
    const modal = document.getElementById('recruitment-applicant-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function _fillActivityLog(activities, cvUploadedAt) {
    const cvDate = document.getElementById('am-cv-uploaded-at');
    if (cvDate) {
        cvDate.textContent = cvUploadedAt ? '(Tải lên: ' + cvUploadedAt + ')' : '';
    }

    const container = document.getElementById('am-activity-log');
    if (!container) return;

    if (!activities || activities.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-400">Chưa có hoạt động nào.</p>';
        return;
    }

    container.innerHTML = activities.map(function (a) {
        var changesHtml = '';
        if (a.changes && a.changes.length > 0) {
            changesHtml = '<div class="mt-1 space-y-0.5">'
                + a.changes.map(function (c) {
                    var oldPart = (c.old !== null && c.old !== undefined)
                        ? '<span class="line-through text-red-400">' + _escapeHtml(String(c.old)) + '</span> → '
                        : '';
                    return '<div class="text-xs text-gray-500"><span class="font-medium">' + _escapeHtml(c.label) + '</span>: '
                        + oldPart
                        + '<span class="text-green-600">' + _escapeHtml(String(c['new'] ?? '')) + '</span></div>';
                }).join('')
                + '</div>';
        }
        return '<div class="flex gap-4 text-sm border-l-2 border-indigo-300 pl-4 py-1">'
            + '<div class="text-gray-400 whitespace-nowrap w-32 shrink-0">' + _escapeHtml(a.created_at) + '</div>'
            + '<div>'
            + '<span class="font-medium text-gray-800 dark:text-gray-200">' + _escapeHtml(a.causer_name) + '</span>'
            + '<span class="text-gray-500 ml-1">' + _escapeHtml(a.description) + '</span>'
            + changesHtml
            + '</div>'
            + '</div>';
    }).join('');
}

// Populate and show the modal to edit an existing applicant (object
// returned by the store/update/show endpoints' `_applicantToJson()`).
function _openApplicantModal(applicant, cvUrl, opts) {
    opts = opts || {};
    _editApplicantId = applicant.id;
    _notifyOnNextSave = !!opts.isNew;

    const titleEl    = document.getElementById('am-title');
    const subtitleEl = document.getElementById('am-subtitle');
    if (titleEl)    titleEl.textContent = opts.isNew ? 'Đã thêm ứng viên từ CV' : 'Chỉnh sửa Ứng viên';
    if (subtitleEl) subtitleEl.classList.toggle('hidden', !opts.isNew);

    _clearApplicantModalErrors();
    _fillApplicantFields(applicant, cvUrl);
    _fillActivityLog(opts.activities || [], opts.cvUploadedAt || null);

    document.getElementById('am-delete-btn')?.classList.remove('hidden');

    _showApplicantModal();
}

// Open the modal to edit an existing applicant (fetches full details).
window.openApplicantEditModal = async function (id) {
    try {
        const resp = await fetch(`${window.recruitmentBaseUrl}/applicants/${id}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!resp.ok) throw new Error('Server error ' + resp.status);

        const data = await resp.json();
        _openApplicantModal(data.applicant, data.cv_url, {
            isNew: false,
            activities: data.activities || [],
            cvUploadedAt: data.cv_uploaded_at || null,
        });
    } catch (err) {
        console.error('Load applicant failed', err);
        alert('Không thể tải thông tin ứng viên. Vui lòng thử lại.');
    }
};

// Open the modal to create a brand new applicant (blank form).
window.openApplicantCreateModal = function () {
    _editApplicantId = null;
    _notifyOnNextSave = false;

    const titleEl    = document.getElementById('am-title');
    const subtitleEl = document.getElementById('am-subtitle');
    if (titleEl)    titleEl.textContent = 'Thêm Ứng viên mới';
    if (subtitleEl) subtitleEl.classList.add('hidden');

    _clearApplicantModalErrors();
    _fillApplicantFields({
        status: 'Lọc CV',
        name: '', notes: '', hr_note: '', email: '', phone: '', profile_url: '',
        salary_expectation: '', available_date: '', evaluation: 0,
        cv_path: null, referer_user_id: null, skills: [], tags: [],
    }, null);
    _fillActivityLog([], null);

    document.getElementById('am-delete-btn')?.classList.add('hidden');

    _showApplicantModal();
};

window.closeApplicantModal = function () {
    const modal = document.getElementById('recruitment-applicant-modal');
    if (modal) modal.classList.add('hidden');
    document.body.style.overflow = '';
    _editApplicantId = null;
    _notifyOnNextSave = false;
};

window.submitApplicantModal = async function () {
    const isCreate = !_editApplicantId;

    const name = document.getElementById('am-name')?.value.trim() || '';
    if (!name) {
        _showApplicantModalErrors({ name: ['Vui lòng nhập tên ứng viên.'] });
        return;
    }

    _clearApplicantModalErrors();

    const formData = new FormData();
    if (!isCreate) formData.append('_method', 'PUT');
    formData.append('name', name);

    const statusVal = document.getElementById('am-status')?.value;
    if (statusVal !== undefined) formData.append('status', statusVal);

    formData.append('notes', document.getElementById('am-notes')?.value || '');

    const hrNoteEl = document.getElementById('am-hr-note');
    if (hrNoteEl) formData.append('hr_note', hrNoteEl.value || '');

    formData.append('evaluation', document.getElementById('evaluation-input')?.value || '0');
    formData.append('email', document.getElementById('am-email')?.value || '');
    formData.append('phone', document.getElementById('am-phone')?.value || '');
    formData.append('profile_url', document.getElementById('am-profile-url')?.value || '');

    const salaryEl = document.getElementById('am-salary-expectation');
    if (salaryEl) formData.append('salary_expectation', salaryEl.value || '');

    formData.append('available_date', document.getElementById('am-available-date')?.value || '');
    formData.append('referer_user_id', _refererTomSelect ? (_refererTomSelect.getValue() || '') : '');

    // Tags (TomSelect multi-select)
    (_tagsTomSelect ? _tagsTomSelect.getValue() : []).forEach(function (tagId) {
        formData.append('tags[]', tagId);
    });

    // Skills (hidden inputs synced by skill-picker.js)
    document.querySelectorAll('#skills-inputs input').forEach(function (input) {
        formData.append(input.name, input.value);
    });

    // CV file
    const cvFile = document.getElementById('am-cv')?.files?.[0];
    if (cvFile) formData.append('cv', cvFile);

    // First save of a single-CV-drop applicant — notify assigned users now.
    if (!isCreate && _notifyOnNextSave) {
        formData.append('notify_applicant_added', '1');
        _notifyOnNextSave = false;
    }

    await _submitApplicantFormData(formData);
};

// Post the applicant create/update FormData to the server. Handles
// validation errors, the "duplicate applicant" pop-up (re-submission is
// driven by that pop-up's buttons), and the generic success/error paths.
async function _submitApplicantFormData(formData) {
    const isCreate = !_editApplicantId;

    const submitBtn = document.getElementById('am-submit-btn');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Đang lưu…'; }

    const url = isCreate
        ? window.recruitmentStoreUrl
        : `${window.recruitmentBaseUrl}/applicants/${_editApplicantId}`;

    try {
        const resp = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (resp.status === 422) {
            const data = await resp.json().catch(() => ({}));
            _showApplicantModalErrors(data.errors);
            return;
        }

        if (!resp.ok) {
            const data = await resp.json().catch(() => ({}));
            throw new Error(data.message || ('Server error ' + resp.status));
        }

        const data = await resp.json().catch(() => ({}));

        if (data && data.duplicate) {
            _pendingApplicantFormData = formData;
            _showDuplicateModal(data.duplicates || []);
            return;
        }

        window.location.reload();
    } catch (err) {
        console.error('Save applicant failed', err);
        _showApplicantModalErrors(null);
        const el = document.getElementById('am-error');
        if (el) { el.textContent = 'Không thể lưu thông tin. Vui lòng thử lại.'; el.classList.remove('hidden'); }
    } finally {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Lưu'; }
    }
}

// ─────────────────────────────────────────────────────────────────────────
// "Duplicate applicant" pop-up
// ─────────────────────────────────────────────────────────────────────────
function _escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function _showDuplicateModal(duplicates) {
    const list = document.getElementById('dup-list');
    if (!list) return;
    list.innerHTML = '';

    if (!duplicates.length) {
        list.innerHTML = '<p class="text-sm text-gray-400">Không tìm thấy ứng viên trùng.</p>';
    }

    duplicates.forEach(function (d) {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700';
        row.innerHTML = `
            <div class="min-w-0">
                <a href="${_escapeHtml(d.url)}" target="_blank" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline truncate block">${_escapeHtml(d.name)}</a>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">${_escapeHtml(d.position_name || '')} · ${_escapeHtml(d.status_label || '')}</p>
            </div>
            <button type="button" class="shrink-0 px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">Nhập thông tin cũ</button>
        `;
        row.querySelector('button').addEventListener('click', function () {
            importDuplicateApplicant(d.id);
        });
        list.appendChild(row);
    });

    document.getElementById('recruitment-duplicate-modal')?.classList.remove('hidden');
}

// Close the pop-up without taking any action — the applicant edit modal
// (with the data the user typed) stays open underneath.
window.cancelDuplicateModal = function () {
    document.getElementById('recruitment-duplicate-modal')?.classList.add('hidden');
    _pendingApplicantFormData = null;
};

// "Giữ thông tin mới" — keep the newly entered data and remember not to
// show this pop-up again (unless email/phone change again).
window.dismissDuplicateModal = function () {
    document.getElementById('recruitment-duplicate-modal')?.classList.add('hidden');
    const formData = _pendingApplicantFormData;
    _pendingApplicantFormData = null;
    if (!formData) return;

    formData.append('skip_duplicate_check', '1');
    _submitApplicantFormData(formData);
};

// "Nhập thông tin cũ" — overwrite this applicant's data with the matched
// past applicant's data (except the CV file).
window.importDuplicateApplicant = function (id) {
    document.getElementById('recruitment-duplicate-modal')?.classList.add('hidden');
    const formData = _pendingApplicantFormData;
    _pendingApplicantFormData = null;
    if (!formData) return;

    formData.append('import_from_applicant_id', id);
    _submitApplicantFormData(formData);
};

// "Xóa ứng viên này" — delete the applicant currently being edited.
window.deleteApplicantFromDuplicateModal = function () {
    document.getElementById('recruitment-duplicate-modal')?.classList.add('hidden');
    _pendingApplicantFormData = null;
    deleteApplicantModal();
};

window.deleteApplicantModal = async function () {
    if (!_editApplicantId) return;
    if (!confirm('Bạn có chắc muốn xóa ứng viên này? Hành động này không thể hoàn tác.')) return;

    const deleteBtn = document.getElementById('am-delete-btn');
    if (deleteBtn) deleteBtn.disabled = true;

    try {
        const resp = await fetch(`${window.recruitmentBaseUrl}/applicants/${_editApplicantId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Accept': 'application/json',
            },
        });
        if (!resp.ok) throw new Error('Server error ' + resp.status);

        window.location.href = window.recruitmentBaseUrl;
    } catch (err) {
        console.error('Delete applicant failed', err);
        alert('Không thể xóa ứng viên. Vui lòng thử lại.');
        if (deleteBtn) deleteBtn.disabled = false;
    }
};

// ─────────────────────────────────────────────────────────────────────────
// CV import: drop a file → create the applicant immediately, then show the
// edit modal (with a real CV preview) to confirm/fill in its details.
// ─────────────────────────────────────────────────────────────────────────
async function _importCvFile(file, status) {
    const formData = new FormData();
    formData.append('name', file.name.replace(/\.[^/.]+$/, ''));
    if (status) formData.append('status', status);
    formData.append('cv', file);
    // Notification is deferred until the modal is saved for the first time
    // (see `submitApplicantModal` / `_notifyOnNextSave`).
    formData.append('skip_notify', '1');

    try {
        const resp = await fetch(window.recruitmentStoreUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await resp.json().catch(() => ({}));

        if (!resp.ok || !data.success) {
            throw new Error(data.message || ('Server error ' + resp.status));
        }

        _openApplicantModal(data.applicant, data.cv_url, { isNew: true });
    } catch (err) {
        console.error('Import applicant failed', err);
        alert('Không thể thêm ứng viên từ file này. Vui lòng thử lại.');
    }
}

// Multi-CV import: drop several files at once → create one applicant per
// file (name = filename, no extension), each with the dropped CV attached,
// without opening the edit modal. Once all uploads finish, send a single
// combined notification to assigned users and reload the board.
async function _importCvFilesBulk(files, status) {
    let successCount = 0;

    for (const file of files) {
        const formData = new FormData();
        formData.append('name', file.name.replace(/\.[^/.]+$/, ''));
        if (status) formData.append('status', status);
        formData.append('cv', file);
        formData.append('skip_notify', '1');

        try {
            const resp = await fetch(window.recruitmentStoreUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': _csrfToken(),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await resp.json().catch(() => ({}));
            if (resp.ok && data.success) successCount++;
        } catch (err) {
            console.error('Import applicant failed', err);
        }
    }

    if (successCount > 0 && window.recruitmentNotifyBulkUrl) {
        try {
            await fetch(window.recruitmentNotifyBulkUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': _csrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ count: successCount }),
            });
        } catch (err) {
            console.error('Bulk-add notification failed', err);
        }
    }

    if (successCount === files.length) {
        window.location.reload();
    } else if (successCount > 0) {
        alert(`Đã thêm ${successCount}/${files.length} ứng viên. Một số file không thể nhập.`);
        window.location.reload();
    } else {
        alert('Không thể thêm ứng viên từ các file này. Vui lòng thử lại.');
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Add custom status
// ─────────────────────────────────────────────────────────────────────────
window.openAddStatusForm = function () {
    document.getElementById('add-status-display')?.classList.add('hidden');
    document.getElementById('add-status-form')?.classList.remove('hidden');
    document.getElementById('add-status-input')?.focus();
};

window.cancelAddStatus = function () {
    document.getElementById('add-status-form')?.classList.add('hidden');
    document.getElementById('add-status-display')?.classList.remove('hidden');

    const input = document.getElementById('add-status-input');
    if (input) input.value = '';

    const errorEl = document.getElementById('add-status-error');
    if (errorEl) { errorEl.classList.add('hidden'); errorEl.textContent = ''; }
};

window.submitAddStatus = async function () {
    const input   = document.getElementById('add-status-input');
    const errorEl = document.getElementById('add-status-error');
    const name    = input ? input.value.trim() : '';

    if (!name) {
        if (errorEl) { errorEl.textContent = 'Vui lòng nhập tên trạng thái.'; errorEl.classList.remove('hidden'); }
        return;
    }

    try {
        const resp = await fetch(window.recruitmentAddStatusUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name }),
        });

        const data = await resp.json().catch(() => ({}));

        if (!resp.ok || !data.ok) {
            throw new Error(data.message || 'Server error');
        }

        window.location.reload();
    } catch (err) {
        console.error('Add status failed', err);
        if (errorEl) { errorEl.textContent = 'Không thể thêm trạng thái. Có thể trạng thái đã tồn tại.'; errorEl.classList.remove('hidden'); }
    }
};

// ─────────────────────────────────────────────────────────────────────────
// Drag & drop: applicant cards between columns + OS file drop to import
// ─────────────────────────────────────────────────────────────────────────
window.kanbanDragStart = function (e) {
    _draggingCard = e.currentTarget;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', _draggingCard.dataset.applicantId);
    requestAnimationFrame(() => _draggingCard.classList.add('opacity-40', 'scale-95'));
};

window.kanbanDragOver = function (e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = e.dataTransfer.types.includes('Files') ? 'copy' : 'move';
    e.currentTarget.classList.add('ring-2', 'ring-indigo-400', 'dark:ring-indigo-500');
};

window.kanbanDragLeave = function (e) {
    if (!e.currentTarget.contains(e.relatedTarget)) {
        e.currentTarget.classList.remove('ring-2', 'ring-indigo-400', 'dark:ring-indigo-500');
    }
};

window.kanbanDrop = async function (e) {
    e.preventDefault();
    const col = e.currentTarget;
    col.classList.remove('ring-2', 'ring-indigo-400', 'dark:ring-indigo-500');

    // Dropping file(s) from the OS → create applicant(s) immediately.
    // A single file opens the edit modal (with a real CV preview) to
    // confirm/fill in details. Multiple files each become an applicant
    // named after their file, with no modal shown.
    if (e.dataTransfer.types.includes('Files') && e.dataTransfer.files.length) {
        if (window.recruitmentCanEdit) {
            const files = Array.from(e.dataTransfer.files);
            if (files.length > 1) {
                _importCvFilesBulk(files, col.dataset.status);
            } else {
                _importCvFile(files[0], col.dataset.status);
            }
        }
        return;
    }

    // Dropping a dragged column header → reorder Kanban columns.
    if (_draggingColumn) {
        const draggingCol = _draggingColumn;
        draggingCol.classList.remove('opacity-50');
        _draggingColumn = null;

        if (draggingCol === col) return;

        const rect   = col.getBoundingClientRect();
        const before = (e.clientX - rect.left) < (rect.width / 2);

        if (before) {
            col.parentNode.insertBefore(draggingCol, col);
        } else {
            col.parentNode.insertBefore(draggingCol, col.nextSibling);
        }

        await _persistColumnOrder();
        return;
    }

    if (!_draggingCard) return;

    _draggingCard.classList.remove('opacity-40', 'scale-95');
    const newStatus   = col.dataset.status;
    const applicantId = _draggingCard.dataset.applicantId;
    const cardsArea   = col.querySelector('.kanban-cards');

    // Move card in DOM
    cardsArea.appendChild(_draggingCard);

    // Update all column count badges
    document.querySelectorAll('.kanban-col').forEach(c => {
        const countEl = c.querySelector('.kanban-col-count');
        if (countEl) countEl.textContent = c.querySelectorAll('.kanban-card').length;
    });

    _draggingCard = null;

    // Persist via AJAX
    try {
        const resp = await fetch(
            `${window.recruitmentBaseUrl}/applicants/${applicantId}/status`,
            {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': _csrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus }),
            }
        );
        if (!resp.ok) throw new Error('Server error ' + resp.status);
    } catch (err) {
        console.error('Kanban status update failed', err);
    }
};

// ─────────────────────────────────────────────────────────────────────────
// Drag & drop: reorder Kanban status columns
// ─────────────────────────────────────────────────────────────────────────
window.kanbanColDragStart = function (e) {
    const col = e.currentTarget.closest('.kanban-col');
    if (!col) return;

    _draggingColumn = col;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', 'column:' + (col.dataset.status || ''));
    requestAnimationFrame(() => col.classList.add('opacity-50'));
};

window.kanbanColDragEnd = function () {
    if (_draggingColumn) {
        _draggingColumn.classList.remove('opacity-50');
    }
    _draggingColumn = null;
};

async function _persistColumnOrder() {
    if (!window.recruitmentReorderStatusesUrl) return;

    const order = Array.from(document.querySelectorAll('.kanban-col[data-status]'))
        .map(c => c.dataset.status);

    try {
        const resp = await fetch(window.recruitmentReorderStatusesUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ order }),
        });
        if (!resp.ok) throw new Error('Server error ' + resp.status);
    } catch (err) {
        console.error('Reorder statuses failed', err);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// Per-card dropdown menu: delete applicant
// ─────────────────────────────────────────────────────────────────────────
window.deleteKanbanApplicant = async function (e, id) {
    e.preventDefault();
    e.stopPropagation();

    if (!confirm('Bạn có chắc muốn xóa ứng viên này?')) return;

    try {
        const resp = await fetch(`${window.recruitmentBaseUrl}/applicants/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Accept': 'application/json',
            },
        });
        if (!resp.ok) throw new Error('Server error ' + resp.status);

        // The dropdown is teleported to <body>, so it's no longer inside
        // the card's DOM tree — look the card up by its applicant id instead.
        const card = document.querySelector(`.kanban-card[data-applicant-id="${id}"]`);
        const col  = card ? card.closest('.kanban-col') : null;
        if (card) card.remove();
        if (col) {
            const countEl = col.querySelector('.kanban-col-count');
            if (countEl) countEl.textContent = col.querySelectorAll('.kanban-card').length;
        }
    } catch (err) {
        console.error('Delete applicant failed', err);
        alert('Không thể xóa ứng viên. Vui lòng thử lại.');
    }
};

// Click card → open the edit modal directly
document.addEventListener('click', function (e) {
    const card = e.target.closest('.kanban-card');
    if (!card) return;
    // Don't open if user was dragging
    if (card.classList.contains('opacity-40')) return;
    openApplicantEditModal(card.dataset.applicantId);
});

// Escape key closes the duplicate-applicant pop-up (if open), otherwise
// the applicant edit modal
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    const dupModal = document.getElementById('recruitment-duplicate-modal');
    if (dupModal && !dupModal.classList.contains('hidden')) {
        cancelDuplicateModal();
        return;
    }

    const modal = document.getElementById('recruitment-applicant-modal');
    if (modal && !modal.classList.contains('hidden')) {
        closeApplicantModal();
    }
});

// ─────────────────────────────────────────────────────────────────────────
// One-time setup: TomSelect instances for the applicant edit modal + CV
// file live preview. Run once the DOM (and the deferred TomSelect CDN
// script) is ready.
// ─────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const refererSelect = document.getElementById('am-referer-select');
    if (refererSelect && window.TomSelect) {
        _refererTomSelect = new TomSelect(refererSelect, { maxOptions: null });
    }

    const tagsSelect = document.getElementById('am-tags-select');
    if (tagsSelect && window.TomSelect) {
        _tagsTomSelect = new TomSelect(tagsSelect, {
            create: true,
            createOnBlur: true,
            persist: false,
            maxOptions: null,
            render: {
                option_create: function (data, escape) {
                    return '<div class="create">Create tag <strong>' + escape(data.input) + '</strong></div>';
                }
            }
        });
    }

    const cvInput = document.getElementById('am-cv');
    if (cvInput) {
        cvInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) _previewLocalCvFile(file);
        });
    }
});
