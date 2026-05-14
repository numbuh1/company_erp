<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('requests.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Xuất yêu cầu</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Configure the filters below and click <strong>Xuất</strong> to download an Excel file.
                </p>

                <form method="POST" action="{{ route('requests.export.download') }}">
                    @csrf

                    {{-- Date range --}}
                    <div class="mb-5">
                        <x-input-label value="Khoảng thời gian" />
                        <div class="flex items-center gap-3 mt-1">
                            <input type="date" name="date_from" id="exp_date_from"
                                value="{{ $dateFrom }}"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm flex-1">
                            <span class="text-gray-400">→</span>
                            <input type="date" name="date_to" id="exp_date_to"
                                value="{{ $dateTo }}"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm flex-1">
                        </div>
                        {{-- Quick select --}}
                        @php
                            $thisM = ['from' => now()->startOfMonth()->format('Y-m-d'), 'to' => now()->endOfMonth()->format('Y-m-d')];
                            $lastM = ['from' => now()->subMonth()->startOfMonth()->format('Y-m-d'), 'to' => now()->subMonth()->endOfMonth()->format('Y-m-d')];
                        @endphp
                        <div class="flex gap-2 mt-2">
                            <button type="button"
                                onclick="document.getElementById('exp_date_from').value='{{ $thisM['from'] }}'; document.getElementById('exp_date_to').value='{{ $thisM['to'] }}';"
                                class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                Tháng này
                            </button>
                            <button type="button"
                                onclick="document.getElementById('exp_date_from').value='{{ $lastM['from'] }}'; document.getElementById('exp_date_to').value='{{ $lastM['to'] }}';"
                                class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                Tháng trước
                            </button>
                            <button type="button"
                                onclick="document.getElementById('exp_date_from').value=''; document.getElementById('exp_date_to').value='';"
                                class="px-2.5 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                All Time
                            </button>
                        </div>
                        @error('date_from')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        @error('date_to')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Request type --}}
                    <div class="mb-5">
                        <x-input-label value="Loại yêu cầu" />
                        <div class="flex gap-3 mt-2">
                            @foreach(['all' => 'All (Leave + OT)', 'leave' => 'Leave Only', 'ot' => 'OT Only'] as $val => $label)
                                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="type" value="{{ $val }}"
                                        {{ $type === $val ? 'checked' : '' }}
                                        class="text-pink-600 focus:ring-pink-500 border-gray-300 dark:border-gray-600">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="mb-6">
                        <x-input-label value="Trạng thái" />
                        <div class="flex gap-3 mt-2 flex-wrap">
                            @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="status" value="{{ $val }}"
                                        {{ $status === $val ? 'checked' : '' }}
                                        class="text-pink-600 focus:ring-pink-500 border-gray-300 dark:border-gray-600">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export Excel
                        </x-primary-button>
                        <a href="{{ route('requests.index') }}"
                            class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Hủy</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>