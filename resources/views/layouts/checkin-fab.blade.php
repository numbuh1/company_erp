@auth
@php
    $fabAtt    = \App\Models\Attendance::where('user_id', auth()->id())
                    ->whereDate('date', now()->toDateString())
                    ->first();
    $fabLat    = \App\Models\AppSetting::get('office_latitude');
    $fabLng    = \App\Models\AppSetting::get('office_longitude');
    $fabRadius = (float) \App\Models\AppSetting::get('office_radius_km', 2);

    $fabCheckedIn  = (bool) $fabAtt;
    $fabCheckedOut = $fabAtt && $fabAtt->check_out_time;
    $fabShow = !$fabCheckedIn
        || ($fabCheckedIn && !$fabCheckedOut && optional($fabAtt)->status === 'approved');

    // Checkout confirmation helpers
    $fabCheckInTime = null;
    if ($fabAtt) {
        $fabCheckInTime = $fabAtt->check_in_time
            ? substr($fabAtt->check_in_time, 0, 5)
            : ($fabAtt->created_at ? $fabAtt->created_at->format('H:i') : null);
    }
    $fabLunchStart = \App\Models\AppSetting::get('lunch_break_start', '12:00');
    $fabLunchEnd   = \App\Models\AppSetting::get('lunch_break_end',   '13:00');
@endphp

@if($fabShow)
<div id="fab-root"
     x-data="{
         open: false, showWfh: false, hours: 8, reason: '', submitting: false,
         showCheckoutConfirm: false, coSubmitting: false, estimatedHours: '0.00',
         checkInTime: '{{ $fabCheckInTime ?? '' }}',
         lunchStart:  '{{ $fabLunchStart }}',
         lunchEnd:    '{{ $fabLunchEnd }}',
         calcEstimate() {
             function toMins(s) {
                 if (!s) return 0;
                 var p = s.split(':');
                 return parseInt(p[0]) * 60 + (parseInt(p[1]) || 0);
             }
             var now = new Date();
             var outMins = now.getHours() * 60 + now.getMinutes();
             var inMins  = toMins(this.checkInTime);
             var lsMin   = toMins(this.lunchStart);
             var leMin   = toMins(this.lunchEnd);
             var total   = Math.max(0, outMins - inMins);
             var olStart = Math.max(inMins, lsMin);
             var olEnd   = Math.min(outMins, leMin);
             var lunch   = Math.max(0, olEnd - olStart);
             return Math.max(0, (total - lunch) / 60).toFixed(2);
         },
         openCheckoutConfirm() {
             this.estimatedHours = this.calcEstimate();
             this.showCheckoutConfirm = true;
         }
     }"
     @keydown.escape.window="open = false; showWfh = false; showCheckoutConfirm = false"
     class="flex flex-col items-end gap-2"
     style="position:fixed; bottom:1.5rem; right:1.5rem; z-index:70">

    @if(!$fabCheckedIn)
    {{-- ── CHECK-IN MODE ───────────────────────────────── --}}

    {{-- Expanded options --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="flex flex-col gap-2 items-end pb-1">

        <form id="fab-onsite-form" method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="type" value="on_site">
            <button type="button" id="fab-onsite-btn"
                data-lat="{{ $fabLat }}" data-lng="{{ $fabLng }}" data-radius="{{ $fabRadius }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700
                       text-white font-semibold rounded-full shadow-lg transition-colors
                       text-sm whitespace-nowrap disabled:opacity-60">
                <span class="text-base leading-none">🏢</span>
                <span data-label>On Site</span>
            </button>
        </form>

        <button type="button" @click="showWfh = true; open = false"
            class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700
                   text-white font-semibold rounded-full shadow-lg transition-colors text-sm whitespace-nowrap">
            <span class="text-base leading-none">🏠</span>
            WFH
        </button>

        <div id="fab-geo-error"
             class="hidden max-w-[260px] text-xs text-red-700 dark:text-red-300
                    bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700
                    rounded-xl px-3 py-2 shadow-lg text-left"></div>
    </div>

    {{-- Main FAB button — bg-indigo-600 is STATIC so always rendered --}}
    <button type="button" @click="open = !open"
        class="flex items-center gap-2 pl-4 pr-5 py-3 bg-indigo-600 hover:bg-indigo-700
               text-white font-bold rounded-full shadow-xl ring-2 ring-white/20
               transition-colors duration-150">
        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-45': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm" x-text="open ? 'Đóng' : 'Check In'">Check In</span>
    </button>

    {{-- WFH modal (fixed inset-0, works fine inside a non-transform parent) --}}
    <div x-show="showWfh" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showWfh = false"
         class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 px-4">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md
                    border border-gray-200 dark:border-gray-700 overflow-hidden">

            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">🏠 Work from Home</h3>
                <button type="button" @click="showWfh = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('attendance.store') }}" @submit="submitting = true" class="px-5 py-5 space-y-4">
                @csrf
                <input type="hidden" name="type" value="wfh">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Số giờ làm hôm nay</label>
                    <input type="number" name="hours" step="0.5" min="0.5" max="24" x-model="hours"
                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lý do / Nhiệm vụ hôm nay</label>
                    <textarea name="reason" rows="3" x-model="reason"
                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Mô tả ngắn gọn công việc bạn sẽ làm..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="showWfh = false"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600
                               text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Hủy
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700
                               text-white font-medium transition disabled:opacity-50">
                        <span x-show="!submitting">Gửi yêu cầu WFH</span>
                        <span x-show="submitting" x-cloak>Đang gửi…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @else
    {{-- ── CHECK-OUT MODE ──────────────────────────────── --}}
    <button type="button" @click="openCheckoutConfirm()"
        class="flex items-center gap-2 pl-4 pr-5 py-3 bg-orange-500 hover:bg-orange-600
               text-white font-bold rounded-full shadow-xl ring-2 ring-white/20
               transition-colors duration-150">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span class="text-sm">Check Out</span>
    </button>
    @endif

    {{-- ── CHECK-OUT CONFIRMATION MODAL ──────────────────────────── --}}
    <div x-show="showCheckoutConfirm" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showCheckoutConfirm = false"
         class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md">

            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">
                🚪 Check Out
            </h3>

            <form method="POST" action="{{ route('attendance.checkout') }}" @submit="coSubmitting = true">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Giờ vào</label>
                    <div class="flex items-center gap-2 mt-1 px-3 py-2
                                border border-gray-300 dark:border-gray-700 rounded-md
                                bg-gray-50 dark:bg-gray-900
                                text-sm text-gray-800 dark:text-gray-200">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="checkInTime"></span>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Giờ làm thực tế ước tính
                        <span class="text-xs font-normal text-gray-400"
                              x-text="'(trừ nghỉ trưa ' + lunchStart + '–' + lunchEnd + ')'"></span>
                    </label>
                    <div class="flex items-baseline gap-1.5 mt-1 px-3 py-2.5
                                border border-orange-200 dark:border-orange-700 rounded-md
                                bg-orange-50 dark:bg-orange-900/20">
                        <span class="text-2xl font-bold text-orange-600 dark:text-orange-400"
                              x-text="estimatedHours"></span>
                        <span class="text-sm font-medium text-orange-500 dark:text-orange-400">giờ</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCheckoutConfirm = false"
                        class="px-4 py-2 text-sm rounded border border-gray-300 dark:border-gray-600
                               text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Hủy
                    </button>
                    <button type="submit" :disabled="coSubmitting"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm rounded
                               bg-orange-500 hover:bg-orange-600 text-white font-medium transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span x-show="!coSubmitting">Check Out</span>
                        <span x-show="coSubmitting" x-cloak>Đang xử lý…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

{{-- GPS verification script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn    = document.getElementById('fab-onsite-btn');
    const form   = document.getElementById('fab-onsite-form');
    const errBox = document.getElementById('fab-geo-error');
    if (!btn || !form) return;

    const officeLat    = btn.dataset.lat    ? parseFloat(btn.dataset.lat)    : null;
    const officeLng    = btn.dataset.lng    ? parseFloat(btn.dataset.lng)    : null;
    const officeRadius = btn.dataset.radius ? parseFloat(btn.dataset.radius) : 2;

    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLon = (lon2-lon1)*Math.PI/180;
        const a = Math.sin(dLat/2)**2
                + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function setLoading(on) {
        btn.disabled = on;
        const lbl = btn.querySelector('[data-label]');
        if (lbl) lbl.textContent = on ? 'Đang kiểm tra…' : 'On Site';
    }

    btn.addEventListener('click', function () {
        if (errBox) errBox.classList.add('hidden');
        if (!officeLat || !officeLng) { form.submit(); return; }
        if (!navigator.geolocation) {
            if (errBox) { errBox.textContent = 'Trình duyệt không hỗ trợ định vị.'; errBox.classList.remove('hidden'); }
            return;
        }
        setLoading(true);
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                const dist = haversine(pos.coords.latitude, pos.coords.longitude, officeLat, officeLng);
                setLoading(false);
                if (dist <= officeRadius) {
                    form.submit();
                } else {
                    if (errBox) {
                        errBox.textContent = 'Cách văn phòng ' + dist.toFixed(1) + 'km (tối đa: ' + officeRadius + 'km).';
                        errBox.classList.remove('hidden');
                    }
                }
            },
            function (err) {
                setLoading(false);
                if (errBox) { errBox.textContent = 'Không thể lấy vị trí: ' + err.message; errBox.classList.remove('hidden'); }
            },
            { timeout: 10000, maximumAge: 60000 }
        );
    });
});
</script>
@endif
@endauth
