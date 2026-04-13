<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($publicHoliday) ? 'Edit Holiday' : 'Add Holiday' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                    action="{{ isset($publicHoliday) ? route('admin.public-holidays.update', $publicHoliday) : route('admin.public-holidays.store') }}">
                    @csrf
                    @if(isset($publicHoliday)) @method('PUT') @endif

                    <div class="space-y-4">
                        <div>
                            <x-input-label value="Name" />
                            <x-text-input name="name" class="mt-1 block w-full"
                                value="{{ old('name', $publicHoliday->name ?? '') }}"
                                placeholder="e.g. New Year's Day" required />
                            @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Start Date" />
                                <x-text-input type="date" name="start_date" class="mt-1 block w-full"
                                    value="{{ old('start_date', isset($publicHoliday) ? $publicHoliday->start_date->format('Y-m-d') : '') }}"
                                    required />
                                @error('start_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="End Date" />
                                <x-text-input type="date" name="end_date" class="mt-1 block w-full"
                                    value="{{ old('end_date', isset($publicHoliday) ? $publicHoliday->end_date->format('Y-m-d') : '') }}"
                                    required />
                                @error('end_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="repeats_annually" id="repeats_annually" value="1"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm"
                                {{ old('repeats_annually', $publicHoliday->repeats_annually ?? false) ? 'checked' : '' }}>
                            <label for="repeats_annually" class="text-sm text-gray-700 dark:text-gray-300">
                                Repeats annually (use month/day only, ignore year)
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <x-primary-button type="submit">{{ isset($publicHoliday) ? 'Update' : 'Add Holiday' }}</x-primary-button>
                        <a href="{{ route('admin.public-holidays.index') }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
