// Kanban board behaviour for the recruitment position "show" page:
// - drag & drop applicant cards between status columns (persisted via AJAX)
// - drag & drop status columns to reorder them (persisted via AJAX)
// - drag & drop a CV file (from the OS) onto a column to import a new applicant
// - inline "add custom status" form
// - click a card to navigate to the applicant's page

let _draggingCard = null;
let _draggingColumn = null;

// State for the currently-open CV import modal.
let _importFile     = null;
let _importStatus   = null;
let _importStoreUrl = null;
let _importPreviewUrl = null;

function _csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function _extOf(filename) {
    const parts = String(filename).split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
}

function _escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Render a preview of the dropped CV file, styled to match the
// `_cv-preview.blade.php` panel used on the applicant edit page.
function _renderImportPreview(file) {
    const container = document.getElementById('import-cv-preview');
    if (!container) return;

    if (_importPreviewUrl) {
        URL.revokeObjectURL(_importPreviewUrl);
        _importPreviewUrl = null;
    }

    const ext = _extOf(file.name);
    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    const url = URL.createObjectURL(file);
    _importPreviewUrl = url;

    if (imageExts.includes(ext)) {
        container.innerHTML = `<img src="${url}" alt="CV Preview" class="max-w-full rounded border border-gray-200 dark:border-gray-700">`;
    } else if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${url}" class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 60vh;"></iframe>`;
    } else if (['doc', 'docx'].includes(ext)) {
        container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded text-center px-4">`
            + `<p>📄 ${_escapeHtml(file.name)}</p>`
            + `<p class="mt-1 text-xs">Bản xem trước Word sẽ khả dụng sau khi lưu.</p>`
            + `</div>`;
    } else {
        container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded text-center px-4">`
            + `<p>Không có bản xem trước cho loại file này.</p>`
            + `<p class="mt-1">📄 ${_escapeHtml(file.name)}</p>`
            + `</div>`;
    }
}

// ─────────────────────────────────────────────────────────────────────────
// CV import modal
// ─────────────────────────────────────────────────────────────────────────
window.openImportModal = function (file, status, storeUrl) {
    if (!window.recruitmentCanEdit) return;

    _importFile     = file;
    _importStatus   = status;
    _importStoreUrl = storeUrl;

    const nameInput  = document.getElementById('import-name');
    const emailInput = document.getElementById('import-email');
    const phoneInput = document.getElementById('import-phone');
    const errorEl    = document.getElementById('import-error');
    const labelEl    = document.getElementById('import-status-label');
    const submitBtn  = document.getElementById('import-submit-btn');

    if (nameInput)  nameInput.value  = file.name.replace(/\.[^/.]+$/, '');
    if (emailInput) emailInput.value = '';
    if (phoneInput) phoneInput.value = '';
    if (errorEl)   { errorEl.classList.add('hidden'); errorEl.textContent = ''; }
    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Thêm ứng viên'; }

    const labels = window.recruitmentStatusLabels || {};
    if (labelEl) labelEl.textContent = labels[status] || status;

    _renderImportPreview(file);

    const modal = document.getElementById('recruitment-import-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
};

window.closeImportModal = function () {
    const modal = document.getElementById('recruitment-import-modal');
    if (modal) modal.classList.add('hidden');
    document.body.style.overflow = '';

    if (_importPreviewUrl) {
        URL.revokeObjectURL(_importPreviewUrl);
        _importPreviewUrl = null;
    }

    _importFile     = null;
    _importStatus   = null;
    _importStoreUrl = null;
};

window.submitImportModal = async function () {
    if (!_importFile || !_importStoreUrl) return;

    const nameInput  = document.getElementById('import-name');
    const emailInput = document.getElementById('import-email');
    const phoneInput = document.getElementById('import-phone');
    const errorEl    = document.getElementById('import-error');
    const submitBtn  = document.getElementById('import-submit-btn');

    const name  = nameInput  ? nameInput.value.trim()  : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.trim() : '';

    if (!name) {
        if (errorEl) { errorEl.textContent = 'Vui lòng nhập tên ứng viên.'; errorEl.classList.remove('hidden'); }
        return;
    }

    const formData = new FormData();
    formData.append('name', name);
    if (email) formData.append('email', email);
    if (phone) formData.append('phone', phone);
    if (_importStatus) formData.append('status', _importStatus);
    formData.append('cv', _importFile);

    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Đang thêm…'; }
    if (errorEl)   { errorEl.classList.add('hidden'); errorEl.textContent = ''; }

    try {
        const resp = await fetch(_importStoreUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrfToken(),
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (!resp.ok) {
            const data = await resp.json().catch(() => ({}));
            throw new Error(data.message || ('Server error ' + resp.status));
        }

        window.location.reload();
    } catch (err) {
        console.error('Import applicant failed', err);
        if (errorEl) { errorEl.textContent = 'Không thể thêm ứng viên. Vui lòng thử lại.'; errorEl.classList.remove('hidden'); }
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Thêm ứng viên'; }
    }
};

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

    // Dropping a file from the OS → open the CV import modal for this column.
    if (e.dataTransfer.types.includes('Files') && e.dataTransfer.files.length) {
        const file = e.dataTransfer.files[0];
        if (window.recruitmentCanEdit) {
            openImportModal(file, col.dataset.status, window.recruitmentStoreUrl);
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

// Click card → navigate to applicant show page
document.addEventListener('click', function (e) {
    const card = e.target.closest('.kanban-card');
    if (!card) return;
    // Don't navigate if user was dragging
    if (card.classList.contains('opacity-40')) return;
    window.location.href = card.dataset.applicantUrl;
});

// Escape key closes the import modal
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    const modal = document.getElementById('recruitment-import-modal');
    if (modal && !modal.classList.contains('hidden')) {
        closeImportModal();
    }
});
