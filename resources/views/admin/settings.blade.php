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

                    <!-- <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-1">
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
                    </div> -->

                    <div class="flex justify-end">
                        <x-primary-button>Lưu cài đặt</x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
