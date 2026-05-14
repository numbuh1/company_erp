<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ isset($role) ? 'Edit Role' : 'Create Role' }}
        </h2>
    </x-slot>

    <div class="py-12 max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 p-6 shadow rounded">

            <form method="POST" action="{{ isset($role) ? route('roles.update', $role) : route('roles.store') }}">
                @csrf
                @if(isset($role)) @method('PUT') @endif

                <!-- Name -->
                <div class="mb-4">
                    <x-input-label value="{{ __('Role Name') }}" />
                    <x-text-input name="name" class="w-full mt-1"
                        value="{{ old('name', $role->name ?? '') }}" required />
                </div>

                <!-- Permissions -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Permissions
                    </h3>

                    <div class="space-y-6">

                        @foreach($permissions as $module)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">

                                <!-- Module Header -->
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $module->display_name }}
                                    </h4>

                                    <!-- Select All -->
                                    <label class="flex items-center space-x-2 text-sm text-gray-500 cursor-pointer">
                                        <input type="checkbox" class="module-toggle">
                                        <span>{{ __('Select All') }}</span>
                                    </label>
                                </div>

                                <!-- Permissions Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($module->children as $permission)
                                        <label class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">

                                            <input type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                class="permission-checkbox"
                                                @checked(isset($rolePermissions) && in_array($permission->name, $rolePermissions))
                                            >

                                            <span class="text-sm text-gray-700 dark:text-gray-200">
                                                {{ $permission->display_name }}
                                            </span>

                                        </label>
                                    @endforeach
                                </div>

                            </div>
                        @endforeach

                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end mt-6 space-x-2">
                    <x-primary-button>
                        {{ isset($role) ? 'Update' : 'Create' }}
                    </x-primary-button>

                    <a href="{{ route('roles.index') }}">
                        <x-secondary-button>{{ __('Cancel') }}</x-secondary-button>
                    </a>
                </div>

            </form>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/roles/form.js')
    @endpush
</x-app-layout>