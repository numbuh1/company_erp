@php
    $wfmAuth         = auth()->user();
    $wfmCanCreate    = $wfmAuth?->canAny(['edit own wfh', 'edit team wfh', 'edit all wfh']);
    $wfmCanTeamOrAll = $wfmAuth?->canAny(['edit team wfh', 'edit all wfh']);

    if ($wfmCanCreate && $wfmCanTeamOrAll) {
        $wfmUsers = $wfmAuth->can('edit all wfh')
            ? \App\Models\User::orderBy('name')->get(['id', 'name', 'position'])
            : \App\Models\User::whereIn('id', $wfmAuth->teamMembers()->pluck('id')->push($wfmAuth->id))->orderBy('name')->get(['id', 'name', 'position']);
    } else {
        $wfmUsers = collect([$wfmAuth])->filter();
    }
    $wfmHasSelect = $wfmCanTeamOrAll && $wfmUsers->count() > 1;
@endphp

<div id="wfm-overlay" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 hidden pb-[4.5rem] sm:pb-0">
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full sm:max-w-lg max-h-[90vh] overflow-y-auto mx-2 sm:mx-0">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 id="wfm-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('WFH Request') }}</h3>
            <button onclick="closeWfhModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            <div id="wfm-loading" class="hidden py-10 text-center text-gray-400 text-sm">{{ __('Loading…') }}</div>

            <div id="wfm-status-banner" class="hidden rounded-lg px-4 py-2.5 text-sm font-medium"></div>

            {{-- User --}}
            @if($wfmHasSelect)
                <div id="wfm-user-row" class="hidden">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('User') }}</label>
                    <p id="wfm-user-display" class="hidden text-sm font-medium text-gray-900 dark:text-gray-100 py-1"></p>
                    <select id="wfm-user-select" class="hidden w-full">
                        <option value="">{{ __('— Select user —') }}</option>
                        @foreach($wfmUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div id="wfm-user-row" class="hidden">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('User') }}</label>
                    <p id="wfm-user-display" class="hidden text-sm font-medium text-gray-900 dark:text-gray-100 py-1"></p>
                </div>
                <input type="hidden" id="wfm-user-select" value="{{ $wfmAuth?->id }}">
            @endif

            {{-- Start / End (same as the leave form) --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Start') }}</label>
                    <p id="wfm-start-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                    <input id="wfm-start-at" type="datetime-local" lang="en-GB" data-default-hour="8" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('End') }}</label>
                    <p id="wfm-end-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                    <input id="wfm-end-at" type="datetime-local" lang="en-GB" data-default-hour="17" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                </div>
            </div>

            {{-- Hours --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Hours') }}</label>
                <p id="wfm-hours-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <input id="wfm-hours" type="number" step="0.25" min="0.25" placeholder="0"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                <p id="wfm-hours-note" class="hidden mt-1 text-xs text-gray-400">{{ __('Calculated from the time range minus the lunch break, can be changed.') }}</p>
            </div>

            {{-- Multi-day breakdown --}}
            <div id="wfm-breakdown" class="hidden px-4 py-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-lg text-xs text-gray-700 dark:text-gray-300"></div>

            {{-- WFH month / year totals --}}
            <div id="wfm-preview" class="hidden p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-lg space-y-1">
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('WFH this month:') }}</span>
                    <span id="wfm-month-total" class="font-semibold text-sky-700 dark:text-sky-300"></span>
                    <span id="wfm-month-arrow" class="hidden text-gray-400">→</span>
                    <span id="wfm-month-after" class="hidden font-semibold text-green-600 dark:text-green-400"></span>
                </div>
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('WFH this year:') }}</span>
                    <span id="wfm-year-total" class="font-semibold text-sky-700 dark:text-sky-300"></span>
                    <span id="wfm-year-arrow" class="hidden text-gray-400">→</span>
                    <span id="wfm-year-after" class="hidden font-semibold text-green-600 dark:text-green-400"></span>
                </div>
            </div>

            {{-- Reason --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('Reason') }}</label>
                <p id="wfm-desc-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1 whitespace-pre-wrap min-h-[1.5rem]"></p>
                <textarea id="wfm-description" rows="3"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"
                    placeholder="{{ __('Enter reason…') }}"></textarea>
            </div>

            {{-- Reject reason display --}}
            <div id="wfm-reject-display" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1">{{ __('Reject Reason') }}</p>
                <p id="wfm-reject-reason-text" class="text-sm text-red-700 dark:text-red-300"></p>
            </div>

            {{-- Inline reject input --}}
            <div id="wfm-reject-section" class="hidden space-y-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <label class="block text-xs font-semibold text-red-600 dark:text-red-400">{{ __('Reject Reason') }} <span>*</span></label>
                <textarea id="wfm-reject-input" rows="3" placeholder="{{ __('Enter rejection reason…') }}"
                    class="w-full border-red-300 dark:border-red-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"></textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="_wfmCancelReject()" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">{{ __('Cancel') }}</button>
                    <button onclick="_wfmConfirmReject(this)" class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">{{ __('Confirm Reject') }}</button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div id="wfm-btn-area" class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-800"></div>
    </div>
</div>

<script>
(function () {
    var _L = @js([
        'close' => __('Close'), 'cancel' => __('Cancel'), 'create' => __('Create'), 'save' => __('Save'),
        'edit' => __('Edit'), 'approve' => __('Approve'), 'reject' => __('Reject'),
        'titleCreate' => __('Create WFH Request'), 'titleView' => __('WFH Request'), 'titleEdit' => __('Edit WFH Request'),
        'pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected'),
        'errSave' => __('Error saving.'), 'errConn' => __('Connection error.'),
        'saving' => __('Saving…'), 'processing' => __('Processing…'),
        'bdTitle' => __('Expected total hours'), 'bdDay' => __('Day'),
        'bdWorkDays' => __('working days'), 'bdExcl' => __('8h/day, excl. weekends & holidays'),
    ]);
    var CSRF        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var AUTH_ID     = @js($wfmAuth?->id);
    var AUTH_NAME   = @js($wfmAuth?->name);
    var HAS_SEL     = @js($wfmHasSelect);
    var WFH_URL     = @js(url('wfh-requests'));
    var USR_URL     = @js(url('users'));
    var LUNCH_START = @js(\App\Models\AppSetting::get('lunch_break_start', '12:00'));
    var LUNCH_END   = @js(\App\Models\AppSetting::get('lunch_break_end', '13:00'));
    var DAY_START   = @js(\App\Models\WfhRequest::DAY_START);
    var DAY_END     = @js(\App\Models\WfhRequest::DAY_END);
    var HOLIDAYS    = @js(\App\Models\PublicHoliday::getHolidayDates(now()->subYear(), now()->addYears(2)));

    var _mode = 'create', _id = null, _data = null;
    var _monthTotal = 0, _yearTotal = 0;
    var _tsUser = null, _manualH = false, _bound = false;

    function $g(id){ return document.getElementById(id); }
    function show(el){ if(!el) return; (el._flatpickr && el._flatpickr.altInput ? el._flatpickr.altInput : el).classList.remove('hidden'); }
    function hide(el){ if(!el) return; (el._flatpickr && el._flatpickr.altInput ? el._flatpickr.altInput : el).classList.add('hidden'); }
    function _fpSet(el, val){ if(el._flatpickr){ el._flatpickr.setDate(val, false); } else { el.value = val; } }
    function _fmtH(h){ return (Math.round(h * 100) / 100) + 'h'; }

    function _send(btn, label, req){
        window.sendRequestModal(btn, $g('wfm-overlay'), Object.assign({ label: label, errMsg: _L.errSave, errConn: _L.errConn }, req));
    }

    function _hideBody(){
        ['wfm-status-banner','wfm-user-display','wfm-user-select','wfm-start-display','wfm-start-at',
         'wfm-end-display','wfm-end-at','wfm-hours-display','wfm-hours','wfm-hours-note','wfm-breakdown',
         'wfm-preview','wfm-month-arrow','wfm-month-after','wfm-year-arrow','wfm-year-after',
         'wfm-desc-display','wfm-description','wfm-reject-display','wfm-reject-section']
        .forEach(function(id){ var el = $g(id); if (el && el.type !== 'hidden') hide(el); });
        show($g('wfm-user-row'));
    }

    // ── Public API ────────────────────────────────────────────────────

    window.openWfhModal = function (id) {
        _mode = 'view'; _id = id; _data = null;
        _open(); show($g('wfm-loading')); _hideBody();
        $g('wfm-btn-area').innerHTML = '';
        fetch(WFH_URL + '/' + id, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then(function(r){ return r.json(); })
            .then(function(d){ hide($g('wfm-loading')); _data = d; _populateView(d); })
            .catch(function(){ hide($g('wfm-loading')); $g('wfm-btn-area').innerHTML = _btn(_L.close, 'closeWfhModal()', 'secondary'); });
    };

    window.openWfhCreate = function () {
        _mode = 'create'; _id = null; _data = null; _manualH = false;
        _open(); hide($g('wfm-loading')); _hideBody();
        $g('wfm-title').textContent = _L.titleCreate;

        if (HAS_SEL) { show($g('wfm-user-select')); _initUserTs(); }
        else { $g('wfm-user-display').textContent = AUTH_NAME; show($g('wfm-user-display')); }

        show($g('wfm-start-at')); show($g('wfm-end-at')); show($g('wfm-hours')); show($g('wfm-hours-note')); show($g('wfm-description'));
        _fpSet($g('wfm-start-at'), ''); _fpSet($g('wfm-end-at'), '');
        $g('wfm-hours').value = ''; $g('wfm-hours').disabled = false; $g('wfm-description').value = '';
        _fetchTotals(AUTH_ID);
        _bindListeners();
        $g('wfm-btn-area').innerHTML = _btn(_L.cancel, 'closeWfhModal()', 'secondary') + _btn(_L.create, '_wfmSubmit(this)', 'primary');
    };

    window.closeWfhModal = function () {
        hide($g('wfm-overlay'));
        if (_tsUser) { try { _tsUser.destroy(); } catch (e) {} _tsUser = null; }
    };

    window._wfmSwitchToEdit = function () { _mode = 'edit'; _populateEdit(); };

    window._wfmSubmit = function (btn) {
        var payload = {
            user_id:     (_mode === 'edit' && _data) ? _data.wfh.user_id : _userVal(),
            start_at:    $g('wfm-start-at').value,
            end_at:      $g('wfm-end-at').value,
            hours:       (_manualH && !_isMultiDay()) ? $g('wfm-hours').value : '',
            description: $g('wfm-description').value,
        };
        _send(btn, _L.saving, {
            url:    _mode === 'create' ? WFH_URL : WFH_URL + '/' + _id,
            method: _mode === 'create' ? 'POST' : 'PUT',
            body:   payload,
        });
    };

    window._wfmApprove = function (btn) {
        _send(btn, _L.processing, { url: WFH_URL + '/' + _id + '/approve' });
    };

    window._wfmShowReject   = function () { show($g('wfm-reject-section')); $g('wfm-reject-input').value = ''; $g('wfm-reject-input').focus(); };
    window._wfmCancelReject = function () { hide($g('wfm-reject-section')); };
    window._wfmConfirmReject = function (btn) {
        var reason = $g('wfm-reject-input').value.trim();
        if (!reason) { $g('wfm-reject-input').focus(); return; }
        _send(btn, _L.processing, { url: WFH_URL + '/' + _id + '/reject', body: { reject_reason: reason } });
    };

    // ── Views ─────────────────────────────────────────────────────────

    function _open(){ $g('wfm-overlay').classList.remove('hidden'); }

    function _populateView(d){
        var w = d.wfh;
        $g('wfm-title').textContent = _L.titleView;
        _monthTotal = d.wfh_month_total || 0;
        _yearTotal  = d.wfh_year_total || 0;

        var banner = $g('wfm-status-banner');
        var cls = {
            pending:  'bg-yellow-50 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
            approved: 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300 border border-green-200 dark:border-green-700',
            rejected: 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300 border border-red-200 dark:border-red-700',
        };
        banner.className = 'rounded-lg px-4 py-2.5 text-sm font-medium ' + (cls[w.status] || '');
        banner.textContent = (_L[w.status] || w.status) + (w.approver_name ? ' · ' + w.approver_name : '');
        show(banner);

        $g('wfm-user-display').textContent = w.user_name; show($g('wfm-user-display'));
        $g('wfm-start-display').textContent = w.start_at_text; show($g('wfm-start-display'));
        $g('wfm-end-display').textContent   = w.end_at_text;   show($g('wfm-end-display'));
        $g('wfm-hours-display').textContent = _fmtH(w.hours); show($g('wfm-hours-display'));
        $g('wfm-desc-display').textContent = w.description || '—'; show($g('wfm-desc-display'));

        _showTotals(w.status === 'approved' ? 0 : parseFloat(w.hours));

        if (w.status === 'rejected' && w.reject_reason) {
            $g('wfm-reject-reason-text').textContent = w.reject_reason; show($g('wfm-reject-display'));
        }

        var btns = '';
        if (d.can_edit)    btns += _btn(_L.edit, '_wfmSwitchToEdit()', 'secondary');
        if (d.can_approve) btns += _btn(_L.approve, '_wfmApprove(this)', 'success') + _btn(_L.reject, '_wfmShowReject()', 'danger');
        btns += _btn(_L.close, 'closeWfhModal()', 'secondary');
        $g('wfm-btn-area').innerHTML = btns;
    }

    function _populateEdit(){
        var w = _data.wfh;
        $g('wfm-title').textContent = _L.titleEdit;
        hide($g('wfm-status-banner'));
        hide($g('wfm-start-display')); show($g('wfm-start-at'));
        hide($g('wfm-end-display'));   show($g('wfm-end-at'));
        hide($g('wfm-hours-display')); show($g('wfm-hours')); show($g('wfm-hours-note'));
        hide($g('wfm-desc-display'));  show($g('wfm-description'));

        _fpSet($g('wfm-start-at'), w.start_at_input);
        _fpSet($g('wfm-end-at'), w.end_at_input);
        $g('wfm-description').value = w.description || '';
        _manualH = false;
        _bindListeners();
        _calcHours();
        if (!w.is_multi_day) {
            _manualH = Math.abs((parseFloat($g('wfm-hours').value) || 0) - parseFloat(w.hours)) > 0.001;
            $g('wfm-hours').value = w.hours;
            _showTotals(parseFloat(w.hours));
        }

        $g('wfm-btn-area').innerHTML = _btn(_L.cancel, 'closeWfhModal()', 'secondary') + _btn(_L.save, '_wfmSubmit(this)', 'primary');
    }

    // ── Totals preview ────────────────────────────────────────────────

    function _fetchTotals(userId){
        if (!userId) return;
        fetch(USR_URL + '/' + userId + '/request-info', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then(function(r){ return r.json(); })
            .then(function(d){
                _monthTotal = parseFloat(d.wfh_month_total) || 0;
                _yearTotal  = parseFloat(d.wfh_year_total) || 0;
                _showTotals(parseFloat($g('wfm-hours').value) || 0);
            });
    }

    // Shows approved totals, plus "→ total after this request" when it adds hours.
    function _showTotals(adding){
        $g('wfm-month-total').textContent = _fmtH(_monthTotal);
        $g('wfm-year-total').textContent  = _fmtH(_yearTotal);
        [['wfm-month-arrow', 'wfm-month-after', _monthTotal], ['wfm-year-arrow', 'wfm-year-after', _yearTotal]].forEach(function(p){
            if (adding > 0) { $g(p[1]).textContent = _fmtH(p[2] + adding); show($g(p[0])); show($g(p[1])); }
            else { hide($g(p[0])); hide($g(p[1])); }
        });
        show($g('wfm-preview'));
    }

    // ── Hours calculation ─────────────────────────────────────────────

    function _toMins(t){ var p = String(t).split(':').map(Number); return p[0] * 60 + (p[1] || 0); }
    function _pad(n){ return String(n).padStart(2, '0'); }
    function _iso(dt){ return dt.getFullYear() + '-' + _pad(dt.getMonth() + 1) + '-' + _pad(dt.getDate()); }
    function _fd(dt){ return _pad(dt.getDate()) + '/' + _pad(dt.getMonth() + 1); }
    function _mins(dt){ return dt.getHours() * 60 + dt.getMinutes(); }

    // Net hours between two minute marks on one day, minus the lunch overlap (mirrors WfhRequest::breakdown)
    function _net(from, to){
        var lunch = Math.max(0, Math.min(to, _toMins(LUNCH_END)) - Math.max(from, _toMins(LUNCH_START)));
        return Math.max(0, to - from - lunch) / 60;
    }

    function _range(){
        var s = $g('wfm-start-at').value, e = $g('wfm-end-at').value;
        if (!s || !e) return null;
        var start = new Date(s), end = new Date(e);
        return end > start ? { start: start, end: end } : null;
    }

    function _isMultiDay(){ var r = _range(); return !!r && _iso(r.start) !== _iso(r.end); }

    function _calcHours(){
        var r = _range(), h = $g('wfm-hours'), bd = $g('wfm-breakdown');
        hide(bd);
        if (!r) { if (!_manualH) h.value = ''; h.disabled = false; _showTotals(0); return; }

        if (!_isMultiDay()) {
            h.disabled = false;
            if (!_manualH) h.value = _net(_mins(r.start), _mins(r.end)).toFixed(2).replace(/\.?0+$/, '');
            _showTotals(parseFloat(h.value) || 0);
            return;
        }

        // Multi-day: first day to DAY_END, full days in between, last day from DAY_START; weekends/holidays count 0
        var workDay = function(dt){ var dow = dt.getDay(); return dow !== 0 && dow !== 6 && HOLIDAYS.indexOf(_iso(dt)) === -1; };
        var first = workDay(r.start) ? _net(_mins(r.start), _toMins(DAY_END)) : 0;
        var last  = workDay(r.end) ? _net(_toMins(DAY_START), _mins(r.end)) : 0;
        var mid = 0, d = new Date(r.start); d.setHours(12, 0, 0, 0); d.setDate(d.getDate() + 1);
        var stop = new Date(r.end); stop.setHours(0, 0, 0, 0);
        while (d < stop) { if (workDay(d)) mid++; d.setDate(d.getDate() + 1); }
        var midH  = mid * _net(_toMins(DAY_START), _toMins(DAY_END));
        var total = first + midH + last;

        _manualH = false; h.disabled = true;
        h.value = total.toFixed(2).replace(/\.?0+$/, '');
        _showTotals(total);

        var esc = function(t){ var x = document.createElement('span'); x.textContent = t; return x.innerHTML; };
        var dot = '<span class="text-sky-400 mr-1">•</span>';
        var html = '<p class="font-semibold text-sky-700 dark:text-sky-400 mb-1.5">' + esc(_L.bdTitle) + '</p><div class="space-y-0.5">';
        html += dot + '<strong>' + esc(_L.bdDay) + ' ' + _fd(r.start) + '</strong>: ' + _fmtH(first) + '<br>';
        if (mid > 0) html += dot + '<strong>' + mid + ' ' + esc(_L.bdWorkDays) + '</strong>: ' + _fmtH(midH) + ' <span class="text-gray-400">(' + esc(_L.bdExcl) + ')</span><br>';
        html += dot + '<strong>' + esc(_L.bdDay) + ' ' + _fd(r.end) + '</strong>: ' + _fmtH(last) + '</div>';
        bd.innerHTML = html; show(bd);
    }

    function _bindListeners(){
        if (_bound) return; _bound = true;
        $g('wfm-start-at').addEventListener('change', function(){
            // Convenience: an empty End defaults to the same day at DAY_END
            if (this.value && !$g('wfm-end-at').value) _fpSet($g('wfm-end-at'), this.value.slice(0, 10) + 'T' + DAY_END);
            _calcHours();
        });
        $g('wfm-end-at').addEventListener('change', _calcHours);
        $g('wfm-hours').addEventListener('input', function(){ _manualH = true; _showTotals(parseFloat(this.value) || 0); });
    }

    function _initUserTs(){
        if (_tsUser) return;
        var sel = $g('wfm-user-select');
        if (!sel || sel.tagName !== 'SELECT') return;
        _tsUser = new TomSelect(sel, { allowEmptyOption: true, maxOptions: 300, onChange: function(v){ _fetchTotals(v); } });
        _tsUser.setValue(String(AUTH_ID), true);
    }

    function _userVal(){
        if (_tsUser) return _tsUser.getValue() || AUTH_ID;
        var sel = $g('wfm-user-select'); return sel ? (sel.value || AUTH_ID) : AUTH_ID;
    }

    function _btn(label, fn, type){
        var base = 'px-4 py-2 text-sm rounded-lg font-medium transition ';
        var cls = { primary:   base + 'bg-indigo-600 hover:bg-indigo-700 text-white',
                    secondary: base + 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                    success:   base + 'bg-green-600 hover:bg-green-700 text-white',
                    danger:    base + 'bg-red-600 hover:bg-red-700 text-white' }[type] || base;
        var b = document.createElement('button');
        b.setAttribute('onclick', fn); b.className = cls; b.textContent = label;
        return b.outerHTML;
    }

    $g('wfm-overlay').addEventListener('click', function(e){ if (e.target === this) closeWfhModal(); });
})();
</script>
