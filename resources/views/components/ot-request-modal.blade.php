@php
    use App\Models\PublicHoliday;
    use Carbon\Carbon;

    $otmAuth         = auth()->user();
    $otmCanCreate    = $otmAuth?->canAny(['edit own ot', 'edit team ot', 'edit all ot']);
    $otmCanTeamOrAll = $otmAuth?->canAny(['edit team ot', 'edit all ot']);

    if ($otmCanCreate) {
        if ($otmAuth->can('edit all ot')) {
            $otmUsers = \App\Models\User::orderBy('name')->get(['id', 'name', 'position']);
        } elseif ($otmAuth->can('edit team ot')) {
            $otmUsers = \App\Models\User::whereIn('id', $otmAuth->teamMembers()->pluck('id'))->orderBy('name')->get(['id', 'name', 'position']);
        } else {
            $otmUsers = collect([$otmAuth]);
        }
        // Projects / tasks for the auth user (create mode)
        $otmProjects = \App\Models\Project::where(function($q) use($otmAuth){
            $q->whereHas('users', fn($q2) => $q2->where('users.id', $otmAuth->id))
              ->orWhereHas('teams', fn($q2) => $q2->whereHas('users', fn($q3) => $q3->where('users.id', $otmAuth->id)));
        })->orderBy('name')->get(['id', 'name', 'project_code']);
        $otmTasks = \App\Models\Task::whereHas('assignees', fn($q) => $q->where('users.id', $otmAuth->id))
            ->orderBy('name')->get(['id', 'name', 'project_id', 'task_code']);
    } else {
        $otmUsers = collect(); $otmProjects = collect(); $otmTasks = collect();
    }

    $otmHolidays = PublicHoliday::getHolidayDates(Carbon::now()->subYear(), Carbon::now()->addYears(2));
@endphp

<div id="otm-overlay" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 hidden">
    <div id="otm-box" class="relative bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg max-h-[90vh] overflow-y-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
            <h3 id="otm-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Yêu cầu tăng ca</h3>
            <button onclick="closeOtModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            <div id="otm-loading" class="hidden py-10 text-center text-gray-400 text-sm">Đang tải…</div>

            {{-- Status banner --}}
            <div id="otm-status-banner" class="hidden rounded-lg px-4 py-2.5 text-sm font-medium"></div>

            {{-- User --}}
            <div id="otm-user-row" class="hidden">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Người dùng</label>
                <p id="otm-user-display" class="hidden text-sm font-medium text-gray-900 dark:text-gray-100 py-1"></p>
                @if($otmCanTeamOrAll && $otmUsers->count() > 1)
                    <select id="otm-user-select" class="hidden w-full">
                        <option value="">— Chọn người dùng —</option>
                        @foreach($otmUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" id="otm-user-select" value="{{ $otmAuth?->id }}">
                @endif
            </div>

            {{-- OT Date --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Ngày tăng ca</label>
                <p id="otm-date-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <input id="otm-ot-date" type="date" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
            </div>

            {{-- From / To time --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Thời gian</label>
                <p id="otm-time-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 dark:text-gray-500 mb-1">Từ</label>
                        <input id="otm-start-time" type="time" lang="en-GB" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 dark:text-gray-500 mb-1">Đến</label>
                        <input id="otm-end-time" type="time" lang="en-GB" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                    </div>
                </div>
            </div>

            {{-- OT Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Loại tăng ca</label>
                <p id="otm-type-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <select id="otm-type-select" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                    <option value="">— Tự động theo ngày —</option>
                    <option value="OT x1.5">OT x1.5</option>
                    <option value="OT x2">OT x2</option>
                    <option value="OT x3">OT x3</option>
                </select>
                <p id="otm-type-auto-note" class="hidden mt-1 text-xs text-gray-400">Tự động chọn theo ngày, có thể thay đổi.</p>
            </div>

            {{-- Hours --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Số giờ</label>
                <p id="otm-hours-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <input id="otm-hours" type="number" step="0.25" min="0.25" placeholder="0"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                <p id="otm-hours-warning" class="hidden mt-1.5 text-xs text-amber-600 dark:text-amber-400 flex items-start gap-1">
                    <span>⚠️</span>
                    <span>Số giờ tăng ca khá cao. Vui lòng kiểm tra lại giờ bắt đầu/kết thúc.</span>
                </p>
            </div>

            {{-- OT month / year preview --}}
            <div id="otm-ot-preview" class="hidden p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg space-y-1">
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Tăng ca tháng này:</span>
                    <span id="otm-ot-month-total" class="font-semibold text-orange-700 dark:text-orange-300"></span>
                    <span id="otm-ot-month-arrow" class="hidden text-gray-400">→</span>
                    <span id="otm-ot-month-after" class="hidden font-semibold text-green-600 dark:text-green-400"></span>
                </div>
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Tăng ca năm nay:</span>
                    <span id="otm-ot-total" class="font-semibold text-orange-700 dark:text-orange-300"></span>
                    <span id="otm-ot-arrow" class="hidden text-gray-400">→</span>
                    <span id="otm-ot-after" class="hidden font-semibold text-green-600 dark:text-green-400"></span>
                </div>
            </div>

            {{-- Project --}}
            <div id="otm-project-row">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Dự án</label>
                <p id="otm-project-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <select id="otm-project-select" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">— Không có —</option>
                    @foreach($otmProjects as $p)
                        <option value="{{ $p->id }}">{{ $p->project_code }} · {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Task --}}
            <div id="otm-task-row">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Công việc</label>
                <p id="otm-task-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1"></p>
                <select id="otm-task-select" class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">— Không có —</option>
                    @foreach($otmTasks as $t)
                        <option value="{{ $t->id }}" data-project="{{ $t->project_id }}">{{ $t->task_code }} · {{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Lý do</label>
                <p id="otm-desc-display" class="hidden text-sm text-gray-900 dark:text-gray-100 py-1 whitespace-pre-wrap min-h-[1.5rem]"></p>
                <textarea id="otm-description" rows="3"
                    class="hidden w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"
                    placeholder="Nhập lý do…"></textarea>
            </div>

            {{-- Reject reason display --}}
            <div id="otm-reject-display" class="hidden p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1">Lý do từ chối</p>
                <p id="otm-reject-reason-text" class="text-sm text-red-700 dark:text-red-300"></p>
            </div>

            {{-- Inline reject input --}}
            <div id="otm-reject-section" class="hidden space-y-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <label class="block text-xs font-semibold text-red-600 dark:text-red-400">Lý do từ chối <span>*</span></label>
                <textarea id="otm-reject-input" rows="3" placeholder="Nhập lý do từ chối…"
                    class="w-full border-red-300 dark:border-red-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2"></textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="_otmCancelReject()" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Hủy</button>
                    <button onclick="_otmConfirmReject()" class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">Xác nhận từ chối</button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div id="otm-btn-area" class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-800"></div>
    </div>
</div>

<script>
(function () {
    var CSRF         = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var AUTH_ID      = {{ $otmAuth?->id ?? 'null' }};
    var HAS_SEL      = {{ ($otmCanTeamOrAll && $otmUsers->count() > 1) ? 'true' : 'false' }};
    var _OT_URL      = '{{ url("overtime-requests") }}';
    var _OTM_USR_URL = '{{ url("users") }}';
    var HOLIDAYS     = {!! json_encode($otmHolidays, JSON_HEX_TAG) !!};
    var ALL_TASKS    = {!! $otmTasks->map(fn($t) => ['id' => (string)$t->id, 'text' => $t->task_code . ' · ' . $t->name, 'proj' => (string)($t->project_id ?? '')])->values()->toJson() !!};

    var _mode   = 'create';
    var _id     = null;
    var _data   = null;
    var _otTotal      = 0;
    var _otMonthTotal = 0;
    var _tsUser  = null;
    var _tsPrj   = null;
    var _tsTask  = null;
    var _manualH = false;
    var _lBound  = false;
    var _dynTasks = ALL_TASKS.slice();

    function $g(id){ return document.getElementById(id); }
    function show(el){ if(el) el.classList.remove('hidden'); }
    function hide(el){ if(el) el.classList.add('hidden'); }

    function _hideBody(){
        ['otm-status-banner','otm-user-display','otm-user-select','otm-date-display','otm-ot-date',
         'otm-time-display','otm-start-time','otm-end-time',
         'otm-type-display','otm-type-select','otm-type-auto-note',
         'otm-hours-display','otm-hours','otm-hours-warning',
         'otm-ot-preview','otm-ot-arrow','otm-ot-after','otm-ot-month-arrow','otm-ot-month-after',
         'otm-project-display','otm-project-select','otm-task-display','otm-task-select',
         'otm-desc-display','otm-description','otm-reject-display','otm-reject-section']
        .forEach(function(id){ hide($g(id)); });
        show($g('otm-user-row')); show($g('otm-project-row')); show($g('otm-task-row'));
    }

    // ── Public API ────────────────────────────────────────────────────

    window.openOtModal = function (id) {
        _mode='view'; _id=id; _data=null;
        _showOverlay(); show($g('otm-loading')); _hideBody();
        $g('otm-btn-area').innerHTML='';
        fetch(_OT_URL+'/'+id, { headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF} })
            .then(function(r){ return r.json(); })
            .then(function(d){ hide($g('otm-loading')); _data=d; _populateView(d); })
            .catch(function(){ hide($g('otm-loading')); $g('otm-btn-area').innerHTML=_btn('Đóng','closeOtModal()','secondary'); });
    };

    window.openOtCreate = function () {
        _mode='create'; _id=null; _data=null; _otTotal=0; _otMonthTotal=0; _manualH=false;
        _showOverlay(); hide($g('otm-loading')); _hideBody();
        $g('otm-title').textContent='Tạo yêu cầu tăng ca';
        _populateCreate();
    };

    window.closeOtModal = function () {
        hide($g('otm-overlay'));
        _destroyTs(); _lBound=false;
    };

    window._otmSwitchToEdit = function () { _mode='edit'; _populateEdit(); };

    window._otmSubmit = function () {
        var userId  = (_mode==='edit'&&_data) ? _data.ot.user_id : _tsUserVal();
        var otDate  = $g('otm-ot-date').value;
        var typeVal = $g('otm-type-select').value || _getOtType(otDate);
        var payload = {
            user_id:     userId,
            ot_date:     otDate,
            start_time:  $g('otm-start-time').value,
            end_time:    $g('otm-end-time').value,
            type:        typeVal,
            project_id:  _tsPrj ? _tsPrj.getValue() : $g('otm-project-select').value,
            task_id:     _tsTask ? _tsTask.getValue() : $g('otm-task-select').value,
            description: $g('otm-description').value,
        };

        var url    = _mode==='create' ? _OT_URL : _OT_URL+'/'+_id;
        var method = _mode==='create' ? 'POST'                         : 'PUT';
        fetch(url,{
            method:method,
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify(payload),
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ if(d.success){ closeOtModal(); location.reload(); } else { alert(d.message||'Lỗi khi lưu.'); } })
        .catch(function(){ alert('Lỗi kết nối.'); });
    };

    window._otmApprove = function () {
        fetch(_OT_URL+'/'+_id+'/approve',{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
        .then(function(r){ return r.json(); })
        .then(function(d){ if(d.success){ closeOtModal(); location.reload(); } });
    };

    window._otmShowReject  = function(){ show($g('otm-reject-section')); $g('otm-reject-input').value=''; $g('otm-reject-input').focus(); };
    window._otmCancelReject= function(){ hide($g('otm-reject-section')); };
    window._otmConfirmReject=function(){
        var reason=$g('otm-reject-input').value.trim();
        if(!reason){ $g('otm-reject-input').focus(); return; }
        fetch(_OT_URL+'/'+_id+'/reject',{
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({reject_reason:reason}),
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ if(d.success){ closeOtModal(); location.reload(); } });
    };

    // ── Private ───────────────────────────────────────────────────────

    function _showOverlay(){
        var ov=$g('otm-overlay'); if(ov) ov.classList.remove('hidden');
    }

    function _populateCreate(){
        show($g('otm-user-row'));
        if(HAS_SEL){ show($g('otm-user-select')); _initUserTs(); }
        show($g('otm-ot-date')); show($g('otm-start-time')); show($g('otm-end-time'));
        show($g('otm-type-select')); show($g('otm-type-auto-note'));
        show($g('otm-hours')); show($g('otm-description'));
        show($g('otm-project-select')); show($g('otm-task-select'));
        $g('otm-ot-date').value=''; $g('otm-start-time').value=''; $g('otm-end-time').value='';
        $g('otm-type-select').value=''; $g('otm-hours').value=''; $g('otm-description').value='';
        hide($g('otm-hours-warning'));
        _fetchOtTotal(AUTH_ID);
        _bindListeners();
        _initProjectTaskTs();
        $g('otm-btn-area').innerHTML=_btn('Bỏ','closeOtModal()','secondary')+_btn('Tạo','_otmSubmit()','primary');
    }

    function _populateView(d){
        var ot=d.ot;
        $g('otm-title').textContent='Yêu cầu tăng ca';
        _otTotal=d.ot_year_total||0;
        _otMonthTotal=d.ot_month_total||0;

        var banner=$g('otm-status-banner');
        var labels={pending:'Đang chờ duyệt',approved:'Đã duyệt',rejected:'Đã từ chối'};
        var cls={
            pending:'bg-yellow-50 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700',
            approved:'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-300 border border-green-200 dark:border-green-700',
            rejected:'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-300 border border-red-200 dark:border-red-700',
        };
        banner.className='rounded-lg px-4 py-2.5 text-sm font-medium '+(cls[ot.status]||'');
        banner.textContent=(labels[ot.status]||ot.status)+(ot.approver_name?' · '+ot.approver_name:'');
        show(banner);

        show($g('otm-user-row'));
        $g('otm-user-display').textContent=ot.user_name; show($g('otm-user-display'));

        $g('otm-date-display').textContent=ot.ot_date ? _fmtDate(ot.ot_date) : (ot.start_at_text||'');
        show($g('otm-date-display'));

        $g('otm-time-display').textContent=ot.start_time+' – '+ot.end_time;
        show($g('otm-time-display'));

        $g('otm-type-display').textContent=ot.type; show($g('otm-type-display'));

        $g('otm-hours-display').textContent=ot.hours+'h'; show($g('otm-hours-display'));

        if(ot.status!=='approved'){
            $g('otm-ot-total').textContent=_otTotal+'h';
            $g('otm-ot-after').textContent=(_otTotal+parseFloat(ot.hours))+'h';
            show($g('otm-ot-arrow')); show($g('otm-ot-after'));
            $g('otm-ot-month-total').textContent=_otMonthTotal+'h';
            $g('otm-ot-month-after').textContent=(_otMonthTotal+parseFloat(ot.hours))+'h';
            show($g('otm-ot-month-arrow')); show($g('otm-ot-month-after'));
            show($g('otm-ot-preview'));
        } else {
            $g('otm-ot-total').textContent=_otTotal+'h';
            $g('otm-ot-month-total').textContent=_otMonthTotal+'h';
            show($g('otm-ot-preview'));
        }

        if(ot.project_name){ $g('otm-project-display').textContent=(ot.project_code?ot.project_code+' · ':'')+ot.project_name; }
        else { $g('otm-project-display').textContent='—'; }
        show($g('otm-project-display'));

        if(ot.task_name){ $g('otm-task-display').textContent=(ot.task_code?ot.task_code+' · ':'')+ot.task_name; }
        else { $g('otm-task-display').textContent='—'; }
        show($g('otm-task-display'));

        $g('otm-desc-display').textContent=ot.description||'—'; show($g('otm-desc-display'));

        if(ot.status==='rejected'&&ot.reject_reason){
            $g('otm-reject-reason-text').textContent=ot.reject_reason; show($g('otm-reject-display'));
        }

        var btns='';
        if(d.can_edit)    btns+=_btn('Chỉnh sửa','_otmSwitchToEdit()','secondary');
        if(d.can_approve) btns+=_btn('Phê duyệt','_otmApprove()','success')+_btn('Từ chối','_otmShowReject()','danger');
        btns+=_btn('Đóng','closeOtModal()','secondary');
        $g('otm-btn-area').innerHTML=btns;
    }

    function _populateEdit(){
        var ot=_data.ot;
        $g('otm-title').textContent='Chỉnh sửa yêu cầu tăng ca';

        hide($g('otm-date-display'));   show($g('otm-ot-date'));
        hide($g('otm-time-display'));   show($g('otm-start-time')); show($g('otm-end-time'));
        hide($g('otm-type-display'));   show($g('otm-type-select')); show($g('otm-type-auto-note'));
        hide($g('otm-hours-display')); show($g('otm-hours'));
        hide($g('otm-desc-display'));  show($g('otm-description'));
        hide($g('otm-project-display')); show($g('otm-project-select'));
        hide($g('otm-task-display'));    show($g('otm-task-select'));

        $g('otm-ot-date').value   =ot.ot_date;
        $g('otm-start-time').value=ot.start_time;
        $g('otm-end-time').value  =ot.end_time;
        $g('otm-type-select').value=ot.type||'';
        $g('otm-hours').value     =ot.hours;
        $g('otm-description').value=ot.description||'';
        _manualH=false;

        // Populate selects with data from API
        var projects=_data.projects||[];
        var tasks   =_data.tasks||[];
        _dynTasks = tasks;
        var pSel=$g('otm-project-select'); var tSel=$g('otm-task-select');
        pSel.innerHTML='<option value="">— Không có —</option>';
        projects.forEach(function(p){ pSel.innerHTML+='<option value="'+p.id+'">'+_esc(p.text)+'</option>'; });
        tSel.innerHTML='<option value="">— Không có —</option>';
        tasks.forEach(function(t){ tSel.innerHTML+='<option value="'+t.id+'" data-project="'+t.project_id+'">'+_esc(t.text)+'</option>'; });

        _bindListeners();
        _initProjectTaskTs();

        if(_tsPrj) _tsPrj.setValue(String(ot.project_id||''), true);
        else pSel.value=ot.project_id||'';
        if(_tsTask) _tsTask.setValue(String(ot.task_id||''), true);
        else tSel.value=ot.task_id||'';

        // OT preview
        $g('otm-ot-total').textContent=_otTotal+'h';
        $g('otm-ot-month-total').textContent=_otMonthTotal+'h';
        hide($g('otm-ot-arrow')); hide($g('otm-ot-after'));
        hide($g('otm-ot-month-arrow')); hide($g('otm-ot-month-after'));
        show($g('otm-ot-preview'));
        _updateOtPreview();
        _checkHoursWarning();

        $g('otm-btn-area').innerHTML=_btn('Bỏ','closeOtModal()','secondary')+_btn('Lưu','_otmSubmit()','primary');
    }

    // ── OT preview ────────────────────────────────────────────────────

    function _fetchOtTotal(userId){
        if(!userId) return;
        fetch(_OTM_USR_URL+'/'+userId+'/request-info',{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
        .then(function(r){ return r.json(); })
        .then(function(d){
            _otTotal=parseFloat(d.ot_year_total)||0;
            _otMonthTotal=parseFloat(d.ot_month_total)||0;
            $g('otm-ot-total').textContent=_otTotal+'h';
            $g('otm-ot-month-total').textContent=_otMonthTotal+'h';
            show($g('otm-ot-preview'));
            _updateOtPreview();
        });
    }

    function _updateOtPreview(){
        var h=parseFloat($g('otm-hours')?.value)||0;
        if(h>0){
            $g('otm-ot-after').textContent=(_otTotal+h).toFixed(2).replace(/\.?0+$/,'')+'h';
            show($g('otm-ot-arrow')); show($g('otm-ot-after'));
            $g('otm-ot-month-after').textContent=(_otMonthTotal+h).toFixed(2).replace(/\.?0+$/,'')+'h';
            show($g('otm-ot-month-arrow')); show($g('otm-ot-month-after'));
        } else {
            hide($g('otm-ot-arrow')); hide($g('otm-ot-after'));
            hide($g('otm-ot-month-arrow')); hide($g('otm-ot-month-after'));
        }
    }

    function _checkHoursWarning(){
        var h=parseFloat($g('otm-hours')?.value)||0;
        if(h>8) show($g('otm-hours-warning')); else hide($g('otm-hours-warning'));
    }

    // ── OT type ───────────────────────────────────────────────────────

    function _getOtType(dateStr){
        if(!dateStr) return 'OT x1.5';
        if(HOLIDAYS.includes(dateStr)) return 'OT x3';
        var day=new Date(dateStr+'T00:00:00').getDay();
        return day===0?'OT x2':'OT x1.5';
    }

    // ── Hours calculation (supports overnight rollover) ────────────────

    function _toMins(t){ var p=t.split(':').map(Number); return p[0]*60+(p[1]||0); }

    function _computeHours(s, e){
        if(!s||!e) return 0;
        var sm=_toMins(s); var em=_toMins(e);
        if(em<=sm) em+=1440; // overnight: end time is on the next day
        return (em-sm)/60;
    }

    function _calcHours(){
        var s=$g('otm-start-time'); var e=$g('otm-end-time'); var h=$g('otm-hours');
        if(_manualH||!s||!e||!s.value||!e.value||!h) return;
        var hrs=_computeHours(s.value, e.value);
        h.value=hrs.toFixed(2).replace(/\.?0+$/,'');
        _updateOtPreview();
        _checkHoursWarning();
    }

    // ── Listeners ─────────────────────────────────────────────────────

    function _bindListeners(){
        if(_lBound) return; _lBound=true;
        var d=$g('otm-ot-date'); var s=$g('otm-start-time'); var e=$g('otm-end-time'); var h=$g('otm-hours');
        d?.addEventListener('change',function(){ $g('otm-type-select').value=_getOtType(d.value); });
        s?.addEventListener('change',_calcHours); e?.addEventListener('change',_calcHours);
        h?.addEventListener('input',function(){ _manualH=true; _updateOtPreview(); _checkHoursWarning(); });
    }

    // ── TomSelect ─────────────────────────────────────────────────────

    function _initUserTs(){
        if(_tsUser) return;
        var sel=$g('otm-user-select');
        if(!sel||sel.tagName!=='SELECT') return;
        _tsUser=new TomSelect(sel,{allowEmptyOption:true,maxOptions:300,
            onChange:function(v){ _fetchOtTotal(v); }});
        _tsUser.setValue(String(AUTH_ID),true);
    }

    function _initProjectTaskTs(){
        var pSel=$g('otm-project-select'); var tSel=$g('otm-task-select');
        if(!pSel||!tSel) return;
        if(_tsPrj||_tsTask) return;
        _tsTask=new TomSelect(tSel,{maxOptions:null,allowEmptyOption:true,
            onChange:function(v){
                if(!v||!_tsPrj) return;
                var t=_dynTasks.find(function(x){ return String(x.id)===String(v)||String(x.value)===String(v); });
                if(t&&t.proj) _tsPrj.setValue(String(t.proj),true);
            }});
        _tsPrj=new TomSelect(pSel,{maxOptions:null,allowEmptyOption:true,
            onChange:function(v){
                var filtered=v?_dynTasks.filter(function(t){ return String(t.proj)===String(v); }):_dynTasks;
                _tsTask.clear(true); _tsTask.clearOptions();
                _tsTask.addOption({value:'',text:'— Không có —'});
                filtered.forEach(function(t){ _tsTask.addOption({value:String(t.id||t.value),text:t.text}); });
                _tsTask.refreshOptions(false);
            }});
    }

    function _destroyTs(){
        [_tsUser,_tsPrj,_tsTask].forEach(function(ts){ if(ts){ try{ts.destroy();}catch(e){} } });
        _tsUser=null; _tsPrj=null; _tsTask=null;
    }

    function _tsUserVal(){
        if(_tsUser) return _tsUser.getValue();
        var sel=$g('otm-user-select'); return sel?sel.value||AUTH_ID:AUTH_ID;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    function _fmtDate(iso){
        if(!iso) return iso;
        var p=iso.split('-'); return p[2]+'/'+p[1]+'/'+p[0];
    }

    function _esc(s){
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function _btn(label,fn,type){
        var base='px-4 py-2 text-sm rounded-lg font-medium transition ';
        var cls={primary:base+'bg-indigo-600 hover:bg-indigo-700 text-white',
                 secondary:base+'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                 success:base+'bg-green-600 hover:bg-green-700 text-white',
                 danger:base+'bg-red-600 hover:bg-red-700 text-white'}[type]||base;
        return '<button onclick="'+fn+'" class="'+cls+'">'+label+'</button>';
    }

    var _otov = $g('otm-overlay');
    if (_otov) _otov.addEventListener('click',function(e){ if(e.target===this) closeOtModal(); });
})();
</script>
