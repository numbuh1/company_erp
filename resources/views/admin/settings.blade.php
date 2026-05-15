<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Cài đặt</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-1">
                        📍 Office Location
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                        Dùng để xác minh chấm công tại văn phòng.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label value="Tên văn phòng" />
                            <x-text-input name="office_name" class="w-full mt-1"
                                value="{{ old('office_name', $settings['office_name']) }}"
                                placeholder="VD: Trụ sở chính" />
                            @error('office_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="mb-4">
                            <x-input-label value="IP công khai văn phòng" />
                            <x-text-input name="office_ips" class="w-full mt-1"
                                value="{{ old('office_ips', $settings['office_ips']) }}"
                                placeholder="e.g. 203.0.113.10, 203.0.113.11" />
                            <p class="text-xs text-gray-400 mt-1">
                                Comma-separated. Only users connecting from these IPs can check in On-Site.
                                Leave blank to disable IP checking.
                                <br>Your current IP: <span class="font-mono">{{ request()->ip() }}</span>
                            </p>
                            @error('office_ips')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ── GPS Coordinates ─────────────────────────────────── --}}
                    <div class="pt-5 border-t border-gray-200 dark:border-gray-600 mb-6">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                            🛰️ Tọa độ GPS
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Khi cài đặt, trình duyệt nhân viên sẽ kiểm tra vị trí GPS khi chấm công
                            <strong>Tại văn phòng</strong>. Nhân viên ngoài bán kính sẽ không thể chấm công on-site.
                            Để trống để tắt xác minh GPS.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                            <div>
                                <x-input-label value="Vĩ độ (Latitude)" />
                                <x-text-input id="settingLat" name="office_latitude" type="text"
                                    inputmode="decimal" class="w-full mt-1"
                                    value="{{ old('office_latitude', $settings['office_latitude']) }}"
                                    placeholder="VD: 10.776900" />
                                @error('office_latitude')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="Kinh độ (Longitude)" />
                                <x-text-input id="settingLng" name="office_longitude" type="text"
                                    inputmode="decimal" class="w-full mt-1"
                                    value="{{ old('office_longitude', $settings['office_longitude']) }}"
                                    placeholder="VD: 106.700900" />
                                @error('office_longitude')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="Bán kính cho phép (km)" />
                                <x-text-input name="office_radius_km" type="text"
                                    inputmode="decimal" class="w-full mt-1"
                                    value="{{ old('office_radius_km', $settings['office_radius_km']) }}"
                                    placeholder="VD: 0.2" />
                                <p class="text-xs text-gray-400 mt-1">Tối thiểu 0.05 km (50 m).</p>
                                @error('office_radius_km')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" id="getMyLocationBtn"
                                class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-indigo-400 dark:border-indigo-500 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                📍 Lấy vị trí hiện tại
                            </button>

                            @if($settings['office_latitude'] && $settings['office_longitude'])
                                <a href="https://www.google.com/maps?q={{ $settings['office_latitude'] }},{{ $settings['office_longitude'] }}"
                                    target="_blank" rel="noopener"
                                    class="text-sm text-blue-500 hover:underline">
                                    🗺️ Xem trên Google Maps ↗
                                </a>
                            @endif

                            <span id="geoStatusMsg" class="text-xs text-gray-400"></span>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Lưu cài đặt</x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@push('scripts')
<script>
document.getElementById('getMyLocationBtn')?.addEventListener('click', function () {
    const btn    = this;
    const status = document.getElementById('geoStatusMsg');

    if (!navigator.geolocation) {
        status.textContent = 'Trình duyệt không hỗ trợ định vị.';
        return;
    }

    btn.disabled    = true;
    status.textContent = 'Đang lấy vị trí…';

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            document.getElementById('settingLat').value = pos.coords.latitude.toFixed(6);
            document.getElementById('settingLng').value = pos.coords.longitude.toFixed(6);
            status.textContent = '✅ Đã điền tọa độ. Kiểm tra và lưu để áp dụng.';
            btn.disabled = false;
        },
        function (err) {
            status.textContent = '❌ Không lấy được vị trí: ' + err.message;
            btn.disabled = false;
        },
        { timeout: 10000, enableHighAccuracy: true }
    );
});
</script>
@endpush
</x-app-layout>
