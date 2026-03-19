<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ isset($user) ? 'Edit User' : 'Create User' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto">
            <form method="POST"
                action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
                enctype="multipart/form-data">
                
                @csrf
                @if(isset($user)) @method('PUT') @endif
                @can('edit all user')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                @else
                <div class="grid grid-cols-1 gap-6">
                @endcan
                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                        <!-- Profile Picture -->
                        <div class="mb-6">
                            <x-input-label value="Profile Picture" />

                            <!-- Current picture -->
                            <div class="mb-3">
                                @if(isset($user) && $user->profile_picture)
                                    <img id="currentPicture"
                                        src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                                        class="w-24 h-24 rounded-full object-cover border-2 border-gray-300">
                                @else
                                    <div id="currentPicture" class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-sm">
                                        No photo
                                    </div>
                                @endif
                            </div>

                            <!-- File input -->
                            <input type="file" id="profilePictureInput" accept="image/*"
                                class="text-sm text-gray-600 dark:text-gray-300">

                            <!-- Hidden field submitted with form -->
                            <input type="hidden" name="profile_picture_cropped" id="profilePictureCropped">
                        </div>

                        <!-- Crop Modal -->
                        <div id="cropModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm">
                                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">Crop Profile Picture</h3>
                                <div id="cropElement"></div>
                                <div class="flex justify-end gap-2 mt-4">
                                    <button type="button" id="cropCancelBtn"
                                        class="px-4 py-1.5 text-sm rounded border border-gray-300 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Cancel
                                    </button>
                                    <button type="button" id="cropConfirmBtn"
                                        class="px-4 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </div>



                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label value="Name" />
                            <x-text-input name="name" class="w-full"
                                value="{{ old('name', $user->name ?? '') }}" />
                        </div>

                        <!-- Position -->
                        <div class="mb-4">
                            <x-input-label value="Position" />
                            <x-text-input name="position" class="w-full"
                                value="{{ old('position', $user->position ?? '') }}" />
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <x-input-label value="Email" />
                            <x-text-input name="email" class="w-full"
                                value="{{ old('email', $user->email ?? '') }}" />
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <x-input-label value="Password" />
                            <x-text-input type="password" name="password" class="w-full" />
                            @if(isset($user))
                                <small class="text-gray-400">Leave blank to keep current password</small>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                        @can('edit all user')
                            <!-- HR Only Zone -->
                            <div class="mb-6 border border-yellow-300 dark:border-yellow-600 rounded-lg p-4 bg-yellow-50 dark:bg-yellow-900/20">
                                <p class="text-xs font-semibold text-yellow-700 dark:text-yellow-400 uppercase tracking-wide mb-4">
                                    HR Only
                                </p>

                                <!-- Roles -->
                                <div class="mb-4">
                                    <x-input-label value="Role" />
                                    <select name="roles[]" class="w-full border rounded p-2" multiple>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}"
                                                @selected(isset($user) && $user->hasRole($role->name))>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-gray-400">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                                </div>

                                <!-- Leave Balance -->
                                <div class="mb-4">
                                    <x-input-label value="Leave Balance (hours)" />
                                    <x-text-input type="number" step="0.5" name="leave_balance" class="w-full"
                                        value="{{ old('leave_balance', $user->leave_balance ?? 112) }}" />
                                </div>

                                <!-- Reason for Change -->
                                <div class="mb-4">
                                    <x-input-label value="Reason for Change" />
                                    <x-text-input name="balance_reason" class="w-full"
                                        value="{{ old('balance_reason') }}" />
                                </div>
                            </div>
                        @else
                            <!-- Role badges (read-only for non-HR) -->
                            <div class="mb-4">
                                <x-input-label value="Role" />
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach(($user->roles ?? []) as $role)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
                
                <!-- Buttons -->
                <br>
                <div class="flex justify-end space-x-2">
                    <x-primary-button>
                        {{ isset($user) ? 'Update' : 'Create' }}
                    </x-primary-button>

                    <a href="{{ route('users.index') }}">
                        <x-secondary-button>Cancel</x-secondary-button>
                    </a>
                </div>
            </form>

        </div>
    </div>
    @push('scripts')
        @vite('resources/js/users/form.js')
    @endpush
</x-app-layout>