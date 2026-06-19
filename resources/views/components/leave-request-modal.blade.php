@php
    use App\Models\AppSetting;
    use App\Models\PublicHoliday;
    use Carbon\Carbon;

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

    $lrmHolidays   = PublicHoliday::getHolidayDates(Carbon::now()->subYear(), Carbon::now()->addYears(2));
    $lrmLunchStart = AppSetting::get('lunch_break_start', '12:00');
    $lrmLunchEnd   = AppSetting::get('lunch_break_end', '13:00');
@endphp

<div id="lrm-overlay" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 hidden">
    <div id="lrm-box" class="relative bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg max-h-[90vh] overflow-y-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 id="lrm-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Yêu cầu nghỉ phép</h3>
            <button onclick="closeLR()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            <div id="lrm-loading" class="hidden py-10 text-center text-gray-400 text-sm">Đang tải…</div>

            {{-- Status banner --}}
            <div id="lrm-status-banner" class="hidden rounded-lg px-4 py-2.5 text-sm font-medium"></div>

            {{-- User --}}
            <div id="lrm-user-row" class="hidden">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Người dùng</label>
                <p id="lrm-user-display" class="hidden text-sm font-medium text-gray-900 dark:text-gray-100 py-1"></p>
                @if($lrmCanTeamOrAll)
                    <select id="lrm-user-select" class="hidden w-full">
                        <option value="">— Chọn người dùng —</option>
                        @foreach($lrmUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" id="lrm-user-select" value="{{ $lrmAuth?->id }}">
                @endif
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Loại nghỉ</label>
                <p id="lrm-type-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <select id="lrm-type" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                    <option value="annual">Nghỉ phép năm</option>
                    <option value="sick">Nghỉ ốm</option>
                    <option value="unpaid">Nghỉ không lương</option>
                </select>
            </div>

            {{-- Start / End --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Bắt đầu</label>
                    <p id="lrm-start-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                    <input id="lrm-start-at" type="datetime-local" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Kết thúc</label>
                    <p id="lrm-end-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                    <input id="lrm-end-at" type="datetime-local" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                </div>
            </div>

            {{-- Partial-day section --}}
            <div id="lrm-partial-section" class="hidden p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg space-y-2">
                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide">⚡ Chỉnh giờ nghỉ từng ngày</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Ngày đầu <span id="lrm-start-label" class="text-gray-400"></span></label>
                        <div class="flex items-center gap-1.5">
                            <input type="number" step="0.25" min="0" max="24" id="lrm-start-day" placeholder="0"
                                class="w-20 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-1.5">
                            <span class="text-xs text-gray-500">giờ</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Ngày cuối <span id="lrm-end-label" class="text-gray-400"></span></label>
                        <div class="flex items-center gap-1.5">
                            <input type="number" step="0.25" min="0" max="24" id="lrm-end-day" placeholder="0"
                                class="w-20 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-1.5">
                            <span class="text-xs text-gray-500">giờ</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hours breakdown --}}
            <div id="lrm-breakdown" class="hidden px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg text-xs text-gray-700 dark:text-gray-300"></div>

            {{-- Total hours --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tổng giờ nghỉ</label>
                <p id="lrm-hours-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <input id="lrm-hours" type="number" step="0.25" min="0" placeholder="0"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
            </div>

            {{-- Balance preview --}}
            <div id="lrm-balance-preview" class="hidden p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg">
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Số dư phép:</span>
                    <span id="lrm-balance-current" class="font-semibold text-indigo-700 dark:text-indigo-300"></span>
                    <span id="lrm-balance-arrow" class="hidden text-gray-400">→</span>
                    <span id="lrm-balance-after" class="hidden font-semibold"></span>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Lý do</label>
                <p id="lrm-desc-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1 whitespace-pre-wrap min-h-[1.5rem]"></p>
                <textarea id="lrm-description" rows="3"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"
                    placeholder="Nhập lý do…"></textarea>
            </div>

            {{-- Reject reason display --}}
            <div id="lrm-reject-display" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1">Lý do từ chối</p>
                <p id="lrm-reject-reason-text" class="text-sm text-red-700 dark:text-red-300"></p>
            </div>

            {{-- Inline reject input --}}
            <div id="lrm-reject-section" class="hidden space-y-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <label class="block text-xs font-semibold text-red-600 dark:text-red-400">Lý do từ chối <span>*</span></label>
                <textarea id="lrm-reject-input" rows="3" placeholder="Nhập lý do từ chối…"
                    class="w-full border-red-300 dark:border-red-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"></textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="_lrmCancelReject()" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Hủy</button>
                    <button onclick="_lrmConfirmReject()" class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">Xác nhận từ chối</button>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div id="lrm-btn-area" class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-800"></div>
    </div>
</div>

<script>
(function () {
    var CSRF    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var AUTH_ID = {{ $lrmAuth?->id ?? 'null' }};
    var CAN_TEAM_OR_ALL = {{ $lrmCanTeamOrAll ? 'true' : 'false' }};
    var _LR_URL  = '{{ url("leave-requests") }}';
    var _USR_URL = '{{ url("users") }}';
    var HAS_SELECT = {{ ($lrmCanTeamOrAll && $lrmUsers->count() > 1) ? 'true' : 'false' }};
    var HOLIDAYS    = {!! json_encode($lrmHolidays, JSON_HEX_TAG) !!};
    var LUNCH_S     = '{{ $lrmLunchStart }}';
    var LUNCH_E     = '{{ $lrmLunchEnd }}';

    var _mode    = 'create';
    var _id      = null;
    var _data    = null;
    var _balance = null;
    var _ts      = null;
    var _listenersBound = false;
    var _totalManual    = false;

    function $g(id) { return document.getElementById(id); }
    function show(el) { if (el) el.classList.remove('hidden'); }
    function hide(el) { if (el) el.classList.add('hidden'); }

    function _hideBody() {
        ['lrm-status-banner','lrm-user-row','lrm-user-display','lrm-partial-section',
         'lrm-breakdown','lrm-balance-preview','lrm-reject-display','lrm-reject-section',
         'lrm-type-display','lrm-type','lrm-start-display','lrm-start-at',
         'lrm-end-display','lrm-end-at','lrm-hours-display','lrm-hours',
         'lrm-desc-display','lrm-description','lrm-balance-arrow','lrm-balance-after']
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
            .catch(function(){ hide($g('lrm-loading')); $g('lrm-btn-area').innerHTML = _btn('Đóng','closeLR()','secondary'); });
    };

    window.openLeaveCreate = function () {
        _mode = 'create'; _id = null; _data = null; _balance = null; _totalManual = false;
        _showOverlay();
        hide($g('lrm-loading')); _hideBody();
        $g('lrm-title').textContent = 'Tạo yêu cầu nghỉ phép';
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

    window._lrmSubmit = function () {
        var userId = (_mode === 'edit' && _data) ? _data.leave.user_id : _tsVal();
        var payload = {
            user_id:         userId,
            type:            $g('lrm-type').value,
            start_at:        $g('lrm-start-at').value,
            end_at:          $g('lrm-end-at').value,
            start_day_hours: $g('lrm-start-day').value || null,
            end_day_hours:   $g('lrm-end-day').value   || null,
            hours:           $g('lrm-hours').value,
            description:     $g('lrm-description').value,
        };
        var url    = _mode === 'create' ? _LR_URL : _LR_URL + '/' + _id;
        var method = _mode === 'create' ? 'POST'                     : 'PUT';
        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload),
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ if (d.success) { closeLR(); location.reload(); } else { alert(d.message || 'Lỗi khi lưu yêu cầu.'); } })
        .catch(function(){ alert('Lỗi kết nối.'); });
    };

    window._lrmApprove = function () {
        fetch(_LR_URL + '/' + _id + '/approve', {
            method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ if (d.success) { closeLR(); location.reload(); } });
    };

    window._lrmShowReject = function () {
        show($g('lrm-reject-section'));
        $g('lrm-reject-input').value = '';
        $g('lrm-reject-input').focus();
    };
    window._lrmCancelReject = function () { hide($g('lrm-reject-section')); };
    window._lrmConfirmReject = function () {
        var reason = $g('lrm-reject-input').value.trim();
        if (!reason) { $g('lrm-reject-input').focus(); return; }
        fetch(_LR_URL + '/' + _id + '/reject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ reject_reason: reason }),
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ if (d.success) { closeLR(); location.reload(); } });
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
        $g('lrm-start-at').value = ''; $g('lrm-end-at').value = '';
        $g('lrm-start-day').value = ''; $g('lrm-end-day').value = '';
        $g('lrm-hours').value = ''; $g('lrm-description').value = '';
        if (HAS_SELECT) {
            show($g('lrm-user-select'));
            _initTs();
        }
        _fetchBalance(AUTH_ID);
        _bindListeners();
        $g('lrm-btn-area').innerHTML =
            _btn('Bỏ', 'closeLR()', 'secondary') +
            _btn('Tạo', '_lrmSubmit()', 'primary');
    }

    function _populateView(d) {
        var lr = d.leave;
        $g('lrm-title').textContent = 'Yêu cầu nghỉ phép';
        _balance = d.leave_balance;

        var banner = $g('lrm-status-banner');
        var labels = { pending: 'Đang chờ duyệt', approved: 'Đã duyệt', rejected: 'Đã từ chối' };
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

        var typeLabels = { annual: 'Nghỉ phép năm', sick: 'Nghỉ ốm', unpaid: 'Nghỉ không lương' };
        $g('lrm-type-display').textContent = typeLabels[lr.type] || lr.type;
        show($g('lrm-type-display'));

        $g('lrm-start-display').textContent = lr.start_at_text;
        $g('lrm-end-display').textContent   = lr.end_at_text;
        show($g('lrm-start-display')); show($g('lrm-end-display'));

        $g('lrm-hours-display').textContent = lr.hours + 'h';
        show($g('lrm-hours-display'));

        if (lr.type === 'annual' && lr.status !== 'approved' && d.leave_balance !== null) {
            $g('lrm-balance-current').textContent = parseFloat(d.leave_balance).toFixed(2).replace(/\.?0+$/,'') + 'h';
            var after = (parseFloat(d.leave_balance) - parseFloat(lr.hours));
            var afterStr = after.toFixed(2).replace(/\.?0+$/,'') + 'h';
            $g('lrm-balance-after').textContent = afterStr;
            $g('lrm-balance-after').className = 'hidden font-semibold ' + (after < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400');
            show($g('lrm-balance-arrow')); show($g('lrm-balance-after'));
            show($g('lrm-balance-preview'));
        }

        $g('lrm-desc-display').textContent = lr.description || '—';
        show($g('lrm-desc-display'));

        if (lr.status === 'rejected' && lr.reject_reason) {
            $g('lrm-reject-reason-text').textContent = lr.reject_reason;
            show($g('lrm-reject-display'));
        }

        var btns = '';
        if (d.can_edit)    btns += _btn('Chỉnh sửa', '_lrmSwitchToEdit()', 'secondary');
        if (d.can_approve) btns += _btn('Phê duyệt', '_lrmApprove()', 'success') + _btn('Từ chối', '_lrmShowReject()', 'danger');
        btns += _btn('Đóng', 'closeLR()', 'secondary');
        $g('lrm-btn-area').innerHTML = btns;
    }

    function _populateEdit() {
        var lr = _data.leave;
        $g('lrm-title').textContent = 'Chỉnh sửa yêu cầu nghỉ phép';

        hide($g('lrm-type-display'));    show($g('lrm-type'));
        hide($g('lrm-start-display'));   show($g('lrm-start-at'));
        hide($g('lrm-end-display'));     show($g('lrm-end-at'));
        hide($g('lrm-hours-display'));   show($g('lrm-hours'));
        hide($g('lrm-desc-display'));    show($g('lrm-description'));
        hide($g('lrm-balance-preview'));

        $g('lrm-type').value     = lr.type;
        $g('lrm-start-at').value = lr.start_at_input;
        $g('lrm-end-at').value   = lr.end_at_input;
        $g('lrm-start-day').value = lr.start_day_hours || '';
        $g('lrm-end-day').value   = lr.end_day_hours   || '';
        $g('lrm-hours').value    = lr.hours;
        $g('lrm-description').value = lr.description || '';
        _totalManual = false;

        _fetchBalance(lr.user_id);
        _bindListeners();
        _lrmCalc();

        $g('lrm-btn-area').innerHTML =
            _btn('Bỏ', 'closeLR()', 'secondary') +
            _btn('Lưu', '_lrmSubmit()', 'primary');
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
        $g('lrm-balance-current').textContent = parseFloat(_balance).toFixed(2).replace(/\.?0+$/,'') + 'h';
        if (hours > 0) {
            var after = parseFloat(_balance) - hours;
            $g('lrm-balance-after').textContent = after.toFixed(2).replace(/\.?0+$/,'') + 'h';
            $g('lrm-balance-after').className = 'font-semibold ' + (after < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400');
            show($g('lrm-balance-arrow')); show($g('lrm-balance-after'));
        } else {
            hide($g('lrm-balance-arrow')); hide($g('lrm-balance-after'));
        }
        show($g('lrm-balance-preview'));
    }

    // ── Date calculation ──────────────────────────────────────────────

    function _bindListeners() {
        if (_listenersBound) return;
        _listenersBound = true;
        var s = $g('lrm-start-at'); var e = $g('lrm-end-at');
        var sd = $g('lrm-start-day'); var ed = $g('lrm-end-day');
        var h = $g('lrm-hours'); var t = $g('lrm-type');
        s?.addEventListener('change', function(){ if(sd) sd.value=''; _totalManual=false; _lrmCalc(); });
        e?.addEventListener('change', function(){ if(ed) ed.value=''; _totalManual=false; _lrmCalc(); });
        sd?.addEventListener('input', function(){ _totalManual=false; _lrmCalc(); });
        ed?.addEventListener('input', function(){ _totalManual=false; _lrmCalc(); });
        h?.addEventListener('input',  function(){ _totalManual=true; _updateBalancePreview(); });
        t?.addEventListener('change', function(){ _updateBalancePreview(); });
    }

    function _lrmCalc() {
        var s = $g('lrm-start-at'); var e = $g('lrm-end-at'); var h = $g('lrm-hours');
        if (!s||!e||!h||!s.value||!e.value) return;
        var startDt = new Date(s.value); var endDt = new Date(e.value);
        if (endDt < startDt) { h.value=''; return; }
        var calDiff = _calDays(startDt, endDt);
        if (calDiff === 0) {
            hide($g('lrm-partial-section')); hide($g('lrm-breakdown'));
            if (!_totalManual) h.value = ((endDt - startDt)/3600000).toFixed(2);
            _updateBalancePreview(); return;
        }
        show($g('lrm-partial-section'));
        var sd=$g('lrm-start-day'); var ed=$g('lrm-end-day');
        if (sd && sd.value==='') sd.value = _defStartH(startDt).toFixed(2);
        if (ed && ed.value==='') ed.value = _defEndH(endDt).toFixed(2);
        var sl=$g('lrm-start-label'); var el=$g('lrm-end-label');
        if(sl) sl.textContent='('+_fd(startDt)+')';
        if(el) el.textContent='('+_fd(endDt)+')';
        var mid=_midDays(startDt,endDt);
        var sdH=parseFloat(sd?.value)||0; var edH=parseFloat(ed?.value)||0;
        if(!_totalManual) h.value=(sdH+mid*8+edH).toFixed(2);
        var bd=$g('lrm-breakdown');
        if(bd){
            var dot='<span class="text-blue-400 mr-1">•</span>';
            var html='<p class="font-semibold text-blue-700 dark:text-blue-400 mb-1.5">Tổng giờ dự kiến</p><div class="space-y-0.5">';
            html+=dot+'<strong>Ngày '+_fd(startDt)+'</strong>: '+sdH.toFixed(1)+'h<br>';
            if(mid>0) html+=dot+'<strong>'+mid+' ngày làm việc</strong>: '+(mid*8)+'h <span class="text-gray-400">(8h/ngày, bỏ qua cuối tuần &amp; ngày lễ)</span><br>';
            html+=dot+'<strong>Ngày '+_fd(endDt)+'</strong>: '+edH.toFixed(1)+'h';
            html+='</div>'; bd.innerHTML=html; show(bd);
        }
        _updateBalancePreview();
    }

    function _pad(n){ return String(n).padStart(2,'0'); }
    function _fd(dt){ return _pad(dt.getDate())+'/'+_pad(dt.getMonth()+1); }
    function _iso(dt){ return dt.getFullYear()+'-'+_pad(dt.getMonth()+1)+'-'+_pad(dt.getDate()); }
    function _calDays(a,b){ var d1=new Date(a); d1.setHours(0,0,0,0); var d2=new Date(b); d2.setHours(0,0,0,0); return Math.round((d2-d1)/86400000); }

    var _pMins = function(str){ var p=(str||'00:00').split(':').map(Number); return p[0]*60+(p[1]||0); };
    var _lS = _pMins(LUNCH_S); var _lE = _pMins(LUNCH_E);
    function _lo(f,t){ return Math.max(0,Math.min(t,_lE)-Math.max(f,_lS))/60; }
    function _defStartH(dt){ var s=dt.getHours()*60+dt.getMinutes(); var g=Math.max(0,17.5*60-s)/60; return Math.max(0,g-_lo(s,17.5*60)); }
    function _defEndH(dt){ var e=dt.getHours()*60+dt.getMinutes(); var g=Math.max(0,e-8.5*60)/60; return Math.max(0,g-_lo(8.5*60,e)); }
    function _midDays(s,e){
        var count=0; var d=new Date(s); d.setHours(12,0,0,0); d.setDate(d.getDate()+1);
        var stop=new Date(e); stop.setHours(0,0,0,0);
        while(d<stop){ var dow=d.getDay(); if(dow!==0&&dow!==6&&!HOLIDAYS.includes(_iso(d))) count++; d.setDate(d.getDate()+1); }
        return count;
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
