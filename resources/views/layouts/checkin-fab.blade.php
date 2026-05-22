@auth
@php
    $fabAtt    = \App\Models\Attendance::where('user_id', auth()->id())
                    ->whereDate('date', now()->toDateString())
                    ->first();
    $fabLat    = \App\Models\AppSetting::get('office_latitude');
    $fabLng    = \App\Models\AppSetting::get('office_longitude');
    $fabRadius = (float) \App\Models\AppSetting::get('office_radius_km', 2);

    // States
    $fabCheckedIn  = (bool) $fabAtt;
    $fabCheckedOut = $fabAtt && $fabAtt->check_out_time;
    // Show if: not yet checked in, OR checked in but not checked out (and approved)
    $fabShow = !$fabCheckedIn || ($fabCheckedIn && !$fabCheckedOut && $fabAtt->status === 'approved');
@endphp

@if($fabShow)
<div id="fab-root"
     x-data="{
         open: false,
         showWfh: false,
         hours: 8,
         reason: '',
         submitting: false,
     }"
     @keydown.escape.window="open = false; showWfh = false"
     class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-2">

    @if(!$fabCheckedIn)
    {{-- ── NOT CHECKED IN: show check-in button ────────────────────── --}}

    {{-- Expanded options (appear above the FAB) --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="flex flex-col gap-2 items-end">

        {{-- On-Site option --}}
        <form id="fab-onsite-form" method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="type" value="on_site">
            <button type="button" id="fab-onsite-btn"
                data-lat="{{ $fabLat }}"
                data-lng="{{ $fabLng }}"
                data-radius="{{ $fabRadius }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-semibold rounded-full shadow-lg transition text-sm whitespace-nowrap">
                <span class="text-base">🏢</span>
                <span data-label>On Site</span>
            </button>
        </form>

        {{-- WFH option --}}
        <button type="button" @click="showWfh = true; open = false"
            class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-full shadow-lg transition text-sm whitespace-nowrap">
            <span class="text-base">🏠</span>
            WFH
        </button>

        {{-- Geo error --}}
        <div id="fab-geo-error"
             class="hidden max-w-xs text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl px-3 py-2 shadow">
        </div>
    </div>

    {{-- Main FAB toggle --}}
    <button type="button" @click="open = !open"
        :class="open ? 'bg-gray-500 hover:bg-gray-600' : 'bg-indigo-600 hover:bg-indigo-700'"
        class="flex items-center gap-2 pl-4 pr-5 py-3 text-white font-bold rounded-full shadow-xl transition-all ring-2 ring-white/30">
        <svg class="w-5 h-5 transition-transform duration-200" :class="open ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm" x-text="open ? 'Đóng' : 'Check In'">Check In</span>
    </button>

    {{-- WFH Modal --}}
    <div x-show="showWfh" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showWfh = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div x-show="showWfh" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-gray-700 overflow-hidden">

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
                    <input type="number" name="hours" step="0.5" min="0.5" max="24"
                        x-model="hours"
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
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Hủy
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition disabled:opacity-50">
                        <span x-show="!submitting">Gửi yêu cầu WFH</span>
                        <span x-show="submitting" x-cloak>Đang gửi…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @else
    {{-- ── CHECKED IN, NOT CHECKED OUT: show check-out button ─────── --}}
    <form method="POST" action="{{ route('attendance.checkout') }}"
          x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <button type="submit" :disabled="submitting"
            class="flex items-center gap-2 pl-4 pr-5 py-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-60 text-white font-bold rounded-full shadow-xl transition ring-2 ring-white/30">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="text-sm" x-text="submitting ? 'Đang xử lý…' : 'Check Out'">Check Out</span>
        </button>
    </form>
    @endif

</div>

{{-- ── FAB on-site GPS verification script ─────────────────────────── --}}
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
        const R    = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a    = Math.sin(dLat/2)**2
                   + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180)
                   * Math.sin(dLon/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
    }

    function setLoading(loading) {
        const label = btn.querySelector('[data-label]');
        btn.disabled = loading;
        if (label) label.textContent = loading ? 'Đang kiểm tra…' : 'On Site';
    }

    btn.addEventListener('click', function () {
        errBox.classList.add('hidden');

        if (!officeLat || !officeLng) {
            form.submit();
            return;
        }

        if (!navigator.geolocation) {
            showError('Trình duyệt không hỗ trợ định vị.');
            return;
        }

        setLoading(true);

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                const dist = haversine(
                    pos.coords.latitude, pos.coords.longitude,
                    officeLat, officeLng
                );
                setLoading(false);
                if (dist <= officeRadius) {
                    form.submit();
                } else {
                    showError(
                        'Bạn đang ở cách công ty ' + dist.toFixed(1) + ' km '
                        + '(tối đa: ' + officeRadius + ' km). Vui lòng chọn WFH.'
                    );
                }
            },
            function (err) {
                setLoading(false);
                showError('Không thể lấy vị trí: ' + err.message);
            },
            { timeout: 10000, maximumAge: 60000 }
        );
    });
});
</script>
@endif
@endauth
