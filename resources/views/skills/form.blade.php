<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($skill) ? 'Chỉnh sửa Kĩ năng' : 'Tạo Kĩ năng' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <form method="POST"
                    action="{{ isset($skill) ? route('skills.update', $skill) : route('skills.store') }}">
                    @csrf
                    @if(isset($skill)) @method('PUT') @endif

                    <div class="mb-4">
                        <x-input-label for="name" value="Skill Name *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            value="{{ old('name', $skill->name ?? '') }}" required />
                        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <x-input-label for="category" value="Category *" />
                        <x-text-input id="category" name="category" type="text" class="mt-1 block w-full"
                            list="category-suggestions"
                            value="{{ old('category', $skill->category ?? '') }}"
                            placeholder="e.g. Engineering, IT, Languages…"
                            required />
                        <datalist id="category-suggestions">
                            @foreach(\App\Models\Skill::$categories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                        @error('category')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>{{ isset($skill) ? 'Lưu' : 'Tạo' }}</x-primary-button>
                        <a href="{{ route('skills.index') }}">
                            <x-secondary-button type="button">Hủy</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
