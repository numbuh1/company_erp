<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Roles
            </h2>

            @can('edit roles')
			    <a href="{{ route('roles.create') }}">
			        <x-primary-button>Create Role</x-primary-button>
			    </a>
			@endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <!-- Header -->
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Role
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Permissions
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

                        @forelse($roles as $role)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">

                                <!-- Role Name -->
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $role->name }}
                                </td>

                                <!-- Permissions -->
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
								    @php
								        $modules = $role->permissions
								            ->map(fn($p) => $p->parent)
								            ->filter() // remove nulls
								            ->unique('id')
								            ->sortBy('display_name');
								    @endphp

								    @forelse($modules as $module)
									    <div class="relative group inline-block mr-1 mb-1">
										    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded cursor-pointer">
										        {{ $module->display_name }}
										    </span>

										    <!-- Tooltip -->
										    <div class="absolute z-50 hidden group-hover:block bottom-full left-1/2 -translate-x-1/2 mb-2 w-56">
										        <div class="bg-gray-900 text-white text-xs rounded px-3 py-2 shadow-lg">
										            @foreach(
										                $role->permissions->where('parent_id', $module->id)
										                    ->sortBy('display_name') as $perm
										            )
										                <div>{{ $perm->display_name }}</div>
										            @endforeach
										        </div>

										        <!-- Arrow -->
										        <div class="w-2 h-2 bg-gray-900 rotate-45 absolute left-1/2 -translate-x-1/2 top-full -mt-1"></div>
										    </div>
										</div>
									@empty
									    <span class="text-gray-400">No modules</span>
									@endforelse
								</td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right space-x-2">

                                    @can('edit roles')
									    <a href="{{ route('roles.edit', $role) }}">
									        <x-secondary-button>Edit</x-secondary-button>
									    </a>
									@endcan

                                    @can('delete roles')
									    <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline">
									        @csrf
									        @method('DELETE')
									        <x-danger-button onclick="return confirm('Delete this role?')">
									            Delete
									        </x-danger-button>
									    </form>
									@endcan
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-6 text-center text-gray-500">
                                    No roles found.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>

            </div>

        </div>
    </div>
</x-app-layout>