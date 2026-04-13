// Skill level cycling picker
// Used in recruitment position and applicant edit forms

const _LEVEL_ORDER = [null, 'beginner', 'intermediate', 'advanced'];

const _LEVEL_LABELS = {
    null:         '',
    beginner:     'Beginner',
    intermediate: 'Intermediate',
    advanced:     'Advanced',
};

// Full class strings defined here so Tailwind picks them up during build
const _LEVEL_CLS = {
    null:         'bg-white border-gray-300 text-gray-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400',
    beginner:     'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-300',
    intermediate: 'bg-blue-100 border-blue-300 text-blue-700 dark:bg-blue-900/40 dark:border-blue-700 dark:text-blue-300',
    advanced:     'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-300',
};

let _skillsData  = {};   // { category: [{ id, name }] }
let _skillState  = {};   // { id: level | null }
let _savedState  = {};   // snapshot before modal open (for cancel)

function _getSkillName(id) {
    for (const skills of Object.values(_skillsData)) {
        const found = skills.find(s => s.id === id);
        if (found) return found.name;
    }
    return '';
}

function _renderSummary() {
    const el = document.getElementById('skills-summary');
    if (!el) return;
    const selected = Object.entries(_skillState).filter(([, v]) => v !== null);
    if (selected.length === 0) {
        el.innerHTML = '<span class="text-xs text-gray-400 dark:text-gray-500">No skills selected</span>';
        return;
    }
    el.innerHTML = selected.map(([id, level]) =>
        `<span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border ${_LEVEL_CLS[level]}">
            ${_getSkillName(parseInt(id))}
            <span class="opacity-60 ml-1">· ${_LEVEL_LABELS[level]}</span>
        </span>`
    ).join('');
}

function _syncHiddenInputs() {
    const container = document.getElementById('skills-inputs');
    if (!container) return;
    const selected = Object.entries(_skillState).filter(([, v]) => v !== null);
    container.innerHTML = selected.map(([id, level]) =>
        `<input type="hidden" name="skills[]" value="${id}">` +
        `<input type="hidden" name="skill_levels[${id}]" value="${level}">`
    ).join('');
}

function _renderModal() {
    const body = document.getElementById('skill-modal-body');
    if (!body) return;
    let html = '';
    for (const [cat, skills] of Object.entries(_skillsData)) {
        if (!skills.length) continue;
        html += `<div class="mb-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">${cat}</p>
            <div class="flex flex-wrap gap-2">`;
        for (const skill of skills) {
            const level = _skillState[skill.id] ?? null;
            const label = level ? ` <span class="opacity-60">· ${_LEVEL_LABELS[level]}</span>` : '';
            html += `<button type="button"
                onclick="window.cycleSkill(${skill.id})"
                id="skill-btn-${skill.id}"
                class="px-2 py-1 rounded-full text-xs font-medium border cursor-pointer transition ${_LEVEL_CLS[level]}">
                ${skill.name}${label}
            </button>`;
        }
        html += `</div></div>`;
    }
    body.innerHTML = html || '<p class="text-sm text-gray-400">No skills available.</p>';
}

window.initSkillPicker = function(skills, initial) {
    _skillsData = skills;
    _skillState = {};
    for (const [id, level] of Object.entries(initial || {})) {
        if (level) _skillState[parseInt(id)] = level;
    }
    _syncHiddenInputs();
    _renderSummary();
};

window.openSkillModal = function() {
    _savedState = { ..._skillState };
    _renderModal();
    document.getElementById('skill-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
};

window.closeSkillModal = function() {
    // Revert to state before modal was opened (cancel)
    _skillState = { ..._savedState };
    document.getElementById('skill-modal').classList.add('hidden');
    document.body.style.overflow = '';
};

window.cycleSkill = function(id) {
    const current = _skillState[id] ?? null;
    const idx  = _LEVEL_ORDER.indexOf(current);
    const next = _LEVEL_ORDER[(idx + 1) % _LEVEL_ORDER.length];
    _skillState[id] = next;

    const btn = document.getElementById(`skill-btn-${id}`);
    if (!btn) return;
    const label = next ? ` <span class="opacity-60">· ${_LEVEL_LABELS[next]}</span>` : '';
    btn.className = `px-2 py-1 rounded-full text-xs font-medium border cursor-pointer transition ${_LEVEL_CLS[next]}`;
    btn.innerHTML = _getSkillName(id) + label;
};

window.applySkills = function() {
    _syncHiddenInputs();
    _renderSummary();
    // Commit — don't revert on close
    _savedState = { ..._skillState };
    document.getElementById('skill-modal').classList.add('hidden');
    document.body.style.overflow = '';
};

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('skill-modal').classList.contains('hidden')) {
        closeSkillModal();
    }
});
