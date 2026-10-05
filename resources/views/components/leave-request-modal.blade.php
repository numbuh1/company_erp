@php
    $lrmAuth         = auth()->user();
    $lrmCanCreate    = $lrmAuth?->canAny(['edit own leaves', 'edit team leaves', 'edit all leaves']);
    $lrmCanTeamOrAll = $lrmAuth?->canAny(['edit team leaves', 'edit all leaves']);
    $lrmCanApprove   = $lrmAuth?->canAny(['approve team leaves', 'approve all leaves']);

    if ($lrmCanCreate) {
        if ($lrmAuth->can('edit all leaves')) {
            $lrmUsers = \App\Models\User::orderBy('name')->get(['id', 'name', 'position']);
        } elseif ($lrmAuth->can('edit team leaves')) {
            $ledTeamIds = $lrmAuth->teams()->wherePivot('is_leader', true)->pluck('teams.id');
            if ($ledTeamIds->isEmpty()) {
                $lrmUsers = collect([$lrmAuth]);
            } else {
                $memberIds = \App\Models\Team::whereIn('id', $ledTeamIds)
                    ->with('users')->get()->flatMap->users->pluck('id')->unique();
                $lrmUsers = \App\Models\User::whereIn('id', $memberIds)->orderBy('name')->get(['id', 'name', 'position']);
            }
        } else {
            $lrmUsers = collect([$lrmAuth]);
        }
    } else {
        $lrmUsers = collect();
    }

@endphp

<div id="lrm-overlay" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 hidden pb-[4.5rem] sm:pb-0">
    <div id="lrm-box" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full sm:max-w-lg max-h-[90vh] overflow-y-auto mx-2 sm:mx-0">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 id="lrm-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Leave Request') }}</h3>
            <button onclick="closeLR()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            <div id="lrm-loading" class="hidden py-10 text-center text-gray-400 text-sm">{{ __('Loading…') }}</div>

            {{-- Status banner --}}
            <div id="lrm-status-banner" class="hidden rounded-lg px-4 py-2.5 text-sm font-medium"></div>

            {{-- User --}}
            @if($lrmCanTeamOrAll && $lrmUsers->count() > 1)
                <div id="lrm-user-row" class="hidden">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('User') }}</label>
                    <p id="lrm-user-display" class="hidden text-sm font-medium text-gray-900 dark:text-gray-100 py-1"></p>                
                        <select id="lrm-user-select" class="hidden w-full">
                            <option value="">{{ __('— Select user —') }}</option>
                            @foreach($lrmUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                            @endforeach
                        </select>
                </div>            
            @else
                <input type="hidden" id="lrm-user-select" value="{{ $lrmAuth?->id }}">
            @endif

            {{-- Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Leave Type') }}</label>
                <p id="lrm-type-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <select id="lrm-type" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                    <option value="annual">{{ __('Annual leave') }}</option>
                    <option value="sick">{{ __('Sick leave') }}</option>
                    <option value="unpaid">{{ __('Unpaid leave') }}</option>
                </select>
            </div>

            {{-- Start / End --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Start') }}</label>
                    <p id="lrm-start-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                    <input id="lrm-start-at" type="datetime-local" lang="en-GB" data-default-hour="8" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('End') }}</label>
                    <p id="lrm-end-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                    <input id="lrm-end-at" type="datetime-local" lang="en-GB" data-default-hour="17" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                </div>
            </div>

            {{-- Hours breakdown --}}
            <div id="lrm-breakdown" class="hidden px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg text-xs text-gray-700 dark:text-gray-300"></div>

            {{-- Total hours --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Total leave hours') }}</label>
                <p id="lrm-hours-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <input id="lrm-hours" type="number" step="0.25" min="0" placeholder="0"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
            </div>

            {{-- Balance preview --}}
            <div id="lrm-balance-preview" class="hidden p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg space-y-1">
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('Annual leave balance:') }}</span>
                    <span id="lrm-balance-current" class="font-semibold text-indigo-700 dark:text-indigo-300"></span>
                    <span id="lrm-balance-arrow" class="hidden text-gray-400">→</span>
                    <span id="lrm-balance-after" class="hidden font-semibold"></span>
                </div>
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('Leave days remaining:') }}</span>
                    <span id="lrm-balance-current-days" class="font-semibold text-indigo-700 dark:text-indigo-300"></span>
                    <span id="lrm-balance-arrow-days" class="hidden text-gray-400">→</span>
                    <span id="lrm-balance-after-days" class="hidden font-semibold"></span>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Reason') }}</label>
                <p id="lrm-desc-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1 whitespace-pre-wrap min-h-[1.5rem]"></p>
                <textarea id="lrm-description" rows="3"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"
                    placeholder="{{ __('Enter reason…') }}"></textarea>
            </div>

            {{-- Reject reason display --}}
            <div id="lrm-reject-display" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1">{{ __('Reject Reason') }}</p>
                <p id="lrm-reject-reason-text" class="text-sm text-red-700 dark:text-red-300"></p>
            </div>

            {{-- Inline reject input --}}
            <div id="lrm-reject-section" class="hidden space-y-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <label class="block text-xs font-semibold text-red-600 dark:text-red-400">{{ __('Reject Reason') }} <span>*</span></label>
                <textarea id="lrm-reject-input" rows="3" placeholder="{{ __('Enter rejection reason…') }}"
                    class="w-full border-red-300 dark:border-red-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"></textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="_lrmCancelReject()" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">{{ __('Cancel') }}</button>
                    <button onclick="_lrmConfirmReject(this)" class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">{{ __('Confirm Reject') }}</button>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div id="lrm-btn-area" class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-800"></div>
    </div>
</div>

<script>
(function () {
    var _LRM = {
        close:       '{{ __("Close") }}',
        cancel:      '{{ __("Cancel") }}',
        create:      '{{ __("Create") }}',
        save:        '{{ __("Save") }}',
        edit:        '{{ __("Edit") }}',
        approve:     '{{ __("Approve") }}',
        reject:      '{{ __("Reject") }}',
        titleCreate: '{{ __("Create Leave Request") }}',
        titleView:   '{{ __("Leave Request") }}',
        titleEdit:   '{{ __("Edit Leave Request") }}',
        pending:     '{{ __("Pending") }}',
        approved:    '{{ __("Approved") }}',
        rejected:    '{{ __("Rejected") }}',
        annual:      '{{ __("Annual leave") }}',
        sick:        '{{ __("Sick leave") }}',
        unpaid:      '{{ __("Unpaid leave") }}',
        errSave:     '{{ __("Error saving request.") }}',
        errConn:     '{{ __("Connection error.") }}',
        saving:      @js(__('Saving…')),
        processing:  @js(__('Processing…')),
    };
    var CSRF    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var AUTH_ID = {{ $lrmAuth?->id ?? 'null' }};
    var CAN_TEAM_OR_ALL = {{ $lrmCanTeamOrAll ? 'true' : 'false' }};
    var _LR_URL  = '{{ url("leave-requests") }}';
    var _USR_URL = '{{ url("users") }}';
    var HAS_SELECT = {{ ($lrmCanTeamOrAll && $lrmUsers->count() > 1) ? 'true' : 'false' }};

    var _mode    = 'create';
    var _id      = null;
    var _data    = null;
    var _balance = null;
    var _ts      = null;
    var _listenersBound = false;
    var _totalManual    = false;

    function $g(id) { return document.getElementById(id); }
    function show(el) {
        if (!el) return;
        if (el._flatpickr && el._flatpickr.altInput) { el._flatpickr.altInput.classList.remove('hidden'); }
        else { el.classList.remove('hidden'); }
    }
    function hide(el) {
        if (!el) return;
        if (el._flatpickr && el._flatpickr.altInput) { el._flatpickr.altInput.classList.add('hidden'); }
        else { el.classList.add('hidden'); }
    }
    function _fpSet(el, val) {
        if (el._flatpickr) { el._flatpickr.setDate(val, false); } else { el.value = val; }
    }

    function _hideBody() {
        ['lrm-status-banner','lrm-user-row','lrm-user-display',
         'lrm-breakdown','lrm-balance-preview','lrm-reject-display','lrm-reject-section',
         'lrm-type-display','lrm-type','lrm-start-display','lrm-start-at',
         'lrm-end-display','lrm-end-at','lrm-hours-display','lrm-hours',
         'lrm-desc-display','lrm-description','lrm-balance-arrow','lrm-balance-after',
         'lrm-balance-arrow-days','lrm-balance-after-days']
        .forEach(function(id) { hide($g(id)); });
    }

    // ── Public API ────────────────────────────────────────────────────

    window.openLeaveModal = function (id) {
        _mode = 'view'; _id = id; _data = null;
        _showOverlay();
        show($g('lrm-loading')); _hideBody();
        $g('lrm-btn-area').innerHTML = '';
        fetch(_LR_URL + '/' + id, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then(function(r){ return r.json(); })
            .then(function(d){ hide($g('lrm-loading')); _data = d; _populateView(d); })
            .catch(function(){ hide($g('lrm-loading')); $g('lrm-btn-area').innerHTML = _btn(_LRM.close,'closeLR()','secondary'); });
    };

    window.openLeaveCreate = function () {
        _mode = 'create'; _id = null; _data = null; _balance = null; _totalManual = false;
        _showOverlay();
        hide($g('lrm-loading')); _hideBody();
        $g('lrm-title').textContent = _LRM.titleCreate;
        _populateCreate();
    };

    window.closeLR = function () {
        hide($g('lrm-overlay'));
        _destroyTs();
        _listenersBound = false;
    };

    window._lrmSwitchToEdit = function () {
        _mode = 'edit';
        _populateEdit();
    };

    window._lrmSubmit = function (btn) {
        var userId = (_mode === 'edit' && _data) ? _data.leave.user_id : _tsVal();
        var payload = {
            user_id:         userId,
            type:            $g('lrm-type').value,
            start_at:        $g('lrm-start-at').value,
            end_at:          $g('lrm-end-at').value,
            hours:           _isMultiDay() ? '' : $g('lrm-hours').value,
            description:     $g('lrm-description').value,
        };
        _send(btn, _LRM.saving, {
            url:    _mode === 'create' ? _LR_URL : _LR_URL + '/' + _id,
            method: _mode === 'create' ? 'POST' : 'PUT',
            body:   payload,
        });
    };

    window._lrmApprove = function (btn) {
        _send(btn, _LRM.processing, { url: _LR_URL + '/' + _id + '/approve' });
    };

    function _send(btn, label, req) {
        window.sendRequestModal(btn, $g('lrm-overlay'), Object.assign({ label: label, errMsg: _LRM.errSave, errConn: _LRM.errConn }, req));
    }

    window._lrmShowReject = function () {
        show($g('lrm-reject-section'));
        $g('lrm-reject-input').value = '';
        $g('lrm-reject-input').focus();
    };
    window._lrmCancelReject = function () { hide($g('lrm-reject-section')); };
    window._lrmConfirmReject = function (btn) {
        var reason = $g('lrm-reject-input').value.trim();
        if (!reason) { $g('lrm-reject-input').focus(); return; }
        _send(btn, _LRM.processing, { url: _LR_URL + '/' + _id + '/reject', body: { reject_reason: reason } });
    };

    // ── Private ───────────────────────────────────────────────────────

    function _showOverlay() {
        var ov = $g('lrm-overlay');
        if (ov) ov.classList.remove('hidden');
    }

    function _populateCreate() {
        show($g('lrm-user-row'));
        show($g('lrm-type'));
        show($g('lrm-start-at')); show($g('lrm-end-at'));
        show($g('lrm-hours')); show($g('lrm-description'));
        $g('lrm-type').value = 'annual';
        _fpSet($g('lrm-start-at'), ''); _fpSet($g('lrm-end-at'), '');
        $g('lrm-hours').value = ''; $g('lrm-hours').disabled = false; $g('lrm-description').value = '';
        if (HAS_SELECT) {
            show($g('lrm-user-select'));
            _initTs();
        }
        _fetchBalance(AUTH_ID);
        _bindListeners();
        $g('lrm-btn-area').innerHTML =
            _btn(_LRM.cancel, 'closeLR()', 'secondary') +
            _btn(_LRM.create, '_lrmSubmit(this)', 'primary');
    }

    function _populateView(d) {
        var lr = d.leave;
        $g('lrm-title').textContent = _LRM.titleView;
        _balance = d.leave_balance;

        var banner = $g('lrm-status-banner');
        var labels = { pending: _LRM.pending, approved: _LRM.approved, rejected: _LRM.rejected };
        var cls = {
            pending:  'bg-yellow-50 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
            approved: 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300 border border-green-200 dark:border-green-700',
            rejected: 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300 border border-red-200 dark:border-red-700',
        };
        banner.className = 'rounded-lg px-4 py-2.5 text-sm font-medium ' + (cls[lr.status] || '');
        banner.textContent = (labels[lr.status] || lr.status) + (lr.approver_name ? ' · ' + lr.approver_name : '');
        show(banner);

        show($g('lrm-user-row'));
        $g('lrm-user-display').textContent = lr.user_name;
        show($g('lrm-user-display'));

        var typeLabels = { annual: _LRM.annual, sick: _LRM.sick, unpaid: _LRM.unpaid };
        $g('lrm-type-display').textContent = typeLabels[lr.type] || lr.type;
        show($g('lrm-type-display'));

        $g('lrm-start-display').textContent = lr.start_at_text;
        $g('lrm-end-display').textContent   = lr.end_at_text;
        show($g('lrm-start-display')); show($g('lrm-end-display'));

        $g('lrm-hours-display').textContent = lr.hours + 'h';
        show($g('lrm-hours-display'));

        if (lr.type === 'annual' && lr.status !== 'approved' && d.leave_balance !== null) {
            var curBal = parseFloat(d.leave_balance);
            $g('lrm-balance-current').textContent = curBal.toFixed(2).replace(/\.?0+$/,'') + 'h';
            $g('lrm-balance-current-days').textContent = (curBal/8).toFixed(2).replace(/\.?0+$/,'') + ' ngày';
            var after = (curBal - parseFloat(lr.hours));
            var afterStr = after.toFixed(2).replace(/\.?0+$/,'') + 'h';
            $g('lrm-balance-after').textContent = afterStr;
            $g('lrm-balance-after').className = 'hidden font-semibold ' + (after < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400');
            $g('lrm-balance-after-days').textContent = (after/8).toFixed(2).replace(/\.?0+$/,'') + ' ngày';
            $g('lrm-balance-after-days').className = 'hidden font-semibold ' + (after < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400');
            show($g('lrm-balance-arrow')); show($g('lrm-balance-after'));
            show($g('lrm-balance-arrow-days')); show($g('lrm-balance-after-days'));
            show($g('lrm-balance-preview'));
        }

        $g('lrm-desc-display').textContent = lr.description || '—';
        show($g('lrm-desc-display'));

        if (lr.status === 'rejected' && lr.reject_reason) {
            $g('lrm-reject-reason-text').textContent = lr.reject_reason;
            show($g('lrm-reject-display'));
        }

        var btns = '';
        if (d.can_edit)    btns += _btn(_LRM.edit, '_lrmSwitchToEdit()', 'secondary');
        if (d.can_approve) btns += _btn(_LRM.approve, '_lrmApprove(this)', 'success') + _btn(_LRM.reject, '_lrmShowReject()', 'danger');
        btns += _btn(_LRM.close, 'closeLR()', 'secondary');
        $g('lrm-btn-area').innerHTML = btns;
    }

    function _populateEdit() {
        var lr = _data.leave;
        $g('lrm-title').textContent = _LRM.titleEdit;

        hide($g('lrm-type-display'));    show($g('lrm-type'));
        hide($g('lrm-start-display'));   show($g('lrm-start-at'));
        hide($g('lrm-end-display'));     show($g('lrm-end-at'));
        hide($g('lrm-hours-display'));   show($g('lrm-hours'));
        hide($g('lrm-desc-display'));    show($g('lrm-description'));
        hide($g('lrm-balance-preview'));

        $g('lrm-type').value     = lr.type;
        _fpSet($g('lrm-start-at'), lr.start_at_input);
        _fpSet($g('lrm-end-at'), lr.end_at_input);
        $g('lrm-description').value = lr.description || '';
        _totalManual = false;

        _fetchBalance(lr.user_id);
        _bindListeners();
        _lrmCalc();
        // Keep a hand-edited single-day total instead of replacing it with the calculated one
        if (!_isMultiDay()) {
            _totalManual = Math.abs((parseFloat($g('lrm-hours').value) || 0) - parseFloat(lr.hours)) > 0.001;
            $g('lrm-hours').value = lr.hours;
            _updateBalancePreview();
        }

        $g('lrm-btn-area').innerHTML =
            _btn(_LRM.cancel, 'closeLR()', 'secondary') +
            _btn(_LRM.save, '_lrmSubmit(this)', 'primary');
    }

    // ── Balance ───────────────────────────────────────────────────────

    function _fetchBalance(userId) {
        if (!userId) return;
        fetch(_USR_URL + '/' + userId + '/request-info', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then(function(r){ return r.json(); })
            .then(function(d){ _balance = d.leave_balance; _updateBalancePreview(); });
    }

    function _updateBalancePreview() {
        var type  = $g('lrm-type') && !$g('lrm-type').classList.contains('hidden') ? $g('lrm-type').value : null;
        var hours = parseFloat($g('lrm-hours')?.value) || 0;
        if (type !== 'annual' || _balance === null) { hide($g('lrm-balance-preview')); return; }
        var curBal = parseFloat(_balance);
        $g('lrm-balance-current').textContent = curBal.toFixed(2).replace(/\.?0+$/,'') + 'h';
        $g('lrm-balance-current-days').textContent = (curBal/8).toFixed(2).replace(/\.?0+$/,'') + ' ngày';
        if (hours > 0) {
            var after = curBal - hours;
            $g('lrm-balance-after').textContent = after.toFixed(2).replace(/\.?0+$/,'') + 'h';
            $g('lrm-balance-after').className = 'font-semibold ' + (after < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400');
            $g('lrm-balance-after-days').textContent = (after/8).toFixed(2).replace(/\.?0+$/,'') + ' ngày';
            $g('lrm-balance-after-days').className = 'font-semibold ' + (after < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400');
            show($g('lrm-balance-arrow')); show($g('lrm-balance-after'));
            show($g('lrm-balance-arrow-days')); show($g('lrm-balance-after-days'));
        } else {
            hide($g('lrm-balance-arrow')); hide($g('lrm-balance-after'));
            hide($g('lrm-balance-arrow-days')); hide($g('lrm-balance-after-days'));
        }
        show($g('lrm-balance-preview'));
    }

    // ── Date calculation ──────────────────────────────────────────────

    function _bindListeners() {
        if (_listenersBound) return;
        _listenersBound = true;
        var s = $g('lrm-start-at'); var e = $g('lrm-end-at');
        var h = $g('lrm-hours'); var t = $g('lrm-type');
        s?.addEventListener('change', function(){
            // Convenience: an empty End defaults to the same day at the end of the work day
            if (s.value && !e.value) _fpSet(e, s.value.slice(0, 10) + 'T' + window.WorkHoursConfig.dayEnd);
            _totalManual = false; _lrmCalc();
        });
        e?.addEventListener('change', function(){ _totalManual = false; _lrmCalc(); });
        h?.addEventListener('input',  function(){ _totalManual = true; _updateBalancePreview(); });
        t?.addEventListener('change', function(){ _updateBalancePreview(); });
    }

    function _range() {
        var s = $g('lrm-start-at').value, e = $g('lrm-end-at').value;
        return s && e ? { start: new Date(s), end: new Date(e) } : null;
    }

    function _isMultiDay() {
        var r = _range();
        return !!r && r.start.toDateString() !== r.end.toDateString();
    }

    // Same rules as the WFH form (window.WorkHours); multi-day totals are calculated, a single day can be edited.
    function _lrmCalc() {
        var r = _range(), h = $g('lrm-hours'), bd = $g('lrm-breakdown');
        hide(bd);
        var b = r ? window.WorkHours.breakdown(r.start, r.end) : null;
        if (!b) { if (!_totalManual) h.value = ''; h.disabled = false; _updateBalancePreview(); return; }

        if (!b.multiDay) {
            h.disabled = false;
            if (!_totalManual) h.value = b.total.toFixed(2).replace(/\.?0+$/, '');
        } else {
            _totalManual = false; h.disabled = true;
            h.value = b.total.toFixed(2).replace(/\.?0+$/, '');
            bd.innerHTML = window.WorkHours.html(b, r.start, r.end, 'blue');
            show(bd);
        }
        _updateBalancePreview();
    }

    // ── TomSelect ─────────────────────────────────────────────────────

    function _initTs() {
        if (_ts || !HAS_SELECT) return;
        var sel = $g('lrm-user-select');
        if (!sel || sel.tagName !== 'SELECT') return;
        _ts = new TomSelect(sel, {
            allowEmptyOption: true, maxOptions: 300,
            onChange: function(v){ _fetchBalance(v); }
        });
        _ts.setValue(String(AUTH_ID), true);
    }

    function _destroyTs() {
        if (_ts) { try { _ts.destroy(); } catch(e){} _ts = null; }
    }

    function _tsVal() {
        if (_ts) return _ts.getValue();
        var sel = $g('lrm-user-select');
        return sel ? sel.value || AUTH_ID : AUTH_ID;
    }

    // ── Button builder ────────────────────────────────────────────────

    function _btn(label, fn, type) {
        var base = 'px-4 py-2 text-sm rounded-lg font-medium transition ';
        var cls = {
            primary:   base + 'bg-indigo-600 hover:bg-indigo-700 text-white',
            secondary: base + 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
            success:   base + 'bg-green-600 hover:bg-green-700 text-white',
            danger:    base + 'bg-red-600 hover:bg-red-700 text-white',
        }[type] || base;
        return '<button onclick="'+fn+'" class="'+cls+'">'+label+'</button>';
    }

    // ── Overlay backdrop click ────────────────────────────────────────

    var _ov = $g('lrm-overlay');
    if (_ov) _ov.addEventListener('click', function(e){ if (e.target===this) closeLR(); });
})();
</script>
